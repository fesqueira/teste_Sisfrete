<?php

use App\Models\User;

test('login devolve token com credenciais válidas', function () {
    $user = User::factory()->create();

    $response = $this->postJson(route('api.v1.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'token', 'token_type'])
        ->assertJsonPath('data.id', $user->id);
});

test('login recusa senha errada com a mesma mensagem de email inexistente', function () {
    $user = User::factory()->create();

    $this->postJson(route('api.v1.login'), ['email' => $user->email, 'password' => 'errada'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Credenciais inválidas.');

    $this->postJson(route('api.v1.login'), ['email' => 'ninguem@example.com', 'password' => 'errada'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Credenciais inválidas.');
});

test('rota protegida exige token', function () {
    $this->getJson(route('api.v1.me'))
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Não autenticado.');
});

test('logout revoga o token usado', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson(route('api.v1.logout'))->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
