<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuário fixo para teste de API
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Catalogo
        $categories = Category::factory()->count(8)->create();
        $products = Product::factory()->count(50)->create();
        $products->each(fn (Product $product) => $product->categories()->attach($categories->random(rand(1, 3))));

        // Clientes
        $customers = User::factory()
            ->count(20)
            ->has(Address::factory()->count(2))
            ->create();

        // Pedidos
        foreach ($customers as $customer) {
            $orders = Order::factory()
                ->count(rand(0, 5))
                ->for($customer)
                ->state(fn () => [
                    'status' => fake()->randomElement(['pending', 'paid', 'shipped', 'delivered', 'cancelled']),
                    'created_at' => fake()->dateTimeBetween('-18 months', 'now'),
                    'address_id' => $customer->addresses->random()->id,
                ])
                ->create();

            foreach ($orders as $order) {
                foreach ($products->random(rand(1, 4)) as $product) {
                    OrderItem::factory()
                        ->for($order)
                        ->for($product)
                        ->create();
                }

                $subtotal = $order->items()->sum(DB::raw('price * quantity'));
                $order->update([
                    'subtotal' => $subtotal,
                    'total' => $subtotal + $order->shipping_cost,
                ]);

                if (! in_array($order->status, ['pending', 'cancelled'])) {
                    Payment::factory()
                        ->for($order)
                        ->create(['paid_at' => $order->created_at]);
                }

            }
        }

    }
}
