<?php

namespace App\Events\Behavior;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductAddToCart
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public $userId, public $productId, public $quantity) {}
}
