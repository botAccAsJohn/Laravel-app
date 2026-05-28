<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable implements HasLocalePreference
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes;

    protected $guard = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
        ];
    }

    /**
     * Fields the RedisUserProvider is allowed to cache for this model.
     * Declared here so the provider stays model-agnostic.
     *
     * NOTE: `role` and `subscription_tier` are intentionally absent —
     * those columns only exist on the `users` table, not `admins`.
     */
    public function getAuthCacheFields(): array
    {
        return [
            'id',
            'name',
            'email',
            'preferred_locale',
            'remember_token',
        ];
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
        static::updated(function (Admin $admin) {
            Cache::tags(['admins'])->forget("auth_admin:{$admin->id}");
        });

        static::deleted(function (Admin $admin) {
            Cache::tags(['admins'])->forget("auth_admin:{$admin->id}");
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
}

