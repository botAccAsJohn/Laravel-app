<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\Product;
use App\Mail\ProductStockLowMail;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Slack\SlackMessage;

class ProductLowStock extends Notification implements ShouldQueue
{
    use Queueable;

    protected const CRITICAL_THRESHOLD = 5;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Product $product) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'slack'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable)
    {
        return (new ProductStockLowMail($this->product))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('Low Stock Alert'),
            'message' => "Product '{$this->product->name}' is running low on stock ({$this->product->quantity} remaining).",
            'url' => route('products.show', $this->product),
            'product_id' => $this->product->id,
            'current_stock' => $this->product->quantity,
        ];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $hasCritical = $this->product->quantity < self::CRITICAL_THRESHOLD;

        $flag = $hasCritical ? ' :rotating_light: *CRITICAL*' : '';
        $lines = "• *{$this->product->name}* — {$this->product->quantity} units left{$flag}";

        $message = (new SlackMessage)
            ->text(':warning: Low Stock Alert: ' . $this->product->name . ' — ' . $this->product->quantity . ' units left' . ($hasCritical ? ' (CRITICAL)' : ''))
            ->headerBlock(':warning: Low Stock Alert')
            ->sectionBlock(function ($section) use ($lines) {
                $section->text($lines)->markdown();
            })
            ->contextBlock(function ($ctx) {
                $ctx->text('*Product:* ' . $this->product->name)->markdown();
                $ctx->text('*Time:* ' . now()->toDateTimeString())->markdown();
            });

        // Mention @warehouse group only when critical stock exists
        if ($hasCritical) {
            $message->sectionBlock(function ($section) {
                // <!subteam^WAREHOUSE_GROUP_ID> triggers a Slack @mention for user groups
                $section->text('<!subteam^WAREHOUSE_GROUP_ID> stock critically low — immediate action needed.')->markdown();
            });
        }

        return $message;
    }
}
