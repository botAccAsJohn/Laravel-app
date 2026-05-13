<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'payment_method' => 'card',
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'total_amount' => 100.00,
            'discount_amount' => 0.00,
            'final_amount' => 100.00,
            'placed_at' => now(),
        ];
    }
}
