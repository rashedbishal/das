<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

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

test('a category page lists only published products in that category', function () {
    $category = Category::factory()->create();
    $published = Product::factory()->published()->create();
    $published->categories()->attach($category);
    $draft = Product::factory()->create(['status' => 'draft']);
    $draft->categories()->attach($category);

    $response = $this->get(route('categories.show', $category));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('products.total', 1));
});

test('guests can browse active brands', function () {
    Brand::factory()->count(2)->create();

    $this->get(route('brands.index'))->assertOk();
});

test('an inactive brand detail page is not found', function () {
    $brand = Brand::factory()->inactive()->create();

    $this->get(route('brands.show', $brand))->assertNotFound();
});
