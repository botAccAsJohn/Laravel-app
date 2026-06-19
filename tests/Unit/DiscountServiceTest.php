<?php

use App\Services\DiscountService;

test('calculates correct discount based on threshold', function () {
    $service = new DiscountService();

    // > 1000 threshold: should be 20%
    expect($service->calculateDiscount(1200))->toBe(240.0);

    // <= 1000 threshold: should be 10%
    expect($service->calculateDiscount(1000))->toBe(100.0);
    expect($service->calculateDiscount(500))->toBe(50.0);
});
