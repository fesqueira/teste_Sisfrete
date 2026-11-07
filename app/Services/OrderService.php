<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Freight\FreightQuoter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private FreightQuoter $freightQuoter) {}

    public function create(User $user, int $addressId, array $items): Order
    {
        $address = $user->addresses()->findOrFail($addressId);

        $products = Product::query()
            ->whereIn('id', array_column($items, 'product_id'))
            ->get()
            ->keyBy('id');

        $freightQuote = $this->freightQuoter->quote($address->zip_code, $this->packages($items, $products));

        return DB::transaction(function () use ($user, $address, $items, $freightQuote) {
            $products = Product::query()
                ->whereIn('id', array_column($items, 'product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $order = $user->orders()->create([
                'address_id' => $address->id,
                'status' => OrderStatus::Pending,
                'subtotal' => 0,
                'shipping_cost' => $freightQuote->price,
                'total' => 0,
            ]);

            $subtotal = 0;

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Estoque insuficiente para o produto {$product->sku}.",
                    ]);
                }

                $product->decrement('stock', $item['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                $subtotal += $product->price * $item['quantity'];
            }

            $order->update([
                'subtotal' => round($subtotal, 2),
                'total' => round($subtotal + $order->shipping_cost, 2),
            ]);

            Log::info('Pedido criado', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'total' => $order->total,
                'shipping_cost' => $order->shipping_cost,
            ]);

            return $order;
        });
    }

    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $previousStatus = $order->status;

            $order->update($data);

            if ($order->status === OrderStatus::Cancelled && $previousStatus !== OrderStatus::Cancelled) {
                foreach ($order->items as $item) {
                    $item->product()->increment('stock', $item->quantity);
                }
            }

            Log::info('Pedido atualizado', [
                'order_id' => $order->id,
                'from' => $previousStatus->value,
                'to' => $order->status->value,
            ]);

            return $order;
        });
    }

    private function packages(array $items, Collection $products): array
    {
        return array_map(function (array $item) use ($products) {
            $product = $products->get($item['product_id']);

            return [
                'weight_kg' => (float) $product->weight_kg,
                'height_cm' => (float) $product->height_cm,
                'width_cm' => (float) $product->width_cm,
                'length_cm' => (float) $product->length_cm,
                'quantity' => $item['quantity'],
            ];
        }, $items);
    }
}
