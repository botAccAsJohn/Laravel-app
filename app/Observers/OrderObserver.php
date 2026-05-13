<?php

namespace App\Observers;

use App\Models\Order;
use App\Enum\OrderStatus;

class OrderObserver
{
    /**
     * Handle the order "created" event.
     */
    public function created(Order $order): void
    {
        // Order creation side-effects (notifications, invoice, inventory)
        // are handled by listeners on the OrderPlaced event,
        // which is dispatched from OrderService::createFromCart().
    }

    /**
     * Handle the order "updated" event.
     *
     * This observer has TWO jobs and ONLY two:
     *   1. Broadcast real-time status updates to the frontend
     *   2. Fire domain events so listeners can handle side-effects
     *
     * It does NOT send notifications directly — that's the listeners' job.
     *
     * Flow:
     *   $order->update(['status' => 'shipped'])
     *     → Observer broadcasts OrderStatusUpdated (real-time UI)
     *     → Observer fires OrderShipped event
     *       → NotifyOrderShippedListener sends OrderShipped notification
     */
    public function updated(Order $order): void
    {
        // 1. Always broadcast for real-time UI (order tracking page, admin dashboard)
        broadcast(new \App\Events\OrderStatusUpdated($order));

        // 2. Fire domain events only when status actually changed
        if (!$order->wasChanged('status')) {
            return;
        }

        match ($order->status) {
            OrderStatus::SHIPPED->value   => event(new \App\Events\Orders\OrderShipped($order)),
            OrderStatus::DELIVERED->value => event(new \App\Events\Orders\OrderDelivered($order)),
            default => null,
        };
    }

    /**
     * Handle the order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
