<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_has_has_api_tokens_trait()
    {
        $traits = class_uses(User::class);
        $this->assertArrayHasKey(
            'Laravel\Sanctum\HasApiTokens',
            $traits,
            'User model must use HasApiTokens trait'
        );
    }

    public function test_api_login_endpoint_returns_token()
    {
        $user = User::factory()->create([
            'email' => 'api-test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'api-test@example.com',
            'password' => 'password123',
            'device_name' => 'PHPUnit Test',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_api_routes_are_protected_by_sanctum()
    {
        // Without a token, should fail auth
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_api_user_route_works_with_valid_token()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');
        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => $user->email]);
    }

    public function test_api_logout_revokes_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/logout');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Token revoked.']);

        // Token should be deleted from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_device_management_page_exists()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('devices.index'));
        $response->assertStatus(200);
        $response->assertSee('API Tokens');
    }
}
