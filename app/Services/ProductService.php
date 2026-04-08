<?php

namespace App\Services;

use App\Exceptions\InvalidPriceException;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    const TTL_ALL_PRODUCTS = 60 * 60; // 1 hour – shared TTL for both cache layers
    const CACHE_KEY_ALL = 'products:all';
    const CACHE_KEY_COUNT = 'products:count';
    const CACHE_KEY_SINGLE = 'products:single:'; // append slug → "products:single:my-product"

    // ── Read ─────────────────────────────────────────────────────────────────

    /**
     * Return all products.
     * Stored in its own "products:all" cache key – completely independent
     * from the per-slug individual cache.
     */
    public function all()
    {
        return Cache::remember(self::CACHE_KEY_ALL, self::TTL_ALL_PRODUCTS, function () {
            return Product::with('category')->latest()->get();
        });
    }

    public function count(): int
    {
        return Cache::remember(self::CACHE_KEY_COUNT, self::TTL_ALL_PRODUCTS, function () {
            return Product::count();
        });
    }

    public function find(string $slug): ?Product
    {
        return Cache::remember(
            self::CACHE_KEY_SINGLE . $slug,
            self::TTL_ALL_PRODUCTS,
            fn() => Product::with('category')->where('slug', $slug)->first()
        );
    }

    /**
     * Return all categories ordered by name.
     * Cached forever — busted only when a category changes (rare).
     */
    public function categories()
    {
        return Cache::rememberForever('categories:all', function () {
            return Category::orderBy('name')->get();
        });
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function create(array $validated, ?UploadedFile $imageFile = null): Product
    {
        if (isset($validated['price']) && (float) $validated['price'] <= 0) {
            throw new InvalidPriceException(
                (float) $validated['price'],
                'Price must be greater than zero.'
            );
        }

        if ($imageFile) {
            $validated['image_path'] = $imageFile->store('products', 'public');
        }

        $validated['tags'] = $this->normalizeTags($validated['tags'] ?? null);

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->generateSlug($validated['name']);
        }

        $product = Product::create($validated);

        // Bust the "all products" list and count; individual cache doesn't exist yet.
        $this->forgetListCache();

        Log::channel('products')->info('Product created', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
        ]);

        return $product;
    }

    public function update(string $slug, array $validated, ?UploadedFile $imageFile = null): bool
    {
        $product = $this->find($slug);
        if (!$product) {
            abort(404);
        }

        if (isset($validated['price']) && (float) $validated['price'] <= 0) {
            throw new InvalidPriceException(
                (float) $validated['price'],
                'Price must be greater than zero.'
            );
        }

        if ($imageFile) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);

                Log::channel('products')->debug('Old product image deleted', [
                    'product_id' => $product->id,
                    'old_image' => $product->image_path,
                ]);
            }
            $validated['image_path'] = $imageFile->store('products', 'public');
        }

        $validated['tags'] = $this->normalizeTags($validated['tags'] ?? null);

        // If slug itself changed, we need to drop the OLD individual cache key
        // before overwriting it with the new slug.
        $newSlug = null;
        if (!isset($validated['slug']) || empty($validated['slug'])) {
            $validated['slug'] = $this->generateSlug($validated['name'] ?? $product->name, $product->id);
        }
        if (isset($validated['slug']) && $validated['slug'] !== $slug) {
            $newSlug = $validated['slug'];
        }

        $updated = $product->update($validated);

        // Bust the "all products" list (order/data changed).
        $this->forgetListCache();

        // Bust the individual cache for the OLD slug.
        Cache::forget(self::CACHE_KEY_SINGLE . $slug);

        // If the slug changed, also prime/bust the new slug's individual cache.
        if ($newSlug) {
            Cache::forget(self::CACHE_KEY_SINGLE . $newSlug);
        }

        Log::channel('products')->info('Product updated', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'changed_keys' => array_keys($validated),
        ]);

        return $updated;
    }

    public function delete(Product $product): bool
    {
        $productId = $product->id;
        $productName = $product->name;
        $slug = $product->slug; // ← bug-fix: was undefined in original

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $deleted = $product->delete();

        // Bust both the list cache and this product's individual cache entry.
        $this->forgetListCache();
        Cache::forget(self::CACHE_KEY_SINGLE . $slug);

        Log::channel('products')->info('Product deleted', [
            'product_id' => $productId,
            'product_name' => $productName,
        ]);

        return $deleted;
    }

    // ── Cache helpers ─────────────────────────────────────────────────────────

    /**
     * Forget the "all products" list cache keys.
     * Call this whenever the set of products changes (create / delete).
     */
    private function forgetListCache(): void
    {
        Cache::forget(self::CACHE_KEY_ALL);
        Cache::forget(self::CACHE_KEY_COUNT);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function normalizeTags(mixed $tags): ?array
    {
        if (is_array($tags)) {
            return array_values(array_filter(array_map('trim', $tags)));
        }

        if (is_string($tags) && $tags !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $tags))));
        }

        return null;
    }

    private function generateSlug(string $name, ?int $excludeProductId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        $query = Product::where('slug', $slug);
        if ($excludeProductId !== null) {
            $query->where('id', '!=', $excludeProductId);
        }

        while ($query->exists()) {
            $slug = $base . '-' . $i++;
            $query = Product::where('slug', $slug);
            if ($excludeProductId !== null) {
                $query->where('id', '!=', $excludeProductId);
            }
        }

        return $slug;
    }

    public function getLogs($name)
    {
        $file = fopen(storage_path('logs/' . $name . '/' . $name . '-' . date('Y-m-d') . '.log'), 'r');
        $logs = [];
        while (!feof($file)) {
            $logs[] = fgets($file);
        }
        fclose($file);
        return $logs;
    }
}