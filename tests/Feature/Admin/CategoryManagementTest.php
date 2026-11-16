<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

test('an uploaded category image is stored as a relative path and survives a domain change', function () {
    Storage::fake('public');
    $admin = adminUser(['categories.manage']);
    $category = Category::factory()->create();

    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'is_active' => true,
        'image_file' => UploadedFile::fake()->image('chair.jpg'),
    ]);

    $storedPath = $category->fresh()->getRawOriginal('image');
    expect($storedPath)->toStartWith("categories/{$category->id}/")
        ->and($category->fresh()->image)->toBe(Storage::disk('public')->url($storedPath));

    // The admin form resubmits the resolved URL; it must not be stored as an absolute URL.
    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'is_active' => true,
        'image' => $category->fresh()->image,
    ]);

    expect($category->fresh()->getRawOriginal('image'))->toBe($storedPath);
    Storage::disk('public')->assertExists($storedPath);
});

test('replacing an uploaded category image with an external url deletes the uploaded file', function () {
    Storage::fake('public');
    $admin = adminUser(['categories.manage']);
    $category = Category::factory()->create();
    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'is_active' => true,
        'image_file' => UploadedFile::fake()->image('chair.jpg'),
    ]);
    $uploadedPath = $category->fresh()->getRawOriginal('image');

    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'is_active' => true,
        'image' => 'https://cdn.example.com/chair.jpg',
    ]);

    expect($category->fresh()->image)->toBe('https://cdn.example.com/chair.jpg');
    Storage::disk('public')->assertMissing($uploadedPath);
});

test('legacy absolute storage urls from an old domain are converted to relative paths', function () {
    $category = Category::factory()->create();
    DB::table('categories')->where('id', $category->id)->update([
        'image' => 'http://192.168.0.111:8000/storage/categories/7/legacy.jpg',
    ]);

    (include database_path('migrations/2026_11_15_060102_convert_category_images_to_relative_paths.php'))->up();

    expect($category->fresh()->getRawOriginal('image'))->toBe('categories/7/legacy.jpg');
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

test('a category cannot be deleted while it has subcategories or products', function () {
    $admin = adminUser(['categories.manage']);
    $withSubcategory = Category::factory()->create();
    Subcategory::factory()->create(['category_id' => $withSubcategory->id]);
    $withProduct = Category::factory()->create();
    Product::factory()->create(['category_id' => $withProduct->id, 'subcategory_id' => null]);

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $withSubcategory))
        ->assertSessionHasErrors(['category' => 'This category still has subcategories and cannot be deleted.']);

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $withProduct))
        ->assertSessionHasErrors(['category' => 'This category still has products assigned and cannot be deleted.']);

    expect(Category::query()->whereKey([$withSubcategory->id, $withProduct->id])->count())->toBe(2);
});
