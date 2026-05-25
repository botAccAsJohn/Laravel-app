<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name  = fake()->words(3, true);
        $price = fake()->randomFloat(2, 5, 500);

        // Pick a random existing category (created by CategorySeeder first)
        $categoryId = Category::inRandomOrder()->value('id');

        // 40% chance the product has a discounted price
        $discountPrice = fake()->boolean(40)
            ? fake()->randomFloat(2, 1, $price - 0.01)
            : null;

        return [
            'name'           => ucwords($name),

            'description'    => fake()->paragraph(2),

            'price'          => $price,

            'discount_price' => $discountPrice,

            'tags'           => fake()->randomElements(
                ['sale', 'new-arrival', 'featured', 'trending', 'limited', 'clearance'],
                fake()->numberBetween(0, 3)  // 0–3 tags per product
            ),

            'category_id'    => $categoryId,

            'slug'           => Str::slug($name) . '-' . fake()->unique()->randomNumber(4),
            'image_path'     => null,
            'quantity'       => fake()->numberBetween(0, 50),
            'is_active'      => fake()->boolean(80),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'tags' => ['featured'],
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_price' => fake()->randomFloat(2, 1, $attributes['price'] - 0.01),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
