<?php

namespace Tests\Feature;

use App\Http\Requests\{StoreProductRequest, UpdateProductRequest, StoreOrderRequest, UpdateOrderRequest};
use App\Models\{Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PolicyFiltersFormRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_policy_has_before_method_for_admin_full_access()
    {
        $code = file_get_contents(app_path('Policies/ProductPolicy.php'));
        $this->assertStringContainsString('function before(User|Admin|null $user', $code);
        $this->assertStringContainsString('is_admin()', $code);
        $this->assertStringContainsString('return true', $code);
    }

    public function test_order_policy_has_before_method_for_admin_full_access()
    {
        $code = file_get_contents(app_path('Policies/OrderPolicy.php'));
        $this->assertStringContainsString('function before(User|Admin|null $user', $code);
        $this->assertStringContainsString('is_admin()', $code);
        $this->assertStringContainsString('return true', $code);
    }

    public function test_store_product_request_authorizes_via_form_request()
    {
        $code = file_get_contents(app_path('Http/Requests/StoreProductRequest.php'));
        $this->assertStringContainsString('function authorize', $code);
        $this->assertStringContainsString("can('create', Product::class)", $code);
    }

    public function test_update_product_request_authorizes_via_form_request()
    {
        $code = file_get_contents(app_path('Http/Requests/UpdateProductRequest.php'));
        $this->assertStringContainsString("can('update', \$product)", $code);
        $this->assertStringContainsString("\$this->route('product')", $code);
    }

    public function test_store_order_request_authorizes_via_form_request()
    {
        $code = file_get_contents(app_path('Http/Requests/StoreOrderRequest.php'));
        $this->assertStringContainsString("can('create', Order::class)", $code);
    }

    public function test_update_order_request_authorizes_via_form_request()
    {
        $code = file_get_contents(app_path('Http/Requests/UpdateOrderRequest.php'));
        $this->assertStringContainsString("can('update', \$order)", $code);
        $this->assertStringContainsString("\$this->route('order')", $code);
    }

    public function test_deny_as_not_found_used_in_order_policy()
    {
        $code = file_get_contents(app_path('Policies/OrderPolicy.php'));
        $this->assertStringContainsString('denyAsNotFound', $code,
            'OrderPolicy should use denyAsNotFound to return 404 instead of 403 for non-owned orders');
        $this->assertGreaterThan(
            3,
            substr_count($code, 'denyAsNotFound'),
            'denyAsNotFound should be used extensively in OrderPolicy'
        );
    }

    public function test_edit_comment_gate_has_multi_parameter_ability()
    {
        $code = file_get_contents(app_path('Providers/AuthServiceProvider.php'));
        $this->assertStringContainsString("edit-comment", $code);
        $this->assertStringContainsString('$u->id === $c->user_id', $code);
        $this->assertStringContainsString('$u->id === $p->user_id', $code);
    }

    public function test_current_user_helper_used_in_request()
    {
        $code = file_get_contents(app_path('Http/Requests/StoreProductRequest.php'));
        $this->assertStringContainsString('current_user()', $code);
    }
}
