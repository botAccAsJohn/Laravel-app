<?php

namespace App\Events\Behavior;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductViewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int    $productId
     * @param string $recentlyViewedKey
     */
    public function __construct(public int $productId, public string $recentlyViewedKey) {}
}
