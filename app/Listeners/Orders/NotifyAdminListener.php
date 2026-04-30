<?php

namespace App\Listeners\Orders;

use App\Events\Orders\OrderPlaced;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

class NotifyAdminListener implements ShouldQueue
{
    public function handle(object $event): void
    {
        $order = $event->order;

        Log::channel('Events')->info("Admin notified of Order Placed", [
            'order_id' => $order->id,
            'customer_name' => $order->user->name,
            'order_total' => $order->total_amount,
            'items_count' => $order->items->count(),
        ]);

        Broadcast::on(new PrivateChannel('admin.orders'))
            ->as("order.placed")
            ->with([
                'orderId' => $order->id,
                'customerName' => $order->user->name,
                'orderTotal' => $order->total_amount,
                'itemsCount' => $order->items->count(),
            ])
            ->send();
    }
}
