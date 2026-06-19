<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class AdminManualAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $subject,
        public string $messageContent
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $email = $notifiable->email ?? $notifiable->routes['mail'] ?? 'unknown';

        Log::channel('admin')->info("On-demand notification sent to {$email}", [
            'subject' => $this->subject,
            'type' => 'AdminManualAlert'
        ]);

        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->messageContent)
            ->line('Thank you for using our application!');
    }
}
