<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'discount_price',
        'tags',
        'category_id',
        'slug',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'discount_price' => 'decimal:2',
        'tags'           => 'array',   // JSON column auto-encoded/decoded as PHP array
        'is_active'      => 'boolean',
    ];

    // ── Route model binding ───────────────────────────────────────────────────
    // Bind by slug instead of id: /products/running-shoes → WHERE slug = 'running-shoes'
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
