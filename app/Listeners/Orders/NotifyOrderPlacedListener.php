<?php

namespace App\Listeners\Orders;

use App\Models\User;
use App\Notifications\{OrderConfirmation, NewOrderReceived};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\{Log, Notification, RateLimiter};

/**
 * Handles ALL notifications when a new order is placed.
 *
 * Triggered by: OrderPlaced event (from OrderService::createFromCart)
 *
 * Responsibilities:
 *   1. Send OrderConfirmation notification to the customer (mail + database)
 *   2. Send NewOrderReceived notification to all admins (mail + database + broadcast + webhook)
 */
class NotifyOrderPlacedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'emails';
    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function handle(object $event): void
    {
        $order = $event->order;
        $order->load('user', 'items');

        // 1. Notify the customer (rate-limited: max 5 per minute per user)
        if ($order->user) {
            $limiterKey = 'order-confirmation:' . $order->user_id;

            RateLimiter::attempt($limiterKey, 5, function () use ($order) {
                $order->user->notify(new OrderConfirmation($order));
            }, 60);
        } elseif (!empty($order->guest_email)) {
            Notification::route('mail', $order->guest_email)->notify(new OrderConfirmation($order));
        }

        // 2. Notify all admins
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new NewOrderReceived($order));

        Log::channel('orders')->info("Order #{$order->id} notifications dispatched", [
            'customer' => $order->user?->email,
            'admin_count' => $admins->count(),
        ]);
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::channel('orders')->error("NotifyOrderPlacedListener failed for Order #{$event->order->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
