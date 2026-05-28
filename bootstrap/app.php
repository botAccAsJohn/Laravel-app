<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\ReqContextMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__ . '/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // Register the admin routes with predefined prefix and namespacing
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withEvents(discover: [
        __DIR__ . '/../app/Listeners',
    ])
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
        $middleware->appendToGroup('web', ReqContextMiddleware::class);
        // Exercise 49.5: required for Auth::logoutOtherDevices() to invalidate
        // other session rows. Must be in the web group, not api.
        $middleware->appendToGroup('web', \Illuminate\Session\Middleware\AuthenticateSession::class);
        $middleware->append(\App\Http\Middleware\LogRequestLifecycle::class);
        $middleware->appendToPriorityList(ReqContextMiddleware::class, 'auth');
        $middleware->alias([
            'api.rate.headers' => \App\Http\Middleware\ApiRateLimitHeaders::class,
            'role' => CheckRole::class,
            'slack.verify' => \App\Http\Middleware\VerifySlackSignature::class,
        ]);
        // ── Redirect unauthenticated users ──────────────────────────────
        // When a guest hits a protected route, send them to the correct login
        // page based on which area they were trying to access.
        $middleware->redirectGuestsTo(function (Request $request): string {
            return $request->is('admin/*')
                ? route('admin.login')   // Admin area → admin login
                : route('login');        // Customer area → customer login
        });

        // ── Redirect already-authenticated users ─────────────────────────
        // When an authenticated user hits a `guest:*` route (e.g., the login
        // form), send them to the correct dashboard for their guard so they
        // are not shown a login form they don't need.
        $middleware->redirectUsersTo(function (Request $request): string {
            return $request->is('admin/*')
                ? route('admin.dashboard')  // Admin hitting /admin/login → admin dashboard
                : route('dashboard');       // Customer hitting /login → customer dashboard
        });


        $middleware->validateCsrfTokens(except: [
            '/post',
            '/products',
            '/products/*',
            '/ai/ask',
            '/ai/chat',
            '/ai/history',
            '/api/slack/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ── Slack alert for 5xx server errors ───────────────────────────
        $exceptions->reportable(function (\Throwable $e) {
            // Only alert on 5xx HTTP errors
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            if ($status < 500) {
                return;
            }

            // Throttle: max 1 alert per exception signature per 10 minutes
            if (\App\Notifications\ServerAlertNotification::isThrottled($e)) {
                return;
            }

            $request = request();
            $alert = \App\Notifications\ServerAlertNotification::fromException($e, $request);

            // Send to all admins from the dedicated admins table (if it exists).
            if (\Illuminate\Support\Facades\Schema::hasTable('admins')) {
                $admins = \App\Models\Admin::all();
                \Illuminate\Support\Facades\Notification::send($admins, $alert);
            }
        });
    })->create();