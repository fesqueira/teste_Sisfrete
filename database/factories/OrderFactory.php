<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'address_id' => fn (array $attributes) => Address::factory(['user_id' => $attributes['user_id']]),
            'status' => 'pending',
            'subtotal' => fake()->randomFloat(2, 10, 1000),
            'shipping_cost' => fake()->randomFloat(2, 5, 50),
            'total' => fn (array $attributes) => $attributes['subtotal'] + $attributes['shipping_cost'],
        ];
    }
}
