<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;

test('guests can browse active categories but not inactive ones', function () {
    Category::factory()->create(['name' => 'Visible', 'is_active' => true]);
    Category::factory()->inactive()->create(['name' => 'Hidden']);

    $response = $this->get(route('categories.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('categories', 1));
});

test('an inactive category detail page is not found', function () {
    $category = Category::factory()->inactive()->create();

    $this->get(route('categories.show', $category))->assertNotFound();
});

test('a category page lists its active subcategories', function () {
    $category = Category::factory()->create();
    $visible = Subcategory::factory()->for($category)->create();
    Subcategory::factory()->for($category)->inactive()->create();

    $response = $this->get(route('categories.show', $category));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('subcategories', 1)
        ->where('subcategories.0.id', $visible->id));
});

test('a subcategory page lists only published products in that subcategory', function () {
    $subcategory = Subcategory::factory()->create();
    $published = Product::factory()->published()->create([
        'category_id' => $subcategory->category_id,
        'subcategory_id' => $subcategory->id,
    ]);
    Product::factory()->create([
        'status' => 'draft',
        'category_id' => $subcategory->category_id,
        'subcategory_id' => $subcategory->id,
    ]);

    $response = $this->get(route('subcategories.show', $subcategory));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('products.total', 1));
});

test('an inactive subcategory detail page is not found', function () {
    $subcategory = Subcategory::factory()->inactive()->create();

    $this->get(route('subcategories.show', $subcategory))->assertNotFound();
});

test('guests can browse active brands', function () {
    Brand::factory()->count(2)->create();

    $this->get(route('brands.index'))->assertOk();
});

test('an inactive brand detail page is not found', function () {
    $brand = Brand::factory()->inactive()->create();

    $this->get(route('brands.show', $brand))->assertNotFound();
});
