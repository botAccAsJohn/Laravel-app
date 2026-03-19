<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 fake products using our factory
        // factory() reads ProductFactory::definition() for each one
        Product::factory(10)->create();

        // You can also create specific products for testing
        Product::factory()->create([
            'name'      => 'Test Laptop',
            'price'     => 999.99,
            'slug'      => 'test-laptop',
            'is_active' => true,
        ]);
    }
}
