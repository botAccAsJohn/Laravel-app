<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\ReqContextMiddleware;

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

        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
        $middleware->appendToGroup('web', ReqContextMiddleware::class);
        $middleware->append(\App\Http\Middleware\LogRequestLifecycle::class);
        $middleware->appendToPriorityList(ReqContextMiddleware::class, 'auth');

        $middleware->validateCsrfTokens(except: [
            '/post',
            '/products',
            '/products/*',
            '/ai/ask',
            '/ai/chat',
            '/ai/history',
            '/api/slack/*',
        ]);
        $middleware->alias([
            'role' => CheckRole::class,
            'slack.verify' => \App\Http\Middleware\VerifySlackSignature::class,
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
            $alert   = \App\Notifications\ServerAlertNotification::fromException($e, $request);

            // Send to all admin users (they route to #alerts via webhook)
            $admins = \App\Models\User::where('role', 'admin')->get();
            \Illuminate\Support\Facades\Notification::send($admins, $alert);
        });
    })->create();