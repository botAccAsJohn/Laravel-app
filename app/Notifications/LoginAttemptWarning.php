<?php
// app/Notifications/LoginAttemptWarning.php
//
// Exercise 49.4 — sent to the account owner on their 5th failed login attempt.

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginAttemptWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $ip,
        public readonly int    $failedAttempts,
        public readonly string $userAgent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Suspicious login activity on your account')
            ->greeting("Hello {$notifiable->name},")
            ->line("We detected **{$this->failedAttempts} failed login attempts** on your account in the last 15 minutes.")
            ->line("**IP address:** {$this->ip}")
            ->line("**Device:** {$this->userAgent}")
            ->line('If this was you (e.g. a forgotten password), you can safely ignore this email.')
            ->action('Reset your password', url(route('password.request')))
            ->line('If this was **not** you, please reset your password immediately and contact support.')
            ->salutation('— The ' . config('app.name') . ' Security Team');
    }
}
