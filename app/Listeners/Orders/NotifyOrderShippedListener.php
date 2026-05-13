<?php

namespace App\Listeners\Orders;

use App\Notifications\OrderShipped;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\{Log, RateLimiter};

/**
 * Handles customer notification when an order is shipped.
 *
 * Triggered by: OrderShipped event (from OrderObserver)
 *
 * Sends the OrderShipped notification which delivers via:
 *   - mail (shipping confirmation email)
 *   - database (notification center)
 *   - slack (team channel alert)
 */
class NotifyOrderShippedListener implements ShouldQueue
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
            Log::channel('orders')->warning("Cannot notify shipped — no user for Order #{$order->id}");
            return;
        }

        // Rate limit: max 5 shipping notifications per user per minute
        $limiterKey = 'order-shipped:' . $order->user_id;

        RateLimiter::attempt($limiterKey, 5, function () use ($order) {
            $order->user->notify(new OrderShipped($order));
        }, 60);

        Log::channel('orders')->info("Shipped notification dispatched for Order #{$order->id}");
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::channel('orders')->error("NotifyOrderShippedListener failed for Order #{$event->order->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
