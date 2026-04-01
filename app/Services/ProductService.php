<?php

namespace App\Services;

use App\Exceptions\InvalidPriceException;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function all()
    {
        return Product::with('category')->latest()->get();
    }

    public function count(): int
    {
        return Product::count();
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
            $validated['image_path'] = $imageFile->store('products', 'public');
        }

        // Convert comma-string tags to array for the JSON cast
        $validated['tags'] = $this->normalizeTags($validated['tags'] ?? null);

        // Auto-generate slug from name if the user left it blank
        if (empty($validated['slug'])) {
            $validated['slug'] = $this->generateSlug($validated['name']);
        }

        $product = Product::create($validated);

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
            // Remove the old image before storing the new one
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);

                Log::channel('products')->debug('Old product image deleted', [
                    'product_id' => $product->id,
                    'old_image' => $product->image_path,
                ]);
            }

            $validated['image_path'] = $imageFile->store('products', 'public');
        }

        // Convert comma-string tags to array for the JSON cast
        $validated['tags'] = $this->normalizeTags($validated['tags'] ?? null);

        // Regenerate slug if it was cleared
        if (isset($validated['slug']) && empty($validated['slug'])) {
            $validated['slug'] = $this->generateSlug($validated['name'] ?? $product->name);
        }

        $updated = $product->update($validated);

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

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $deleted = $product->delete();

        Log::channel('products')->info('Product deleted', [
            'product_id' => $productId,
            'product_name' => $productName,
        ]);

        return $deleted;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Convert a comma-separated tag string (from the form) into a clean array.
     * Returns null when the input is empty so the column stays NULL in the DB.
     */
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
    /**
     * Generate a unique URL-friendly slug from a product name.
     */
    private function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
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
