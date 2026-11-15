<?php

use App\Models\Brand;
use App\Models\Category;
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

test('a guest receives product detail from the api without prices', function () {
    $product = Product::factory()->published()->create(['regular_price' => 1500, 'show_price' => true]);

    $this->getJson(route('api.v1.products.show', $product))
        ->assertOk()
        ->assertJsonPath('data.sku', $product->sku)
        ->assertJsonPath('data.regular_price', null)
        ->assertJsonPath('data.show_price', false)
        ->assertJsonPath('data.price_login_required', true);
});

test('a guest receives a 404 for a draft product via the api', function () {
    $product = Product::factory()->create(['status' => 'draft']);

    $this->getJson(route('api.v1.products.show', $product))->assertNotFound();
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

test('the categories and brands endpoints include published product counts', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $brand = Brand::factory()->create(['is_active' => true]);
    Product::factory()->published()->for($category)->for($brand)->count(2)->create();
    Product::factory()->for($category)->for($brand)->create(['status' => 'draft']);

    $categories = $this->getJson(route('api.v1.categories.index'))->assertOk()->collect('data');
    $brands = $this->getJson(route('api.v1.brands.index'))->assertOk()->collect('data');

    expect($categories->firstWhere('slug', $category->slug))
        ->toMatchArray(['products_count' => 2])
        ->not->toHaveKey('deleted_at')
        ->and($brands->firstWhere('slug', $brand->slug))
        ->toMatchArray(['products_count' => 2]);
});

test('product detail only recommends published related products', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->published()->create();
    $publishedRelated = Product::factory()->published()->create();
    $draftRelated = Product::factory()->create(['status' => 'draft']);
    $product->relatedProducts()->sync([$publishedRelated->id, $draftRelated->id]);

    $response = $this->actingAs($customer)->getJson(route('api.v1.products.show', $product));

    $response->assertOk()
        ->assertJsonCount(1, 'data.related_products')
        ->assertJsonPath('data.related_products.0.slug', $publishedRelated->slug);
});

test('product search treats percent and underscore literally', function () {
    Product::factory()->published()->create(['name' => '100% Cotton Rolls']);
    Product::factory()->published()->create(['name' => 'Cotton Pellets']);

    $this->getJson(route('api.v1.products.index', ['search' => '100%']))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson(route('api.v1.products.index', ['search' => '%']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
