<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\Order;
use App\Mail\Orders\SendOrderDeliveredMail;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderDelivered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Determine which queues should be used for each notification channel.
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails',
        ];
    }

    public function toMail(object $notifiable)
    {
        return (new SendOrderDeliveredMail($this->order))
            ->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('Order Delivered'),
            'message' => __('Your order #:number has been delivered.', ['number' => $this->order->id]),
            'url' => route('orders.show', $this->order),
            'data' => [
                'order_id' => $this->order->id,
            ],
        ];
    }
}
