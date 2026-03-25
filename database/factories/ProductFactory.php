<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        // fake() gives access to Faker — a library of realistic fake data
        $name = fake()->words(3, true);
        // words(3, true) generates 3 random words as a single string
        // e.g. "ergonomic rubber chair"

        return [
            'name'        => ucwords($name),
            // ucwords() capitalises the first letter of every word
            // "ergonomic rubber chair" → "Ergonomic Rubber Chair"

            'description' => fake()->paragraph(2),
            // paragraph(2) generates 2 sentences of lorem ipsum text

            'price'       => fake()->randomFloat(2, 5, 500),
            // randomFloat(decimal_places, min, max)
            // generates a price between 5.00 and 500.00

            'category'    => fake()->randomElement(['Electronics', 'Clothing', 'Books', 'Home', 'Sports']),
            // random product category

            'slug'        => Str::slug($name) . '-' . fake()->unique()->randomNumber(4),
            // Str::slug() converts the name to a URL-friendly string
            // "Ergonomic Rubber Chair" → "ergonomic-rubber-chair"
            // We append a random 4-digit number to guarantee uniqueness

            'is_active'   => fake()->boolean(80),
            // boolean(80) = 80% chance of true, 20% chance of false
            // Most products will be active
        ];
    }
}
