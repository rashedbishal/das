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

test('an admin with categories.manage can create a subcategory under a top-level category', function () {
    $admin = adminUser(['categories.manage', 'categories.view']);
    $parent = Category::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Electric Handpieces',
        'parent_id' => $parent->id,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $response->assertRedirect(route('admin.categories.index'));
    expect(Category::query()->where('slug', 'electric-handpieces')->first()?->parent_id)->toBe($parent->id);
});

test('creating a category rejects a parent that is itself a subcategory', function () {
    $admin = adminUser(['categories.manage']);
    $topLevel = Category::factory()->create();
    $subcategory = Category::factory()->child($topLevel)->create();

    $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Too Deep',
        'parent_id' => $subcategory->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect(Category::query()->where('name', 'Too Deep')->exists())->toBeFalse();
});

test('updating a category rejects a parent that is itself a subcategory', function () {
    $admin = adminUser(['categories.manage']);
    $topLevel = Category::factory()->create();
    $subcategory = Category::factory()->child($topLevel)->create();
    $other = Category::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.categories.update', $other), [
        'name' => $other->name,
        'parent_id' => $subcategory->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect($other->fresh()->parent_id)->toBeNull();
});

test('a category with subcategories cannot itself become a subcategory', function () {
    $admin = adminUser(['categories.manage']);
    $parent = Category::factory()->create();
    Category::factory()->child($parent)->create();
    $otherTopLevel = Category::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.categories.update', $parent), [
        'name' => $parent->name,
        'parent_id' => $otherTopLevel->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect($parent->fresh()->parent_id)->toBeNull();
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
    $category = Category::factory()->child(Category::factory()->create())->create();
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
