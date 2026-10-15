<?php

use App\Models\User;
use App\Services\SettingsService;

test('an admin with settings.manage can update general settings', function () {
    $admin = adminUser(['settings.manage']);

    $response = $this->actingAs($admin)->put(route('admin.settings.general.update'), [
        'site_name' => 'DAS Dental',
        'currency' => 'BDT',
        'timezone' => 'Asia/Dhaka',
    ]);

    $response->assertRedirect();
    expect(app(SettingsService::class)->get('general', 'site_name'))->toBe('DAS Dental');
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

test('updating the login mode changes the configured auth setting', function () {
    $admin = adminUser(['settings.manage']);

    $this->actingAs($admin)->put(route('admin.settings.auth.update'), [
        'login_mode' => 'phone',
        'registration_enabled' => true,
        'require_email_verification' => true,
    ]);

    expect(app(SettingsService::class)->loginMode())->toBe('phone');
});

test('disabling email verification updates the configured auth setting', function () {
    $admin = adminUser(['settings.manage']);

    $this->actingAs($admin)->put(route('admin.settings.auth.update'), [
        'login_mode' => 'both',
        'registration_enabled' => true,
        'require_email_verification' => false,
    ]);

    expect(app(SettingsService::class)->emailVerificationRequired())->toBeFalse();
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
        'default_title' => 'DAS Dental Catalog',
    ]);

    $response->assertRedirect();
    expect(app(SettingsService::class)->get('catalog', 'page_size'))->toBe(36);
});

test('guests cannot access the settings page', function () {
    $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
});

test('customers cannot access the settings page', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('admin.settings.edit'))->assertForbidden();
});
