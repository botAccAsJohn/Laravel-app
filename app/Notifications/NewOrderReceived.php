<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\Order;
use App\Mail\Orders\SendOrderPlacedMail;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use \App\Notifications\Channels\WebhookChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;

class NewOrderReceived extends Notification implements ShouldQueue
{
    use Queueable;


    protected const HIGH_VALUE_THRESHOLD = 500;

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
        return ['mail', 'database', 'broadcast', WebhookChannel::class, 'slack'];
    }

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails',
            'broadcast' => 'realtime',
            WebhookChannel::class => 'webhooks',
            'slack' => 'slack',
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('admin')->error("NewOrderReceived notification failed for Order #{$this->order->id}", [
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Get the webhook representation of the notification.
     */
    public function toWebhook(object $notifiable): array
    {
        $customerName = $this->order->user ? $this->order->user->name : ($this->order->guest_email ?: __('Guest'));
        return [
            'event' => 'order.received',
            'order_id' => $this->order->id,
            'customer' => $customerName,
            'amount' => $this->order->final_amount,
            'placed_at' => $this->order->placed_at->toDateTimeString(),
        ];
    }
    public function toSlack(object $notifiable): SlackMessage
    {
        $order = $this->order;
        $order->loadMissing('user', 'items'); // Ensure relationships are loaded
        $isHighValue = $order->total_amount >= self::HIGH_VALUE_THRESHOLD;

        // Colour: green for normal orders, orange for high-value
        $color = $isHighValue ? '#FFA500' : '#36A64F';
        $emoji = $isHighValue ? ':rotating_light:' : ':white_check_mark:';

        $customerName = $order->user ? $order->user->name : ($order->guest_email ?: __('Guest Customer'));
        $adminUrl = route('orders.show', $order->id);

        return (new SlackMessage)
            ->text("{$emoji} New Order Received — #{$order->id} by {$customerName}")
            ->headerBlock("{$emoji} " . __('New Order Received'))
            ->sectionBlock(function ($section) use ($order, $customerName) {
                $section->text("*Order:* #{$order->id}\n*Customer:* {$customerName}")->markdown();
            })
            ->contextBlock(function ($context) use ($order) {
                $context->text("*Total:* \${$order->total_amount}")->markdown();
                $context->text("*Items:* " . $order->items->count())->markdown();
                $context->text("*Method:* {$order->payment_method}")->markdown();
            })
            ->actionsBlock(function ($actions) use ($adminUrl) {
                $actions->button(__('View Order'))->url($adminUrl);
            });
    }


    /**
     * Get the mail representation of the notification.
     *** here we send the email to the user not the admin
     * to send to admin we can use the same listener as the user with admin role
     */

    public function toMail(object $notifiable)
    {
        $this->order->load('user');

        return (new SendOrderPlacedMail($this->order))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $customerName = $this->order->user ? $this->order->user->name : ($this->order->guest_email ?: __('Guest'));
        return [
            'title' => __('New Order Received'),
            'message' => "Order #{$this->order->id} has been placed by {$customerName}.",
            'url' => route('orders.show', $this->order),
            'order_id' => $this->order->id,
            'order_total' => $this->order->total_amount,
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $customerName = $this->order->user ? $this->order->user->name : ($this->order->guest_email ?: __('Guest'));
        return new BroadcastMessage([
            'orderId' => $this->order->id,
            'customerName' => $customerName,
            'orderTotal' => $this->order->total_amount,
            'itemsCount' => $this->order->items->count(),
            'title' => __('New Order Received'),
            'message' => "Order #{$this->order->id} has been placed successfully.",
            'url' => route('orders.show', $this->order),
        ]);
    }

    public function broadcastOn()
    {
        return new \Illuminate\Broadcasting\PrivateChannel('admin.orders');
    }

    public function broadcastType()
    {
        return 'order.placed';
    }
}
