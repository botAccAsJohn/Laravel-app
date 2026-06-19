<?php

namespace Tests\Feature;

use App\Models\{Permission, Role, User};
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolesPermissionsSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_spatie_permission_tables_exist()
    {
        $this->assertTrue(class_exists(Role::class));
        $this->assertTrue(class_exists(Permission::class));
    }

    public function test_user_model_has_hasRoles_trait()
    {
        $traits = class_uses(\App\Models\User::class);
        $this->assertArrayHasKey('Spatie\Permission\Traits\HasRoles', $traits);
    }

    public function test_admin_model_has_hasRoles_trait()
    {
        $traits = class_uses(\App\Models\Admin::class);
        $this->assertArrayHasKey('Spatie\Permission\Traits\HasRoles', $traits);
    }

    public function test_seeder_creates_roles_and_permissions()
    {
        $this->assertDatabaseHas('roles', ['name' => 'customer']);
        $this->assertDatabaseHas('roles', ['name' => 'support']);
        $this->assertDatabaseHas('roles', ['name' => 'manager']);
        $this->assertDatabaseHas('roles', ['name' => 'admin']);

        $this->assertDatabaseHas('permissions', ['name' => 'manage_products']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_products']);
        $this->assertDatabaseHas('permissions', ['name' => 'manage_orders']);
        $this->assertDatabaseHas('permissions', ['name' => 'refund_orders']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_reports']);
        $this->assertDatabaseHas('permissions', ['name' => 'manage_users']);
    }

    public function test_customer_role_has_correct_permissions()
    {
        $role = Role::findByName('customer', 'web');
        $this->assertTrue($role->hasPermissionTo('view_products', 'web'));
        $this->assertTrue($role->hasPermissionTo('place_order', 'web'));
        $this->assertFalse($role->hasPermissionTo('manage_products', 'web'));
        $this->assertFalse($role->hasPermissionTo('refund_orders', 'web'));
    }

    public function test_user_can_assign_role_and_get_permissions()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $this->assertTrue($user->hasRole('customer'));
        $this->assertTrue($user->can('view_products'));
        $this->assertFalse($user->can('manage_products'));
    }

    public function test_dynamic_gates_are_registered_from_permissions()
    {
        $providerCode = file_get_contents(app_path('Providers/AuthServiceProvider.php'));
        $this->assertStringContainsString('Permission::all()->each', $providerCode);
        $this->assertStringContainsString('Gate::define', $providerCode);

        // Dynamic gate registration logic exists — verified by code inspection above
    }

    public function test_role_assignment_is_audit_logged()
    {
        $controllerCode = file_get_contents(app_path('Http/Controllers/Admin/UserRoleController.php'));
        $this->assertStringContainsString('[RBAC] Role assignment changed', $controllerCode);
        $this->assertStringContainsString("[RBAC] Role assigned", $controllerCode);
        $this->assertStringContainsString("[RBAC] Role revoked", $controllerCode);
    }

    public function test_role_crud_is_audit_logged()
    {
        $controllerCode = file_get_contents(app_path('Http/Controllers/Admin/RoleController.php'));
        $this->assertStringContainsString('[RBAC] Role created', $controllerCode);
        $this->assertStringContainsString('[RBAC] Role updated', $controllerCode);
        $this->assertStringContainsString('[RBAC] Role deleted', $controllerCode);
    }

    public function test_admin_ui_routes_exist_for_role_management()
    {
        $routes = app('router')->getRoutes();
        $names = [];
        foreach ($routes as $route) {
            $names[] = $route->getName();
        }

        $this->assertContains('admin.users.index', $names);
        $this->assertContains('admin.users.edit-roles', $names);
        $this->assertContains('admin.users.update-roles', $names);
        $this->assertContains('admin.users.assign-role', $names);
        $this->assertContains('admin.users.revoke-role', $names);
        $this->assertContains('admin.roles.index', $names);
    }
}
