<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
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

test('a guest cannot read product detail from the api', function (string $status) {
    $product = Product::factory()->create(['status' => $status]);

    $this->getJson(route('api.v1.products.show', $product))->assertUnauthorized();
})->with(['published', 'draft']);

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

test('the subcategories endpoint lists only subcategories holding the brand\'s published products', function () {
    $brand = Brand::factory()->create();
    $otherBrand = Brand::factory()->create();
    $category = Category::factory()->create(['is_active' => true]);
    $otherCategory = Category::factory()->create(['is_active' => true]);

    $withBrand = Subcategory::factory()->for($category)->create();
    $otherBrandOnly = Subcategory::factory()->for($category)->create();
    $draftOnly = Subcategory::factory()->for($category)->create();
    $inactive = Subcategory::factory()->for($category)->inactive()->create();
    $inOtherCategory = Subcategory::factory()->for($otherCategory)->create();

    Product::factory()->published()->for($brand)->for($category)->for($withBrand)->count(2)->create();
    Product::factory()->published()->for($otherBrand)->for($category)->for($otherBrandOnly)->create();
    Product::factory()->for($brand)->for($category)->for($draftOnly)->create(['status' => 'draft']);
    Product::factory()->published()->for($brand)->for($category)->for($inactive)->create();
    Product::factory()->published()->for($brand)->for($otherCategory)->for($inOtherCategory)->create();

    $all = $this->getJson(route('api.v1.subcategories.index', ['brand' => $brand->slug]))
        ->assertOk()
        ->collect('data');

    expect($all->pluck('slug')->sort()->values()->all())
        ->toBe(collect([$withBrand->slug, $inOtherCategory->slug])->sort()->values()->all())
        ->and($all->firstWhere('slug', $withBrand->slug))
        ->toMatchArray(['products_count' => 2, 'category' => ['name' => $category->name, 'slug' => $category->slug]]);

    $narrowed = $this->getJson(route('api.v1.subcategories.index', ['brand' => $brand->slug, 'category' => $category->slug]))
        ->assertOk()
        ->collect('data');

    expect($narrowed->pluck('slug')->all())->toBe([$withBrand->slug]);
});

test('the subcategories endpoint requires a brand', function () {
    $this->getJson(route('api.v1.subcategories.index'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('brand');
});

test('the products api matches any of several comma-separated filter values', function () {
    $brandA = Brand::factory()->create();
    $brandB = Brand::factory()->create();
    $brandC = Brand::factory()->create();
    $a = Product::factory()->published()->for($brandA)->create(['stock_status' => 'in_stock', 'show_stock' => true]);
    $b = Product::factory()->published()->for($brandB)->create(['stock_status' => 'backorder', 'show_stock' => true]);
    Product::factory()->published()->for($brandC)->create(['stock_status' => 'in_stock', 'show_stock' => true]);

    $brands = $this->getJson(route('api.v1.products.index', ['brand' => "{$brandA->slug},{$brandB->slug}"]))
        ->assertOk()
        ->collect('data');

    expect($brands->pluck('id')->sort()->values()->all())->toBe(collect([$a->id, $b->id])->sort()->values()->all());

    $narrowed = $this->getJson(route('api.v1.products.index', [
        'brand' => "{$brandA->slug},{$brandB->slug}",
        'stock_status' => 'backorder,out_of_stock',
    ]))->assertOk()->collect('data');

    expect($narrowed->pluck('id')->all())->toBe([$b->id]);
});

test('signed-in users get the price range for the current filters, ignoring the price filter', function () {
    $brand = Brand::factory()->create();
    Product::factory()->published()->for($brand)->create(['regular_price' => 1200, 'show_price' => true]);
    Product::factory()->published()->for($brand)->create(['regular_price' => 4800.5, 'show_price' => true]);
    Product::factory()->published()->for($brand)->create(['regular_price' => 99999, 'show_price' => false]);
    Product::factory()->published()->create(['regular_price' => 10, 'show_price' => true]);

    $response = $this->actingAs(User::factory()->create())
        ->getJson(route('api.v1.products.index', ['brand' => $brand->slug, 'min_price' => 2000, 'max_price' => 3000]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(0)
        ->and($response->json('price_range'))->toEqual(['min' => 1200, 'max' => 4800.5]);
});

test('guests get no price range from the products api', function () {
    Product::factory()->published()->create(['show_price' => true]);

    $this->getJson(route('api.v1.products.index'))
        ->assertOk()
        ->assertJsonPath('price_range', null);
});

test('the subcategories endpoint accepts several brands', function () {
    $brandA = Brand::factory()->create();
    $brandB = Brand::factory()->create();
    $category = Category::factory()->create(['is_active' => true]);
    $subA = Subcategory::factory()->for($category)->create();
    $subB = Subcategory::factory()->for($category)->create();
    Product::factory()->published()->for($brandA)->for($category)->for($subA)->create();
    Product::factory()->published()->for($brandB)->for($category)->for($subB)->create();

    $slugs = $this->getJson(route('api.v1.subcategories.index', ['brand' => "{$brandA->slug},{$brandB->slug}"]))
        ->assertOk()
        ->collect('data')
        ->pluck('slug')
        ->sort()
        ->values()
        ->all();

    expect($slugs)->toBe(collect([$subA->slug, $subB->slug])->sort()->values()->all());
});
