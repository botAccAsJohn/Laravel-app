<?php

namespace App\Events\Behavior;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use \App\Models\Review;

class ProductReviewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public  $review;

    public function __construct(Review $review)
    {
        $this->review = $review;
    }
}
