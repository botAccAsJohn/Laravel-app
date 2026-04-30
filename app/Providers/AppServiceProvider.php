<?php

namespace App\Providers;

use App\Services\{OrderService, ExternalApiService, CartService, PaymentService, LoggerService, MathService, AIService};
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{Log, RateLimiter, Blade, View, Auth, Response, DB, Http, Mail};
use App\Auth\RedisUserProvider;

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
        $this->app->bind(OrderService::class, function ($app) {
            return new OrderService($app->make(CartService::class));
        });
        $this->app->singleton(ExternalApiService::class, function () {
            return new ExternalApiService();
        });
    }

    public function boot(): void
    {
        // [STEP 5] Runs after all providers are registered
        Log::info('[STEP 5] AppServiceProvider boot() called all services ready');

        RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        // Only provide user to layouts, not every single partial/component
        // View::composer('*', function ($view) {
        View::composer(['layouts.*', 'admin.*', 'dashboard'], function ($view) {
            $view->with('user', Auth::user());
        });
        Blade::directive('currency', function ($amount) {
            return "<?php 
                \$currency = match(App::getLocale()) {
                    'hi' => 'INR',
                    'ar' => 'SAR',
                    default => 'USD'
                };
                echo \Illuminate\Support\Number::currency($amount ?? 0, in: \$currency); 
            ?>";
        });
        Response::macro('success', function ($data) {
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        });

        Http::macro('jsonApi', function () {
            return Http::acceptJson()
                ->baseUrl('https://fakestoreapi.com')
                ->withHeaders(['X-API-KEY' => 'my-secret-key']);
        });

        DB::listen(function ($query) {
            Log::channel('DBInteraction')->info('SQL Query', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
            ]);
        });

        if ($this->app->environment('local')) {
            Mail::alwaysTo(config('mail.admin_email'));
        }

        // Add Collection macro for manual pagination
        \Illuminate\Support\Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: \Illuminate\Pagination\Paginator::resolveCurrentPage($pageName);
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
        Auth::provider('redis-eloquent', function ($app, array $config) {
            return new RedisUserProvider(
                $app['hash'],
                $config['model']
            );
        });
    }
}
