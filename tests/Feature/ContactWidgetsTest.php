<?php

use App\Services\SettingsService;

test('the contact widgets endpoint hides a disabled whatsapp number', function () {
    app(SettingsService::class)->set('whatsapp', 'enabled', false);
    app(SettingsService::class)->set('whatsapp', 'phone', '01712345678');

    $response = $this->getJson(route('contact-widgets'));

    $response->assertOk()->assertJson(['whatsapp' => ['enabled' => false, 'phone' => null]]);
});

test('the contact widgets endpoint exposes an enabled whatsapp number in international format', function () {
    app(SettingsService::class)->set('whatsapp', 'enabled', true);
    app(SettingsService::class)->set('whatsapp', 'phone', '01712345678');

    $response = $this->getJson(route('contact-widgets'));

    $response->assertOk()->assertJsonPath('whatsapp.phone', '8801712345678');
});

test('the contact widgets endpoint is public and requires no authentication', function () {
    $this->getJson(route('contact-widgets'))->assertOk();
});
