<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

test('guests can browse active categories but not inactive ones', function () {
    Category::factory()->create(['name' => 'Visible', 'is_active' => true]);
    Category::factory()->inactive()->create(['name' => 'Hidden']);

    $response = $this->get(route('catalog.categories.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('categories', 1));
});

test('an inactive category detail page is not found', function () {
    $category = Category::factory()->inactive()->create();

    $this->get(route('catalog.categories.show', $category))->assertNotFound();
});

test('a subcategory page lists only published products in that subcategory', function () {
    $subcategory = Category::factory()->child(Category::factory()->create())->create();
    $published = Product::factory()->published()->create();
    $published->categories()->attach($subcategory);
    $draft = Product::factory()->create(['status' => 'draft']);
    $draft->categories()->attach($subcategory);

    $response = $this->get(route('catalog.categories.show', $category));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('products.total', 1));
});

test('a top-level category page lists its active subcategories instead of products', function () {
    $topLevel = Category::factory()->create();
    $visibleChild = Category::factory()->child($topLevel)->create();
    Category::factory()->child($topLevel)->inactive()->create();

    $response = $this->get(route('categories.show', $topLevel));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->missing('products')
        ->has('subcategories', 1)
        ->where('subcategories.0.id', $visibleChild->id));
});

test('guests can browse active brands', function () {
    Brand::factory()->count(2)->create();

    $this->get(route('catalog.brands.index'))->assertOk();
});

test('an inactive brand detail page is not found', function () {
    $brand = Brand::factory()->inactive()->create();

    $this->get(route('catalog.brands.show', $brand))->assertNotFound();
});
