<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

use App\Collections\ProductCollection;

class Product extends Model
{
    use HasFactory;

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \App\Collections\ProductCollection
     */
    public function newCollection(array $models = [])
    {
        return new ProductCollection($models);
    }


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
        'quantity',
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


    const TTL_ALL_PRODUCTS = 60 * 60; // 1 hour – shared TTL for both cache layers
    const CACHE_KEY_ALL = 'products:all';
    const CACHE_KEY_COUNT = 'products:count';
    const CACHE_KEY_SINGLE = 'products:single:'; // append slug → "products:single:my-product"

    public static function getAllProductsFromCache()
    {
        return Cache::remember(self::CACHE_KEY_ALL, self::TTL_ALL_PRODUCTS, function () {
            return Product::with('category')->latest()->get();
        });
    }

    public static function countFromCache(): int
    {
        Log::channel('orders')->warning("Just testing out the things");
        return Cache::remember(self::CACHE_KEY_COUNT, self::TTL_ALL_PRODUCTS, function () {
            return Product::count();
        });
    }


    public function resolveRouteBinding($value, $field = null)
    {
        return Cache::remember(
            self::CACHE_KEY_SINGLE . $value,
            self::TTL_ALL_PRODUCTS,
            fn() => Product::with('category')->where('slug', $value)->first()
        );
    }
}
