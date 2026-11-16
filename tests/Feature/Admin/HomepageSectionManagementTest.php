<?php

use App\Models\HomepageSection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an admin with content.manage can create a homepage banner', function () {
    $admin = adminUser(['content.manage']);

    $response = $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'Spring Sale',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $response->assertRedirect(route('admin.homepage-sections.index'));
    expect(HomepageSection::query()->where('title', 'Spring Sale')->exists())->toBeTrue();
});

test('an admin without content.manage cannot create a homepage banner', function () {
    $admin = adminUser([]);

    $response = $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'Spring Sale',
    ]);

    $response->assertForbidden();
});

test('an end date before the start date is rejected', function () {
    $admin = adminUser(['content.manage']);

    $response = $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'Spring Sale',
        'starts_at' => '2026-02-01',
        'ends_at' => '2026-01-01',
    ]);

    $response->assertSessionHasErrors('ends_at');
});

test('an admin can delete a homepage section', function () {
    $admin = adminUser(['content.manage']);
    $section = HomepageSection::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.homepage-sections.destroy', $section));

    $response->assertRedirect(route('admin.homepage-sections.index'));
    expect(HomepageSection::query()->whereKey($section->id)->exists())->toBeFalse();
});

test('an admin can activate homepage sections in bulk', function () {
    $admin = adminUser(['content.manage']);
    $sections = HomepageSection::factory()->count(2)->create(['is_active' => false]);

    $response = $this->actingAs($admin)->post(route('admin.homepage-sections.bulk'), [
        'ids' => $sections->modelKeys(),
        'action' => 'activate',
    ]);

    $response->assertRedirect(route('admin.homepage-sections.index'));
    expect(HomepageSection::query()->whereKey($sections->modelKeys())->where('is_active', false)->count())->toBe(0);
});

test('banner uploads are cropped and converted to desktop, tablet and mobile webp images', function () {
    Storage::fake('public');
    $admin = adminUser(['content.manage']);

    $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'New suite',
        'landscape_image' => UploadedFile::fake()->image('wide.png', 1536, 1024),
        'portrait_image' => UploadedFile::fake()->image('tall.png', 1024, 1536),
    ])->assertRedirect(route('admin.homepage-sections.index'));

    $section = HomepageSection::query()->where('title', 'New suite')->firstOrFail();

    foreach (['image' => [1920, 640], 'image_tablet' => [1536, 768], 'image_mobile' => [1080, 1350]] as $column => [$width, $height]) {
        $path = $section->{$column};
        expect($path)->toEndWith('.webp');
        Storage::disk('public')->assertExists($path);
        [$actualWidth, $actualHeight] = getimagesizefromstring((string) Storage::disk('public')->get($path));
        expect([$actualWidth, $actualHeight])->toBe([$width, $height]);
    }
});

test('mobile is cut from the landscape image when no portrait is uploaded', function () {
    Storage::fake('public');
    $admin = adminUser(['content.manage']);

    $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'Landscape only',
        'landscape_image' => UploadedFile::fake()->image('wide.jpg', 1536, 1024),
    ]);

    $section = HomepageSection::query()->where('title', 'Landscape only')->firstOrFail();

    expect($section->image_mobile)->not->toBeNull();
    Storage::disk('public')->assertExists($section->image_mobile);
});

test('replacing and deleting a banner removes its old image files', function () {
    Storage::fake('public');
    $admin = adminUser(['content.manage']);

    $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'Rotating',
        'landscape_image' => UploadedFile::fake()->image('a.png', 1536, 1024),
    ]);
    $section = HomepageSection::query()->where('title', 'Rotating')->firstOrFail();
    $old = [$section->image, $section->image_tablet, $section->image_mobile];

    $this->actingAs($admin)->put(route('admin.homepage-sections.update', $section), [
        'type' => 'banner',
        'title' => 'Rotating',
        'landscape_image' => UploadedFile::fake()->image('b.png', 1536, 1024),
    ]);

    foreach ($old as $path) {
        Storage::disk('public')->assertMissing($path);
    }

    $section->refresh();
    $this->actingAs($admin)->delete(route('admin.homepage-sections.destroy', $section));

    Storage::disk('public')->assertDirectoryEmpty('banners');
});

test('too small images are rejected', function () {
    $admin = adminUser(['content.manage']);

    $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'Tiny',
        'landscape_image' => UploadedFile::fake()->image('tiny.png', 600, 300),
    ])->assertSessionHasErrors('landscape_image');
});

test('the storefront home page receives only visible banners that have images', function () {
    HomepageSection::factory()->create(['title' => 'Shown', 'image' => 'banners/a.webp']);
    HomepageSection::factory()->create(['title' => 'No image']);
    HomepageSection::factory()->create(['title' => 'Hidden', 'image' => 'banners/b.webp', 'is_active' => false]);

    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->component('storefront/Home')
        ->has('banners', 1)
        ->where('banners.0.title', 'Shown'));
});

test('a banner image wider than the maximum source dimension is rejected', function () {
    Storage::fake('public');
    $admin = adminUser(['content.manage']);

    $response = $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'Huge Banner',
        'landscape_image' => UploadedFile::fake()->image('huge.jpg', 8001, 1000),
    ]);

    $response->assertSessionHasErrors('landscape_image');
    expect(HomepageSection::query()->where('title', 'Huge Banner')->exists())->toBeFalse()
        ->and(Storage::disk('public')->allFiles('banners'))->toBeEmpty();
});

test('a banner link must be an http or https url', function () {
    $admin = adminUser(['content.manage']);

    $response = $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'Scripted Link',
        'link_url' => 'javascript://example.com/%0Aalert(1)',
    ]);

    $response->assertSessionHasErrors('link_url');
});

test('a landscape banner upload produces desktop, tablet and mobile renditions', function () {
    Storage::fake('public');
    $admin = adminUser(['content.manage']);

    $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'Renditions',
        'landscape_image' => UploadedFile::fake()->image('wide.jpg', 1920, 640),
    ])->assertSessionHasNoErrors();

    $section = HomepageSection::query()->where('title', 'Renditions')->firstOrFail();
    expect([$section->image, $section->image_tablet, $section->image_mobile])->each->not->toBeNull()
        ->and(Storage::disk('public')->allFiles('banners'))->toHaveCount(3);
});

test('two banners cannot share an order number', function () {
    $admin = adminUser(['content.manage']);
    HomepageSection::factory()->create(['type' => 'banner', 'sort_order' => 3]);

    $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'Duplicate order',
        'sort_order' => 3,
    ])->assertSessionHasErrors(['sort_order' => 'Another banner already uses order 3. Choose a different number.']);

    expect(HomepageSection::query()->where('title', 'Duplicate order')->exists())->toBeFalse();
});

test('a banner keeps its own order number when edited, but cannot take another banner\'s', function () {
    $admin = adminUser(['content.manage']);
    HomepageSection::factory()->create(['type' => 'banner', 'sort_order' => 1]);
    $banner = HomepageSection::factory()->create(['type' => 'banner', 'sort_order' => 2]);
    $payload = ['type' => 'banner', 'title' => 'Renamed'];

    $this->actingAs($admin)->put(route('admin.homepage-sections.update', $banner), [...$payload, 'sort_order' => 2])
        ->assertSessionHasNoErrors();
    $this->actingAs($admin)->put(route('admin.homepage-sections.update', $banner), [...$payload, 'sort_order' => 1])
        ->assertSessionHasErrors('sort_order');

    expect($banner->fresh()->sort_order)->toBe(2);
});

test('a banner and a section may use the same order number', function () {
    $admin = adminUser(['content.manage']);
    HomepageSection::factory()->create(['type' => 'section', 'sort_order' => 1]);

    $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'First banner',
        'sort_order' => 1,
    ])->assertSessionHasNoErrors();
});

test('a banner created without an order number goes after the last banner', function () {
    $admin = adminUser(['content.manage']);
    HomepageSection::factory()->create(['type' => 'banner', 'sort_order' => 7]);

    $this->actingAs($admin)->post(route('admin.homepage-sections.store'), [
        'type' => 'banner',
        'title' => 'No order given',
    ])->assertSessionHasNoErrors();

    expect(HomepageSection::query()->where('title', 'No order given')->value('sort_order'))->toBe(8);
});
