<?php

namespace App\Providers;

use App\Services\GreetingService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CustomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GreetingService::class, function ($app) {
            $language = config('greeting.language', 'en');

            return new GreetingService($language);
        });
    }

    public function boot(): void
    {
        View::composer('layouts.header', function ($view) {
            $view->with([
                'appName'  => config('greeting.app_name') ?? 'My App',
                'greeting' => "hello ",
            ]);
        });
    }
}
