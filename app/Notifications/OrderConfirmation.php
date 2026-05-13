<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\Order;
use App\Mail\Orders\SendOrderConfirmationMail;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
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

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable)
    {
        $email = ($notifiable instanceof \App\Models\User) 
            ? $notifiable->email 
            : ($notifiable->routes['mail'] ?? 'unknown');

        return (new SendOrderConfirmationMail($this->order))
            ->to($email);
    }

    /**
     * Get the array representation for the database channel.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('Order Confirmed'),
            'message' => __('Your order #:number has been placed successfully.', ['number' => $this->order->id]),
            'url' => route('orders.show', $this->order),
            'data' => [
                'order_id' => $this->order->id,
                'total' => $this->order->final_amount,
            ],
        ];
    }
}
