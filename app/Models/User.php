<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Translation\HasLocalePreference;

class User extends Authenticatable implements HasLocalePreference
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'preferred_locale',
        'webhook_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Route notifications for the webhook channel.
     */
    public function routeNotificationForWebhook(): ?string
    {
        return $this->webhook_url ?? null;
    }

    /**
     * Get the user's preferred locale for notifications.
     */
    public function preferredLocale(): string
    {
        return $this->preferred_locale ?? config('app.locale');
    }

    protected static function booted(): void
    {
        static::updated(function (User $user) {
            Cache::tags(['users'])->forget("auth_user:{$user->id}");
        });

        static::deleted(function (User $user) {
            Cache::tags(['users'])->forget("auth_user:{$user->id}");
        });
    }

    /**
     * Route Slack notifications using the Hybrid approach:
     *
     * - Webhook URL → message is POSTed directly to the webhook (channel is fixed by the webhook config in Slack)
     * - Channel name → message is sent via Bot Token's chat.postMessage API (bot must be invited to the channel)
     *
     * Both approaches support Block Kit (headerBlock, sectionBlock, etc.)
     */
    public function routeNotificationForSlack(mixed $notification): mixed
    {
        return match (true) {
            // ── Webhook-routed (posts to the channel the webhook is configured for) ──
            $notification instanceof \App\Notifications\NewOrderReceived,
            $notification instanceof \App\Notifications\OrderShipped
                => config('services.slack.webhook_order_url'),

            $notification instanceof \App\Notifications\ProductLowStock,
            $notification instanceof \App\Notifications\ServerAlertNotification
                => config('services.slack.webhook_alert_url'),

            // ── Bot Token-routed (uses channel name + bot token) ──
            $notification instanceof \App\Notifications\NewSupportTicket
                => config('services.slack.channels.support', '#support'),

            default => config('services.slack.notifications.channel', '#general'),
        };
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }
}
