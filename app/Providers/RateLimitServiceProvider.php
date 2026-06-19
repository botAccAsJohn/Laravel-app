<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class RateLimitServiceProvider extends ServiceProvider
{

    public function register(): void
    {
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?? $request->ip())
                ->response(function (Request $request, array $headers) {
                    Log::channel('security')->warning('Rate limit hit: api', [
                        'ip' => $request->ip(),
                        'user_id' => $request->user()?->id,
                        'url' => $request->fullUrl(),
                    ]);
                    return response()->json(['error' => 'rate-limited'], 429, $headers);
                });
        });

        RateLimiter::for('login', function (Request $request) {
            $email = strval($request->input('email'));
            return Limit::perMinutes(5, 5)
                ->by('login:' . $email . '|' . $request->ip())
                ->response(function (Request $request, array $headers) use ($email) {
                    Log::channel('security')->warning('Rate limit hit: login', [
                        'ip' => $request->ip(),
                        'email' => $email,
                        'url' => $request->fullUrl(),
                    ]);

                    $seconds = $headers['Retry-After'] ?? 300;
                    $message = __('auth.throttle', ['seconds' => $seconds]);

                    if ($request->wantsJson() || $request->expectsJson()) {
                        return response()->json(['error' => $message], 429, $headers);
                    }

                    return back()
                        ->withInput($request->only('email', 'remember'))
                        ->withErrors([
                            'email' => $message,
                        ])
                        ->withHeaders($headers);
                });
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $email = strval($request->input('email'));
            return Limit::perHour(3)
                ->by('password-reset:' . $email)
                ->response(function (Request $request, array $headers) use ($email) {
                    Log::channel('security')->warning('Rate limit hit: password-reset', [
                        'ip' => $request->ip(),
                        'email' => $email,
                        'url' => $request->fullUrl(),
                    ]);

                    $seconds = $headers['Retry-After'] ?? 3600;
                    $message = __('auth.throttle', ['seconds' => $seconds]);

                    if ($request->wantsJson() || $request->expectsJson()) {
                        return response()->json(['error' => $message], 429, $headers);
                    }

                    return back()
                        ->withInput($request->only('email'))
                        ->withErrors([
                            'email' => $message,
                        ])
                        ->withHeaders($headers);
                });
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?? $request->ip())
                ->response(function (Request $request, array $headers) {
                    Log::channel('security')->warning('Rate limit hit: checkout', [
                        'ip' => $request->ip(),
                        'user_id' => $request->user()?->id,
                        'url' => $request->fullUrl(),
                    ]);

                    $seconds = $headers['Retry-After'] ?? 60;
                    $message = 'Too many checkout attempts. Please try again in ' . $seconds . ' seconds.';

                    if ($request->wantsJson() || $request->expectsJson()) {
                        return response()->json(['error' => $message], 429, $headers);
                    }

                    return back()
                        ->withErrors([
                            'error' => $message,
                        ])
                        ->withHeaders($headers);
                });
        });

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?? $request->ip())
                ->response(function (Request $request, array $headers) {
                    Log::channel('security')->warning('Rate limit hit: search', [
                        'ip' => $request->ip(),
                        'user_id' => $request->user()?->id,
                        'url' => $request->fullUrl(),
                    ]);

                    $seconds = $headers['Retry-After'] ?? 60;
                    $message = 'Too many search attempts. Please try again in ' . $seconds . ' seconds.';

                    if ($request->wantsJson() || $request->expectsJson()) {
                        return response()->json(['error' => $message], 429, $headers);
                    }

                    return back()
                        ->withErrors([
                            'error' => $message,
                        ])
                        ->withHeaders($headers);
                });
        });

        RateLimiter::for('api-tiered', function (Request $request) {
            $user = $request->user();
            $tier = $user?->subscription_tier ?? 'free';
            $key = 'api-tiered:' . ($user?->id ?? $request->ip());

            $limit = match ($tier) {
                'enterprise' => Limit::perMinute(6000)->by($key),
                'pro' => Limit::perMinute(600)->by($key),
                'free' => Limit::perMinute(60)->by($key),
                default => Limit::perMinute(60)->by($key),
            };

            return $limit->response(function (Request $request, array $headers) use ($tier) {
                Log::channel('security')->warning('Rate limit hit: api-tiered', [
                    'ip' => $request->ip(),
                    'user_id' => $request->user()?->id,
                    'tier' => $tier,
                    'url' => $request->fullUrl(),
                ]);

                $seconds = $headers['Retry-After'] ?? 60;
                $message = "API rate limit exceeded for {$tier} tier. Please try again in {$seconds} seconds.";

                return response()->json([
                    'error' => $message,
                    'tier' => $tier,
                    'limit' => match ($tier) {
                        'enterprise' => 6000,
                        'pro' => 600,
                        default => 60,
                    },
                    'retry_after' => $seconds,
                ], 429, $headers);
            });
        });
    }
}
