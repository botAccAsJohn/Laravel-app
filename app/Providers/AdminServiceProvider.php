<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton('admin-helper', fn() => new \App\Services\AdminHelper());
    }

    public function boot(): void
    {

    }
}
