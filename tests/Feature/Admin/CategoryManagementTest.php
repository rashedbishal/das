<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
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

test('an admin with categories.manage can update a category', function () {
    $admin = adminUser(['categories.manage']);
    $category = Category::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => 'New Name',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.categories.index'));
    expect($category->fresh()->name)->toBe('New Name');
});

test('a category cannot be deleted while it still has subcategories', function () {
    $admin = adminUser(['categories.manage']);
    $category = Category::factory()->create();
    Subcategory::factory()->for($category)->create();

    $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

    $response->assertSessionHasErrors('category');
    expect(Category::query()->whereKey($category->id)->exists())->toBeTrue();
});

test('a category cannot be deleted while products are assigned to it', function () {
    $admin = adminUser(['categories.manage']);
    $category = Category::factory()->create();
    $subcategory = Subcategory::factory()->for($category)->create();
    Product::factory()->create(['category_id' => $category->id, 'subcategory_id' => $subcategory->id]);

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
