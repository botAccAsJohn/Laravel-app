<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};
use Illuminate\Http\Request;
use App\Http\Middleware\{CheckRole, CheckPermission, ApiRateLimitHeaders, ReqContextMiddleware, RequirePasswordReset, SetLocale, CheckImpersonation, LogRequestLifecycle, VerifySlackSignature, AuthenticateApiKey, GuestOrCustomer};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__ . '/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withEvents(discover: [
        __DIR__ . '/../app/Listeners',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            SetLocale::class,
            ReqContextMiddleware::class,
            CheckImpersonation::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            RequirePasswordReset::class,
        ]);
        $middleware->append(LogRequestLifecycle::class);
        $middleware->appendToPriorityList(ReqContextMiddleware::class, 'auth');
        $middleware->alias([
            'api.rate.headers' => ApiRateLimitHeaders::class,
            // 'role' => CheckRole::class,
            // 'permission' => CheckPermission::class,
            'slack.verify' => VerifySlackSignature::class,
            'force.password.reset' => RequirePasswordReset::class,
            'auth.apikey' => AuthenticateApiKey::class,
            'guest_or_customer' => GuestOrCustomer::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request): string {
            return $request->is('admin/*')
                ? route('admin.login')   
                : route('login');
        });

        $middleware->redirectUsersTo(function (Request $request): string {
            return $request->is('admin/*')
                ? route('admin.dashboard')
                : route('dashboard');
        });

        $middleware->validateCsrfTokens(except: [
            '/ai/ask',
            '/ai/chat',
            '/ai/history',
            '/api/slack/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e) {
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            if ($status < 500) {
                return;
            }
            if (\App\Notifications\ServerAlertNotification::isThrottled($e)) {
                return;
            }
            $request = request();
            $alert = \App\Notifications\ServerAlertNotification::fromException($e, $request);
            if (\Illuminate\Support\Facades\Schema::hasTable('admins')) {
                $admins = \App\Models\Admin::all();
                \Illuminate\Support\Facades\Notification::send($admins, $alert);
            }
        });
    })->create();