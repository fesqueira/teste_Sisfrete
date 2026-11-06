<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function create(User $user, int $addressId, array $items): Order
    {
        return DB::transaction(function () use ($user, $addressId, $items) {
            $products = Product::query()
                ->whereIn('id', array_column($items, 'product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $order = $user->orders()->create([
                'address_id' => $addressId,
                'status' => OrderStatus::Pending,
                'subtotal' => 0,
                'shipping_cost' => 0,
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
}
