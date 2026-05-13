<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\Orders\{OrderPlaced, OrderDelivered, OrderShipped};
use App\Events\Behavior\{ProductViewed, ProductAddToCart, ProductReviewed, CartAbandoned};
use App\Events\Inventory\ProductStockLow;
use App\Listeners\Orders\{
    NotifyOrderPlacedListener,
    NotifyOrderShippedListener,
    NotifyOrderDeliveredListener,
    UpdateInventoryListener,
};
use App\Listeners\GenerateInvoice;
use App\Listeners\Behavior\{
    ProductViewedListener,
    ProductAddToCartListener,
    RecalculateProductRating,
    SendCartAbandonedEmailListener,
};
use App\Listeners\Inventory\ProductStockLowListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * ┌────────────────────────────────────────────────────────────────┐
     * │                    EVENT → LISTENER MAP                        │
     * ├────────────────────────────────────────────────────────────────┤
     * │                                                                │
     * │  ORDER LIFECYCLE                                               │
     * │  ─────────────────                                             │
     * │  OrderPlaced (fired by OrderService::createFromCart)           │
     * │    → GenerateInvoice           PDF invoice creation            │
     * │    → UpdateInventoryListener   Stock deduction + broadcast     │
     * │    → NotifyOrderPlacedListener Customer + Admin notifications  │
     * │                                                                │
     * │  OrderShipped (fired by OrderObserver when status → shipped)   │
     * │    → NotifyOrderShippedListener  Mail + DB + Slack to customer │
     * │                                                                │
     * │  OrderDelivered (fired by OrderObserver when status → delivered)│
     * │    → NotifyOrderDeliveredListener  Mail + DB to customer       │
     * │                                                                │
     * │  PRODUCT & INVENTORY                                           │
     * │  ───────────────────                                           │
     * │  ProductStockLow (fired by ProductObserver when qty <= 10)     │
     * │    → ProductStockLowListener   Low stock alert to admins       │
     * │                                                                │
     * │  BEHAVIOR TRACKING                                             │
     * │  ─────────────────                                             │
     * │  ProductViewed → ProductViewedListener                         │
     * │  ProductAddToCart → ProductAddToCartListener                   │
     * │  ProductReviewed → RecalculateProductRating                    │
     * │  CartAbandoned → SendCartAbandonedEmailListener                │
     * │                                                                │
     * │  FRAMEWORK HOOKS                                               │
     * │  ────────────────                                              │
     * │  NotificationSent / NotificationFailed → LogNotificationStatus │
     * │                                                                │
     * └─────────────────────────────────────────────────────────────────┘
     */
    protected $listen = [

            // ── Order Lifecycle ──────────────────────────────────────────
        OrderPlaced::class => [
            GenerateInvoice::class,
            UpdateInventoryListener::class,
            NotifyOrderPlacedListener::class,
        ],

        OrderShipped::class => [
            NotifyOrderShippedListener::class,
        ],

        OrderDelivered::class => [
            NotifyOrderDeliveredListener::class,
        ],

            // ── Behavior Tracking ────────────────────────────────────────
        ProductViewed::class => [
            ProductViewedListener::class,
        ],

        ProductAddToCart::class => [
            ProductAddToCartListener::class,
        ],

        ProductReviewed::class => [
            RecalculateProductRating::class,
        ],

        CartAbandoned::class => [
            SendCartAbandonedEmailListener::class,
        ],

            // ── Inventory ────────────────────────────────────────────────
        ProductStockLow::class => [
            ProductStockLowListener::class,
        ],

        // ── Notification Audit Log ───────────────────────────────────
        \Illuminate\Notifications\Events\NotificationSent::class => [
            \App\Listeners\Admin\LogNotificationStatus::class,
        ],

        \Illuminate\Notifications\Events\NotificationFailed::class => [
            \App\Listeners\Admin\LogNotificationStatus::class,
        ],
    ];
}
