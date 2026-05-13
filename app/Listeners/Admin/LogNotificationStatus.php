<?php

namespace App\Listeners\Admin;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Log;

class LogNotificationStatus
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $channel = $event->channel;
        $notifiable = $event->notifiable;
        $notification = $event->notification;

        if ($event instanceof NotificationSent) {
            Log::channel('admin')->info("Notification Sent via {$channel}", [
                'notifiable' => $this->getNotifiableIdentifier($notifiable),
                'notification' => get_class($notification),
            ]);
        } elseif ($event instanceof NotificationFailed) {
            Log::channel('admin')->error("Notification Failed via {$channel}", [
                'notifiable' => $this->getNotifiableIdentifier($notifiable),
                'notification' => get_class($notification),
                'exception' => $event->data['exception'] ?? 'No exception data',
            ]);
        }
    }

    private function getNotifiableIdentifier(object $notifiable): string
    {
        if (isset($notifiable->id)) {
            return get_class($notifiable) . " #{$notifiable->id}";
        }

        if (isset($notifiable->email)) {
            return "Email: {$notifiable->email}";
        }

        if (isset($notifiable->routes['mail'])) {
            return "On-Demand: {$notifiable->routes['mail']}";
        }

        return get_class($notifiable);
    }
}
