<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderPlaced implements ShouldBroadcast
{
    use SerializesModels;

    public $customerName;
    public $orderTotal;
    public $itemsCount;
    public $orderId;

    public function __construct(Order $order)
    {
        $this->customerName = $order->user->name;
        $this->orderTotal = $order->total_amount;
        $this->itemsCount = $order->items->count();
        $this->orderId = $order->id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.placed';
    }
}
