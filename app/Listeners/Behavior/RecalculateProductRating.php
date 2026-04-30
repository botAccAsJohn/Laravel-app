<?php

namespace App\Listeners\Behavior;



class RecalculateProductRating
{
    public function __construct()
    {
    }

    public function handle(object $event): void
    {
        $product = $event->review->product;

        if ($product) {
            $average = $product->reviews()->avg('rating') ?? 0;
            $count = $product->reviews()->count();

            $product->update([
                'average_rating' => $average,
                'review_count' => $count,
            ]);
        }
    }
}
