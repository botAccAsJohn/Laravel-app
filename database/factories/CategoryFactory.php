<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Electronics',
            'Clothing',
            'Books',
            'Home & Garden',
            'Sports',
            'Toys',
            'Automotive',
            'Health & Beauty',
            'Food & Grocery',
            'Furniture',
        ]);

        return [
            'name'        => $name,
        ];
    }
}
