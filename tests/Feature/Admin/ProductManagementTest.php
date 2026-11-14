<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an admin with products.create can create a product with relations and specifications', function () {
    $admin = adminUser(['products.create', 'products.view']);
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $subcategory = Subcategory::factory()->for($category)->create();
    $tag = Tag::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Dental Chair X1',
        'sku' => 'SKU-0001',
        'brand_id' => $brand->id,
        'status' => 'draft',
        'regular_price' => 1000,
        'stock_quantity' => 10,
        'stock_status' => 'in_stock',
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
        'tag_ids' => [$tag->id],
        'specifications' => [
            ['name' => 'Weight', 'value' => '80kg'],
        ],
    ]);

    $response->assertRedirect();
    $product = Product::query()->where('sku', 'SKU-0001')->firstOrFail();
    expect($product->created_by)->toBe($admin->id)
        ->and($product->category_id)->toBe($category->id)
        ->and($product->subcategory_id)->toBe($subcategory->id)
        ->and($product->show_price)->toBeTrue()
        ->and($product->show_stock)->toBeTrue()
        ->and($product->tags->pluck('id'))->toContain($tag->id)
        ->and($product->specifications)->toHaveCount(1);
});

test('an admin can save product price and stock visibility settings', function () {
    $admin = adminUser(['products.create']);
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Hidden Price and Stock Product',
        'sku' => 'SKU-HIDDEN',
        'status' => 'published',
        'regular_price' => 1000,
        'stock_quantity' => 7,
        'stock_status' => 'in_stock',
        'show_price' => '0',
        'show_stock' => '0',
        'category_id' => $category->id,
    ]);

    $response->assertRedirect();
    $product = Product::query()->where('sku', 'SKU-HIDDEN')->firstOrFail();
    expect($product->show_price)->toBeFalse()
        ->and($product->show_stock)->toBeFalse()
        ->and($product->regular_price)->toBe('1000.00')
        ->and($product->stock_quantity)->toBe(7);
});

test('creating a product requires a category', function () {
    $admin = adminUser(['products.create']);

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Dental Chair X2',
        'sku' => 'SKU-NOCAT',
        'regular_price' => 1000,
        'stock_quantity' => 10,
        'status' => 'draft',
        'stock_status' => 'in_stock',
    ]);

    $response->assertSessionHasErrors('category_id');
    expect(Product::query()->where('sku', 'SKU-NOCAT')->exists())->toBeFalse();
});

test('an admin can create a product directly under a category without a price', function () {
    $admin = adminUser(['products.create']);
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Direct Category Product',
        'sku' => 'SKU-DIRECT',
        'status' => 'published',
        'stock_quantity' => 0,
        'stock_status' => 'on_order',
        'category_id' => $category->id,
    ]);

    $response->assertRedirect();
    $product = Product::query()->where('sku', 'SKU-DIRECT')->firstOrFail();
    expect($product->category_id)->toBe($category->id)
        ->and($product->subcategory_id)->toBeNull()
        ->and($product->regular_price)->toBeNull();
});

test('an admin can create a product with images and returns to the product list', function () {
    Storage::fake('public');
    $admin = adminUser(['products.create']);
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Product with images',
        'sku' => 'SKU-WITH-IMAGES',
        'status' => 'draft',
        'stock_quantity' => 0,
        'stock_status' => 'on_order',
        'category_id' => $category->id,
        'images' => [UploadedFile::fake()->image('product.webp', 1200, 1200)],
    ]);

    $response->assertRedirect(route('admin.products.index'));
    $product = Product::query()->where('sku', 'SKU-WITH-IMAGES')->firstOrFail();
    expect($product->media)->toHaveCount(1)
        ->and($product->media->first()->is_main)->toBeTrue();
});

test('creating a product rejects a subcategory that does not belong to the selected category', function () {
    $admin = adminUser(['products.create']);
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $subcategoryOfOtherCategory = Subcategory::factory()->for($otherCategory)->create();

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Dental Chair X3',
        'sku' => 'SKU-MISMATCH',
        'regular_price' => 1000,
        'stock_quantity' => 10,
        'status' => 'draft',
        'stock_status' => 'in_stock',
        'category_id' => $category->id,
        'subcategory_id' => $subcategoryOfOtherCategory->id,
    ]);

    $response->assertSessionHasErrors('subcategory_id');
    expect(Product::query()->where('sku', 'SKU-MISMATCH')->exists())->toBeFalse();
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
        'category_id' => $product->category_id,
        'subcategory_id' => $product->subcategory_id,
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

test('an admin can publish selected products in bulk', function () {
    $admin = adminUser(['products.publish']);
    $products = Product::factory()->count(2)->create(['status' => 'draft', 'published_at' => null]);

    $response = $this->actingAs($admin)->post(route('admin.products.bulk'), [
        'ids' => $products->modelKeys(),
        'action' => 'publish',
    ]);

    $response->assertRedirect(route('admin.products.index'));
    foreach ($products as $product) {
        expect($product->fresh()->status)->toBe('published')
            ->and($product->fresh()->published_at)->not->toBeNull();
    }
});

test('an admin can change selected products price and stock visibility in bulk', function () {
    $admin = adminUser(['products.update']);
    $products = Product::factory()->count(2)->create([
        'regular_price' => 1200,
        'stock_quantity' => 8,
        'show_price' => true,
        'show_stock' => true,
    ]);

    foreach (['hide_price', 'hide_stock'] as $action) {
        $response = $this->actingAs($admin)->post(route('admin.products.bulk'), [
            'ids' => $products->modelKeys(),
            'action' => $action,
        ]);
        $response->assertRedirect(route('admin.products.index'));
    }

    foreach ($products as $product) {
        expect($product->fresh()->show_price)->toBeFalse()
            ->and($product->fresh()->show_stock)->toBeFalse()
            ->and($product->fresh()->regular_price)->toBe('1200.00')
            ->and($product->fresh()->stock_quantity)->toBe(8);
    }

    foreach (['show_price', 'show_stock'] as $action) {
        $this->actingAs($admin)->post(route('admin.products.bulk'), [
            'ids' => $products->modelKeys(),
            'action' => $action,
        ])->assertRedirect(route('admin.products.index'));
    }

    foreach ($products as $product) {
        expect($product->fresh()->show_price)->toBeTrue()
            ->and($product->fresh()->show_stock)->toBeTrue();
    }
});

test('bulk product visibility changes require products.update permission', function () {
    $admin = adminUser(['products.publish']);
    $product = Product::factory()->create(['show_price' => true]);

    $response = $this->actingAs($admin)->post(route('admin.products.bulk'), [
        'ids' => [$product->id],
        'action' => 'hide_price',
    ]);

    $response->assertForbidden();
    expect($product->fresh()->show_price)->toBeTrue();
});

test('bulk product deletion requires delete permission for every selected product', function () {
    $admin = adminUser(['products.view']);
    $products = Product::factory()->count(2)->create();

    $response = $this->actingAs($admin)->post(route('admin.products.bulk'), [
        'ids' => $products->modelKeys(),
        'action' => 'delete',
    ]);

    $response->assertForbidden();
    expect(Product::query()->whereKey($products->modelKeys())->count())->toBe(2);
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

test('an empty image slot does not block creating a product', function () {
    $admin = adminUser(['products.create']);
    $category = Category::factory()->create();

    $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'No images',
        'sku' => 'SKU-NO-IMAGES',
        'status' => 'draft',
        'stock_quantity' => 0,
        'stock_status' => 'on_order',
        'category_id' => $category->id,
        'images' => [null],
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.products.index'));

    expect(Product::query()->where('sku', 'SKU-NO-IMAGES')->firstOrFail()->media)->toBeEmpty();
});
