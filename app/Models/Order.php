<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'status',
        'payment_method',
        'address',
        'phone',
        'total_amount',
        'discount_amount',
        'final_amount',
        'placed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'placed_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
