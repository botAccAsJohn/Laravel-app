<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\LifecycleController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\GreetingService;


Route::get('/lifecycle', [LifecycleController::class, 'index'])
    ->middleware('web');

Route::get('/test-bind', function () {
    // Ask the container for logger.bind TWICE
    $logger1 = app('logger.bind'); // app() resolves from the container
    $logger2 = app('logger.bind');

    // Log the IDs — are they the same or different?
    Log::info("bind() — logger1 ID: {$logger1->id}");
    Log::info("bind() — logger2 ID: {$logger2->id}");
    Log::info("bind() — Same instance? " . ($logger1 === $logger2 ? 'YES' : 'NO'));

    $logger1->log("Hello from logger1");
    $logger2->log("Hello from logger2");

    return "Check your logs!";
});

Route::get('/test-singleton', function () {
    // Ask the container for logger.singleton TWICE
    $logger1 = app('logger.singleton');
    $logger2 = app('logger.singleton');

    Log::info("singleton() — logger1 ID: {$logger1->id}");
    Log::info("singleton() — logger2 ID: {$logger2->id}");
    Log::info("singleton() — Same instance? " . ($logger1 === $logger2 ? 'YES' : 'NO'));

    $logger1->log("Hello from logger1");
    $logger2->log("Hello from logger2");

    return "Check your logs!";
});

Route::get('/greet/{name}', function (GreetingService $greeting, string $name) {
    return $greeting->greet($name);
});

Route::get('/pay', [PaymentController::class, 'pay']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/users', CompanyController::class);
Route::get('/discount/{price}', [ProductController::class, 'calculate']);

Route::get('/test-facade', function () {
    Log::info("Process started");
    Cache::put('username_key', 'nothing', now()->addMinutes(1));

    $cachedValue = Cache::get('username_key');

    Log::info("Process ended");
    return $responseData = [
        'name_from_cache' => $cachedValue,
    ];
});
