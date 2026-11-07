<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sleep::fake();

    $this->user = User::factory()->create();
    $this->address = Address::factory()->for($this->user)->create(['zip_code' => '01310100']);
    $this->product = Product::factory()->create(['stock' => 10]);

    Sanctum::actingAs($this->user);
});

function placeOrder(): TestResponse
{
    return test()->postJson(route('api.v1.orders.store'), [
        'address_id' => test()->address->id,
        'items' => [['product_id' => test()->product->id, 'quantity' => 2]],
    ]);
}

test('envia o CEP de destino e as dimensões dos produtos para a transportadora', function () {
    Http::fake(['*' => Http::response(['service' => 'Padrão', 'price' => 20, 'deadline_days' => 4])]);

    placeOrder()->assertCreated();

    Http::assertSent(fn (Request $request) => $request['destination_zip_code'] === '01310100'
        && $request['origin_zip_code'] === config('services.freight.origin_zip_code')
        && $request['packages'][0]['quantity'] === 2
        && $request->hasHeader('Authorization'));
});

test('tenta de novo quando a transportadora falha e usa a cotação seguinte', function () {
    Http::fake(['*' => Http::sequence()
        ->push(['message' => 'indisponível'], 503)
        ->push(['service' => 'Padrão', 'price' => 20, 'deadline_days' => 4])]);

    placeOrder()->assertCreated()->assertJsonPath('data.shipping_cost', '20.00');

    Http::assertSentCount(2);
});

test('devolve 503 e não cria o pedido quando a transportadora continua fora do ar', function () {
    Http::fake(['*' => Http::response(['message' => 'indisponível'], 503)]);

    placeOrder()
        ->assertServiceUnavailable()
        ->assertJsonPath('message', 'Não foi possível calcular o frete agora. Tente novamente em instantes.');

    Http::assertSentCount(3);
    expect(Order::count())->toBe(0)
        ->and($this->product->fresh()->stock)->toBe(10);
});

test('devolve 503 quando não consegue conectar na transportadora', function () {
    Http::fake(['*' => Http::failedConnection()]);

    placeOrder()->assertServiceUnavailable();

    Http::assertSentCount(3);
});

test('não tenta de novo e devolve 422 quando a transportadora não atende o CEP', function () {
    Http::fake(['*' => Http::response(['message' => 'CEP não atendido'], 422)]);

    placeOrder()->assertUnprocessable()->assertJsonValidationErrors('address_id');

    Http::assertSentCount(1);
});

test('devolve 503 quando a resposta da transportadora vem incompleta', function () {
    Http::fake(['*' => Http::response(['service' => 'Padrão'])]);

    placeOrder()->assertServiceUnavailable();
});
