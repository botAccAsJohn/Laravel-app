<?php

namespace App\Listeners\Behavior;

use App\Services\CartService;

class ProductAddToCartListener
{
    public function __construct(private CartService $cartService) {}

    public function handle(object $event): void
    {
        $this->cartService->add($event->cartKey, $event->productId, $event->quantity);
    }
}
