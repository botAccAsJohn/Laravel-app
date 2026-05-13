<?php

namespace App\Listeners\Inventory;

use App\Events\Inventory\ProductStockLow;
use App\Models\User;
use App\Notifications\ProductLowStock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class ProductStockLowListener implements ShouldQueue
{
    public $queue = 'emails';
    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function handle(object $event): void
    {
        $product = $event->product;

        $key = "stock_low_alert_{$product->id}";

        // Only allow once per hour
        if (Cache::has($key)) {
            return; // already sent within last hour
        }

        Cache::put($key, true, now()->addHour());

        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new ProductLowStock($product));
    }
}
