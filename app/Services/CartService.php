<?php

namespace App\Services;

use App\Exceptions\ProductOutOfStockException;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\{Log, Redis, Cache};
use Illuminate\Support\Str;

class CartService
{
    private string $userPrefix  = 'cart:user:';
    private string $guestPrefix = 'cart:guest:';

    // ── Cart-key resolution ────────────────────────────────────────────────────

    /**
     * Resolve the correct Redis cart key for the current request:
     *   - Authenticated customer → cart:user:{id}
     *   - Guest                  → cart:guest:{uuid}  (UUID stored in session)
     *
     * Call this once in the controller action and pass the result to every
     * service method so we never read session/auth twice per request.
     */
    public function resolveCartKey(): string
    {
        if (auth()->check()) {
            return $this->userPrefix . auth()->id();
        }

        if (! session()->has('cart_id')) {
            session()->put('cart_id', Str::uuid()->toString());
        }

        return $this->guestPrefix . session('cart_id');
    }

    /**
     * Whether the given key belongs to a guest cart.
     */
    public function isGuestKey(string $cartKey): bool
    {
        return str_starts_with($cartKey, $this->guestPrefix);
    }

    // ── Reminder cooldown (auth users only) ────────────────────────────────────

    private function getRemindedKey(int $userId): string
    {
        return 'cart:reminded:user:' . $userId;
    }

    /**
     * Check if a reminder was already sent to this user within the cooldown period.
     */
    public function wasReminded(int $userId): bool
    {
        return (bool) Redis::exists($this->getRemindedKey($userId));
    }

    /**
     * Mark a user as reminded. The flag expires after $hours (default 24h),
     * preventing duplicate emails until the cooldown clears.
     */
    public function markAsReminded(int $userId, int $hours = 24): void
    {
        Redis::setex($this->getRemindedKey($userId), $hours * 3600, '1');
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    /**
     * Return all cart items stored in Redis for the given cart key.
     * Each item: ['id', 'name', 'price', 'quantity']
     *
     * @param  string $cartKey  Resolved via resolveCartKey()
     */
    public function get(string $cartKey): array
    {
        $raw = Redis::get($cartKey);
        return $raw ? json_decode($raw, true) : [];
    }

    /**
     * Scan Redis and return all active *user* carts (guest carts are excluded —
     * they have no associated email to remind).
     * Returns an array keyed by user_id => cart items array.
     * Uses SCAN (non-blocking) instead of KEYS for production safety.
     *
     * @return array<int, array>
     */
    public function findAllCarts(): array
    {
        // Match only authenticated-user carts
        $pattern = '*' . $this->userPrefix . '*';
        $carts   = [];
        $cursor  = '0';

        do {
            [$cursor, $keys] = Redis::scan($cursor, ['match' => $pattern, 'count' => 100]);

            foreach ($keys as $fullKey) {
                // Extract user ID from the last segment: "session:cart:user:42" → 42
                $userId = (int) substr($fullKey, strrpos($fullKey, ':') + 1);

                if ($userId <= 0) {
                    continue;
                }

                // Strip the auto-prefix so Redis::get() doesn't double-prefix.
                $plainKey = substr($fullKey, strpos($fullKey, $this->userPrefix));
                $raw      = Redis::get($plainKey);

                if ($raw) {
                    $carts[$userId] = json_decode($raw, true);
                }
            }
        } while ($cursor !== '0');

        return $carts;
    }

    /**
     * Hydrate full Eloquent models for every item currently in the cart.
     * Keyed by product ID so blade templates can do $cartModels[$item['id']].
     *
     * @param  string $cartKey
     * @return Collection<int, Product>
     */
    public function getCartModels(string $cartKey): Collection
    {
        $cart = $this->get($cartKey);
        $ids  = array_column($cart, 'id');

        if (empty($ids)) {
            return new Collection();
        }

        // Try warm cache first
        $cached = \App\Services\CacheService::getAllProductsFromCache();
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
     *
     * @param  string $cartKey
     */
    public function total(string $cartKey): float
    {
        $cart   = $this->get($cartKey);
        $models = $this->getCartModels($cartKey);

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
            // Skip metadata keys starting with _
            if (is_string($productId) && str_starts_with($productId, '_')) {
                continue;
            }

            $model          = $models[$productId] ?? null;
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
     * @param  string $cartKey
     * @throws ProductOutOfStockException  When the product is not found or out of stock.
     */
    public function add(string $cartKey, int $productId, int $quantity = 1): void
    {
        $product = $this->findProduct($productId);

        if (! $product) {
            throw new ProductOutOfStockException(
                productName: "Product #{$productId}",
                productId: $productId,
                requestedQty: $quantity,
                availableQty: 0,
            );
        }

        $cart                    = $this->get($cartKey);
        $currentQuantityInCart   = isset($cart[$productId]) ? $cart[$productId]['quantity'] : 0;
        $totalRequestedQuantity  = $currentQuantityInCart + $quantity;

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
                'id'       => $product->id,
                'name'     => $product->name,
                'price'    => $product->price,
                'quantity' => $quantity,
            ];
        }

        $this->save($cartKey, $cart);

        Log::channel('cart')->info('Item added to cart', [
            'cart_key'   => $cartKey,
            'product_id' => $product->id,
            'name'       => $product->name,
            'quantity'   => $cart[$productId]['quantity'],
        ]);
    }

    /**
     * Remove a single item from the cart entirely.
     *
     * @param string $cartKey
     */
    public function remove(string $cartKey, int $productId): void
    {
        $cart = $this->get($cartKey);

        if (isset($cart[$productId])) {
            $name = $cart[$productId]['name'] ?? "Product #{$productId}";
            unset($cart[$productId]);
            $this->save($cartKey, $cart);

            Log::channel('cart')->info('Item removed from cart', [
                'cart_key'   => $cartKey,
                'product_id' => $productId,
                'name'       => $name,
            ]);
        }
    }

    /**
     * Decrement quantity by 1; remove the item entirely when quantity reaches 0.
     *
     * @param string $cartKey
     */
    public function decrement(string $cartKey, int $productId): void
    {
        $cart = $this->get($cartKey);

        if (! isset($cart[$productId])) {
            return; // item not in cart — nothing to do
        }

        if ($cart[$productId]['quantity'] > 1) {
            $cart[$productId]['quantity']--;
            $this->save($cartKey, $cart);

            Log::channel('cart')->debug('Item quantity decremented', [
                'cart_key'    => $cartKey,
                'product_id'  => $productId,
                'new_quantity' => $cart[$productId]['quantity'],
            ]);
        } else {
            $this->remove($cartKey, $productId);
        }
    }


    public function mergeSessionCart(array $sessionCart = []): void
    {
        $userId = current_user()?->id;

        // Only merge if logged in AND is a customer
        if (empty($sessionCart) || !$userId || !is_customer()) {
            return;
        }

        $userKey = $this->userPrefix . $userId;

        foreach ($sessionCart as $productId => $item) {
            // Handle different session cart formats (e.g. [id => ['quantity' => 2]] or [id => 2])
            $qty = is_array($item) ? ($item['quantity'] ?? 1) : (int) $item;
            
            try {
                $this->add($userKey, (int) $productId, $qty);
            } catch (ProductOutOfStockException) {
                // Silently skip out-of-stock items during merge
            }
        }

        // Clear generic session cart if it exists
        session()->forget('cart');

        Log::channel('cart')->info('Legacy session cart merged to Redis', [
            'user_id' => $userId,
            'item_count' => count($sessionCart),
        ]);
    }

    /**
     * Set an item's quantity explicitly; removes item when $quantity <= 0.
     *
     * @param string $cartKey
     * @throws ProductOutOfStockException
     */
    public function updateQuantity(string $cartKey, int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($cartKey, $productId);
            return;
        }

        $product = $this->findProduct($productId);

        if (! $product || $quantity > $product->quantity) {
            throw new ProductOutOfStockException(
                productName: $product ? $product->name : "Product #{$productId}",
                productId: $productId,
                requestedQty: $quantity,
                availableQty: $product ? $product->quantity : 0,
            );
        }

        $cart = $this->get($cartKey);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $quantity;
            $this->save($cartKey, $cart);

            Log::channel('cart')->debug('Item quantity updated', [
                'cart_key'   => $cartKey,
                'product_id' => $productId,
                'quantity'   => $quantity,
            ]);
        }
    }

    /**
     * Remove all items from the cart.
     *
     * @param string $cartKey
     */
    public function clear(string $cartKey): void
    {
        Redis::del($cartKey);

        Log::channel('cart')->info('Cart cleared', ['cart_key' => $cartKey]);
    }

    /**
     * Store an applied coupon code in the cart metadata.
     *
     * @param string $cartKey
     */
    public function applyCoupon(string $cartKey, ?string $couponCode): void
    {
        $cart = $this->get($cartKey);

        if ($couponCode) {
            $cart['_applied_coupon'] = $couponCode;
        } else {
            unset($cart['_applied_coupon']);
        }

        $this->save($cartKey, $cart);
    }

    /**
     * Retrieve the applied coupon code from the cart metadata.
     *
     * @param string $cartKey
     */
    public function getAppliedCoupon(string $cartKey): ?string
    {
        $cart = $this->get($cartKey);
        return $cart['_applied_coupon'] ?? null;
    }

    /**
     * Merge a guest cart into an authenticated user's cart after login.
     * Called from a login listener or post-login hook.
     * Deletes the guest cart once merged.
     */
    public function mergeGuestCart(string $guestCartKey, int $userId): void
    {
        if (! $this->isGuestKey($guestCartKey)) {
            return;
        }

        $guestCart = $this->get($guestCartKey);
        $userKey   = $this->userPrefix . $userId;

        foreach ($guestCart as $productId => $item) {
            if (is_string($productId) && str_starts_with($productId, '_')) {
                continue; // skip metadata
            }

            try {
                $this->add($userKey, (int) $productId, $item['quantity']);
            } catch (ProductOutOfStockException) {
                // Silently skip out-of-stock items during merge
            }
        }

        // Clean up the guest cart
        Redis::del($guestCartKey);

        Log::channel('cart')->info('Guest cart merged into user cart', [
            'guest_key' => $guestCartKey,
            'user_id'   => $userId,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Look up a Product by ID — from the warm cache first, DB on a cold miss.
     */
    private function findProduct(int $productId): ?Product
    {
        $cached = \App\Services\CacheService::getAllProductsFromCache();
        if ($cached) {
            $found = $cached->firstWhere('id', $productId);
            if ($found) {
                return $found;
            }
        }

        return Product::find($productId);
    }

    /**
     * Persist the cart to Redis with a 30-day TTL.
     */
    private function save(string $cartKey, array $cart): void
    {
        $cart['_last_activity_at'] = now()->timestamp;

        Redis::set($cartKey, json_encode($cart));
        Redis::expire($cartKey, 60 * 60 * 24 * 30); // 30-day TTL
    }
}
