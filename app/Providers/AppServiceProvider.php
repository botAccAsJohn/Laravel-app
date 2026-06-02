<?php

namespace App\Providers;

use App\Services\{OrderService, ExternalApiService, CartService, AIService, LoginThrottleService};
use App\Listeners\HandleFailedJob;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{Event, Gate, URL, Log, RateLimiter, Blade, View, Auth, Response, DB, Http, Mail, Schema};
use App\Auth\RedisUserProvider;
use App\Models\{Permission, User};
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Carbon\Carbon;



class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Log::info('[STEP 2b] AppServiceProvider register() called');
        $this->app->singleton(AIService::class, function () {
            return new AIService();
        });
        $this->app->bind(OrderService::class, function ($app) {
            return new OrderService($app->make(CartService::class));
        });
        $this->app->singleton(ExternalApiService::class, function () {
            return new ExternalApiService();
        });
        // Exercise 49.4 — account-based brute-force protection service
        $this->app->singleton(LoginThrottleService::class);
    }

    public function boot(): void
    {
        // [STEP 5] Runs after all providers are registered
        Log::info('[STEP 5] AppServiceProvider boot() called all services ready');

        // Customise the verification URL callback
        // \Illuminate\Auth\Notifications\VerifyEmail::createUrlUsing(function ($notifiable) { // $notification
        //     return \Illuminate\Support\Facades\URL::temporarySignedRoute(
        //         'verification.verify',
        //         now()->addMinutes(config('auth.verification.expire', 60)),
        //         [
        //             'id' => $notifiable->getKey(),
        //             'hash' => sha1($notifiable->getEmailForVerification()),
        //         ]
        //     );
        // });
        VerifyEmail::createUrlUsing(function (object $notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        // Module 36 locale on verification email
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $locale = $notifiable->preferred_locale ?? app()->getLocale();

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject(__('mail.verify_subject', [], $locale))
                ->line(__('mail.verify_intro', [], $locale))
                ->action(__('mail.verify_action', [], $locale), $url)
                ->line(__('mail.verify_footer', [], $locale));
        });

        // ── Exercise 52.1: Customise the password-reset URL ──────────────────
        //
        // createUrlUsing() lets us control the URL shape — e.g. pointing to a
        // custom frontend SPA or applying a signed route. Here we keep the
        // default Breeze route but make it explicit (the closure receives the
        // notifiable user and the raw token).
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });

        // ── Exercise 52.1: Branded, localised reset email ────────────────────
        //
        // toMailUsing() replaces the default Laravel reset notification with our
        // own HTML template (resources/views/email/password-reset.blade.php).
        //
        // Rate-limiter note (Exercise 52.1 / 47.2)
        // ─────────────────────────────────────────
        // The 'password-reset' limiter (defined below) is keyed by EMAIL, not
        // by IP. Here is why:
        //   • IP-based limiting is trivially bypassed with Tor, proxies, or
        //     botnets — an attacker sends 3 requests from 3 different IPs.
        //   • The goal is to prevent a targeted victim's mailbox from being
        //     flooded with reset links. The attack vector is email-targeted,
        //     so the key must be the email address.
        //   • An attacker who controls many IPs but does NOT control the
        //     victim's email cannot bypass an email-keyed limiter.
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $locale = $notifiable->preferred_locale ?? app()->getLocale();
            $name   = $notifiable->name ?? $notifiable->email;

            // Generate the absolute reset password URL using the token
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new \Illuminate\Mail\Mailable)
                ->subject(__('reset_subject', [], $locale))
                ->html(
                    view('email.password-reset', [
                        'resetUrl' => $url,
                        'name'     => $name,
                        'locale'   => $locale,
                    ])->render()
                );
        });

        //
        // Instead of hard-coding every permission as a Gate::define() call,
        // we load all permissions from the database at boot time and register
        // a Gate closure for each one. This means:
        //   • Adding a new permission is a DB/seed operation — no code change.
        //   • The Gate name matches the permission name (e.g. 'manage_products').
        //   • Checked via: Gate::check('manage_products') or @can('manage_products')
        //
        // Schema::hasTable guard: prevents crashing before the first migration.
        if (Schema::hasTable('permissions')) {
            Permission::all()->each(function (Permission $permission) {
                Gate::define($permission->name, function ($user) use ($permission) {
                    // Admins implicitly have all dynamically defined permissions
                    if ($user instanceof \App\Models\Admin) {
                        return true;
                    }

                    // Delegates to the User model's permission check which
                    // flattens all roles → permissions and caches the result.
                    return method_exists($user, 'hasPermission') ? $user->hasPermission($permission->name) : false;
                });
            });
        }

        // Define Named Rate Limiters (Exercise 47.1 & 47.2)

        // 1. 'api' rate limiter: 60 per minute
        RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by($request->user()?->id ?? $request->ip())
                ->response(function (\Illuminate\Http\Request $request, array $headers) {
                    Log::channel('security')->warning('Rate limit hit: api', [
                        'ip' => $request->ip(),
                        'user_id' => $request->user()?->id,
                        'url' => $request->fullUrl(),
                    ]);
                    return response()->json(['error' => 'rate-limited'], 429, $headers);
                });
        });

        // 2. 'login' rate limiter: 5 per 5 minutes by email and IP (Exercise 47.2)
        RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
            $email = strval($request->input('email'));
            return \Illuminate\Cache\RateLimiting\Limit::perMinutes(5, 5)
                ->by('login:' . $email . '|' . $request->ip())
                ->response(function (\Illuminate\Http\Request $request, array $headers) use ($email) {
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

        // 3. 'password-reset' rate limiter: 3 per hour by email (Exercise 47.1 & 47.2)
        RateLimiter::for('password-reset', function (\Illuminate\Http\Request $request) {
            $email = strval($request->input('email'));
            return \Illuminate\Cache\RateLimiting\Limit::perHour(3)
                ->by('password-reset:' . $email)
                ->response(function (\Illuminate\Http\Request $request, array $headers) use ($email) {
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

        // 4. 'checkout' rate limiter: 10 per minute by user
        RateLimiter::for('checkout', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)
                ->by($request->user()?->id ?? $request->ip())
                ->response(function (\Illuminate\Http\Request $request, array $headers) {
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

        // 5. 'search' rate limiter: 30 per minute by user-or-IP
        RateLimiter::for('search', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(30)
                ->by($request->user()?->id ?? $request->ip())
                ->response(function (\Illuminate\Http\Request $request, array $headers) {
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

        // 6. 'api-tiered' rate limiter: differentiated by subscription tier (Exercise 47.4)
        RateLimiter::for('api-tiered', function (\Illuminate\Http\Request $request) {
            $user = $request->user();
            $tier = $user?->subscription_tier ?? 'free';
            $key = 'api-tiered:' . ($user?->id ?? $request->ip());

            $limit = match ($tier) {
                'enterprise' => \Illuminate\Cache\RateLimiting\Limit::perMinute(6000)->by($key),
                'pro'        => \Illuminate\Cache\RateLimiting\Limit::perMinute(600)->by($key),
                'free'       => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($key),
                default      => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($key),
            };

            return $limit->response(function (\Illuminate\Http\Request $request, array $headers) use ($tier) {
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

        // Only provide user to layouts, not every single partial/component
        // View::composer('*', function ($view) {
        View::composer(['layouts.*', 'admin.*', 'dashboard'], function ($view) {
            $user = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user();
            $view->with('user', $user);
        });
        Blade::directive('currency', function ($amount) {
            return "<?php echo format_price($amount ?? 0); ?>";
        });

        Paginator::useTailwind();// Tailwind CSS

        Response::macro('success', function ($data) {
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        });

        Http::macro('jsonApi', function () {
            return Http::acceptJson()
                ->baseUrl('https://fakestoreapi.com')
                ->withHeaders(['X-API-KEY' => 'my-secret-key']);
        });

        if ($this->app->environment('local')) {
            Mail::alwaysTo(config('mail.admin_email'));
            Model::preventLazyLoading();
            DB::listen(function ($query) {
                if ($query->time >= 100) {
                    Log::channel('SlowQueries')->info('slow query', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                    ]);
                }
                Log::channel('DBInteraction')->info('SQL Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                ]);
            });
        }

        // Set default Slack webhook route for notifications
        if (config('services.slack.notifications.bot_user_oauth_token')) {
            \Illuminate\Support\Facades\Notification::route('slack', config('services.slack.notifications.bot_user_oauth_token'));
        }

        // Exercise 46.5 — Centralised job-failure reporting via JobFailed event
        Event::listen(JobFailed::class, HandleFailedJob::class);

        // Add Collection macro for manual pagination
        \Illuminate\Support\Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: \Illuminate\Pagination\Paginator::resolveCurrentPage($pageName);
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
        Auth::provider('redis-eloquent', function ($app, array $config) {
            return new RedisUserProvider(
                $app['hash'],
                $config['model']
            );
        });

        // Invalidate notification cache globally whenever a notification changes
        \Illuminate\Notifications\DatabaseNotification::saved(function ($notification) {
            \Illuminate\Support\Facades\Cache::forget("user:{$notification->notifiable_id}:notifications:latest");
            \Illuminate\Support\Facades\Cache::forget('unread_count_' . $notification->notifiable_id);
        });

        \Illuminate\Notifications\DatabaseNotification::deleted(function ($notification) {
            \Illuminate\Support\Facades\Cache::forget("user:{$notification->notifiable_id}:notifications:latest");
            \Illuminate\Support\Facades\Cache::forget('unread_count_' . $notification->notifiable_id);
        });
    }
}
