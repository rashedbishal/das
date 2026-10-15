<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('an admin with categories.manage can create a category', function () {
    $admin = adminUser(['categories.manage', 'categories.view']);

    $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Dental Chairs',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $response->assertRedirect(route('admin.categories.index'));
    expect(Category::query()->where('slug', 'dental-chairs')->exists())->toBeTrue();
});

test('an admin without categories.manage cannot create a category', function () {
    $admin = adminUser(['categories.view']);

    $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Dental Chairs',
    ]);

    $response->assertForbidden();
    expect(Category::query()->count())->toBe(0);
});

test('creating a category requires a name', function () {
    $admin = adminUser(['categories.manage']);

    $response = $this->actingAs($admin)->post(route('admin.categories.store'), []);

    $response->assertSessionHasErrors('name');
});

test('a category cannot be deleted while it still has subcategories', function () {
    $admin = adminUser(['categories.manage']);
    $parent = Category::factory()->create();
    Category::factory()->child($parent)->create();

    $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $parent));

    $response->assertSessionHasErrors('category');
    expect(Category::query()->whereKey($parent->id)->exists())->toBeTrue();
});

test('a category cannot be deleted while products are assigned to it', function () {
    $admin = adminUser(['categories.manage']);
    $category = Category::factory()->create();
    $category->products()->attach(Product::factory()->create());

    $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

    $response->assertSessionHasErrors('category');
});

test('guests cannot access category management', function () {
    $category = Category::factory()->create();

    $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    $this->delete(route('admin.categories.destroy', $category))->assertRedirect(route('login'));
});

test('customers cannot access category management', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('admin.categories.index'))->assertForbidden();
});
