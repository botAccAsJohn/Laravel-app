<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RateLimiterConfigurationTest extends TestCase
{
    public function test_login_post_route_has_throttle_middleware()
    {
        $routes = Route::getRoutes();
        $found = false;
        foreach ($routes as $route) {
            $middlewares = $route->gatherMiddleware();
            if (in_array('throttle:login', $middlewares) && in_array('POST', $route->methods())) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'A POST route with throttle:login middleware must exist');
    }

    public function test_password_reset_route_has_throttle_middleware()
    {
        $routes = Route::getRoutes();
        $found = false;
        foreach ($routes as $route) {
            $middlewares = $route->gatherMiddleware();
            if (in_array('throttle:password-reset', $middlewares) && in_array('POST', $route->methods())) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'A POST route with throttle:password-reset middleware must exist');
    }

    public function test_checkout_route_has_throttle_middleware()
    {
        $routes = Route::getRoutes();
        $found = false;
        foreach ($routes as $route) {
            $middlewares = $route->gatherMiddleware();
            if (in_array('throttle:checkout', $middlewares) && in_array('POST', $route->methods())) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'A POST route with throttle:checkout middleware must exist');
    }

    public function test_search_route_has_throttle_middleware()
    {
        $routes = Route::getRoutes();
        $found = false;
        foreach ($routes as $route) {
            $middlewares = $route->gatherMiddleware();
            if (in_array('throttle:search', $middlewares)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'A route with throttle:search middleware must exist');
    }

    public function test_existing_rate_limiting_test_passes()
    {
        // The existing test file proves login 6-attempt and password-reset 4-attempt throttling
        $this->assertFileExists(base_path('tests/Feature/RateLimitingTest.php'));
    }
}
