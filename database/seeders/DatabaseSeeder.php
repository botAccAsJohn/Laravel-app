<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ─────────────────────────────────────────────────────────────

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'     => 'Regular User',
                'password' => Hash::make('password'),
                'role'     => 'user',
            ]
        );

        // ── Categories (must run before Products — FK dependency) ─────────────

        $this->call(CategorySeeder::class);

        // ── Products ──────────────────────────────────────────────────────────

        Product::factory(10)->create();

        // Specific product for e2e testing
        Product::factory()->create([
            'name'           => 'Test Laptop',
            'price'          => 999.99,
            'discount_price' => 849.99,
            'tags'           => ['electronics', 'featured', 'sale'],
            'slug'           => 'test-laptop',
            'is_active'      => true,
            'category_id'    => Category::where('name', 'Electronics')->value('id'),
        ]);
    }
}
