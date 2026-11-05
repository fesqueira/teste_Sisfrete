<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-#####'),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 100),
            'stock' => fake()->numberBetween(0, 100),
            'weight_kg' => fake()->randomFloat(2, 0.1, 10),
            'height_cm' => fake()->randomFloat(2, 1, 100),
            'width_cm' => fake()->randomFloat(2, 1, 100),
            'length_cm' => fake()->randomFloat(2, 1, 100),
            'active' => true,
        ];
    }
}
