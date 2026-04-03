<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RecentlyViewServices
{
    private string $prefix = 'viewed:user:';
    private int $limit = 10;

    private function getKey(int $userId): string
    {
        return $this->prefix . $userId;
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    /**
     * Get the list of recently viewed product IDs for a user.
     * Returns IDs in order (most recent first).
     */
    public function get(int $userId): array
    {
        $raw = Redis::get($this->getKey($userId));
        return $raw ? json_decode($raw, true) : [];
    }

    /**
     * Hydrate full Eloquent models for recently viewed products.
     * Maintains the order (most recent first).
     *
     * @return Collection<int, Product>
     */
    public function getRecentlyViewedModels(int $userId): Collection
    {
        $ids = $this->get($userId);
        if (empty($ids)) {
            return new Collection();
        }

        // Fetch products and maintain the order from the list
        return Product::with('category')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(function ($product) use ($ids) {
                return array_search($product->id, $ids);
            })
            ->values();
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /**
     * Record a product view for a user.
     * Moves the product to the front if it already exists, and limits the list to $this->limit.
     */
    public function record(int $userId, int $productId): void
    {
        $ids = $this->get($userId);

        // Remove if already exists (to move it to the front)
        if (($key = array_search($productId, $ids)) !== false) {
            unset($ids[$key]);
        }

        // Add to the front
        array_unshift($ids, $productId);

        // Limit the size
        $ids = array_slice($ids, 0, $this->limit);

        $this->save($userId, $ids);

        Log::channel('products')->debug('Product view recorded', [
            'user_id'    => $userId,
            'product_id' => $productId,
        ]);
    }

    /**
     * Clear the recently viewed history for a user.
     */
    public function clear(int $userId): void
    {
        Redis::del($this->getKey($userId));
        Log::channel('products')->info('Recently viewed history cleared', ['user_id' => $userId]);
    }

    private function save(int $userId, array $ids): void
    {
        $key = $this->getKey($userId);
        Redis::set($key, json_encode(array_values($ids)));
        Redis::expire($key, 60 * 60 * 24 * 7); // 7-day TTL
    }
}
