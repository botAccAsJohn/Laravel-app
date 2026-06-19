<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RecentlyViewServices
{
    private string $userPrefix = 'viewed:user:';
    private string $guestPrefix = 'viewed:guest:';
    private int $limit = 10;

    /**
     * Resolve the correct Redis recently viewed key for the current request.
     */
    public function resolveRecentlyViewedKey(): string
    {
        if (auth()->check()) {
            return $this->userPrefix . auth()->id();
        }

        if (! session()->has('recently_viewed_id')) {
            session()->put('recently_viewed_id', Str::uuid()->toString());
        }

        return $this->guestPrefix . session('recently_viewed_id');
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    /**
     * Get the list of recently viewed product IDs for a given key.
     * Returns IDs in order (most recent first).
     */
    public function get(string $key): array
    {
        $raw = Redis::get($key);
        return $raw ? json_decode($raw, true) : [];
    }

    /**
     * Hydrate full Eloquent models for recently viewed products.
     * Maintains the order (most recent first).
     *
     * @return Collection<int, Product>
     */
    public function getRecentlyViewedModels(string $key): Collection
    {
        $ids = $this->get($key);
        if (empty($ids)) {
            return new Collection();
        }

        // Try warm cache first (zero DB hit)
        $cached = Cache::get('products:all');
        if ($cached) {
            $filtered = $cached
                ->whereIn('id', $ids)
                ->sortBy(fn($product) => array_search($product->id, $ids))
                ->values();

            return new Collection($filtered->all());
        }

        // Cold cache — fall back to a targeted DB query, preserve order
        $products = Product::with('category')->whereIn('id', $ids)->get();

        return new Collection(
            $products
                ->sortBy(fn($p) => array_search($p->id, $ids))
                ->values()
                ->all()
        );
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /**
     * Record a product view for a given key.
     * Moves the product to the front if it already exists, and limits the list to $this->limit.
     */
    public function record(string $key, int $productId): void
    {
        $ids = $this->get($key);

        // Remove if already exists (to move it to the front)
        if (($arrKey = array_search($productId, $ids)) !== false) {
            unset($ids[$arrKey]);
        }

        // Add to the front
        array_unshift($ids, $productId);

        // Limit the size
        $ids = array_slice($ids, 0, $this->limit);

        $this->save($key, $ids);

        Log::channel('products')->debug('Product view recorded', [
            'recently_viewed_key' => $key,
            'product_id' => $productId,
        ]);
    }

    /**
     * Clear the recently viewed history for a key.
     */
    public function clear(string $key): void
    {
        Redis::del($key);
        Log::channel('products')->info('Recently viewed history cleared', ['recently_viewed_key' => $key]);
    }

    /**
     * Merge guest recently viewed history into user history.
     */
    public function mergeGuestHistory(string $guestKey, string $userKey): void
    {
        $guestIds = $this->get($guestKey);
        $userIds = $this->get($userKey);

        $merged = array_unique(array_merge($guestIds, $userIds));
        $merged = array_slice($merged, 0, $this->limit);

        $this->save($userKey, $merged);
        $this->clear($guestKey);
    }

    private function save(string $key, array $ids): void
    {
        Redis::set($key, json_encode(array_values($ids)));
        Redis::expire($key, 60 * 60 * 24 * 7); // 7-day TTL
    }
}
