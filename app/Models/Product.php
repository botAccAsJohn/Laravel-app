<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Collections\ProductCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, MorphTo, BelongsToMany};
use App\Models\{Category, User, OrderItem, Review};

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
    // Bind by slug instead of id
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ── Relationships ─────────────────────────────────────────────────────────
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by');
    }

    public function updatedBy(): MorphTo
    {
        return $this->morphTo('updated_by');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // public function waitlistUsers(): BelongsToMany
    // {
    //     return $this->belongsToMany(User::class, 'product_waitlist')->withTimestamps();
    // }
    // public function getFinalPriceAttribute()
    // {
    //     return $this->discount_price ?? $this->price;
    // }
    
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

    // public function toSearchableArray(): array
    // {
    //         return [
    //     'id'          => $this->id,
    //     'name'        => $this->name,
    //     'description' => $this->description,
    //     'slug'        => $this->slug,
    //     'category'    => $this->category?->name,
    //     'category_id' => $this->category_id,
    //     'tags'        => $this->tags,        // already cast to array ✓
    //     'price'       => (float) $this->price,
    //     'quantity'    => (int) $this->quantity,
    //     'is_active'   => (bool) $this->is_active,  // ← correct field
    //     'created_at'  => $this->created_at?->timestamp,
    // ];
    // }

    public function toSearchableArray(): array
{
    return [
        'id'              => $this->id,
        'name'            => $this->name,
        'description'     => $this->description,
        'slug'            => $this->slug,
        'category'        => $this->category?->name,
        'category_id'     => $this->category_id,
        'tags'            => $this->tags,
        'price'           => (float) $this->price,
        'discount_price'  => (float) ($this->discount_price ?? 0),
        'quantity'        => (int) $this->quantity,
        'average_rating'  => (float) ($this->average_rating ?? 0),
        'review_count'    => (int) ($this->review_count ?? 0),
        'is_active'       => (bool) $this->is_active,
        'created_at'      => $this->created_at?->timestamp,
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

    public function resolveRouteBinding($value, $field = null)
    {
        return Cache::tags([CacheService::CACHE_PRODUCT_TAG])->remember(
            CacheService::CACHE_PRODUCT_SINGLE . $value,
            CacheService::TTL_ALL_PRODUCTS,
            fn() => Product::with('category')->where('slug', $value)->first()
        );
    }
}
