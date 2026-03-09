# 🚀 Laravel 12 — Request Lifecycle

> **Module 4 · Exercise 4.1** — Trace the full journey of an HTTP request through Laravel 12.
>
> ✅ This guide is written specifically for **Laravel 12** (released February 2025).

---

## 📋 Table of Contents

- [What Changed in Laravel 12](#what-changed-in-laravel-12)
- [The Big Picture](#the-big-picture)
- [Step-by-Step Lifecycle](#step-by-step-lifecycle)
    - [Step 1 — public/index.php](#step-1--publicindexphp)
    - [Step 2 — bootstrap/app.php](#step-2--bootstrapappphp)
    - [Step 3 — HTTP Kernel](#step-3--http-kernel)
    - [Step 4 — Service Providers](#step-4--service-providers)
    - [Step 5 — Middleware Pipeline](#step-5--middleware-pipeline)
    - [Step 6 — Router](#step-6--router)
    - [Step 7 — Controller](#step-7--controller)
    - [Step 8 — Response](#step-8--response)
- [Adding Logs to Trace Execution](#adding-logs-to-trace-execution)
    - [1. public/index.php](#1-publicindexphp-1)
    - [2. Custom Middleware](#2-custom-middleware)
    - [3. AppServiceProvider](#3-appserviceprovider)
    - [4. Controller](#4-controller)
- [Execution Order](#execution-order)
- [Reading the Log Output](#reading-the-log-output)
- [Common Issues & Fixes](#common-issues--fixes)

---

## What Changed in Laravel 12

Laravel 12 **does NOT have** `app/Http/Kernel.php` — it was removed in Laravel 11 and stays gone in 12.

| Feature                  | Laravel 10 ❌ (old)                       | Laravel 11 / 12 ✅ (current)                |
| ------------------------ | ----------------------------------------- | ------------------------------------------- |
| Global middleware        | `$middleware[]` in `Kernel.php`           | `->append()` in `bootstrap/app.php`         |
| Middleware groups        | `$middlewareGroups[]` in `Kernel.php`     | `->appendToGroup()` in `bootstrap/app.php`  |
| Route middleware aliases | `$routeMiddleware[]` in `Kernel.php`      | `->alias()` in `bootstrap/app.php`          |
| Exception handling       | `app/Exceptions/Handler.php`              | `->withExceptions()` in `bootstrap/app.php` |
| Routing setup            | Automatic                                 | `->withRouting()` in `bootstrap/app.php`    |
| Sending response         | `$kernel->handle()` + `$response->send()` | `$app->handleRequest()`                     |

> 💡 In Laravel 12, **`bootstrap/app.php` is the single place** where middleware, routing, and exceptions are all configured.

---

## The Big Picture

```
Browser / HTTP Client
        │
        ▼  HTTP Request
┌─────────────────────────┐
│    public/index.php     │  ← #1 Front Controller — EVERY request starts here
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│   bootstrap/app.php     │  ← #2 Creates the Application + configures everything
│  Application::configure │       (middleware, routing, exceptions all live here)
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│       HTTP Kernel       │  ← #3 Orchestrates the request pipeline
│  (internal to Laravel)  │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│    Service Providers    │  ← #4 register() then boot() on all providers
│  AppServiceProvider...  │       (database, cache, auth, etc. all boot here)
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│   Global Middleware     │  ← #5 Runs on EVERY request (CSRF, session, etc.)
│   (inbound pass)        │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│        Router           │  ← #6 Matches URI to route in routes/web.php
│  + Route Middleware     │       Then applies route-specific middleware
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│      Controller         │  ← #7 Your business logic, returns a Response
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  Response (outbound)    │  ← #8 Travels back through middleware in REVERSE
│  Global Middleware      │       then sent to the browser
└────────────┬────────────┘
             │
             ▼  HTTP Response
    Browser / Client
```

> 💡 **The middleware pipeline is symmetric.** Every middleware that runs on the way _in_ also runs on the way _out_ — in reverse order. This is how session saving, cookie writing, and response headers get added after your controller finishes.

---

## Step-by-Step Lifecycle

### Step 1 — `public/index.php`

This is the **single entry point** for every HTTP request. Your web server (Nginx or Apache) is configured to route all traffic here — no other PHP file is ever hit directly.

**It does 3 things:**

```php
<?php

// 1. Record the start time (used for performance tracking)
define('LARAVEL_START', microtime(true));

// 2. Load Composer autoloader — makes ALL classes available
require __DIR__ . '/../vendor/autoload.php';

// 3. Boot the app and handle the request — all in one call (Laravel 12)
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());
```

> ⚠️ **Laravel 12 change:** The old pattern of `$kernel->handle()` + `$response->send()` + `$kernel->terminate()` was replaced with the single `$app->handleRequest()` call. All three actions now happen internally.

> 🔐 **Why one entry point?** This is the _Front Controller Pattern_ — a security standard. It guarantees ALL requests pass through one controlled location before any application code runs.

---

### Step 2 — `bootstrap/app.php`

This file **creates the Laravel Application** and configures its core settings. In Laravel 12, this is also where middleware, routes, and exception handling are all defined.

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register YOUR custom middleware here
        $middleware->append(\App\Http\Middleware\LogRequestLifecycle::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling goes here
    })->create();
```

**Key methods:**

| Method               | Purpose                                     |
| -------------------- | ------------------------------------------- |
| `->withRouting()`    | Points to your route files                  |
| `->withMiddleware()` | Register global & route middleware          |
| `->withExceptions()` | Customise error reporting and rendering     |
| `->create()`         | Builds and returns the Application instance |

---

### Step 3 — HTTP Kernel

The HTTP Kernel is the internal orchestrator. You do not edit or create this class in Laravel 12 — it lives inside the framework itself.

**What it does:**

- Runs **bootstrappers** before the request is handled: loads `.env`, configures logging, detects the app environment, and registers all service providers
- Passes the request through the **middleware pipeline**
- Hands the request to the **Router**

> Think of the Kernel as a factory conveyor belt — the request enters one end, passes through every station (middleware, routing, controller), and a response exits the other end.

---

### Step 4 — Service Providers

Service providers are truly the key to bootstrapping a Laravel application. The application instance is created, the service providers are registered, and the request is handed to the bootstrapped application.

Every service provider has two phases, and the order matters:

```php
// app/Providers/AppServiceProvider.php
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Phase 1 — REGISTER
        // Bind classes into the IoC container
        // ⚠️ Do NOT use other services here — they may not exist yet
        $this->app->bind(PaymentService::class, StripeService::class);
    }

    public function boot(): void
    {
        // Phase 2 — BOOT
        // All providers are registered by now — safe to use any service
        // Good for: event listeners, view composers, model observers
    }
}
```

**Execution order across ALL providers:**

1. Laravel instantiates every provider
2. Calls `register()` on **all** of them
3. Then calls `boot()` on **all** of them

This guarantees that when `boot()` runs, every binding is already in the container.

---

### Step 5 — Middleware Pipeline

Some middleware are assigned to all routes within the application, like `PreventRequestsDuringMaintenance`, while some are only assigned to specific routes or route groups.

**Every middleware follows this pattern:**

```php
public function handle(Request $request, Closure $next): Response
{
    // ← Inbound: runs BEFORE the controller

    $response = $next($request); // Pass to the next layer

    // ← Outbound: runs AFTER the controller returns

    return $response;
}
```

**The onion model:**

```
Request  →  [Middleware A → [Middleware B → [Controller]]]
Response ←  [Middleware A ← [Middleware B ← [Controller]]]
```

**Common built-in middleware (web group):**

| Middleware           | Purpose                                               |
| -------------------- | ----------------------------------------------------- |
| `EncryptCookies`     | Encrypts all cookie values                            |
| `StartSession`       | Starts the PHP session                                |
| `ValidateCsrfToken`  | Blocks forged POST requests                           |
| `SubstituteBindings` | Resolves route model bindings (`{user}` → User model) |

---

### Step 6 — Router

After global middleware, the Router takes over.

**What it does:**

1. Parses the URI and HTTP verb (`GET`, `POST`, `PUT`, `DELETE`, etc.)
2. Scans `routes/web.php` and `routes/api.php` for a match
3. Extracts route parameters (e.g. `/user/{id}` → `$id`)
4. Applies route-specific middleware (`auth`, `throttle`, etc.)
5. Dispatches to the matched Controller and method

```php
// routes/web.php
use App\Http\Controllers\LifecycleController;

Route::get('/lifecycle', [LifecycleController::class, 'index']);

// With route middleware:
Route::get('/dashboard', [DashboardController::class, 'index'])
     ->middleware(['auth', 'verified']);
```

> If no route matches → Laravel automatically returns a **404 response**.

---

### Step 7 — Controller

The controller is where **your code runs**. By now the request has already been authenticated, CSRF-verified, had its session started, and been matched to a route.

```php
// app/Http/Controllers/LifecycleController.php
class LifecycleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // $request has everything: inputs, headers, auth user, route params

        return response()->json([
            'message' => 'Lifecycle complete!',
            'time_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2),
        ]);
    }
}
```

The controller can return:

- A Blade view: `return view('home', $data)`
- JSON: `return response()->json([...])`
- A redirect: `return redirect('/dashboard')`
- A file download: `return response()->download($path)`

---

### Step 8 — Response

Once the response travels back through the middleware, the HTTP kernel's handle method returns the response object to the `handleRequest` of the application instance, and this method calls the `send` method on the returned response. The `send` method sends the response content to the user's web browser.

The return journey:

1. Controller returns Response object
2. Route middleware runs **outbound** (adds cookies, headers)
3. Global middleware runs **outbound** (encrypts cookies, saves session)
4. `$response->send()` writes HTTP headers + body to the browser
5. Post-response cleanup fires (`terminate()` on middleware, flush queued logs)

---

## Adding Logs to Trace Execution

### 1. `public/index.php`

> ⚠️ Laravel hasn't loaded yet at this point — you **cannot** use `Log::info()`. Use `file_put_contents()` instead.

```php
<?php

define('LARAVEL_START', microtime(true));

// ✅ Must use file_put_contents — Log facade not available yet
file_put_contents(
    __DIR__ . '/../storage/logs/laravel.log',
    '[' . date('Y-m-d H:i:s') . '] [STEP 1] Entry point — public/index.php reached' . PHP_EOL,
    FILE_APPEND
);

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());
```

---

### 2. Custom Middleware

```bash
php artisan make:middleware LogRequestLifecycle
```

```php
// app/Http/Middleware/LogRequestLifecycle.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestLifecycle
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('[STEP 5] Middleware — inbound (before controller)', [
            'url'    => $request->fullUrl(),
            'method' => $request->method(),
            'ip'     => $request->ip(),
        ]);

        $response = $next($request);

        Log::info('[STEP 8-pre] Middleware — outbound (after controller)', [
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
```

Register it in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\LogRequestLifecycle::class);
})
```

---

### 3. `AppServiceProvider`

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Log::info('[STEP 3] AppServiceProvider::register() called');
    }

    public function boot(): void
    {
        Log::info('[STEP 4] AppServiceProvider::boot() — all services registered and ready');
    }
}
```

---

### 4. Controller

```php
// app/Http/Controllers/LifecycleController.php
use Illuminate\Support\Facades\Log;

class LifecycleController extends Controller
{
    public function index(Request $request)
    {
        Log::info('[STEP 7] Controller::index() reached', [
            'class'   => __CLASS__,
            'method'  => __FUNCTION__,
            'time_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2),
        ]);

        return response()->json([
            'message' => 'Lifecycle traced successfully!',
            'step'    => 7,
        ]);
    }
}
```

---

## Execution Order

When you visit `/lifecycle`, log entries appear in this exact order:

| #   | Where                                    | What Happens                                       |
| --- | ---------------------------------------- | -------------------------------------------------- |
| 1   | `public/index.php`                       | Raw `file_put_contents` — framework not loaded yet |
| 2   | `bootstrap/app.php`                      | Application container created                      |
| 3   | `AppServiceProvider::register()`         | Services bound into container                      |
| 4   | `AppServiceProvider::boot()`             | All services ready to use                          |
| 5   | `LogRequestLifecycle::handle()` inbound  | Before controller — request going in               |
| 6   | Router                                   | Matches URI to route _(internal, no log needed)_   |
| 7   | `LifecycleController::index()`           | Your business logic executes                       |
| 8   | `LogRequestLifecycle::handle()` outbound | After controller — response going out              |
| 9   | `$response->send()`                      | Response delivered to browser                      |

---

## Reading the Log Output

```bash
# Watch logs in real time — Mac / Linux
tail -f storage/logs/laravel.log

# Windows (PowerShell)
Get-Content storage/logs/laravel.log -Wait -Tail 20
```

Then visit `http://127.0.0.1:8000/lifecycle` and watch the entries flow in step by step.

---

## Common Issues & Fixes

### ❌ `file_put_contents(): Failed to open stream`

**Cause A — `storage/logs/` folder missing:**

```bash
mkdir storage/logs
php artisan optimize:clear
```

**Cause B — String bug: date gets merged into the filename:**

```php
// ❌ Wrong — missing dot before PHP_EOL
'[' . date('Y-m-d H:i:s') . '] [STEP 1] reached' PHP_EOL,

// ✅ Correct — every part separated by a dot operator
'[' . date('Y-m-d H:i:s') . '] [STEP 1] reached' . PHP_EOL,
```

---

### ❌ Logs not appearing in `laravel.log`

```bash
php artisan config:clear
php artisan cache:clear

# Mac / Linux only
chmod -R 775 storage/
```

Check your `.env`:

```
LOG_CHANNEL=stack
```

---

### ❌ Middleware not running

Use the **Laravel 12 way** in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    // Add to every request globally
    $middleware->append(\App\Http\Middleware\LogRequestLifecycle::class);

    // OR — only for the web group
    $middleware->appendToGroup('web', \App\Http\Middleware\LogRequestLifecycle::class);

    // OR — as a named alias to use on specific routes
    $middleware->alias(['log.lifecycle' => \App\Http\Middleware\LogRequestLifecycle::class]);
})
```

---

### ❌ Which `index.php` pattern is correct for Laravel 12?

```php
// ✅ Laravel 12 — correct
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());

// ❌ Laravel 10 pattern — do NOT use in Laravel 12
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());
$response->send();
$kernel->terminate($request, $response);
```

---

_Module 4 · Exercise 4.1 · Laravel 12 Request Lifecycle_
_Reference: [laravel.com/docs/12.x/lifecycle](https://laravel.com/docs/12.x/lifecycle)_
