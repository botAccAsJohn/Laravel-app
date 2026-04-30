<?php

namespace App\Listeners\Behavior;

use App\Events\Behavior\ProductViewed;
use Illuminate\Support\Facades\Log;
use App\Services\RecentlyViewServices;


class ProductViewedListener
{
    public function __construct(private RecentlyViewServices $recentlyViewServices)
    {
    }

    public function handle(object $event): void
    {
        Log::channel('products')->debug('Product view inside to it ', [
            'user_id' => $event->userId,
            'product_id' => $event->productId,
        ]);

        $this->recentlyViewServices->record($event->userId, $event->productId);
    }
}
