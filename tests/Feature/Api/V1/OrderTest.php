<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->for($this->user)->create();

    Sanctum::actingAs($this->user);
});

test('cria pedido usando o preço do banco e baixa o estoque', function () {
    $product = Product::factory()->create(['price' => 40, 'stock' => 10]);

    $response = $this->postJson(route('api.v1.orders.store'), [
        'address_id' => $this->address->id,
        'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 0.01]],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.items.0.unit_price', '40.00')
        ->assertJsonPath('data.total', '80.00');

    expect($product->fresh()->stock)->toBe(8);
});

test('desfaz o pedido inteiro quando um item não tem estoque', function () {
    $available = Product::factory()->create(['stock' => 10]);
    $unavailable = Product::factory()->create(['stock' => 1]);

    $this->postJson(route('api.v1.orders.store'), [
        'address_id' => $this->address->id,
        'items' => [
            ['product_id' => $available->id, 'quantity' => 1],
            ['product_id' => $unavailable->id, 'quantity' => 5],
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors('items');

    expect($available->fresh()->stock)->toBe(10)
        ->and(Order::count())->toBe(0);
});

test('não aceita endereço de outro cliente', function () {
    $otherAddress = Address::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);

    $this->postJson(route('api.v1.orders.store'), [
        'address_id' => $otherAddress->id,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertUnprocessable()->assertJsonValidationErrors('address_id');
});

test('não permite ver o pedido de outro cliente', function () {
    $otherOrder = Order::factory()->create();

    $this->getJson(route('api.v1.orders.show', $otherOrder))->assertForbidden();
});

test('recusa transição de status inválida', function () {
    $order = Order::factory()->for($this->user)->create(['address_id' => $this->address->id]);

    $this->patchJson(route('api.v1.orders.update', $order), ['status' => 'delivered'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

test('cancelar o pedido devolve o estoque', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $orderId = $this->postJson(route('api.v1.orders.store'), [
        'address_id' => $this->address->id,
        'items' => [['product_id' => $product->id, 'quantity' => 3]],
    ])->json('data.id');

    $this->patchJson(route('api.v1.orders.update', $orderId), ['status' => 'cancelled'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($product->fresh()->stock)->toBe(10);
});
