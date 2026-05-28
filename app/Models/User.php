<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'preferred_locale',
        'subscription_tier',
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
            'password'          => 'hashed',
        ];
    }

    /**
     * Fields the RedisUserProvider is allowed to cache for this model.
     * Declared here so the provider stays model-agnostic.
     */
    public function getAuthCacheFields(): array
    {
        return [
            'id',
            'name',
            'email',
            'role',
            'subscription_tier',
            'preferred_locale',
            'email_verified_at',
            'remember_token',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // ── Exercise 50.4: RBAC ─────────────────────────────────────────────────────

    /**
     * The roles assigned to this user.
     * Includes pivot: assigned_by, assigned_at.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
                    ->withPivot(['assigned_by', 'assigned_at'])
                    ->withTimestamps();
    }

    /**
     * Whether the user holds a given role (by name).
     *
     * Example: $user->hasRole('manager')
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->pluck('name')->contains($role);
    }

    /**
     * Whether the user has a specific permission via any of their roles.
     *
     * Example: $user->hasPermission('manage_products')
     */
    public function hasPermission(string $permission): bool
    {
        return $this->allPermissions()->contains($permission);
    }

    /**
     * Get a flat unique collection of all permission names across all roles.
     *
     * Cached per-user for 5 minutes to avoid redundant DB queries on every
     * request. Cache is busted when roles are assigned/revoked.
     */
    public function allPermissions(): \Illuminate\Support\Collection
    {
        return Cache::remember(
            "user_permissions:{$this->id}",
            now()->addMinutes(5),
            fn () => $this->roles->load('permissions')
                        ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                        ->unique()
        );
    }

    /**
     * Forget the cached permission set for this user.
     * Called on every role assign/revoke.
     */
    public function forgetPermissionsCache(): void
    {
        Cache::forget("user_permissions:{$this->id}");
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
