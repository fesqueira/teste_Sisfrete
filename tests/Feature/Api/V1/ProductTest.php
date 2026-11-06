<?php

use App\Models\Category;
use App\Models\Product;

test('lista apenas produtos ativos', function () {
    Product::factory()->count(2)->create();
    Product::factory()->create(['active' => false]);

    $this->getJson(route('api.v1.products.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('filtra produtos pelo slug da categoria', function () {
    $category = Category::factory()->create();
    $productInCategory = Product::factory()->create();
    $productInCategory->categories()->attach($category);
    Product::factory()->create();

    $this->getJson(route('api.v1.products.index', ['category' => $category->slug]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $productInCategory->id);
});

test('filtra produtos apenas pelo preço máximo', function () {
    Product::factory()->create(['price' => 50]);
    Product::factory()->create(['price' => 150]);

    $this->getJson(route('api.v1.products.index', ['max_price' => 100]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.price', '50.00');
});

test('recusa ordenação por coluna fora da lista permitida', function () {
    $this->getJson(route('api.v1.products.index', ['sort' => 'password']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sort');
});

test('produto inativo não pode ser consultado', function () {
    $product = Product::factory()->create(['active' => false]);

    $this->getJson(route('api.v1.products.show', $product))
        ->assertNotFound()
        ->assertJsonPath('message', 'Recurso não encontrado.');
});
