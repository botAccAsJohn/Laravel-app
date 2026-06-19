<?php

namespace App\Services;

class DiscountService
{
    public function calculateDiscount($price)
    {
        if ($price > 1000) {
            return $price * 0.20;
        }
        return $price * 0.10;
    }
}
