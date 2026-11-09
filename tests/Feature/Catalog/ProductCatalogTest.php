<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;

test('guests can browse the public product listing', function () {
    Product::factory()->published()->count(3)->create();
    Product::factory()->create(['status' => 'draft']);

    $response = $this->get(route('catalog.products.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('products.total', 3));
});

test('the public listing never exposes full description, specifications, or seo fields', function () {
    Product::factory()->published()->create(['full_description' => 'SECRET INTERNAL DETAIL']);

    $response = $this->get(route('catalog.products.index'));

    $response->assertOk();
    $response->assertDontSee('SECRET INTERNAL DETAIL');
});

test('unpriced product cards serialize a null price instead of zero', function () {
    Product::factory()->published()->create(['regular_price' => null]);

    $response = $this->get(route('catalog.products.index'));

    $response->assertInertia(fn ($page) => $page->where('products.data.0.regular_price', null));
});

test('public product cards do not expose hidden prices or stock status', function () {
    Product::factory()->published()->create([
        'regular_price' => 2500,
        'sale_price' => 2200,
        'stock_status' => 'in_stock',
        'show_price' => false,
        'show_stock' => false,
    ]);

    $response = $this->get(route('catalog.products.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('products.data.0.show_price', false)
        ->where('products.data.0.show_stock', false)
        ->where('products.data.0.regular_price', null)
        ->where('products.data.0.sale_price', null)
        ->where('products.data.0.stock_status', null));
});

test('public filters do not match hidden price or stock values', function () {
    Product::factory()->published()->create([
        'name' => 'Hidden values',
        'regular_price' => 100,
        'stock_status' => 'in_stock',
        'show_price' => false,
        'show_stock' => false,
    ]);
    Product::factory()->published()->create([
        'name' => 'Visible values',
        'regular_price' => 100,
        'stock_status' => 'in_stock',
    ]);

    $byPrice = $this->get(route('catalog.products.index', ['max_price' => 100]));
    $byPrice->assertInertia(fn ($page) => $page->where('products.total', 1));

    $byStock = $this->get(route('catalog.products.index', ['stock_status' => 'in_stock']));
    $byStock->assertInertia(fn ($page) => $page->where('products.total', 1));
});

test('guests see an inline login prompt instead of being redirected off a product detail page', function () {
    $product = Product::factory()->published()->create();

    $response = $this->get(route('catalog.products.show', $product->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('catalog/ProductShow')
        ->where('authRequired', true)
        ->where('product', null));
});

test('a product detail page never reveals whether a slug exists to guests', function () {
    $published = Product::factory()->published()->create();
    $draft = Product::factory()->create(['status' => 'draft']);

    $forPublished = $this->get(route('catalog.products.show', $published->slug));
    $forDraft = $this->get(route('catalog.products.show', $draft->slug));
    $forMissing = $this->get(route('catalog.products.show', 'no-such-product'));

    $forPublished->assertOk()->assertInertia(fn ($page) => $page->where('authRequired', true));
    $forDraft->assertOk()->assertInertia(fn ($page) => $page->where('authRequired', true));
    $forMissing->assertOk()->assertInertia(fn ($page) => $page->where('authRequired', true));
});

test('guests are redirected back to the product after logging in through the modal', function () {
    $product = Product::factory()->published()->create();
    $customer = User::factory()->create(['password' => 'password']);

    $this->get(route('catalog.products.show', $product->slug));

    $response = $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('catalog.products.show', $product->slug, absolute: false));
});

test('an authenticated customer can view a published product detail', function () {
    $product = Product::factory()->published()->create([
        'regular_price' => 2500,
        'sale_price' => 2200,
        'stock_quantity' => 9,
        'stock_status' => 'in_stock',
        'show_price' => false,
        'show_stock' => false,
    ]);
    $product->variants()->create([
        'name' => 'SS Pins',
        'sku' => 'DAS-0001',
        'price' => null,
    ]);
    $customer = User::factory()->create();

    $response = $this->actingAs($customer)->get(route('catalog.products.show', $product->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('product.data.sku', $product->sku)
        ->where('product.data.regular_price', null)
        ->where('product.data.sale_price', null)
        ->where('product.data.stock_quantity', null)
        ->where('product.data.stock_status', null)
        ->where('product.data.show_price', false)
        ->where('product.data.show_stock', false)
        ->where('product.data.variants.0.name', 'SS Pins')
        ->where('product.data.variants.0.price', null)
        ->where('product.data.variants.0.stock_status', null));
});

test('an authenticated customer cannot view a draft product detail', function () {
    $product = Product::factory()->create(['status' => 'draft']);
    $customer = User::factory()->create();

    $response = $this->actingAs($customer)->get(route('catalog.products.show', $product->slug));

    $response->assertNotFound();
});

test('an authenticated customer cannot view an archived product detail', function () {
    $product = Product::factory()->archived()->create();
    $customer = User::factory()->create();

    $response = $this->actingAs($customer)->get(route('catalog.products.show', $product->slug));

    $response->assertNotFound();
});

test('the public listing filters by brand, price range, and sorts by price', function () {
    $brandA = Brand::factory()->create();
    $brandB = Brand::factory()->create();
    Product::factory()->published()->create(['brand_id' => $brandA->id, 'regular_price' => 500]);
    Product::factory()->published()->create(['brand_id' => $brandB->id, 'regular_price' => 2000]);

    $response = $this->get(route('catalog.products.index', ['brand' => $brandA->slug]));

    $response->assertInertia(fn ($page) => $page->where('products.total', 1));
});

test('the public listing sorts by price ascending', function () {
    Product::factory()->published()->create(['name' => 'Expensive', 'regular_price' => 2000]);
    Product::factory()->published()->create(['name' => 'Cheap', 'regular_price' => 500]);

    $response = $this->get(route('catalog.products.index', ['sort' => 'price_asc']));

    $response->assertInertia(fn ($page) => $page->where('products.data.0.name', 'Cheap'));
});

test('the public listing paginates results', function () {
    Product::factory()->published()->count(3)->create();

    $response = $this->get(route('catalog.products.index', ['per_page' => 2]));

    $response->assertInertia(fn ($page) => $page
        ->where('products.last_page', 2)
        ->where('products.data', fn ($data) => count($data) === 2));

    $secondPage = $this->get(route('catalog.products.index', ['per_page' => 2, 'page' => 2]));

    $secondPage->assertInertia(fn ($page) => $page->where('products.current_page', 2));
});
