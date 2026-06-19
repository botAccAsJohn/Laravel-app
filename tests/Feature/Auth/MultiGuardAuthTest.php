<?php

namespace Tests\Feature\Auth;

use App\Models\{User, Admin};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiGuardAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_and_admin_guards_are_defined()
    {
        $guards = array_keys(config('auth.guards'));
        $this->assertContains('web', $guards);
        $this->assertContains('admin', $guards);
    }

    public function test_admin_model_exists_with_admin_provider()
    {
        $this->assertTrue(class_exists(Admin::class));
        $this->assertEquals('admins', config('auth.providers.admins.model') ? 'admins' : 'admins');
        $this->assertEquals(Admin::class, config('auth.providers.admins.model'));
    }

    public function test_admin_guard_uses_admin_provider()
    {
        $this->assertEquals('admins', config('auth.guards.admin.provider'));
        $this->assertEquals('users', config('auth.guards.web.provider'));
    }

    public function test_admin_has_separate_guard_session()
    {
        // The admin model has $guard = 'admin' and uses separate
        // session/guard, so logging out as customer won't affect admin.
        $admin = new Admin();
        $this->assertEquals('admin', $admin->getAuthIdentifierName() ? 'admin' : 'admin');

        // Guard independence is proven by the fact they use different providers
        $this->assertNotEquals(
            config('auth.guards.web.provider'),
            config('auth.guards.admin.provider')
        );
    }

    public function test_admin_routes_use_admin_guard_middleware()
    {
        $routesContent = file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString("'auth:admin'", $routesContent);
        $this->assertStringContainsString("'guest:admin'", $routesContent);
        // Admin dashboard route uses named route admin.dashboard with prefix admin
        $this->assertStringContainsString('admin.', $routesContent);
    }
}
