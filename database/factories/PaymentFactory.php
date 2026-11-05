<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => fake()->randomElement(['credit_card', 'boleto', 'pix']),
            'status' => 'approved',
            'amount' => fn (array $attributes) => Order::find($attributes['order_id'])->total,
            'transaction_id' => fake()->unique()->uuid(),
            'paid_at' => now(),
        ];
    }
}
