<?php

namespace App\Events\Behavior;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductAddToCart
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param string $cartKey   Resolved cart key (cart:user:{id} or cart:guest:{uuid})
     * @param int    $productId
     * @param int    $quantity
     */
    public function __construct(
        public string $cartKey,
        public int    $productId,
        public int    $quantity,
    ) {}
}
