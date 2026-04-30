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
        // 0. Authenticate a user to prevent view layout errors
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

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

        // 3. Assert the page loads successfully and shows our fake data
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

        // 3. Assert it returns a 500 status and our friendly error message
        $response->assertStatus(500);
        $response->assertSee('The external service is currently unavailable. Please try again later.');
    }
}
