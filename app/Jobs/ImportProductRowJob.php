<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ImportProductRowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected array $row;

    /**
     * Create a new job instance.
     */
    public function __construct(array $row)
    {
        $this->row = $row;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Respect batch cancellation
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        // Basic Validation
        if (empty($this->row['name'])) {
            throw new \InvalidArgumentException("Product name is empty.");
        }

        $slug = trim($this->row['slug'] ?? '');
        if (empty($slug)) {
            $slug = Str::slug($this->row['name']) . '-' . rand(1000, 9999);
        }

        $categoryId = isset($this->row['category_id']) && is_numeric($this->row['category_id'])
            ? (int) $this->row['category_id']
            : null;

        // Parse and normalize tags
        $tags = $this->row['tags'] ?? [];
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $tags = $decoded;
            } else {
                $tags = array_filter(array_map('trim', explode(',', $tags)));
            }
        }

        Product::withoutSyncingToSearch(function () use ($slug, $categoryId, $tags) {
            Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => trim($this->row['name']),
                    'description' => $this->row['description'] ?? null,
                    'price' => (float) ($this->row['price'] ?? 0),
                    'discount_price' => isset($this->row['discount_price']) && $this->row['discount_price'] !== ''
                        ? (float) $this->row['discount_price']
                        : null,
                    'tags' => $tags,
                    'category_id' => $categoryId,
                    'image_path' => $this->row['image_path'] ?? null,
                    'is_active' => isset($this->row['is_active'])
                        ? filter_var($this->row['is_active'], FILTER_VALIDATE_BOOLEAN)
                        : true,
                    'quantity' => isset($this->row['quantity']) ? (int) $this->row['quantity'] : 0,
                ]
            );
        });
    }
}
