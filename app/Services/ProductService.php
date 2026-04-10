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

    public function update(Product $product, array $validated, ?UploadedFile $imageFile = null): bool
    {
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
        if (isset($validated['slug']) && $validated['slug'] !== $product->slug) {
            $newSlug = $validated['slug'];
        }

        $oldQuantity = $product->quantity;
        $updated = $product->update($validated);

        // Broadcast if quantity changed
        if ($updated && isset($validated['quantity']) && (int)$validated['quantity'] !== (int)$oldQuantity) {
            broadcast(new \App\Events\ProductStockChanged($product->id, $product->quantity));
        }

        // Bust the "all products" list (order/data changed).
        $this->forgetListCache();

        // Bust the individual cache for the OLD slug.
        Cache::forget(Product::CACHE_KEY_SINGLE . $product->slug);

        // If the slug changed, also prime/bust the new slug's individual cache.
        if ($newSlug) {
            Cache::forget(Product::CACHE_KEY_SINGLE . $newSlug);
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
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $deleted = $product->delete();

        // Bust both the list cache and this product's individual cache entry.
        $this->forgetListCache();
        Cache::forget(Product::CACHE_KEY_SINGLE . $product->slug);

        Log::channel('products')->info('Product deleted', [
            'product_id' => $product->id,
            'product_name' => $product->name,
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
        Cache::forget(Product::CACHE_KEY_ALL);
        Cache::forget(Product::CACHE_KEY_COUNT);
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
