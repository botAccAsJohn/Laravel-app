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

    public function filterProducts($allProducts, $request)
    {
        $filters = [
            'categories' => (array) $request->input('categories', []),
            'min_price' => $request->filled('min_price') ? (float) $request->input('min_price') : null,
            'max_price' => $request->filled('max_price') ? (float) $request->input('max_price') : null,
            'in_stock' => $request->boolean('in_stock'),
            'on_sale' => $request->boolean('on_sale'),
            'sort' => $request->input('sort', 'newest'),
        ];

        $priceRange = [
            'min' => (float) $allProducts->map(fn($p) => (float) ($p->discount_price ?? $p->price))->min(),
            'max' => (float) $allProducts->map(fn($p) => (float) ($p->discount_price ?? $p->price))->max(),
        ];

        $products = $allProducts
            // Category Filter (Multiple) - using filter()
            ->when(!empty($filters['categories']), function ($collection) use ($filters) {
                return $collection->filter(fn($p) => in_array($p->category_id, $filters['categories']));
            })
            // Price Filters - using filter()
            ->when($filters['min_price'] !== null, function ($collection) use ($filters) {
                return $collection->filter(fn($p) => (float) ($p->discount_price ?? $p->price) >= $filters['min_price']);
            })
            ->when($filters['max_price'] !== null, function ($collection) use ($filters) {
                return $collection->filter(fn($p) => (float) ($p->discount_price ?? $p->price) <= $filters['max_price']);
            })
            // Stock Filter - using where() + filter()
            ->when($filters['in_stock'], function ($collection) {
                return $collection->where('is_active', true)->filter(fn($p) => $p->quantity > 0);
            })
            // Sale Filter - using filter()
            ->when($filters['on_sale'], function ($collection) {
                return $collection->filter(fn($p) => $p->discount_price > 0);
            });

        // Sorting - using sortBy() and sortByDesc()
        $products = match ($filters['sort']) {
            'price_low_high' => $products->sortBy(fn($p) => (float) ($p->discount_price ?? $p->price)),
            'price_high_low' => $products->sortByDesc(fn($p) => (float) ($p->discount_price ?? $p->price)),
            'popularity' => $products->sortByDesc('sales_count'),
            'newest' => $products->sortByDesc('created_at'),
            default => $products->sortByDesc('created_at'),
        };


        return [
            'products' => $products->values(),
            'filters' => $filters,
            'priceRange' => $priceRange,
        ];
    }


}
