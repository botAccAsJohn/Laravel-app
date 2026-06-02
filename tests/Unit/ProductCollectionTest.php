<?php

use App\Collections\ProductCollection;
use App\Models\Product;

uses(Tests\TestCase::class);

test('product collection filters by in stock', function () {
    $p1 = new Product(['quantity' => 5]);
    $p2 = new Product(['quantity' => 0]);
    $p3 = new Product(['quantity' => -1]);

    $collection = new ProductCollection([$p1, $p2, $p3]);

    $inStock = $collection->inStock();

    expect($inStock)->toHaveCount(1);
    expect((int)$inStock->first()->quantity)->toBe(5);
});

test('product collection filters by price range', function () {
    $p1 = new Product(['price' => 10.00]);
    $p2 = new Product(['price' => 20.00]);
    $p3 = new Product(['price' => 50.00]);

    $collection = new ProductCollection([$p1, $p2, $p3]);

    $filtered = $collection->byPriceRange(15.00, 30.00);

    expect($filtered)->toHaveCount(1);
    expect((float)$filtered->first()->price)->toBe(20.00);
});

test('product collection filters by featured', function () {
    $p1 = new Product(['tags' => ['featured', 'new']]);
    $p2 = new Product(['tags' => ['new']]);
    $p3 = new Product(['tags' => null]);

    $collection = new ProductCollection([$p1, $p2, $p3]);

    $featured = $collection->featured();

    expect($featured)->toHaveCount(1);
});

test('product collection filters by on sale', function () {
    $p1 = new Product(['price' => 100.00, 'discount_price' => 80.00]);
    $p2 = new Product(['price' => 100.00, 'discount_price' => null]);
    $p3 = new Product(['price' => 100.00, 'discount_price' => 120.00]);

    $collection = new ProductCollection([$p1, $p2, $p3]);

    $onSale = $collection->onSale();

    expect($onSale)->toHaveCount(1);
    expect((float)$onSale->first()->discount_price)->toBe(80.00);
});

test('product collection calculates total value', function () {
    $p1 = new Product(['price' => 10.00, 'quantity' => 2]);
    $p2 = new Product(['price' => 20.00, 'quantity' => 3]);

    $collection = new ProductCollection([$p1, $p2]);

    expect((float)$collection->totalValue())->toBe(80.00);
});

test('product collection filters by low stock', function () {
    $p1 = new Product(['quantity' => 5]);
    $p2 = new Product(['quantity' => 15]);

    $collection = new ProductCollection([$p1, $p2]);

    $lowStock = $collection->lowStock(10);

    expect($lowStock)->toHaveCount(1);
    expect($lowStock->first()->quantity)->toBe(5);
});
