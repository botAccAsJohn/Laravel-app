<?php

namespace App\Services;

use App\Exceptions\ProductOutOfStockException;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class CartService
{
    private string $prefix = 'cart:user:';

    // ── Redis key ─────────────────────────────────────────────────────────────

    private function getKey(int $userId): string
    {
        return $this->prefix . $userId;
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    /**
     * Return all cart items stored in Redis for a user.
     * Each item: ['id', 'name', 'price', 'quantity']
     */
    public function get(int $userId): array
    {
        $raw = Redis::get($this->getKey($userId));
        return $raw ? json_decode($raw, true) : [];
    }

    /**
     * Hydrate full Eloquent models for every item currently in the cart.
     * Keyed by product ID so blade templates can do $cartModels[$item['id']].
     * Falls back to a DB query when the products cache is cold.
     *
     * @return Collection<int, Product>
     */
    public function getCartModels(int $userId): Collection
    {
        $cart = $this->get($userId);
        $ids  = array_column($cart, 'id');

        if (empty($ids)) {
            return new Collection();
        }

        // Try warm cache first
        $cached = Cache::get(Product::CACHE_KEY_ALL);
        if ($cached) {
            return new Collection(
                $cached->filter(fn($p) => in_array($p->id, $ids))->keyBy('id')
            );
        }

        // Cold cache — fall back to a targeted DB query
        return Product::whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * Calculate the grand total using the effective price per item.
     * Accepts pre-fetched $cart and $models to avoid redundant reads.
     * When called without arguments it fetches them itself.
     */
    public function total(int $userId): float
    {
        $cart   = $this->get($userId);
        $models = $this->getCartModels($userId);

        return $this->calcTotal($cart, $models);
    }

    /**
     * Compute the grand total from already-loaded cart data.
     * Used by cartSummary() to avoid re-fetching cart + models.
     */
    public function calcTotal(array $cart, Collection $models): float
    {
        $total = 0.0;

        foreach ($cart as $productId => $item) {
            $model = $models[$productId] ?? null;
            $effectivePrice = $model && $model->discount_price
                ? (float) $model->discount_price
                : (float) $item['price'];

            $total += $effectivePrice * $item['quantity'];
        }

        return $total;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /**
     * Add a product to the cart (or increment its quantity).
     * Resolves the product from the cache first, DB second.
     *
     * @throws ProductOutOfStockException  When the product is not found.
     */
    public function add(int $userId, int $productId, int $quantity = 1): Product
    {
        $product = $this->findProduct($productId);

        if (!$product) {
            throw new ProductOutOfStockException(
                productName: "Product #{$productId}",
                productId: $productId,
                requestedQty: $quantity,
                availableQty: 0,
            );
        }

        $cart = $this->get($userId);
        $currentQuantityInCart = isset($cart[$productId]) ? $cart[$productId]['quantity'] : 0;
        $totalRequestedQuantity = $currentQuantityInCart + $quantity;

        // Check if the total requested quantity exceeds available stock
        if ($totalRequestedQuantity > $product->quantity) {
            throw new ProductOutOfStockException(
                productName: $product->name,
                productId: $product->id,
                requestedQty: $totalRequestedQuantity,
                availableQty: $product->quantity,
            );
        }

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $totalRequestedQuantity;
        } else {
            $cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
            ];
        }

        $this->save($userId, $cart);

        Log::channel('cart')->info('Item added to cart', [
            'user_id' => $userId,
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => $cart[$productId]['quantity'],
        ]);

        return $product;
    }

    /**
     * Remove a single item from the cart entirely.
     */
    public function remove(int $userId, int $productId): void
    {
        $cart = $this->get($userId);

        if (isset($cart[$productId])) {
            $name = $cart[$productId]['name'] ?? "Product #{$productId}";
            unset($cart[$productId]);
            $this->save($userId, $cart);

            Log::channel('cart')->info('Item removed from cart', [
                'user_id' => $userId,
                'product_id' => $productId,
                'name' => $name,
            ]);
        }
    }

    /**
     * Decrement quantity by 1; remove the item entirely when quantity reaches 0.
     */
    public function decrement(int $userId, int $productId): void
    {
        $cart = $this->get($userId);

        if (!isset($cart[$productId])) {
            return; // item not in cart — nothing to do
        }

        if ($cart[$productId]['quantity'] > 1) {
            $cart[$productId]['quantity']--;
            $this->save($userId, $cart);

            Log::channel('cart')->debug('Item quantity decremented', [
                'user_id' => $userId,
                'product_id' => $productId,
                'new_quantity' => $cart[$productId]['quantity'],
            ]);
        } else {
            $this->remove($userId, $productId);
        }
    }

    /**
     * Set an item's quantity explicitly; removes item when $quantity <= 0.
     */
    public function updateQuantity(int $userId, int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($userId, $productId);
            return;
        }

        $product = $this->findProduct($productId);

        if (!$product || $quantity > $product->quantity) {
            throw new ProductOutOfStockException(
                productName: $product ? $product->name : "Product #{$productId}",
                productId: $productId,
                requestedQty: $quantity,
                availableQty: $product ? $product->quantity : 0,
            );
        }

        $cart = $this->get($userId);
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $quantity;
            $this->save($userId, $cart);

            Log::channel('cart')->debug('Item quantity updated', [
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }
    }

    /**
     * Remove all items from the user's cart.
     */
    public function clear(int $userId): void
    {
        Redis::del($this->getKey($userId));

        Log::channel('cart')->info('Cart cleared', ['user_id' => $userId]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Look up a Product by ID — from the warm cache first, DB on a cold miss.
     */
    private function findProduct(int $productId): ?Product
    {
        $cached = Cache::get(Product::CACHE_KEY_ALL);
        if ($cached) {
            $found = $cached->firstWhere('id', $productId);
            if ($found) {
                return $found;
            }
        }

        // Cache is cold — fall back to a direct DB lookup
        return Product::find($productId);
    }

    private function save(int $userId, array $cart): void
    {
        $key = $this->getKey($userId);
        Redis::set($key, json_encode($cart));
        Redis::expire($key, 60 * 60 * 24 * 30); // 30-day TTL
    }
}
