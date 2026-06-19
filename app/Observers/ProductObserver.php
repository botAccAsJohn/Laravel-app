<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\{Cache, Storage, Auth};



class ProductObserver
{
    public function created(Product $product): void
    {
        Cache::forget('products:all');
        Cache::forget('products:count');
        $product->created_by = Auth::id();
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // this line is for the add the review and clear the page cache,so data showup
        Cache::forget('products:single:' . $product->slug);

        // if the stock of any product is low then send the mail to admin
        if ($product->wasChanged('quantity') && $product->quantity <= 10) {
            event(new \App\Events\Inventory\ProductStockLow($product));
        }
        $product->updated_by = Auth::id();
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        Cache::forget('products:single:' . $product->slug);
        Cache::forget('products:all');
        Cache::forget('products:count');

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
