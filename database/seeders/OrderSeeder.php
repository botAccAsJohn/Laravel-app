<?php

namespace Database\Seeders;

use App\Models\{Order, OrderItem, User, Product};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OrderSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function () {
            // Get all regular users to assign orders to
            $users = User::all();
            if ($users->isEmpty()) {
                $users = User::factory()->count(10)->create();
            }

            // Fetch existing products to avoid creating new ones recursively
            $products = Product::all();
            if ($products->isEmpty()) {
                $products = Product::factory()->count(10)->create();
            }

            foreach (range(1, 50) as $i) {
                // Pre-calculate items and total amount
                $itemCount = random_int(1, min(5, $products->count()));
                // Ensure unique products by pulling a random slice/collection of products
                $selectedProducts = $products->random($itemCount);
                if ($selectedProducts instanceof \App\Models\Product) {
                    $selectedProducts = collect([$selectedProducts]);
                }
                $totalAmount = 0;
                $itemsData = [];

                foreach ($selectedProducts as $product) {
                    $quantity = random_int(1, 5);
                    $unitPrice = (float) ($product->discount_price ?? $product->price);
                    $totalPrice = $quantity * $unitPrice;
                    $totalAmount += $totalPrice;

                    $itemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ];
                }

                // Insert order directly via DB query builder to bypass Eloquent overhead
                $orderId = DB::table('orders')->insertGetId([
                    'order_number' => 'ORD-' . now()->format('ymdHis') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                    'user_id' => $users->random()->id,
                    'status' => 'pending',
                    'payment_method' => 'card',
                    'address' => fake()->address,
                    'phone' => fake()->phoneNumber,
                    'total_amount' => $totalAmount,
                    'discount_amount' => 0.00,
                    'final_amount' => $totalAmount,
                    'placed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Bulk insert items for this order to reduce DB queries
                foreach ($itemsData as &$item) {
                    $item['order_id'] = $orderId;
                    $item['created_at'] = now();
                    $item['updated_at'] = now();
                }
                DB::table('order_items')->insert($itemsData);
            }
        });
    }
}
