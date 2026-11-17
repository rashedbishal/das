<?php

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an admin with brands.manage can create a brand', function () {
    $admin = adminUser(['brands.manage']);

    $response = $this->actingAs($admin)->post(route('admin.brands.store'), [
        'name' => 'Dentsply Sirona',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.brands.index'));
    expect(Brand::query()->where('slug', 'dentsply-sirona')->exists())->toBeTrue();
});

test('an admin without brands.manage cannot create a brand', function () {
    $admin = adminUser(['brands.view']);

    $response = $this->actingAs($admin)->post(route('admin.brands.store'), ['name' => 'Dentsply Sirona']);

    $response->assertForbidden();
});

test('a brand cannot be deleted while products are assigned to it', function () {
    $admin = adminUser(['brands.manage']);
    $brand = Brand::factory()->create();
    Product::factory()->create(['brand_id' => $brand->id]);

    $response = $this->actingAs($admin)->delete(route('admin.brands.destroy', $brand));

    $response->assertSessionHasErrors('brand');
});

test('creating a brand validates the website url', function () {
    $admin = adminUser(['brands.manage']);

    $response = $this->actingAs($admin)->post(route('admin.brands.store'), [
        'name' => 'Acme',
        'website_url' => 'not-a-url',
    ]);

    $response->assertSessionHasErrors('website_url');
});

test('an admin can activate brands in bulk', function () {
    $admin = adminUser(['brands.manage']);
    $brands = Brand::factory()->count(2)->create(['is_active' => false]);

    $response = $this->actingAs($admin)->post(route('admin.brands.bulk'), [
        'ids' => $brands->modelKeys(),
        'action' => 'activate',
    ]);

    $response->assertRedirect(route('admin.brands.index'));
    expect(Brand::query()->whereKey($brands->modelKeys())->where('is_active', false)->count())->toBe(0);
});

test('bulk brand deletion rejects the entire selection if a brand has products', function () {
    $admin = adminUser(['brands.manage']);
    $brands = Brand::factory()->count(2)->create();
    Product::factory()->create(['brand_id' => $brands[1]->id]);

    $response = $this->actingAs($admin)->post(route('admin.brands.bulk'), [
        'ids' => $brands->modelKeys(),
        'action' => 'delete',
    ]);

    $response->assertSessionHasErrors('ids');
    expect(Brand::query()->whereKey($brands->modelKeys())->count())->toBe(2);
});

/** A real image file of random noise (hard to compress), written with GD. */
function noiseImage(string $name, int $width, int $height, string $format = 'png'): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            imagesetpixel($image, $x, $y, random_int(0, 0xFFFFFF));
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'logo');
    $format === 'webp' ? imagewebp($image, $path, 80) : imagepng($image, $path);
    imagedestroy($image);

    return new UploadedFile($path, $name, $format === 'webp' ? 'image/webp' : 'image/png', null, true);
}

test('an uploaded brand logo is stored on the public disk as webp within 50 KB', function () {
    Storage::fake('public');
    $admin = adminUser(['brands.manage']);
    $upload = noiseImage('logo.png', 300, 300);
    expect($upload->getSize())->toBeGreaterThan(50 * 1024);

    $this->actingAs($admin)->post(route('admin.brands.store'), [
        'name' => 'Acme',
        'is_active' => true,
        'logo_file' => $upload,
    ])->assertSessionHasNoErrors();

    $brand = Brand::query()->where('slug', 'acme')->firstOrFail();
    $path = $brand->getRawOriginal('logo');

    expect($path)->toStartWith("brands/{$brand->id}/")->toEndWith('.webp');
    $stored = Storage::disk('public')->get($path);
    expect(strlen((string) $stored))->toBeLessThanOrEqual(50 * 1024)
        ->and(getimagesizefromstring((string) $stored)['mime'])->toBe('image/webp')
        ->and($brand->logo)->toBe(Storage::disk('public')->url($path));
});

test('a small webp logo that already fits is stored unchanged', function () {
    Storage::fake('public');
    $admin = adminUser(['brands.manage']);
    $upload = noiseImage('logo.webp', 60, 30, 'webp');
    $original = file_get_contents($upload->getRealPath());

    $this->actingAs($admin)->post(route('admin.brands.store'), ['name' => 'Acme', 'logo_file' => $upload])
        ->assertSessionHasNoErrors();

    $path = Brand::query()->where('slug', 'acme')->firstOrFail()->getRawOriginal('logo');
    expect(Storage::disk('public')->get($path))->toBe($original);
});

test('a brand logo over 500 KB is rejected', function () {
    Storage::fake('public');
    $admin = adminUser(['brands.manage']);

    $this->actingAs($admin)->post(route('admin.brands.store'), [
        'name' => 'Acme',
        'logo_file' => UploadedFile::fake()->image('logo.png', 400, 200)->size(501),
    ])->assertSessionHasErrors('logo_file');

    expect(Brand::query()->where('slug', 'acme')->exists())->toBeFalse();
});

test('a brand logo can only be uploaded, not set by url', function () {
    $admin = adminUser(['brands.manage']);

    $this->actingAs($admin)->post(route('admin.brands.store'), [
        'name' => 'Acme',
        'logo' => 'https://example.com/logo.png',
    ])->assertSessionHasNoErrors();

    expect(Brand::query()->where('slug', 'acme')->firstOrFail()->logo)->toBeNull();
});

test('replacing or removing a brand logo deletes the old file', function () {
    Storage::fake('public');
    $admin = adminUser(['brands.manage']);
    $brand = Brand::factory()->create(['name' => 'Acme']);
    $disk = Storage::disk('public');

    $this->actingAs($admin)->put(route('admin.brands.update', $brand), [
        'name' => 'Acme',
        'logo_file' => UploadedFile::fake()->image('first.png', 200, 100),
    ])->assertSessionHasNoErrors();
    $first = $brand->refresh()->getRawOriginal('logo');

    $this->actingAs($admin)->put(route('admin.brands.update', $brand), [
        'name' => 'Acme',
        'logo_file' => UploadedFile::fake()->image('second.png', 200, 100),
    ])->assertSessionHasNoErrors();
    $second = $brand->refresh()->getRawOriginal('logo');

    expect($second)->not->toBe($first);
    $disk->assertMissing($first);
    $disk->assertExists($second);

    $this->actingAs($admin)->put(route('admin.brands.update', $brand), [
        'name' => 'Acme',
        'remove_logo' => true,
    ])->assertSessionHasNoErrors();

    expect($brand->refresh()->logo)->toBeNull();
    $disk->assertMissing($second);
});
