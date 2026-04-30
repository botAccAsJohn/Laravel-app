<?php

namespace App\Listeners\Behavior;

use App\Events\Behavior\ProductAddToCart;
use App\Services\CartService;

class ProductAddToCartListener
{
    public function __construct(private CartService $cartService)
    {
    }

    public function handle(object $event): void
    {
        $this->cartService->add($event->userId, $event->productId, $event->quantity);
    }
}
