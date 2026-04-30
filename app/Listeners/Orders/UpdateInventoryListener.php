<?php

namespace App\Listeners\Orders;

use App\Events\Orders\OrderPlaced;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateInventoryListener
{
    use InteractsWithQueue;

    /**
     * Decrement product stock when an order is placed.
     */
    public function handle(object $event): void
    {
        // Load the products if they are not already loaded
        $event->order->load('items.product');

        foreach ($event->order->items as $item) {
            $model = $item->product;

            if ($model) {
                // Deduct inventory in the database
                $model->quantity = $model->quantity - $item->quantity;
                $model->save();

                // Get fresh stock and dispatch the event to notify the frontend
                broadcast(new \App\Events\ProductStockChanged($model->id, $model->quantity));

                // Clear the cache for this product since quantity changed
                \Illuminate\Support\Facades\Cache::forget(\App\Models\Product::CACHE_KEY_SINGLE . $model->slug);
            }
        }
    }
}
