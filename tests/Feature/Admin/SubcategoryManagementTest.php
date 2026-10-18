<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;

test('an admin with categories.manage can create a subcategory under a category', function () {
    $admin = adminUser(['categories.manage', 'categories.view']);
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.subcategories.store'), [
        'category_id' => $category->id,
        'name' => 'Electric Handpieces',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $response->assertRedirect(route('admin.categories.index'));
    expect(Subcategory::query()->where('slug', 'electric-handpieces')->first()?->category_id)->toBe($category->id);
});

test('an admin without categories.manage cannot create a subcategory', function () {
    $admin = adminUser(['categories.view']);
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.subcategories.store'), [
        'category_id' => $category->id,
        'name' => 'Electric Handpieces',
    ]);

    $response->assertForbidden();
    expect(Subcategory::query()->count())->toBe(0);
});

test('creating a subcategory requires a category', function () {
    $admin = adminUser(['categories.manage']);

    $response = $this->actingAs($admin)->post(route('admin.subcategories.store'), [
        'name' => 'Electric Handpieces',
    ]);

    $response->assertSessionHasErrors('category_id');
});

test('creating a subcategory rejects a category that does not exist', function () {
    $admin = adminUser(['categories.manage']);

    $response = $this->actingAs($admin)->post(route('admin.subcategories.store'), [
        'category_id' => 999,
        'name' => 'Electric Handpieces',
    ]);

    $response->assertSessionHasErrors('category_id');
});

test('an admin with categories.manage can update a subcategory, including moving it to another category', function () {
    $admin = adminUser(['categories.manage']);
    $subcategory = Subcategory::factory()->create();
    $newCategory = Category::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.subcategories.update', $subcategory), [
        'category_id' => $newCategory->id,
        'name' => $subcategory->name,
    ]);

    $response->assertRedirect(route('admin.categories.index'));
    expect($subcategory->fresh()->category_id)->toBe($newCategory->id);
});

test('a subcategory cannot be deleted while products are assigned to it', function () {
    $admin = adminUser(['categories.manage']);
    $subcategory = Subcategory::factory()->create();
    Product::factory()->create(['category_id' => $subcategory->category_id, 'subcategory_id' => $subcategory->id]);

    $response = $this->actingAs($admin)->delete(route('admin.subcategories.destroy', $subcategory));

    $response->assertSessionHasErrors('subcategory');
    expect(Subcategory::query()->whereKey($subcategory->id)->exists())->toBeTrue();
});

test('an admin with categories.manage can delete an unused subcategory', function () {
    $admin = adminUser(['categories.manage']);
    $subcategory = Subcategory::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.subcategories.destroy', $subcategory));

    $response->assertRedirect(route('admin.categories.index'));
    expect(Subcategory::query()->whereKey($subcategory->id)->exists())->toBeFalse();
});

test('guests cannot access subcategory management', function () {
    $subcategory = Subcategory::factory()->create();

    $this->delete(route('admin.subcategories.destroy', $subcategory))->assertRedirect(route('admin.login'));
});

test('customers cannot manage subcategories', function () {
    $customer = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($customer)->post(route('admin.subcategories.store'), [
        'category_id' => $category->id,
        'name' => 'Electric Handpieces',
    ])->assertForbidden();
});
