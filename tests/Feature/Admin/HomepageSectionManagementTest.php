<?php

use App\Models\HomepageSection;

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
