<?php

namespace App\Listeners\Orders;

use Illuminate\Support\Facades\Log;

class LogEventListener
{

    public function __construct() {}

    public function handle(object $event): void
    {
        Log::channel('Events')->info('Order Event :', [
            'order_id' => $event->order->id,
            'order_status' => $event->order->status,
            'customer_name' => $event->order->user->name,
            'order_total' => $event->order->total_amount,
            'items_count' => $event->order->items->count(),
        ]);
    }
}
