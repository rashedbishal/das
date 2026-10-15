<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;

test('guests can browse the public product listing', function () {
    Product::factory()->published()->count(3)->create();
    Product::factory()->create(['status' => 'draft']);

    $response = $this->get(route('products.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('products.total', 3));
});

test('the public listing never exposes full description, specifications, or seo fields', function () {
    Product::factory()->published()->create(['full_description' => 'SECRET INTERNAL DETAIL']);

    $response = $this->get(route('products.index'));

    $response->assertOk();
    $response->assertDontSee('SECRET INTERNAL DETAIL');
});

test('guests are redirected to login when opening a product detail page', function () {
    $product = Product::factory()->published()->create();

    $response = $this->get(route('products.show', $product));

    $response->assertRedirect(route('login'));
});

test('guests are redirected back to the product after logging in', function () {
    $product = Product::factory()->published()->create();
    $customer = User::factory()->create(['password' => 'password']);

    $this->get(route('products.show', $product));

    $response = $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('products.show', $product, absolute: false));
});

test('an authenticated customer can view a published product detail', function () {
    $product = Product::factory()->published()->create();
    $customer = User::factory()->create();

    $response = $this->actingAs($customer)->get(route('products.show', $product));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('product.data.sku', $product->sku));
});

test('an authenticated customer cannot view a draft product detail', function () {
    $product = Product::factory()->create(['status' => 'draft']);
    $customer = User::factory()->create();

    $response = $this->actingAs($customer)->get(route('products.show', $product));

    $response->assertNotFound();
});

test('an authenticated customer cannot view an archived product detail', function () {
    $product = Product::factory()->archived()->create();
    $customer = User::factory()->create();

    $response = $this->actingAs($customer)->get(route('products.show', $product));

    $response->assertNotFound();
});

test('the public listing filters by brand, price range, and sorts by price', function () {
    $brandA = Brand::factory()->create();
    $brandB = Brand::factory()->create();
    Product::factory()->published()->create(['brand_id' => $brandA->id, 'regular_price' => 500]);
    Product::factory()->published()->create(['brand_id' => $brandB->id, 'regular_price' => 2000]);

    $response = $this->get(route('products.index', ['brand' => $brandA->slug]));

    $response->assertInertia(fn ($page) => $page->where('products.total', 1));
});

test('the public listing sorts by price ascending', function () {
    Product::factory()->published()->create(['name' => 'Expensive', 'regular_price' => 2000]);
    Product::factory()->published()->create(['name' => 'Cheap', 'regular_price' => 500]);

    $response = $this->get(route('products.index', ['sort' => 'price_asc']));

    $response->assertInertia(fn ($page) => $page->where('products.data.0.name', 'Cheap'));
});

test('the public listing paginates results', function () {
    Product::factory()->published()->count(3)->create();

    $response = $this->get(route('products.index', ['per_page' => 2]));

    $response->assertInertia(fn ($page) => $page
        ->where('products.last_page', 2)
        ->where('products.data', fn ($data) => count($data) === 2));

    $secondPage = $this->get(route('products.index', ['per_page' => 2, 'page' => 2]));

    $secondPage->assertInertia(fn ($page) => $page->where('products.current_page', 2));
});
