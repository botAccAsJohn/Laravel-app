<?php

namespace App\Listeners\Orders;

use App\Notifications\OrderDelivered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\{Log, RateLimiter};

/**
 * Handles customer notification when an order is delivered.
 *
 * Triggered by: OrderDelivered event (from OrderObserver)
 *
 * Sends the OrderDelivered notification which delivers via:
 *   - mail (delivery confirmation email)
 *   - database (notification center)
 */
class NotifyOrderDeliveredListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'emails';
    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function handle(object $event): void
    {
        $order = $event->order;
        $order->load('user');

        if (!$order->user) {
            Log::channel('orders')->warning("Cannot notify delivered — no user for Order #{$order->id}");
            return;
        }

        // Rate limit: max 5 delivery notifications per user per minute
        $limiterKey = 'order-delivered:' . $order->user_id;

        RateLimiter::attempt($limiterKey, 5, function () use ($order) {
            $order->user->notify(new OrderDelivered($order));
        }, 60);

        Log::channel('orders')->info("Delivered notification dispatched for Order #{$order->id}");
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::channel('orders')->error("NotifyOrderDeliveredListener failed for Order #{$event->order->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
