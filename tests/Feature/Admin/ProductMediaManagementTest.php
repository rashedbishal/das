<?php

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an admin can upload an image for a product', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'image',
        'file' => UploadedFile::fake()->image('photo.webp', 1200, 1200),
        'is_main' => true,
    ]);

    $response->assertRedirect();
    $media = $product->media()->first();
    expect($media)->not->toBeNull()
        ->and($media->source)->toBe('upload')
        ->and($media->is_main)->toBeTrue();
    Storage::disk('public')->assertExists($media->path);
});

test('uploading a disallowed file type is rejected', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'image',
        'file' => UploadedFile::fake()->create('malware.php', 10, 'application/x-php'),
    ]);

    $response->assertSessionHasErrors('file');
});

test('an admin can attach a video by url', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'url',
        'type' => 'video',
        'url' => 'https://www.youtube.com/watch?v=abc123',
    ]);

    $response->assertRedirect();
    expect($product->media()->where('type', 'video')->where('source', 'url')->exists())->toBeTrue();
});

test('setting a new main image unsets the previous main image', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    $existingMain = ProductMedia::factory()->for($product)->main()->create();

    $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'image',
        'file' => UploadedFile::fake()->image('new-main.webp', 1200, 1200),
        'is_main' => true,
    ]);

    expect($existingMain->fresh()->is_main)->toBeFalse();
});

test('an admin cannot delete media belonging to a different product', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();
    $media = ProductMedia::factory()->for($otherProduct)->create();

    $response = $this->actingAs($admin)->delete(route('admin.products.media.destroy', [$product, $media]));

    $response->assertNotFound();
    expect(ProductMedia::query()->whereKey($media->id)->exists())->toBeTrue();
});

test('an oversized image is rejected', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'image',
        'file' => UploadedFile::fake()->image('too-big.jpg', 400, 400)->size(600),
    ]);

    $response->assertSessionHasErrors('file');
    expect($product->media()->exists())->toBeFalse();
});

test('a webm video is rejected in favour of mp4 and mov only', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'video',
        'file' => UploadedFile::fake()->create('clip.webm', 500, 'video/webm'),
    ]);

    $response->assertSessionHasErrors('file');
});

test('a product cannot exceed the per-type media limit', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    ProductMedia::factory()->for($product)->video()->count(2)->create();

    $response = $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'url',
        'type' => 'video',
        'url' => 'https://www.youtube.com/watch?v=onetoomany',
    ]);

    $response->assertSessionHasErrors('file');
    expect($product->media()->where('type', 'video')->count())->toBe(2);
});

test('an admin can switch which image is the main one', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    $currentMain = ProductMedia::factory()->for($product)->main()->create();
    $other = ProductMedia::factory()->for($product)->create(['is_main' => false]);

    $response = $this->actingAs($admin)->patch(route('admin.products.media.update', [$product, $other]));

    $response->assertRedirect();
    expect($other->fresh()->is_main)->toBeTrue()
        ->and($currentMain->fresh()->is_main)->toBeFalse();
});
