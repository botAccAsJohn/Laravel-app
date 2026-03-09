<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Log::info('[STEP 2b] AppServiceProvider register() called');
    }

    public function boot(): void
    {
        // [STEP 5] Runs after all providers are registered
        Log::info('[STEP 5] AppServiceProvider boot() called all services ready');
    }
}
