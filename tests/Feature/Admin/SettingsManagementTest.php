<?php

use App\Models\User;
use App\Services\SettingsService;

test('business details are not pre-filled: they stay empty until set in the CMS', function () {
    $this->seed();

    $this->get(route('contact'))
        ->assertInertia(fn ($page) => $page
            ->where('siteSettings.contactPhone', null)
            ->where('siteSettings.contactEmail', null)
            ->where('siteSettings.address', null)
            ->where('siteSettings.deliveryNote', null)
            ->where('seo.defaultTitle', null));
});

test('an admin with settings.manage can update general settings', function () {
    $admin = adminUser(['settings.manage']);

    $response = $this->actingAs($admin)->put(route('admin.settings.general.update'), [
        'site_name' => 'DAS Dental',
        'contact_phone' => '01761158577',
        'contact_email' => 'afazad@gmail.com',
        'currency' => 'BDT',
        'timezone' => 'Asia/Dhaka',
    ]);

    $response->assertRedirect();
    expect(app(SettingsService::class)->get('general', 'site_name'))->toBe('DAS Dental');

    $this->actingAs($admin)->get(route('contact'))
        ->assertInertia(fn ($page) => $page
            ->where('siteSettings.contactPhone', '01761158577')
            ->where('siteSettings.contactEmail', 'afazad@gmail.com'));
});

test('an admin without settings.manage cannot update settings', function () {
    $admin = adminUser(['settings.view']);

    $response = $this->actingAs($admin)->put(route('admin.settings.general.update'), [
        'site_name' => 'Hacked',
        'currency' => 'BDT',
        'timezone' => 'Asia/Dhaka',
    ]);

    $response->assertForbidden();
});

test('an admin can turn customer registration off, and there is no login-mode setting any more', function () {
    $admin = adminUser(['settings.manage']);

    $this->actingAs($admin)->put(route('admin.settings.auth.update'), [
        'login_mode' => 'phone',
        'registration_enabled' => false,
    ])->assertSessionHasNoErrors();

    expect(app(SettingsService::class)->registrationEnabled())->toBeFalse()
        ->and(app(SettingsService::class)->get('auth', 'login_mode'))->toBeNull();
});

test('whatsapp settings normalize the phone number before saving', function () {
    $admin = adminUser(['settings.manage']);

    $this->actingAs($admin)->put(route('admin.settings.whatsapp.update'), [
        'enabled' => true,
        'phone' => '+880 171-234-5678',
        'message' => 'Hi there',
    ]);

    expect(app(SettingsService::class)->get('whatsapp', 'phone'))->toBe('01712345678');
});

test('enabling the whatsapp widget requires a valid phone number', function () {
    $admin = adminUser(['settings.manage']);

    $response = $this->actingAs($admin)->put(route('admin.settings.whatsapp.update'), [
        'enabled' => true,
        'phone' => '123',
    ]);

    $response->assertSessionHasErrors('phone');
});

test('a seo manager can update catalog and seo settings without settings.manage', function () {
    $admin = adminUser(['seo.manage']);

    $response = $this->actingAs($admin)->put(route('admin.settings.catalog-seo.update'), [
        'page_size' => 36,
        'featured_limit' => 6,
        'delivery_note' => 'Courier delivery throughout Bangladesh',
        'default_title' => 'DAS Dental Catalog',
    ]);

    $response->assertRedirect();
    expect(app(SettingsService::class)->get('catalog', 'page_size'))->toBe(36)
        ->and(app(SettingsService::class)->get('catalog', 'delivery_note'))
        ->toBe('Courier delivery throughout Bangladesh');
});

test('guests cannot access the settings page', function () {
    $this->get(route('admin.settings.edit'))->assertRedirect(route('admin.login'));
});

test('customers cannot access the settings page', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('admin.settings.edit'))->assertForbidden();
});

test('only the mother admin can update tracking scripts', function () {
    $settingsManager = adminUser(['settings.manage']);
    $motherAdmin = User::factory()->motherAdmin()->create();
    $payload = ['head_scripts' => '<script>window.tracked = true;</script>', 'body_scripts' => ''];

    $this->actingAs($settingsManager)
        ->put(route('admin.settings.tracking.update'), $payload)
        ->assertForbidden();
    expect(app(SettingsService::class)->get('tracking', 'head_scripts'))->toBeNull();

    $this->actingAs($motherAdmin)
        ->put(route('admin.settings.tracking.update'), $payload)
        ->assertRedirect();
    expect(app(SettingsService::class)->get('tracking', 'head_scripts'))->toBe($payload['head_scripts']);
});

test('tracking scripts are injected into storefront pages but never admin pages', function () {
    app(SettingsService::class)->set('tracking', 'head_scripts', '<script>window.gtmMarker = 1;</script>');
    $admin = adminUser(['settings.view']);

    $this->get(route('about'))->assertSee('window.gtmMarker = 1;', escape: false);

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertDontSee('window.gtmMarker', escape: false);
});

test('the mother admin receives the saved tracking scripts on the settings page', function () {
    app(SettingsService::class)->set('tracking', 'head_scripts', '<script>window.gtmMarker = 1;</script>');

    $this->actingAs(User::factory()->motherAdmin()->create())
        ->get(route('admin.settings.edit'))
        ->assertInertia(fn ($page) => $page->where('tracking.head_scripts', '<script>window.gtmMarker = 1;</script>'));
});

test('admins who cannot edit tracking scripts do not receive them', function () {
    app(SettingsService::class)->set('tracking', 'head_scripts', '<script>window.gtmMarker = 1;</script>');

    $this->actingAs(adminUser(['settings.manage']))
        ->get(route('admin.settings.edit'))
        ->assertInertia(fn ($page) => $page->where('tracking', []));
});

test('settings.manage alone is enough to open the settings page and api', function () {
    $admin = adminUser(['settings.manage']);

    $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
    $this->actingAs($admin)->getJson(route('api.v1.admin.settings.show'))->assertOk();
    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('permissions.settings', true));
});

function aboutPayload(array $overrides = []): array
{
    return array_replace([
        'headline_line_1' => 'Focused on',
        'headline_line_2' => 'dental excellence.',
        'intro' => 'New intro.',
        'heading' => 'New heading.',
        'paragraph_1' => 'First paragraph.',
        'paragraph_2' => 'Second paragraph.',
        'principles' => [
            ['title' => 'One', 'text' => 'Card one.'],
            ['title' => 'Two', 'text' => 'Card two.'],
            ['title' => 'Three', 'text' => 'Card three.'],
        ],
    ], $overrides);
}

test('the about page shows the default copy until an admin edits it', function () {
    $this->get(route('about'))
        ->assertInertia(fn ($page) => $page
            ->component('storefront/About')
            ->where('about.headline_line_1', 'Only by focusing')
            ->has('about.principles', 3));
});

test('an admin with settings.manage can edit the about page copy', function () {
    $admin = adminUser(['settings.manage']);

    $this->actingAs($admin)
        ->put(route('admin.settings.about.update'), aboutPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->get(route('about'))
        ->assertInertia(fn ($page) => $page
            ->where('about.headline_line_2', 'dental excellence.')
            ->where('about.paragraph_1', 'First paragraph.')
            ->where('about.principles.2.title', 'Three'));
});

test('about page fields are required and capped at their character limits', function () {
    $admin = adminUser(['settings.manage']);

    $this->actingAs($admin)
        ->put(route('admin.settings.about.update'), aboutPayload([
            'headline_line_1' => str_repeat('a', 31),
            'intro' => '',
            'principles' => [['title' => 'Only one', 'text' => 'Card.']],
        ]))
        ->assertSessionHasErrors(['headline_line_1', 'intro', 'principles']);
});

test('an admin without settings.manage cannot edit the about page', function () {
    $this->actingAs(adminUser(['settings.view']))
        ->put(route('admin.settings.about.update'), aboutPayload())
        ->assertForbidden();
});
