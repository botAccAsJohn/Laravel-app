<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = Str::slug($this->faker->unique()->words(2, true), '_');

        return [
            'name'         => $name,
            'display_name' => ucwords(str_replace('_', ' ', $name)),
            'description'  => $this->faker->sentence(),
        ];
    }
}
