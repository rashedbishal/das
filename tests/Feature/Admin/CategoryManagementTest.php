<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('uploading an image when creating a category stores it on the public disk', function () {
    Storage::fake('public');
    $admin = adminUser(['categories.manage', 'categories.view']);

    $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Dental Chairs',
        'is_active' => true,
        'image_file' => UploadedFile::fake()->image('chair.jpg'),
    ]);

    $response->assertRedirect(route('admin.categories.index'));
    $category = Category::query()->where('slug', 'dental-chairs')->firstOrFail();
    expect($category->image)->not->toBeNull();
    Storage::disk('public')->assertExists("categories/{$category->id}/".basename($category->image));
});

test('uploading a new image when updating a category replaces and deletes the old one', function () {
    Storage::fake('public');
    $admin = adminUser(['categories.manage']);
    $category = Category::factory()->create();
    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'is_active' => true,
        'image_file' => UploadedFile::fake()->image('first.jpg'),
    ]);
    $firstImagePath = "categories/{$category->id}/".basename($category->fresh()->image);
    Storage::disk('public')->assertExists($firstImagePath);

    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'is_active' => true,
        'image_file' => UploadedFile::fake()->image('second.jpg'),
    ]);

    Storage::disk('public')->assertMissing($firstImagePath);
    $secondImagePath = "categories/{$category->id}/".basename($category->fresh()->image);
    Storage::disk('public')->assertExists($secondImagePath);
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

    $this->get(route('admin.categories.index'))->assertRedirect(route('admin.login'));
    $this->delete(route('admin.categories.destroy', $category))->assertRedirect(route('admin.login'));
});

test('customers cannot access category management', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('admin.categories.index'))->assertForbidden();
});

test('an admin can deactivate categories in bulk', function () {
    $admin = adminUser(['categories.manage']);
    $categories = Category::factory()->count(2)->create(['is_active' => true]);

    $response = $this->actingAs($admin)->post(route('admin.categories.bulk'), [
        'ids' => $categories->modelKeys(),
        'action' => 'deactivate',
    ]);

    $response->assertRedirect(route('admin.categories.index'));
    expect(Category::query()->whereKey($categories->modelKeys())->where('is_active', true)->count())->toBe(0);
});
