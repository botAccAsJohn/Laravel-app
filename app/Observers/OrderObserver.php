<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the order "created" event.
     */
    public function created(Order $order): void
    {
        event(new \App\Events\Orders\OrderPlaced($order));
    }

    /**
     * Handle the order "updated" event.
     */
    public function updated(Order $order): void
    {
        broadcast(new \App\Events\OrderStatusUpdated($order));
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
