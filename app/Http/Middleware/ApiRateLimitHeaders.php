<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $tier = $user?->subscription_tier ?? 'free';
        $key = 'api:' . ($user?->id ?: $request->ip());

        $limit = match ($tier) {
            'enterprise' => 6000,
            'pro'        => 600,
            default      => 60,
        };

        $remaining = max(0, $limit - RateLimiter::attempts($key));

        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Tier', $tier);

        return $response;
    }
}
