<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;
    public const CREATED_AT = null;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'payment_method',
        'payment_ref',
        'address',
        'phone',
        'coupon_code',
        'total_amount',
        'discount_amount',
        'final_amount',
        'placed_at',
        'invoice_path',
        'is_digital',
        'billing_address',
        'shipping_address',
    ];

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    protected $casts = [
        'total_amount'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount'    => 'decimal:2',
        'placed_at'       => 'datetime',
        'updated_at'      => 'datetime',
        'is_digital'      => 'boolean',
        'billing_address'  => 'encrypted', // Exercise 54.2
        'shipping_address' => 'encrypted', // Exercise 54.2
    ];

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $cacheKey = "orders:{$value}";
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(30), function () use ($value, $field) {
            $order = parent::resolveRouteBinding($value, $field);
            return $order ? $order->load(['user', 'items.product']) : null;
        });
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (!$order->order_number) {
                // $order->order_number = 'ORD-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(6));
                $order->order_number = 'ORD-' . (string) Str::ulid();
            }
        });

        static::saved(function (Order $order) {
            \Illuminate\Support\Facades\Cache::tags('ordersPage')->flush();
            \Illuminate\Support\Facades\Cache::forget("orders:{$order->order_number}");
            \Illuminate\Support\Facades\Cache::forget("orders:id:{$order->id}");
        });

        static::deleted(function (Order $order) {
            \Illuminate\Support\Facades\Cache::tags('ordersPage')->flush();
            \Illuminate\Support\Facades\Cache::forget("orders:{$order->order_number}");
            \Illuminate\Support\Facades\Cache::forget("orders:id:{$order->id}");
        });
    }
}
