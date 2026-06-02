<?php

namespace App\Jobs\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{DB, Log};
use App\Exceptions\ProductOutOfStockException;


class ReserveStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly Order $order)
    {
    }

    public function handle(): void
    {
        // Skip stock reservation for digital/virtual products
        if ($this->order->is_digital ?? false) {
            Log::channel('orders')->info("Chain Step 2 — Skipped (digital order) for Order #{$this->order->order_number}");
            return;
        }

        Log::channel('orders')->info("Chain Step 2 — Reserving stock for Order #{$this->order->order_number}");

        DB::transaction(function () {
            $this->order->loadMissing('items.product');

            foreach ($this->order->items as $item) {
                $product = $item->product;

                if (!$product) {
                    continue;
                }

                // Lock the product row to prevent race conditions
                $product = $product->lockForUpdate()->find($product->id);

                if ($product->quantity < $item->quantity) {
                    throw new ProductOutOfStockException(
                        productName: $product->name,
                        productId: $product->id,
                        requestedQty: $item->quantity,
                        availableQty: $product->quantity,
                    );
                }

                $product->decrement('quantity', $item->quantity);
            }
        });

        Log::channel('orders')->info("Chain Step 2 ✓ — Stock reserved for Order #{$this->order->order_number}");
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('orders')->error("Chain Step 2 ✗ — Stock reservation FAILED for Order #{$this->order->order_number}: {$e->getMessage()}");

        // Mark as needs-review so support can handle the refund manually
        $this->order->update(['status' => 'payment_review']);
    }
}
