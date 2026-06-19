<?php

namespace Tests\Feature;

use App\Models\{Admin, Order, Product, Review, User};
use App\Policies\{OrderPolicy, ProductPolicy, ReviewPolicy, UserPolicy};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationGatesPoliciesTest extends TestCase
{
    use RefreshDatabase;

    // Exercise 50.1 - Gate definitions

    public function test_all_static_gates_are_defined()
    {
        $gates = ['view_admin_dashboard', 'manage_products', 'manage_orders',
                  'impersonate_users', 'view_analytics', 'manage_reports',
                  'view_logs', 'send_alerts'];
        foreach ($gates as $gate) {
            $this->assertTrue(Gate::has($gate), "Gate '{$gate}' must be defined");
        }
    }

    public function test_gate_before_super_admin_bypass_exists()
    {
        $code = file_get_contents(app_path('Providers/AuthServiceProvider.php'));
        $this->assertStringContainsString('Gate::before', $code);
        $this->assertStringContainsString('super_admin_emails', $code);
    }

    public function test_gate_after_audit_log_exists()
    {
        $code = file_get_contents(app_path('Providers/AuthServiceProvider.php'));
        $this->assertStringContainsString('Gate::after', $code);
        $this->assertStringContainsString('[Gate::after]', $code);
    }

    public function test_gate_authorize_used_in_admin_controllers()
    {
        $code = file_get_contents(app_path('Http/Controllers/Admin/AuthController.php'));
        $this->assertStringContainsString('Gate::authorize', $code);
    }

    public function test_can_directives_in_blade()
    {
        $code = file_get_contents(base_path('resources/views/layouts/admin_navigation.blade.php'));
        $this->assertStringContainsString("@can('view_admin_dashboard')", $code);
    }

    public function test_dynamic_permissions_gates_registered()
    {
        $code = file_get_contents(app_path('Providers/AuthServiceProvider.php'));
        $this->assertStringContainsString('Permission::all()->each', $code);
    }

    // Exercise 50.2 - Model Policies

    public function test_all_four_policies_exist_and_registered()
    {
        $code = file_get_contents(app_path('Providers/AuthServiceProvider.php'));
        $this->assertStringContainsString("Gate::policy(Product::class, ProductPolicy::class)", $code);
        $this->assertStringContainsString("Gate::policy(Order::class, OrderPolicy::class)", $code);
        $this->assertStringContainsString("Gate::policy(Review::class, ReviewPolicy::class)", $code);
        $this->assertStringContainsString("Gate::policy(User::class, UserPolicy::class)", $code);
    }

    public function test_product_policy_has_all_standard_methods()
    {
        $methods = ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'];
        foreach ($methods as $method) {
            $this->assertTrue(method_exists(ProductPolicy::class, $method));
        }
    }

    public function test_order_policy_has_all_standard_methods()
    {
        $methods = ['viewAny', 'view', 'create', 'update', 'delete', 'cancel', 'restore', 'forceDelete'];
        foreach ($methods as $method) {
            $this->assertTrue(method_exists(OrderPolicy::class, $method));
        }
    }

    public function test_review_policy_has_24_hour_update_restriction()
    {
        $code = file_get_contents(app_path('Policies/ReviewPolicy.php'));

        // ReviewPolicy enforces 24-hour edit window
        $this->assertStringContainsString("subHours(24)", $code);
        $this->assertStringContainsString("created_at->gt", $code);

        // Only author can update
        $this->assertStringContainsString("review->user_id === \$user->id", $code);

        // Admin can always delete
        $this->assertStringContainsString("is_Admin()", $code);
    }

    public function test_order_policy_customer_owns_order_check()
    {
        $code = file_get_contents(app_path('Policies/OrderPolicy.php'));

        // Must check order ownership or manage_orders permission
        $this->assertStringContainsString("can('manage_orders')", $code);
        $this->assertStringContainsString("order->user_id === \$user->id", $code);

        // Denies with 404 for security
        $this->assertStringContainsString("denyAsNotFound", $code);
    }

    public function test_review_policy_author_in_controllers()
    {
        $code = file_get_contents(app_path('Http/Controllers/ReviewController.php'));
        $this->assertStringContainsString("\$this->authorize('create', Review::class)", $code);
        $this->assertStringContainsString("\$this->authorize('update', \$review)", $code);
        $this->assertStringContainsString("\$this->authorize('delete', \$review)", $code);
    }

    public function test_order_policy_author_in_controller()
    {
        $code = file_get_contents(app_path('Http/Controllers/OrderController.php'));
        $this->assertStringContainsString("\$this->authorize('view'", $code);
        $this->assertStringContainsString("\$this->authorize('create'", $code);
    }
}
