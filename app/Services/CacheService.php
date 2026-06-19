<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class CacheService
{

    // here the product.list for the all the product & pages cache and the products has the single product cache
    const TTL_ALL_PRODUCTS = 60 * 60;
    const CACHE_PRODUCT_TAG = 'products';
    const CACHE_PRODUCT_LIST_TAG = 'products:list'; // append page → "products:paginated:1"
    const CACHE_PRODUCT_ALL = 'products:all';
    const CACHE_PRODUCT_SEARCH = 'products:search:'; // append query → "products:search:my-product"
    const CACHE_PRODUCT_COUNT = 'products:count';
    const CACHE_PRODUCT_SINGLE = 'products:single:'; // append slug → "products:single:my-product"

    public static function getAllProductsFromCache()
    {
        return Cache::tags([self::CACHE_PRODUCT_LIST_TAG])->remember(self::CACHE_PRODUCT_ALL, self::TTL_ALL_PRODUCTS, function () {
            return Product::with('category')->latest()->get();
        });
    }
    public static function getPaginatedProductsFromCache(int $page = 1, int $perPage = 12)
    {
        $key = md5("page:{$page}:perPage:{$perPage}");
        $cacheKey = self::CACHE_PRODUCT_SINGLE . "_{$key}";

        return Cache::tags([self::CACHE_PRODUCT_LIST_TAG])->remember($cacheKey, self::TTL_ALL_PRODUCTS, function () use ($page, $perPage) {
            return Product::with('category')
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);
        });
    }

    public static function countFromCache(): int
    {
        return Cache::tags([self::CACHE_PRODUCT_LIST_TAG])->remember(self::CACHE_PRODUCT_COUNT, self::TTL_ALL_PRODUCTS, function () {
            return Product::count();
        });
    }

    // ─── Product Cache ───────────────────────────────
    public function forgetProduct(string $slug): void
    {
        Cache::tags([self::CACHE_PRODUCT_TAG])->forget(self::CACHE_PRODUCT_SINGLE . $slug);
        $this->forgetProductList();
    }

    public function forgetProductList(): void
    {
        Cache::tags([self::CACHE_PRODUCT_LIST_TAG])->flush();
    }

    // ─── Category Cache ──────────────────────────────
    public function forgetCategories(): void
    {
        Cache::tags(['categories'])->flush();
    }

    // ─── Admin Dashboard Cache ────────────────────────────
    // public function forgetDashboard(): void
    // {
    //     Cache::tags(['admin'])->flush();
    // }

    // ─── Cart Cache ─────────────────────────────────────
    // public function forgetCart(?int $userId = null): void
    // {
    //     $userId = $userId ?? current_user()?->id;

    //     if ($userId) {
    //         Cache::tags(["cart.user.{$userId}"])->flush();
    //     }
    // }

    // ─── Flush Everything ────────────────────────────
    public function flushAll(): void
    {
        Cache::tags([self::CACHE_PRODUCT_TAG, self::CACHE_PRODUCT_LIST_TAG, 'admin', 'customer', 'orders'])->flush();
    }

    public function forgetOrders(){
        Cache::tags(['orders'])->flush();
    }
}