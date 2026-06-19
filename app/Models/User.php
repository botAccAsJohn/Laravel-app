<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'preferred_locale',
        'subscription_tier',
        'webhook_url',
        'force_password_reset',
        'phone',
        'address',
        'tax_id',
        'extra_attributes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'force_password_reset' => 'boolean',
            'phone'                => 'encrypted',
            'address'              => 'encrypted',
            'tax_id'               => 'encrypted',
            'extra_attributes'     => 'encrypted:array',
        ];
    }

    /**
     * Exercise 54.2 — Blind Index Generation
     * Used for timing-safe lookup of randomly-encrypted fields.
     */
    public static function generateBlindIndex(?string $value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        return hash_hmac('sha256', $value, config('app.key') . 'user_blind_index_salt');
    }

    public static function findByPhone(string $phone): ?self
    {
        $bindex = self::generateBlindIndex($phone);

        return static::where('phone_bindex', $bindex)->first();
    }

    public static function findByTaxId(string $taxId): ?self
    {
        $bindex = self::generateBlindIndex($taxId);

        return static::where('tax_id_bindex', $bindex)->first();
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
            'subscription_tier',
            'preferred_locale',
            'email_verified_at',
            'remember_token',
            'force_password_reset',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    // Spatie\Permission\Traits\HasRoles already provides:
    // - roles()
    // - hasRole()
    // - hasAnyRole()
    // - hasPermissionTo()
    // - assignRole()
    // - removeRole()

    public function hasPermission(string $permission): bool
    {
        return $this->allPermissions()->contains($permission);
    }


    public function allPermissions(): \Illuminate\Support\Collection
    {
        return Cache::remember(
            "user_permissions:{$this->id}",
            now()->addMinutes(5),
            fn () => $this->roles()
                ->with('permissions')
                ->get()
                ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                ->unique()
                ->values()
        );
    }

    public function forgetPermissionsCache(): void
    {
        Cache::forget("user_permissions:{$this->id}");
    }


    public function routeNotificationForWebhook(): ?string
    {
        return $this->webhook_url ?? null;
    }

    public function preferredLocale(): string
    {
        return $this->preferred_locale ?? config('app.locale');
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('phone')) {
                $user->phone_bindex = self::generateBlindIndex($user->phone);
            }

            if ($user->isDirty('tax_id')) {
                $user->tax_id_bindex = self::generateBlindIndex($user->tax_id);
            }
        });

        static::updated(function (User $user) {
            Cache::tags(['users'])->forget("auth_user:{$user->id}");
        });

        static::deleted(function (User $user) {
            Cache::tags(['users'])->forget("auth_user:{$user->id}");
        });
    }


    public function routeNotificationForSlack(mixed $notification): mixed
    {
        return match (true) {
            $notification instanceof \App\Notifications\NewOrderReceived,
            $notification instanceof \App\Notifications\OrderShipped
                => config('services.slack.webhook_order_url'),

            $notification instanceof \App\Notifications\ProductLowStock,
            $notification instanceof \App\Notifications\ServerAlertNotification
                => config('services.slack.webhook_alert_url'),

            $notification instanceof \App\Notifications\NewSupportTicket
                => config('services.slack.channels.support', '#support'),

            default => config('services.slack.notifications.channel', '#general'),
        };
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }
}