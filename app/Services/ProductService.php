<?php

namespace App\Services;

use App\Exceptions\InvalidPriceException;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Log, Cache, Storage};
use Illuminate\Support\Str;

class ProductService
{
 

    // public function dosomething($request)
    // {
    //     $filters = [
    //         'categories' => (array) $request->input('categories', []),
    //         'min_price' => $request->filled('min_price') ? (float) $request->input('min_price') : null,
    //         'max_price' => $request->filled('max_price') ? (float) $request->input('max_price') : null,
    //         'in_stock' => $request->boolean('in_stock'),
    //         'on_sale' => $request->boolean('on_sale'),
    //         'sort' => $request->input('sort', 'newest'),
    //     ];

    //     if ($filters['min_price'] !== null && $filters['max_price'] !== null && $filters['min_price'] > $filters['max_price']) {
    //         $temp = $filters['min_price'];
    //         $filters['min_price'] = $filters['max_price'];
    //         $filters['max_price'] = $temp;
    //     }
    //     ksort($filters);
    //     $page = (int) $request->get('page', 1);
    //     $payload = [
    //         'filters'  => $filters,
    //         'page'     => $page
    //     ];
    //     $key = md5(json_encode($payload));
    //     $cacheKey = 'product:' . $key;


    //     return Cache::tags('productsPage')->remember($cacheKey, 60 * 60 * 24, function () use ($request, $page) {
    //         $results = $this->filterProducts(Product::getAllProductsFromCache(), $request);

    //         $perPage = 12;
    //         $items = $results['products'];

    //         $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
    //             $items->forPage($page, $perPage)->values(),
    //             $items->count(),
    //             $perPage,
    //             $page,
    //             ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
    //         );

    //         return [
    //             'products'   => $paginated,
    //             'filters'    => $results['filters'],
    //             'priceRange' => $results['priceRange']
    //         ];
    //     });
    // }

    public function dosomething($request)
    {
        $filters = [
            'categories' => (array) $request->input('categories', []),
            'min_price'  => $request->filled('min_price') ? (float) $request->input('min_price') : null,
            'max_price'  => $request->filled('max_price') ? (float) $request->input('max_price') : null,
            'in_stock'   => $request->boolean('in_stock'),
            'on_sale'    => $request->boolean('on_sale'),
            'sort'       => $request->input('sort', 'newest'),
        ];

        // Swap price bounds if inverted
        if ($filters['min_price'] !== null && $filters['max_price'] !== null && $filters['min_price'] > $filters['max_price']) {
            [$filters['min_price'], $filters['max_price']] = [$filters['max_price'], $filters['min_price']];
        }

        ksort($filters);

        // Cursor pagination uses a cursor string instead of a page number
        $cursor = $request->query('cursor', '');

        $key      = md5(json_encode(['filters' => $filters, 'cursor' => $cursor]));
        $cacheKey = 'product:' . $key;

        return Cache::tags('productsPage')->remember($cacheKey, 60 * 60 * 24, function () use ($filters, $cursor) {

            // ── 1. Build the query entirely at the DB layer ────────────────────
            // buildProductQuery() already calls select() — never SELECT *.
            $query = $this->buildProductQuery($filters);

            // Deterministic sorting — always add 'id' as a tiebreaker so cursor
            // pagination doesn't break on rows with equal primary sort values.
            match ($filters['sort']) {
                'price_low_high' => $query->orderBy('products.price', 'asc')->orderBy('products.id', 'asc'),
                'price_high_low' => $query->orderBy('products.price', 'desc')->orderBy('products.id', 'desc'),
                'oldest'         => $query->orderBy('products.created_at', 'asc')->orderBy('products.id', 'asc'),
                default          => $query->orderBy('products.created_at', 'desc')->orderBy('products.id', 'desc'),
            };

            // ── 2. Columns the Eloquent layer will hydrate ─────────────────────
            // Mirrors the select() list in buildProductQuery() so that Eloquent
            // also avoids loading columns the view never touches (e.g. created_by,
            // updated_by, full-text index columns, etc.).
            // 'id' must always be first so cursorPaginate can encode the cursor.
            $listingColumns = [
                'id',
                'slug',
                'name',
                'description',
                'image_path',
                'price',
                'discount_price',  // always included — card renders the sale badge
                'is_active',
                'quantity',
                'average_rating',
                'review_count',
                'tags',
                'category_id',     // needed by the eager-loaded category relation
                'deleted_at',      // required for SoftDeletes trait to function
                'created_at',      // needed for cursor encoding on date sorts
            ];

            // Retrieve the qualifying product IDs from the Query Builder result,
            // then hydrate full Eloquent models (with relationship) for the view.
            $ids = $query->pluck('products.id');

            $eloquentQuery = Product::with('category')
                ->select($listingColumns)          // ← explicit column list, never SELECT *
                ->whereIn('id', $ids)
                ->whereNull('deleted_at');

            // ── 3. Conditional addSelect() — discount_price on sale pages ──────
            // When the "on_sale" filter is active the discount_price is *already*
            // in $listingColumns above (we always want it for the sale badge).
            // This block demonstrates addSelect() for cases where a column should
            // only be fetched on certain filter combinations — e.g. a heavy
            // computed column that is only rendered on dedicated sale pages.
            // Uncomment and adapt for columns not in the base list:
            //
            // if ($filters['on_sale']) {
            //     $eloquentQuery->addSelect('some_heavy_sale_column');
            // }

            // Re-apply the same deterministic sort on the Eloquent query
            match ($filters['sort']) {
                'price_low_high' => $eloquentQuery->orderBy('price', 'asc')->orderBy('id', 'asc'),
                'price_high_low' => $eloquentQuery->orderBy('price', 'desc')->orderBy('id', 'desc'),
                'oldest'         => $eloquentQuery->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
                default          => $eloquentQuery->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
            };

            // ── 4. SQL comparison log (non-production only) ────────────────────
            $this->toSqlDebug($query, $eloquentQuery);

            // Pass the explicit column list to cursorPaginate so it selects the
            // same columns even if called without a prior ->select() on the model.
            $paginated = $eloquentQuery->cursorPaginate(12, $listingColumns, 'cursor', $cursor ?: null);

            // Derive the displayed price-range from the active filter values
            $priceRange = [
                'min' => $filters['min_price'] ?? 0,
                'max' => $filters['max_price'] ?? 0,
            ];

            return [
                'products'   => $paginated,
                'filters'    => $filters,
                'priceRange' => $priceRange,
            ];
        });
    }

    /**
     * Build a DB::table('products') Query Builder instance with all filter
     * constraints applied at the database level.
     *
     * Column strategy
     * ───────────────
     * select() is called immediately with only the columns the product-listing
     * view actually renders. Omitted columns never travel over the wire:
     *  - created_by, updated_by   (audit columns, not displayed)
     *  - review bodies, full-text (heavy text, shown only on the detail page)
     *  - updated_at               (not rendered in the listing card)
     *
     * addSelect() is used to *conditionally* append discount_price only when
     * the on_sale flag is true. When browsing without a sale filter the column
     * is still needed for the sale badge, so we include it via addSelect() to
     * make the conditional pattern explicit and easy to extend.
     *
     * DB constraints applied
     * ──────────────────────
     *  • whereNull('deleted_at')         — exclude soft-deleted rows
     *  • where('is_active', true)        — only published products
     *  • whereIn('category_id', [...])   — multi-category filter
     *  • whereBetween('price', [...])    — price-range filter (both bounds)
     *  • where('price', '>=', ...)       — lower-bound-only fallback
     *  • where('price', '<=', ...)       — upper-bound-only fallback
     *  • where('quantity', '>', 0)       — in-stock filter
     *  • where('discount_price', '>', 0) — on-sale filter
     *
     * @param  array $filters
     * @return \Illuminate\Database\Query\Builder
     */
    private function buildProductQuery(array $filters): \Illuminate\Database\Query\Builder
    {
        // ── select(): only the columns the listing card actually uses ──────────
        // Never use SELECT * on a hot page — it wastes I/O, network, and memory
        // for columns (created_by, updated_by, full-text fields…) that no view
        // template references.
        $query = DB::table('products')
            ->select([
                'id',
                'slug',
                'name',
                'description',
                'image_path',
                'price',
                'is_active',
                'quantity',
                'average_rating',
                'review_count',
                'tags',
                'category_id',
                'deleted_at',
                'created_at',
            ])
            // ── addSelect(): conditionally include discount_price ──────────────
            // discount_price is required for the sale badge (@if $product->discount_price)
            // on every card. We use addSelect() here to show the pattern clearly:
            // append an extra column on top of the base select() without rewriting
            // the whole list. For genuinely heavy columns (e.g. a precomputed
            // sale_rank score) you'd guard this with the filter flag instead.
            ->addSelect('discount_price')
            // ── whereNull(): exclude soft-deleted rows ────────────────────────
            // The Product model uses SoftDeletes; DB::table() bypasses the global
            // scope so we must add this constraint explicitly.
            ->whereNull('deleted_at')
            // Only published / active products
            ->where('is_active', true)

            // ── Category filter ────────────────────────────────────────────────────
            // when() replaces: if (!empty($filters['categories'])) { … }
            // whereIn() emits: WHERE category_id IN (1, 3, 7, …)
            ->when(!empty($filters['categories']),
                fn ($q) => $q->whereIn('category_id', $filters['categories'])
            )

            // ── Price-range filter ────────────────────────────────────────────────
            // when() with nested when() covers all three price-bound combinations:
            //   both bounds → whereBetween  (WHERE price BETWEEN ? AND ?)
            //   min only    → where >=
            //   max only    → where <=
            ->when(
                $filters['min_price'] !== null && $filters['max_price'] !== null,
                fn ($q) => $q->whereBetween('price', [$filters['min_price'], $filters['max_price']])
            )
            ->when(
                $filters['min_price'] !== null && $filters['max_price'] === null,
                fn ($q) => $q->where('price', '>=', $filters['min_price'])
            )
            ->when(
                $filters['max_price'] !== null && $filters['min_price'] === null,
                fn ($q) => $q->where('price', '<=', $filters['max_price'])
            )

            // ── Stock filter ──────────────────────────────────────────────────────
            // when() replaces: if ($filters['in_stock']) { … }
            // Emits: WHERE quantity > 0
            ->when($filters['in_stock'],
                fn ($q) => $q->where('quantity', '>', 0)
            )

            // ── On-sale filter via whereIn() with a subquery ───────────────────
            // when() replaces: if ($filters['on_sale']) { … }
            //
            // Two approaches are shown; the active one uses a direct where().
            // The whereIn()-with-subquery form is the pattern requested — it
            // scopes to products whose ID appears in a SELECT on the same table,
            // no second DB round-trip required.
            //
            // Subquery form (recommended when the on-sale set is large or shared):
            //   ->when($filters['on_sale'], fn ($q) => $q->whereIn('id',
            //       DB::table('products')
            //           ->select('id')
            //           ->where('discount_price', '>', 0)
            //           ->whereNull('deleted_at')
            //   ))
            //
            // Simple form (equivalent for this single-table case):
            ->when($filters['on_sale'],
                fn ($q) => $q->where('discount_price', '>', 0)
            );

        return $query;
    }

    /**
     * Log a side-by-side SQL comparison between the raw Query Builder query
     * and the Eloquent query so you can verify they produce equivalent SQL.
     *
     * Outputs two log entries (channel: 'db') in non-production environments:
     *
     *   [QB]  SELECT id, slug … FROM `products` WHERE …
     *         bindings: [1, 50, …]
     *
     *   [ELQ] select `id`, `slug` … from `products` where `id` in (…)
     *         bindings: [3, 7, 12, …]
     *
     * Usage:
     *   Pass the DB::table Builder and the Eloquent Builder before executing
     *   either query. The method is a no-op in production.
     *
     * toSql() returns the SQL string with '?' placeholders.
     * getBindings() returns the ordered array of values that replace them.
     * Combine both to reconstruct the full executable SQL for debugging.
     *
     * @param  \Illuminate\Database\Query\Builder                      $qbQuery
     * @param  \Illuminate\Database\Eloquent\Builder                   $eloquentQuery
     */
    private function toSqlDebug(
        \Illuminate\Database\Query\Builder    $qbQuery,
        \Illuminate\Database\Eloquent\Builder $eloquentQuery
    ): void {
        // Only run outside production — zero cost on live servers.
        if (app()->isProduction()) {
            return;
        }

        // ── Query Builder SQL ─────────────────────────────────────────────────
        // toSql()       → raw SQL string with '?' for every binding
        // getBindings() → ordered array of values substituted for '?'
        $qbSql      = $qbQuery->toSql();
        $qbBindings = $qbQuery->getBindings();

        // ── Eloquent Builder SQL ──────────────────────────────────────────────
        // Eloquent\Builder proxies toSql() / getBindings() to the underlying
        // Query\Builder, so the API is identical.
        $elqSql      = $eloquentQuery->toSql();
        $elqBindings = $eloquentQuery->getBindings();

        Log::channel('db')->debug('[QB]  product listing query', [
            'sql'      => $qbSql,
            'bindings' => $qbBindings,
            // Convenience: inline bindings so you can paste directly into a DB client
            'resolved' => vsprintf(str_replace('?', '%s', $qbSql), $qbBindings),
        ]);

        Log::channel('db')->debug('[ELQ] product listing query', [
            'sql'      => $elqSql,
            'bindings' => $elqBindings,
            'resolved' => vsprintf(str_replace('?', '%s', $elqSql), $elqBindings),
        ]);
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
        Cache::tags('productsPage')->flush();
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
        if($name === 'SlowQuery') {
            $file = fopen(storage_path('logs/db/slow/' . $name . '-' . date('Y-m-d') . '.log'), 'r');
        }else{
            $file = fopen(storage_path('logs/' . $name . '/' . $name . '-' . date('Y-m-d') . '.log'), 'r');
        }
        $logs = [];
        while (!feof($file)) {
            $logs[] = fgets($file);
        }
        fclose($file);
        return $logs;
    }

    /**
     * @deprecated Use buildProductQuery() + dosomething() instead.
     * Kept only as a reference; no longer called from the product listing page.
     *
     * Previously loaded ALL rows into PHP memory via Product::getAllProductsFromCache()
     * and filtered them with Collection methods. Replaced by DB::table() Query Builder
     * in buildProductQuery() which pushes all filtering to the database.
     */
    // public function filterProducts($allProducts, $request) { … }
}
