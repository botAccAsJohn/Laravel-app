<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toWebhook')) {
            return;
        }

        $payload = $notification->toWebhook($notifiable);

        // Get the webhook URL: prioritize notifiable routing, fallback to config
        $url = $notifiable->routeNotificationFor('webhook', $notification) 
            ?? config('services.webhook.url');

        if (!$url) {
            return;
        }

        rescue(function () use ($url, $payload) {
            Http::timeout(5)
                ->retry(3, 100)
                ->post($url, $payload)
                ->throw();
        }, function ($e) use ($url) {
            Log::channel('admin')->error("Webhook notification failed for URL: {$url}", [
                'error' => $e->getMessage()
            ]);
        });
    }
}
