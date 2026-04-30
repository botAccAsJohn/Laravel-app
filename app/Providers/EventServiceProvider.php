<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use \App\Events\Orders\{OrderPlaced, OrderDelivered, OrderShipped, OrderPaid};
use \App\Events\Behavior\{ProductViewed, ProductAddToCart, ProductReviewed, CartAbandoned};
use \App\Events\Inventory\{ProductStockLow};
use App\Listeners\Orders\{NotifyAdminListener, SendEmailListener, UpdateInventoryListener, LogEventListener};
use \App\Listeners\Behavior\{ProductViewedListener, ProductAddToCartListener, RecalculateProductRating, SendCartAbandonedEmailListener};
use \App\Listeners\Inventory\{ProductStockLowListener};

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            NotifyAdminListener::class,
            SendEmailListener::class,
            UpdateInventoryListener::class,
            LogEventListener::class,
        ],
        OrderDelivered::class => [
            SendEmailListener::class,
            LogEventListener::class,
        ],
        OrderShipped::class => [
            SendEmailListener::class,
            LogEventListener::class,
        ],
        OrderPaid::class => [
            SendEmailListener::class,
            LogEventListener::class,
        ],
        ProductViewed::class => [
            ProductViewedListener::class,
        ],
        ProductAddToCart::class => [
            ProductAddToCartListener::class,
        ],
        ProductReviewed::class => [
            RecalculateProductRating::class,
        ],
        ProductStockLow::class => [
            ProductStockLowListener::class,
        ],
        CartAbandoned::class => [
            SendCartAbandonedEmailListener::class,
        ],
    ];
}
