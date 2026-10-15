<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;

test('an admin with products.create can create a product with relations and specifications', function () {
    $admin = adminUser(['products.create', 'products.view']);
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Dental Chair X1',
        'sku' => 'SKU-0001',
        'brand_id' => $brand->id,
        'status' => 'draft',
        'regular_price' => 1000,
        'stock_quantity' => 10,
        'stock_status' => 'in_stock',
        'category_ids' => [$category->id],
        'tag_ids' => [$tag->id],
        'specifications' => [
            ['name' => 'Weight', 'value' => '80kg'],
        ],
    ]);

    $response->assertRedirect();
    $product = Product::query()->where('sku', 'SKU-0001')->firstOrFail();
    expect($product->created_by)->toBe($admin->id)
        ->and($product->categories->pluck('id'))->toContain($category->id)
        ->and($product->tags->pluck('id'))->toContain($tag->id)
        ->and($product->specifications)->toHaveCount(1);
});

test('an admin without products.create cannot create a product', function () {
    $admin = adminUser(['products.view']);

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Dental Chair X1',
        'sku' => 'SKU-0002',
        'regular_price' => 1000,
        'stock_quantity' => 10,
        'status' => 'draft',
        'stock_status' => 'in_stock',
    ]);

    $response->assertForbidden();
});

test('creating a product rejects a duplicate sku', function () {
    $admin = adminUser(['products.create']);
    Product::factory()->create(['sku' => 'DUPLICATE']);

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Another Product',
        'sku' => 'DUPLICATE',
        'regular_price' => 1000,
        'stock_quantity' => 1,
        'status' => 'draft',
        'stock_status' => 'in_stock',
    ]);

    $response->assertSessionHasErrors('sku');
});

test('creating a product rejects a sale price that is not lower than the regular price', function () {
    $admin = adminUser(['products.create']);

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Priced Wrong',
        'sku' => 'SKU-PRICE',
        'regular_price' => 1000,
        'sale_price' => 1000,
        'stock_quantity' => 1,
        'status' => 'draft',
        'stock_status' => 'in_stock',
    ]);

    $response->assertSessionHasErrors('sale_price');
});

test('publishing a draft product for the first time stamps published_at', function () {
    $admin = adminUser(['products.create', 'products.update']);
    $product = Product::factory()->create(['status' => 'draft', 'published_at' => null]);

    $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
        'name' => $product->name,
        'sku' => $product->sku,
        'regular_price' => $product->regular_price,
        'stock_quantity' => $product->stock_quantity,
        'stock_status' => $product->stock_status,
        'status' => 'published',
    ]);

    $response->assertRedirect();
    expect($product->fresh()->published_at)->not->toBeNull();
});

test('an admin with products.delete can delete a product', function () {
    $admin = adminUser(['products.delete']);
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

    $response->assertRedirect(route('admin.products.index'));
    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse();
});

test('an admin without products.delete cannot delete a product', function () {
    $admin = adminUser(['products.view']);
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

    $response->assertForbidden();
    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue();
});

test('the admin product listing can search, filter by status, and paginate', function () {
    $admin = adminUser(['products.view']);
    Product::factory()->create(['name' => 'Ultrasonic Scaler', 'status' => 'draft']);
    Product::factory()->published()->count(30)->create();

    $response = $this->actingAs($admin)->get(route('admin.products.index', ['search' => 'Ultrasonic', 'status' => 'draft']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('products.data.0.name', 'Ultrasonic Scaler')
        ->where('products.total', 1));
});
