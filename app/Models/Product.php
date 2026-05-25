<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Collections\ProductCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, SoftDeletes, Searchable;

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
        'average_rating',
        'review_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'tags' => 'array',   // JSON column auto-encoded/decoded as PHP array
        'is_active' => 'boolean',
    ];

    // ── Accessors ─────────────────────────────────────────────────────────────
    public function getImageUrlAttribute(): string
    {
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : asset('storage/products/default.jpg');
    }

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

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }


    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }


        public function searchableAs(): string
    {
        return 'products_v1';
    }

    // ── 2. Exclude unpublished + soft-deleted ────────────
    public function shouldBeSearchable(): bool
    {
        return $this->is_active === true   // ← is_active not is_published
        && ! $this->trashed();
    }

    // ── 3. Eager-load category before indexing ───────────
    public function makeSearchableUsing(
        \Illuminate\Database\Eloquent\Collection $models
    ): \Illuminate\Database\Eloquent\Collection {
        return $models->load('category');
    }

    public function toSearchableArray(): array
    {
            return [
        'id'          => $this->id,
        'name'        => $this->name,
        'description' => $this->description,
        'slug'        => $this->slug,
        'category'    => $this->category?->name,
        'category_id' => $this->category_id,
        'tags'        => $this->tags,        // already cast to array ✓
        'price'       => (float) $this->price,
        'quantity'    => (int) $this->quantity,
        'is_active'   => (bool) $this->is_active,  // ← correct field
        'created_at'  => $this->created_at?->timestamp,
    ];
    }

    // /**
    //  * Override search index name (optional).
    //  */
    // public function searchableAs(): string
    // {
    //     return 'products_index';
    // }

    public function newCollection(array $models = [])
    {
        return new ProductCollection($models);
    }

    const TTL_ALL_PRODUCTS = 60 * 60; // 1 hour – shared TTL for both cache layers
    const CACHE_KEY_ALL = 'products:all';
    const CACHE_NAME = 'products';
    const CACHE_KEY_COUNT = 'products:count';
    const CACHE_KEY_SINGLE = 'products:single:'; // append slug → "products:single:my-product"

    public static function getAllProductsFromCache()
    {
        return Cache::remember(self::CACHE_KEY_ALL, self::TTL_ALL_PRODUCTS, function () {
            return Product::with('category')->latest()->get();
        });
    }
    public static function getPaginatedProductsFromCache(int $page = 1, int $perPage = 12)
    {
        $key = md5("page:{$page}:perPage:{$perPage}");
        $cacheKey = self::CACHE_NAME . "_{$key}";

        return Cache::remember($cacheKey, self::TTL_ALL_PRODUCTS, function () use ($page, $perPage) {
            return Product::with('category')
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);
        });
    }

    public static function countFromCache(): int
    {
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
