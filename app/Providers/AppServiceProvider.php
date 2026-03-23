<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentService;
use App\Services\LoggerService;
use App\Services\MathService;
use App\Services\AIService;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Log::info('[STEP 2b] AppServiceProvider register() called');
        $this->app->bind(PaymentService::class, function ($app) {
            return new PaymentService();
        });
        $this->app->bind('logger.bind', function () {
            return new LoggerService();
        });
        $this->app->singleton('logger.singleton', function () {
            return new LoggerService();
        });
        $this->app->singleton('math.service', function () {
            return new MathService();
        });
        $this->app->singleton(AIService::class, function () {
            return new AIService();
        });
    }

    public function boot(): void
    {
        // [STEP 5] Runs after all providers are registered
        Log::info('[STEP 5] AppServiceProvider boot() called all services ready');
        Response::macro('success', function ($data) {
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        });
    }
}
