<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\{Product, Order};
use App\Observers\{ProductObserver, OrderObserver};

class ObserverServiceProvider extends ServiceProvider
{

    public function register(): void
    {
    }

    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
    }
}
