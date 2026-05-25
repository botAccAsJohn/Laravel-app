<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

beforeEach(function () {
    // Ensure we have a Category as ProductFactory picks an existing one randomly
    Category::factory()->create(['name' => 'Electronics']);
});

test('withTrashed returns both active and soft-deleted products', function () {
    // Create 2 products
    $product1 = Product::factory()->create(['name' => 'Laptop']);
    $product2 = Product::factory()->create(['name' => 'Phone']);

    // Soft delete the first product
    $product1->delete();

    // Standard query should not retrieve the soft-deleted product
    $activeProducts = Product::all();
    expect($activeProducts)->toHaveCount(1)
        ->and($activeProducts->first()->id)->toBe($product2->id);

    // withTrashed query should retrieve both products
    $allProducts = Product::withTrashed()->get();
    expect($allProducts)->toHaveCount(2)
        ->and($allProducts->pluck('id'))->toContain($product1->id, $product2->id);
});

test('onlyTrashed returns only soft-deleted products', function () {
    // Create 2 products
    $product1 = Product::factory()->create(['name' => 'Laptop']);
    $product2 = Product::factory()->create(['name' => 'Phone']);

    // Soft delete the first product
    $product1->delete();

    // onlyTrashed query should only retrieve the soft-deleted product
    $trashedProducts = Product::onlyTrashed()->get();
    expect($trashedProducts)->toHaveCount(1)
        ->and($trashedProducts->first()->id)->toBe($product1->id);
});

test('restore recovers a soft-deleted product successfully', function () {
    // Create a product
    $product = Product::factory()->create(['name' => 'Laptop']);

    // Soft delete it
    $product->delete();
    expect($product->deleted_at)->not->toBeNull();

    // Restore it
    $product->restore();

    // Check that deleted_at is now null
    expect($product->deleted_at)->toBeNull();

    // Check that it is searchable by standard queries again
    $activeProducts = Product::all();
    expect($activeProducts)->toHaveCount(1)
        ->and($activeProducts->first()->id)->toBe($product->id);
});
