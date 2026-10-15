<?php

use App\Models\Product;
use App\Models\User;

test('the public products api returns only published products with summary fields', function () {
    Product::factory()->published()->create(['full_description' => 'SECRET INTERNAL DETAIL']);
    Product::factory()->create(['status' => 'draft']);

    $response = $this->getJson(route('api.v1.products.index'));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonMissingPath('data.0.full_description');
    $response->assertDontSee('SECRET INTERNAL DETAIL');
});

test('a guest receives a 401 from the product detail api', function () {
    $product = Product::factory()->published()->create();

    $this->getJson(route('api.v1.products.show', $product))->assertUnauthorized();
});

test('an authenticated customer receives full product detail from the api', function () {
    $product = Product::factory()->published()->create();
    $customer = User::factory()->create();

    $response = $this->actingAs($customer)->getJson(route('api.v1.products.show', $product));

    $response->assertOk()->assertJsonPath('data.sku', $product->sku);
});

test('an authenticated customer receives a 404 for a draft product via the api', function () {
    $product = Product::factory()->create(['status' => 'draft']);
    $customer = User::factory()->create();

    $this->actingAs($customer)->getJson(route('api.v1.products.show', $product))->assertNotFound();
});

test('the categories, brands, tags, and homepage endpoints are public', function () {
    $this->getJson(route('api.v1.categories.index'))->assertOk();
    $this->getJson(route('api.v1.brands.index'))->assertOk();
    $this->getJson(route('api.v1.tags.index'))->assertOk();
    $this->getJson(route('api.v1.homepage'))->assertOk();
    $this->getJson(route('api.v1.settings.contact-widgets'))->assertOk();
});
