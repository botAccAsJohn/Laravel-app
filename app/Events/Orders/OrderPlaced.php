<?php

// namespace App\Events\Orders;

// use App\Models\Order;
// use Illuminate\Broadcasting\InteractsWithSockets;
// use Illuminate\Broadcasting\PrivateChannel;
// use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
// use Illuminate\Foundation\Events\Dispatchable;
// use Illuminate\Queue\SerializesModels;

// class OrderPlaced implements ShouldBroadcast
// {
//     use SerializesModels, Dispatchable, InteractsWithSockets;

//     public function __construct(public Order $order) {}

//     /**
//      * Get the channels the event should broadcast on.
//      *
//      * @return array<int, \Illuminate\Broadcasting\Channel>
//      */
//     public function broadcastOn(): array
//     {
//         return [
//             new PrivateChannel('admin.orders'),
//         ];
//     }

//     /**
//      * Get the data to broadcast.
//      *
//      * @return array<string, mixed>
//      */
//     public function broadcastWith(): array
//     {
//         return [
//             'customer_name' => $this->order->user->name ?? 'Guest',
//             'order_total' => $this->order->final_amount,
//             'items_count' => $this->order->items()->count(),
//             'order_id' => $this->order->id,
//         ];
//     }
// }

namespace App\Events\Orders;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use SerializesModels, Dispatchable;
    public function __construct(public Order $order) {}
}
