<?php

namespace App\Http\Controllers;

use App\Services\DiscountService;

class ProductController extends Controller
{
    protected $discountService;
    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    public function calculate($price)
    {
        $discount = $this->discountService->calculateDiscount($price);
        return "Discount is: " . $discount;
    }
}
