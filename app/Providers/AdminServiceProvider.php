<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('admin-helper', fn() => new \App\Services\AdminHelper());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
