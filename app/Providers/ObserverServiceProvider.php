<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\{Product, Order};
use App\Observers\{ProductObserver, OrderObserver};

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
    }
}
