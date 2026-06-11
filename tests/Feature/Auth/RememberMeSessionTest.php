<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RememberMeSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_remember_token_exists_on_user_model()
    {
        $user = User::factory()->create();
        $this->assertArrayHasKey('remember_token', $user->getAttributes());
    }

    public function test_remember_me_flag_is_passed_to_auth_attempt()
    {
        // The ManualAuthController passes $remember = $request->boolean('remember')
        // to Auth::attempt($credentials, $remember)
        $controllerCode = file_get_contents(app_path('Http/Controllers/Auth/ManualAuthController.php'));
        $this->assertStringContainsString('Auth::attempt($credentials, $remember)', $controllerCode);
    }

    public function test_login_view_has_remember_me_checkbox()
    {
        $response = $this->get(route('manual-auth.login'));
        $response->assertStatus(200);
        $response->assertSee('remember_me');
    }

    public function test_session_lifetime_is_configured()
    {
        $lifetime = env('SESSION_LIFETIME', 120);
        $this->assertEquals('120', $lifetime, 'SESSION_LIFETIME must be 120 (2 hours)');
    }

    public function test_logout_other_devices_endpoint_exists_in_controller()
    {
        $this->assertTrue(
            method_exists(\App\Http\Controllers\ProfileController::class, 'logoutOtherDevices'),
            'ProfileController must have logoutOtherDevices method'
        );

        $controllerCode = file_get_contents(app_path('Http/Controllers/ProfileController.php'));
        $this->assertStringContainsString('Auth::logoutOtherDevices', $controllerCode);
        $this->assertStringContainsString('current_password', $controllerCode);
    }

    public function test_logout_other_devices_route_is_defined()
    {
        $route = app('router')->getRoutes()->getByName('profile.logout-other-devices');
        $this->assertNotNull($route, 'profile.logout-other-devices route must exist');
        $this->assertContains('DELETE', $route->methods());
    }

    public function test_remember_me_docs_exist()
    {
        // Verify controller has docblock explaining remember-me cookie trade-off
        $controllerCode = file_get_contents(app_path('Http/Controllers/ProfileController.php'));
        $this->assertStringContainsString('Remember Me', $controllerCode);
        $this->assertStringContainsString('remember_token', $controllerCode);
    }
}
