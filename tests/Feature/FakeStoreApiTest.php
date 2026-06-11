<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakeStoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_display_products_from_fake_api()
    {
        // 1. Fake the HTTP response
        Http::fake([
            'fakestoreapi.com/products' => Http::response([
                [
                    'id' => 1,
                    'title' => 'Awesome Fake Laptop',
                    'price' => 1299.99,
                    'description' => 'A faked laptop for testing',
                    'category' => 'electronics',
                    'image' => 'http://example.com/laptop.jpg',
                    'rating' => ['rate' => 4.5, 'count' => 120]
                ]
            ], 200)
        ]);

        // 2. Make the request to the route
        $response = $this->get('/api/calling/getProducts');

        // 3. Assert the page loads successfully
        $response->assertStatus(200);
        $response->assertSee('Awesome Fake Laptop');

        // 4. Verify the correct URL and headers were used via the macro
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() == 'https://fakestoreapi.com/products' &&
                   $request->hasHeader('Accept', 'application/json') &&
                   $request->hasHeader('X-API-KEY', 'my-secret-key');
        });
    }

    public function test_displays_friendly_error_on_500_response()
    {
        // 1. Fake a 500 Server Error
        Http::fake([
            'fakestoreapi.com/products' => Http::response(null, 500)
        ]);

        // 2. Make the request
        $response = $this->get('/api/calling/getProducts');

        // 3. API routes get JSON from ExternalApiException::render()
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'The external service returned an error. Please try again later.',
        ]);
    }

    public function test_displays_friendly_error_on_connection_failure()
    {
        // 1. Fake a connection failure (unreachable server)
        Http::fake([
            'fakestoreapi.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException(
                    'cURL error 7: Failed to connect to fakestoreapi.com port 443: Connection refused',
                    0
                );
            }
        ]);

        // 2. Make the request
        $response = $this->get('/api/calling/getProducts');

        // 3. Assert friendly JSON error message - no raw exception exposed
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Could not connect to the external service. Please try again later.',
        ]);
        $response->assertDontSee('Connection refused');
        $response->assertDontSee('cURL error');
    }
}
