<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Tag;

test('an admin can create a product through the api', function () {
    $admin = adminUser(['products.create']);

    $response = $this->actingAs($admin)->postJson(route('api.v1.admin.products.store'), [
        'name' => 'API Dental Chair',
        'sku' => 'API-SKU-1',
        'regular_price' => 1200,
        'stock_quantity' => 5,
        'status' => 'draft',
        'stock_status' => 'in_stock',
    ]);

    $response->assertCreated()->assertJsonPath('data.sku', 'API-SKU-1');
});

test('an admin without products.create receives a 403 from the api', function () {
    $admin = adminUser(['products.view']);

    $response = $this->actingAs($admin)->postJson(route('api.v1.admin.products.store'), [
        'name' => 'API Dental Chair',
        'sku' => 'API-SKU-2',
        'regular_price' => 1200,
        'stock_quantity' => 5,
        'status' => 'draft',
        'stock_status' => 'in_stock',
    ]);

    $response->assertForbidden();
});

test('a guest receives a 401 from the admin products api', function () {
    $this->getJson(route('api.v1.admin.products.index'))->assertUnauthorized();
});

test('an admin can manage categories, brands, and tags through the api', function () {
    $admin = adminUser(['categories.manage', 'brands.manage', 'tags.manage']);

    $this->actingAs($admin)->postJson(route('api.v1.admin.categories.store'), ['name' => 'API Category'])
        ->assertCreated();
    $this->actingAs($admin)->postJson(route('api.v1.admin.brands.store'), ['name' => 'API Brand'])
        ->assertCreated();
    $this->actingAs($admin)->postJson(route('api.v1.admin.tags.store'), ['name' => 'API Tag'])
        ->assertCreated();

    expect(Category::query()->where('name', 'API Category')->exists())->toBeTrue()
        ->and(Brand::query()->where('name', 'API Brand')->exists())->toBeTrue()
        ->and(Tag::query()->where('name', 'API Tag')->exists())->toBeTrue();
});

test('an admin cannot delete media belonging to a different product through the api', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();
    $media = ProductMedia::factory()->for($otherProduct)->create();

    $response = $this->actingAs($admin)->deleteJson(route('api.v1.admin.products.media.destroy', [$product, $media]));

    $response->assertNotFound();
});
