<?php

namespace App\Events\Behavior;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class CartAbandoned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $cart,
        public $user,
        public float $cartTotal,
        public int $itemCount
    ) {}
}
