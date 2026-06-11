<?php

namespace App\Services;

use App\Exceptions\InvalidPriceException;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Log, Cache, Storage};
use Illuminate\Support\Str;     
use Illuminate\Pagination\{LengthAwarePaginator,Paginator};

class ProductService
{
    public function __construct(private CacheService $cacheService) {}

public function getProducts(\Illuminate\Http\Request $request): array
{
    $query = trim($request->get('q', ''));
    $filters = [
        'categories' => (array) $request->input('categories', []),
        'min_price'  => $request->filled('min_price')
            ? (float) $request->input('min_price')
            : null,
        'max_price'  => $request->filled('max_price')
            ? (float) $request->input('max_price')
            : null,
        'in_stock'   => $request->boolean('in_stock') ?? true,
        'on_sale'    => $request->boolean('on_sale'),
        'sort'       => $request->input('sort', 'newest'),
    ];
    if (
        $filters['min_price'] !== null &&
        $filters['max_price'] !== null &&
        $filters['min_price'] > $filters['max_price']
    ) {
        [$filters['min_price'], $filters['max_price']] = [
            $filters['max_price'],
            $filters['min_price'],
        ];
    }
    sort($filters['categories']);
    ksort($filters);

    $page    = max(1, (int) $request->input('page', 1));
    $perPage = 12;

    $cacheKey = CacheService::CACHE_PRODUCT_SEARCH . md5(json_encode([
        'q'       => $query,
        'filters' => $filters,
        'page'    => $page,
    ]));
    return Cache::tags(CacheService::CACHE_PRODUCT_LIST_TAG)
        ->remember($cacheKey, CacheService::TTL_ALL_PRODUCTS, function () use (
            $query,
            $filters,
            $page,
            $perPage,
            $request
        ) {
            $raw = Product::search($query,
                function ($meili, string $query, array $options) use ($filters, $page, $perPage) {
                    $filterString = $this->buildMeiliFilterString($filters);
                    if (!empty($filterString)) {
                        $options['filter'] = $filterString;
                    }

                    $options['sort']        = $this->buildMeiliSort($filters['sort']);
                    $options['page']        = $page;
                    $options['hitsPerPage'] = $perPage;

                    return $meili->search($query, $options);
                }
            )->raw();

            $hits = $raw['hits'] ?? [];
            $ids = collect($hits)->pluck('id');


            if ($ids->isEmpty()) {
                $products = collect();
            } else { // this block we can replace with the cache list
                $products = Product::with('category')
                    ->whereNull('deleted_at')
                    ->whereIn('id', $ids)
                    ->get()
                    ->sortBy(fn ($product) => $ids->search($product->id))
                    ->values();
            }
            $total = (int) (
                $raw['totalHits']
                ?? $raw['estimatedTotalHits']
                ?? 0
            );
            $paginator = new LengthAwarePaginator(
                $products,
                $total,
                $perPage,
                $page,
                [
                    'path'  => Paginator::resolveCurrentPath(),
                    'query' => $request->except('page'),
                ]
            );

            return [
                'products' => $paginator,
                'filters' => $filters,
                'priceRange' => [
                    'min' => $filters['min_price'] ?? 0,
                    'max' => $filters['max_price'] ?? 0,
                ],
                'total' => $total,
            ];
        });
}

    private function buildMeiliFilterString(array $filters): ?string
{
    $conditions = [];
    if (!empty($filters['categories'])) {
        // Assumes category_id is an integer. Adapt if using parent_id.
        $conditions[] = 'category_id IN [' . implode(',', $filters['categories']) . ']';
    }
    if ($filters['min_price'] !== null) {
        $conditions[] = "price >= {$filters['min_price']}";
    }
    if ($filters['max_price'] !== null) {
        $conditions[] = "price <= {$filters['max_price']}";
    }
    if ($filters['in_stock']) {
        $conditions[] = "quantity > 0";
    }
    if ($filters['on_sale']) {
        $conditions[] = "discount_price > 0";
    }

    return $conditions ? implode(' AND ', $conditions) : null;
}

private function buildMeiliSort(string $sort): array
{
    return match ($sort) {
        'price_low_high'  => ['price:asc'],
        'price_high_low'  => ['price:desc'],
        'oldest'          => ['created_at:asc'],
        default           => ['created_at:desc'], // newest
    };
}

    public function create(array $validated, ?UploadedFile $imageFile = null): Product
    {
        if (isset($validated['price']) && (float) $validated['price'] <= 0) {
            throw new InvalidPriceException(
                (float) $validated['price'],
                'Price must be greater than zero.'
            );
        }

        if ($imageFile) {
            $validated['image_path'] = Storage::disk('public')->putFile('products', $imageFile);
        }

        $validated['tags'] = $this->normalizeTags($validated['tags'] ?? null);

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->generateSlug($validated['name']);
        }

        $product = Product::create($validated);

        // Bust the "all products" list and count; individual cache doesn't exist yet.
        // i think in future we can move to event !!!
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
            $validated['image_path'] = Storage::disk('public')->putFile('products', $imageFile);
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
        if ($updated && isset($validated['quantity']) && (int) $validated['quantity'] !== (int) $oldQuantity) {
            broadcast(new \App\Events\ProductStockChanged($product->id, $product->quantity));
        }

        // Bust the "all products" list (order/data changed).
        $this->forgetListCache();

        // // Clear listing page cache if image changed (Module 28)
        if ($updated && $product->wasChanged('image_path')) {
            Cache::tags('productsPage')->flush();
        }

        // Bust the individual cache for the OLD slug.
        $this->cacheService->forgetProduct($product->slug);

        // If the slug changed, also prime/bust the new slug's individual cache.
        if ($newSlug) {
            $this->cacheService->forgetProduct($newSlug);
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
        $deleted = $product->delete();

        // Bust both the list cache and this product's individual cache entry.
        $this->cacheService->forgetProduct($product->slug);

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
        $this->cacheService->forgetProductList();
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
        if ($name == 'slowquery') {
            $file = fopen(storage_path('logs/db/slow/' . $name . '-' . date('Y-m-d') . '.log'), 'r');
        } else {
            $file = fopen(storage_path('logs/' . $name . '/' . $name . '-' . date('Y-m-d') . '.log'), 'r');
        }
        $logs = [];
        while (!feof($file)) {
            $logs[] = fgets($file);
        }
        fclose($file);
        return $logs;
    }
}
