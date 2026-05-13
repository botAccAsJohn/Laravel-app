<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderShipped extends Notification implements ShouldQueue
{
    use Queueable;
    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'slack', 'broadcast'];
    }

    /**
     * Determine which queues should be used for each notification channel.
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails',
            'slack' => 'slack',
            'broadcast' => 'realtime',
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error("OrderShipped notification failed for Order #{$this->order->id}: " . $exception->getMessage());
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing('items');
        $customerName = $notifiable->name ?? 'Customer';

        return (new MailMessage)
            ->subject(__('Your Order Has Shipped!'))
            ->line(__('Hello :name, your order #:number has been shipped.', [
                'name' => $notifiable->name,
                'number' => $this->order->id
            ]))
            ->line(__('Tracking Number: :tracking', ['tracking' => $this->order->tracking_number ?? 'PENDING-123']))
            ->action(__('View Order'), route('orders.show', $this->order))
            ->line(__('Thank you for shopping with us!'))
            ->view('email.order-shipped', [
                'order'        => $this->order,
                'url'          => route('orders.show', $this->order),
                'customerName' => $customerName,
                'orderId'      => $this->order->id,
                'orderTotal'   => $this->order->total_amount,
                'itemsCount'   => $this->order->items->count(),
            ]);
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text('📦 ' . __('Order #:number has been shipped!', ['number' => $this->order->id]))
            ->headerBlock('📦 ' . __('Order Shipped'))
            ->sectionBlock(function ($section) {
                $section->text(
                    '*' . __('Order ID') . ':* ' . $this->order->id . '\n' .
                    '*' . __('Tracking Number') . ':* ' . ($this->order->tracking_number ?? 'PENDING') . '\n' .
                    '*' . __('Status') . ':* Shipped'
                )->markdown();
            })
            ->contextBlock(function ($context) {
                $context->text('*' . config('app.name') . '*')->markdown();
                $context->text(now()->toDateTimeString())->markdown();
            });
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            "title" => __("Order Shipped"),
            "message" => __("Your order #:number has been shipped.", ['number' => $this->order->id]),
            "url" => route('orders.show', $this->order),
            "tracking_number" => $this->order->tracking_number ?? 'PENDING-123',
            "icon_name" => "truck",
            "data" => [
                "order_id" => $this->order->id,
            ],
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage($this->toArray($notifiable));
    }
}
