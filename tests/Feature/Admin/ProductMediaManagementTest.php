<?php

use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\ProductMediaService;
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
        'file' => UploadedFile::fake()->image('too-big.jpg', 400, 400)->size(ProductMediaService::MAX_IMAGE_UPLOAD_SIZE_KB + 1),
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

test('a video larger than 10 MB is rejected', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'video',
        'file' => UploadedFile::fake()->create('too-large.mp4', 10241, 'video/mp4'),
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

/**
 * A real 1200×1200 WebP of roughly 200 KB: detailed enough to be over the
 * 50 KB target, simple enough to compress below it.
 */
function largeWebpUpload(): UploadedFile
{
    $image = imagecreatetruecolor(1200, 1200);
    mt_srand(1);
    for ($y = 0; $y < 1200; $y += 4) {
        for ($x = 0; $x < 1200; $x += 4) {
            $colour = (int) imagecolorallocate($image, intdiv($x, 5) % 256, intdiv($y, 5) % 256, mt_rand(0, 60));
            imagefilledrectangle($image, $x, $y, $x + 3, $y + 3, $colour);
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'webp').'.webp';
    imagewebp($image, $path, 100);

    return new UploadedFile($path, 'large.webp', 'image/webp', null, true);
}

test('an image over 50 KB is compressed below 50 KB on upload', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();
    $upload = largeWebpUpload();
    expect($upload->getSize())->toBeGreaterThan(50 * 1024);

    $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'image',
        'file' => $upload,
    ])->assertSessionHasNoErrors();

    $media = $product->media()->firstOrFail();
    expect(Storage::disk('public')->size($media->path))->toBeLessThanOrEqual(50 * 1024)
        ->and($media->path)->toEndWith('.webp');
});

test('an image of a few MB is accepted and compressed', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'image',
        'file' => UploadedFile::fake()->image('photo.jpg', 3000, 3000)->size(2048),
    ])->assertSessionHasNoErrors();

    $media = $product->media()->firstOrFail();
    expect(Storage::disk('public')->size($media->path))->toBeLessThanOrEqual(50 * 1024)
        ->and($media->path)->toEndWith('.webp');
});

test('an image larger than 10 MB is rejected', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'image',
        'file' => UploadedFile::fake()->image('huge.webp', 1200, 1200)->size(10241),
    ]);

    $response->assertSessionHasErrors(['file' => 'Images must be 10 MB or smaller.']);
});

test('an image that is not 1200×1200 is accepted and resized to 1200×1200', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'image',
        'file' => UploadedFile::fake()->image('wide.webp', 800, 400),
    ])->assertSessionHasNoErrors();

    $media = $product->media()->firstOrFail();
    $size = getimagesizefromstring((string) Storage::disk('public')->get($media->path));
    expect($size[0])->toBe(1200)
        ->and($size[1])->toBe(1200)
        ->and(Storage::disk('public')->size($media->path))->toBeLessThanOrEqual(50 * 1024);
});

test('a non-square image keeps its aspect ratio and is padded to a square', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    // A 2:1 opaque image should fill the middle half of the square, with
    // transparent bands above and below rather than being stretched.
    $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'image',
        'file' => UploadedFile::fake()->image('wide.jpg', 800, 400),
    ])->assertSessionHasNoErrors();

    $stored = imagecreatefromstring((string) Storage::disk('public')->get($product->media()->firstOrFail()->path));
    $width = imagesx($stored);
    $height = imagesy($stored);
    $alphaAt = fn (float $y) => (imagecolorat($stored, intdiv($width, 2), (int) ($height * $y)) >> 24) & 0x7F;

    expect($width)->toBe($height)
        ->and($alphaAt(0.1))->toBeGreaterThan(100)
        ->and($alphaAt(0.9))->toBeGreaterThan(100)
        ->and($alphaAt(0.5))->toBeLessThan(20);
});

test('a jpg image is accepted and stored as webp', function () {
    Storage::fake('public');
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create();

    $this->actingAs($admin)->post(route('admin.products.media.store', $product), [
        'source' => 'upload',
        'type' => 'image',
        'file' => UploadedFile::fake()->image('photo.jpg', 900, 700),
    ])->assertSessionHasNoErrors();

    $media = $product->media()->firstOrFail();
    expect($media->path)->toEndWith('.webp')
        ->and(Storage::disk('public')->mimeType($media->path))->toBe('image/webp');
});
