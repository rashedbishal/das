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
