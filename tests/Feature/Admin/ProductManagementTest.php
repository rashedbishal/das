<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Support\FieldLimits;
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
    $admin = adminUser(['products.create', 'products.publish']);
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
    $admin = adminUser(['products.create', 'products.publish']);
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
    $admin = adminUser(['products.create', 'products.update', 'products.publish']);
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

test('product text fields accept values up to their validation limits', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    $canonicalUrl = 'https://example.com/'.str_repeat('a', FieldLimits::get('product.canonical_url') - strlen('https://example.com/'));
    $shortDescriptionLength = FieldLimits::get('product.short_description');
    $seoDescriptionLength = FieldLimits::get('product.seo_description');

    $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
        'name' => $product->name,
        'sku' => $product->sku,
        'stock_quantity' => $product->stock_quantity,
        'stock_status' => $product->stock_status,
        'status' => $product->status,
        'category_id' => $product->category_id,
        'short_description' => str_repeat('s', $shortDescriptionLength),
        'seo_description' => str_repeat('d', $seoDescriptionLength),
        'canonical_url' => $canonicalUrl,
    ]);

    $response->assertSessionHasNoErrors();
    expect($product->fresh())
        ->short_description->toHaveLength($shortDescriptionLength)
        ->seo_description->toHaveLength($seoDescriptionLength)
        ->canonical_url->toBe($canonicalUrl);
});

test('an admin without products.publish cannot change a product status through the edit form', function (string $from, string $to) {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create(['status' => $from]);

    $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
        'name' => $product->name,
        'sku' => $product->sku,
        'stock_quantity' => $product->stock_quantity,
        'stock_status' => $product->stock_status,
        'status' => $to,
        'category_id' => $product->category_id,
    ]);

    $response->assertSessionHasErrors(['status' => 'You do not have permission to change a product\'s publish status.']);
    expect($product->fresh()->status)->toBe($from);
})->with([
    'publishing a draft' => ['draft', 'published'],
    'archiving a published product' => ['published', 'archived'],
    'unpublishing a published product' => ['published', 'draft'],
]);

test('an admin without products.publish can edit a published product without changing its status', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create(['status' => 'published', 'name' => 'Old Name']);

    $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
        'name' => 'New Name',
        'sku' => $product->sku,
        'stock_quantity' => $product->stock_quantity,
        'stock_status' => $product->stock_status,
        'status' => 'published',
        'category_id' => $product->category_id,
    ]);

    $response->assertSessionHasNoErrors();
    expect($product->fresh())
        ->name->toBe('New Name')
        ->status->toBe('published');
});

test('an admin without products.publish can only create draft products', function () {
    $admin = adminUser(['products.create']);
    $category = Category::factory()->create();
    $payload = [
        'name' => 'Editor Product',
        'stock_quantity' => 1,
        'stock_status' => 'in_stock',
        'category_id' => $category->id,
    ];

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [...$payload, 'sku' => 'SKU-EDITOR-PUB', 'status' => 'published'])
        ->assertSessionHasErrors('status');

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [...$payload, 'sku' => 'SKU-EDITOR-DRAFT', 'status' => 'draft'])
        ->assertSessionHasNoErrors();

    expect(Product::query()->where('sku', 'SKU-EDITOR-PUB')->exists())->toBeFalse()
        ->and(Product::query()->where('sku', 'SKU-EDITOR-DRAFT')->value('status'))->toBe('draft');
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

test('a deleted product can be restored from the trash', function () {
    $admin = adminUser(['products.view', 'products.delete']);
    $product = Product::factory()->create();
    $product->delete();

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['trashed' => 1]))
        ->assertInertia(fn ($page) => $page->where('products.data.0.id', $product->id));

    $this->actingAs($admin)
        ->post(route('admin.products.restore', $product))
        ->assertRedirect(route('admin.products.index', ['trashed' => 1]));

    expect($product->fresh()->trashed())->toBeFalse();
});

test('permanently deleting a trashed product removes its files and frees its sku', function () {
    Storage::fake('public');
    $admin = adminUser(['products.delete', 'products.create']);
    $product = Product::factory()->create(['sku' => 'SKU-REUSE']);
    Storage::disk('public')->put("products/{$product->id}/photo.webp", 'image');
    $product->delete();

    $this->actingAs($admin)->delete(route('admin.products.force-destroy', $product))->assertRedirect();

    expect(Product::withTrashed()->whereKey($product->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing("products/{$product->id}/photo.webp");

    $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Reused SKU',
        'sku' => 'SKU-REUSE',
        'status' => 'draft',
        'stock_quantity' => 1,
        'stock_status' => 'in_stock',
        'category_id' => Category::factory()->create()->id,
    ])->assertSessionHasNoErrors();
});

test('a product that is not in the trash cannot be permanently deleted', function () {
    $admin = adminUser(['products.delete']);
    $product = Product::factory()->create();

    $this->actingAs($admin)->delete(route('admin.products.force-destroy', $product))->assertNotFound();

    expect($product->fresh())->not->toBeNull();
});

test('restoring or permanently deleting requires products.delete', function () {
    $admin = adminUser(['products.view']);
    $product = Product::factory()->create();
    $product->delete();

    $this->actingAs($admin)->post(route('admin.products.restore', $product))->assertForbidden();
    $this->actingAs($admin)->delete(route('admin.products.force-destroy', $product))->assertForbidden();

    expect(Product::withTrashed()->find($product->id)->trashed())->toBeTrue();
});

test('a product saved with required fields missing gets a readable message for each field', function () {
    $this->actingAs(adminUser(['products.create']))
        ->post(route('admin.products.store'), [])
        ->assertSessionHasErrors([
            'name' => 'The name field is required.',
            'sku' => 'The SKU field is required.',
            'category_id' => 'The category field is required.',
            'status' => 'The status field is required.',
            'stock_status' => 'The stock status field is required.',
        ]);
});

test('products with the same name each get a unique slug', function () {
    $admin = adminUser(['products.create', 'products.delete']);
    $category = Category::factory()->create();
    $trashed = Product::factory()->create(['name' => 'Dental Mirror', 'slug' => 'dental-mirror-1']);
    $trashed->delete();

    foreach (['SKU-MIRROR-A', 'SKU-MIRROR-B', 'SKU-MIRROR-C'] as $sku) {
        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Dental Mirror',
            'sku' => $sku,
            'status' => 'draft',
            'stock_quantity' => 1,
            'stock_status' => 'in_stock',
            'category_id' => $category->id,
        ])->assertSessionHasNoErrors();
    }

    expect(Product::query()->whereIn('sku', ['SKU-MIRROR-A', 'SKU-MIRROR-B', 'SKU-MIRROR-C'])->orderBy('id')->pluck('slug')->all())
        ->toBe(['dental-mirror', 'dental-mirror-2', 'dental-mirror-3']);
});

test('an admin can set and clear a product\'s SEO title, description and keywords', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $payload = fn (array $seo) => [
        'name' => $product->name,
        'sku' => $product->sku,
        'stock_quantity' => $product->stock_quantity,
        'stock_status' => $product->stock_status,
        'status' => $product->status,
        'category_id' => $product->category_id,
        ...$seo,
    ];

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), $payload([
            'seo_title' => 'Cordless Curing Light',
            'seo_description' => 'Buy a cordless LED curing light in Bangladesh.',
            'seo_keywords' => 'curing light, dental LED',
        ]))
        ->assertSessionHasNoErrors();

    expect($product->fresh())
        ->seo_title->toBe('Cordless Curing Light')
        ->seo_description->toBe('Buy a cordless LED curing light in Bangladesh.')
        ->seo_keywords->toBe('curing light, dental LED');

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), $payload([
            'seo_title' => '',
            'seo_description' => '',
            'seo_keywords' => '',
        ]))
        ->assertSessionHasNoErrors();

    expect($product->fresh())
        ->seo_title->toBeNull()
        ->seo_description->toBeNull()
        ->seo_keywords->toBeNull();
});

test('an admin can replace, reorder and clear a product\'s specifications from the edit form', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    $product->specifications()->create(['name' => 'Material', 'value' => 'Steel', 'sort_order' => 0]);

    $payload = fn (array $specs) => [
        'name' => $product->name,
        'sku' => $product->sku,
        'stock_status' => $product->stock_status,
        'status' => $product->status,
        'category_id' => $product->category_id,
        ...$specs,
    ];

    // A blank row (the form always shows one) is ignored rather than rejected.
    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), $payload(['specifications' => [
            ['name' => 'Place of Origin', 'value' => 'China'],
            ['name' => '', 'value' => ''],
            ['name' => 'Material', 'value' => 'Ceramic'],
        ]]))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->specifications->map(fn ($spec) => [$spec->name, $spec->value])->all())
        ->toBe([['Place of Origin', 'China'], ['Material', 'Ceramic']]);

    // The form sends an empty "specifications" field once every row is removed.
    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), $payload(['specifications' => '']))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->specifications)->toBeEmpty();
});

test('a specification row with only a name or only a value is rejected on that row', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'sku' => $product->sku,
            'stock_status' => $product->stock_status,
            'status' => $product->status,
            'category_id' => $product->category_id,
            'specifications' => [
                ['name' => '', 'value' => ''],
                ['name' => 'Warranty', 'value' => ''],
            ],
        ])
        ->assertSessionHasErrors('specifications.1.value');
});

test('the admin product listing includes each product\'s specifications for the edit form', function () {
    $admin = adminUser(['products.view']);
    $product = Product::factory()->create();
    $product->specifications()->create(['name' => 'Material', 'value' => 'Ceramic', 'sort_order' => 0]);

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.specifications.0.name', 'Material')
            ->where('products.data.0.specifications.0.value', 'Ceramic'));
});

/**
 * The minimum fields the edit form always sends, for tests that change one list.
 *
 * @param  array<string, mixed>  $fields
 * @return array<string, mixed>
 */
function productFormPayload(Product $product, array $fields = []): array
{
    return [
        'name' => $product->name,
        'sku' => $product->sku,
        'stock_status' => $product->stock_status,
        'status' => $product->status,
        'category_id' => $product->category_id,
        ...$fields,
    ];
}

test('an admin can set, reorder and clear a product\'s highlights', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create(['highlights' => ['Old point']]);

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productFormPayload($product, [
            'highlights' => ['Second-hand safe', '', 'Autoclavable'],
        ]))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->highlights)->toBe(['Second-hand safe', 'Autoclavable']);

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productFormPayload($product, ['highlights' => '']))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->highlights)->toBeNull();
});

test('an admin can set and clear a product\'s canonical url', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productFormPayload($product, [
            'canonical_url' => 'https://example.com/crucibles',
        ]))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->canonical_url)->toBe('https://example.com/crucibles');

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productFormPayload($product, ['canonical_url' => '']))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->canonical_url)->toBeNull();
});

test('an admin can pick and clear related products, and a product is never related to itself', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    [$first, $second] = Product::factory()->count(2)->create();

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productFormPayload($product, [
            'related_product_ids' => [$first->id, $second->id, $product->id],
        ]))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->relatedProducts->pluck('id')->sort()->values()->all())
        ->toBe(collect([$first->id, $second->id])->sort()->values()->all());

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productFormPayload($product, ['related_product_ids' => '']))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->relatedProducts)->toBeEmpty();
});

test('unticking every tag removes the product\'s tags', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    $product->tags()->attach(Tag::factory()->create());

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productFormPayload($product, ['tag_ids' => '']))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->tags)->toBeEmpty();
});

test('an admin can create a product with options', function () {
    $admin = adminUser(['products.create']);
    $category = Category::factory()->create();

    $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Ortho Pins',
        'sku' => 'SKU-PINS',
        'status' => 'draft',
        'stock_status' => 'on_order',
        'category_id' => $category->id,
        'variants' => [
            ['name' => 'SS Pins', 'sku' => 'PIN-SS', 'price' => '120', 'stock_status' => 'in_stock', 'is_active' => '1'],
            ['name' => '', 'sku' => '', 'price' => '', 'stock_status' => 'on_order', 'is_active' => '1'],
            ['name' => 'Brass Pins', 'sku' => '', 'price' => '', 'stock_status' => 'on_order', 'is_active' => '0'],
        ],
    ])->assertSessionHasNoErrors();

    $variants = Product::query()->where('sku', 'SKU-PINS')->firstOrFail()->variants;

    expect($variants->map(fn ($v) => [$v->name, $v->sku, $v->price, $v->stock_status, $v->is_active, $v->sort_order])->all())
        ->toBe([
            ['SS Pins', 'PIN-SS', '120.00', 'in_stock', true, 0],
            ['Brass Pins', null, null, 'on_order', false, 1],
        ]);
});

test('editing options updates existing rows in place, adds new ones and deletes removed ones', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    $kept = $product->variants()->create(['name' => 'Small', 'sku' => 'V-S', 'sort_order' => 0]);
    $swapped = $product->variants()->create(['name' => 'Large', 'sku' => 'V-L', 'sort_order' => 1]);
    $removed = $product->variants()->create(['name' => 'Medium', 'sort_order' => 2]);

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productFormPayload($product, [
            'variants' => [
                // Reordered, and the two SKUs swapped between rows.
                ['id' => $swapped->id, 'name' => 'Large', 'sku' => 'V-S', 'price' => '', 'stock_status' => 'in_stock'],
                ['id' => $kept->id, 'name' => 'Small', 'sku' => 'V-L', 'price' => '50', 'stock_status' => 'on_order'],
                ['name' => 'Extra large', 'sku' => '', 'price' => '', 'stock_status' => 'backorder'],
            ],
        ]))
        ->assertSessionHasNoErrors();

    $variants = $product->fresh()->variants;

    expect($variants->pluck('name')->all())->toBe(['Large', 'Small', 'Extra large'])
        ->and($variants[0]->id)->toBe($swapped->id)
        ->and($variants[0]->sku)->toBe('V-S')
        ->and($variants[1]->id)->toBe($kept->id)
        ->and($variants[1]->price)->toBe('50.00')
        ->and($product->variants()->whereKey($removed->id)->exists())->toBeFalse();

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productFormPayload($product, ['variants' => '']))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->variants)->toBeEmpty();
});

test('options reject another product\'s variant id, a taken sku and a duplicate sku in the same form', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    $other = Product::factory()->create();
    $foreign = $other->variants()->create(['name' => 'Theirs', 'sku' => 'TAKEN']);

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productFormPayload($product, [
            'variants' => [
                ['id' => $foreign->id, 'name' => 'Stolen', 'stock_status' => 'on_order'],
                ['name' => 'A', 'sku' => 'TAKEN', 'stock_status' => 'on_order'],
                ['name' => 'B', 'sku' => 'dup', 'stock_status' => 'on_order'],
                ['name' => 'C', 'sku' => 'DUP', 'stock_status' => 'on_order'],
                ['name' => '', 'sku' => 'NO-NAME', 'stock_status' => 'on_order'],
            ],
        ]))
        ->assertSessionHasErrors(['variants.0.id', 'variants.1.sku', 'variants.2.sku', 'variants.4.name']);

    expect($foreign->fresh()->name)->toBe('Theirs');
});

test('the admin product listing includes options and related product ids for the edit form', function () {
    $admin = adminUser(['products.view']);
    $product = Product::factory()->create(['name' => 'AAA product']);
    $related = Product::factory()->create(['name' => 'ZZZ product']);
    $product->relatedProducts()->attach($related);
    $product->variants()->create(['name' => 'SS Pins']);

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['sort' => 'name']))
        ->assertInertia(fn ($page) => $page
            ->where('productOptions.0.name', 'AAA product')
            ->has('products.data', 2)
            ->where('products.data', fn ($rows) => collect($rows)->firstWhere('id', $product->id)['variants'][0]['name'] === 'SS Pins'
                && collect($rows)->firstWhere('id', $product->id)['related_products'][0]['id'] === $related->id));
});
