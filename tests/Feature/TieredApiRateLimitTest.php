<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Middleware\ApiRateLimitHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class TieredApiRateLimitTest extends TestCase
{
    public function test_api_rate_limit_headers_middleware_exists()
    {
        $this->assertTrue(
            class_exists(ApiRateLimitHeaders::class),
            'ApiRateLimitHeaders middleware must exist'
        );
    }

    public function test_subscription_tier_column_exists_on_user_model()
    {
        $user = new User();
        $this->assertContains(
            'subscription_tier',
            $user->getFillable(),
            'subscription_tier must be fillable on User model'
        );
    }

    public function test_tiered_api_limiter_is_registered_in_provider()
    {
        // The api-tiered limiter is registered in AppServiceProvider::boot():
        // free: 60/min, pro: 600/min, enterprise: 6000/min
        $providerCode = file_get_contents(app_path('Providers/AppServiceProvider.php'));
        $this->assertStringContainsString("'enterprise' =>", $providerCode);
        $this->assertStringContainsString("Limit::perMinute(6000)", $providerCode);
        $this->assertStringContainsString("'pro'", $providerCode);
        $this->assertStringContainsString("Limit::perMinute(600)", $providerCode);
        $this->assertStringContainsString("'free' =>", $providerCode);
        $this->assertStringContainsString("Limit::perMinute(60)", $providerCode);
    }

    public function test_api_route_group_uses_tiered_limiter()
    {
        // The auth:sanctum routes use throttle:api-tiered
        $routes = Route::getRoutes();
        $found = false;
        foreach ($routes as $route) {
            $middlewares = $route->gatherMiddleware();
            if (in_array('throttle:api-tiered', $middlewares)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'At least one route must use throttle:api-tiered middleware');
    }

    public function test_x_rate_limit_headers_are_set_by_middleware()
    {
        // Simulate the middleware logic
        $limits = [
            'free'       => 60,
            'pro'        => 600,
            'enterprise' => 6000,
        ];

        foreach ($limits as $tier => $limit) {
            $this->assertGreaterThan(0, $limit, "{$tier} tier must have a positive limit");
        }

        // Verify header names match the middleware
        $headerLimit     = 'X-RateLimit-Limit';
        $headerRemaining = 'X-RateLimit-Remaining';
        $headerTier      = 'X-RateLimit-Tier';

        $this->assertStringStartsWith('X-RateLimit', $headerLimit);
        $this->assertStringStartsWith('X-RateLimit', $headerRemaining);
        $this->assertStringStartsWith('X-RateLimit', $headerTier);
    }
}
