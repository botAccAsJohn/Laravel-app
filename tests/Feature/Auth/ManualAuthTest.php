<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\LoginThrottleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, Notification, Log};
use Tests\TestCase;

class ManualAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_login_uses_auth_attempt_with_remember()
    {
        $user = User::factory()->create([
            'email' => 'manual-login@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post(route('manual-auth.login.store'), [
            'email' => 'manual-login@example.com',
            'password' => 'password123',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertAuthenticated();
    }

    public function test_manual_login_fails_and_increments_throttle_counter()
    {
        $user = User::factory()->create([
            'email' => 'fail-test@example.com',
        ]);

        $service = app(LoginThrottleService::class);
        $this->assertEquals(0, $service->failureCount('fail-test@example.com'));

        $this->post(route('manual-auth.login.store'), [
            'email' => 'fail-test@example.com',
            'password' => 'wrong',
        ]);

        $this->assertEquals(1, $service->failureCount('fail-test@example.com'));
        $this->assertGuest();
    }

    public function test_successful_login_resets_throttle_counter()
    {
        $user = User::factory()->create([
            'email' => 'reset-test@example.com',
            'password' => 'password123',
        ]);

        $service = app(LoginThrottleService::class);

        // Simulate 3 prior failures
        Cache::put(LoginThrottleService::accountKey('reset-test@example.com'), 3, now()->addMinutes(15));

        $response = $this->post(route('manual-auth.login.store'), [
            'email' => 'reset-test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertAuthenticated();
        $this->assertEquals(0, $service->failureCount('reset-test@example.com'));
    }

    public function test_captcha_required_after_10_failures()
    {
        User::factory()->create(['email' => 'captcha-test@example.com']);
        $service = app(LoginThrottleService::class);

        Cache::put(
            LoginThrottleService::accountKey('captcha-test@example.com'),
            10,
            now()->addMinutes(15)
        );

        $this->assertTrue($service->requiresCaptcha('captcha-test@example.com'));
    }

    public function test_manual_login_throttle_middleware_applied()
    {
        // Verify the route has throttle:login middleware
        $route = app('router')->getRoutes()->getByName('manual-auth.login.store');
        $this->assertNotNull($route, 'manual-auth.login.store route must exist');
        $this->assertContains('throttle:login', $route->gatherMiddleware());
    }
}
