<?php

test('the privacy policy and terms pages are public', function (string $route, string $component) {
    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    'privacy policy' => ['privacy', 'storefront/PrivacyPolicy'],
    'terms and conditions' => ['terms', 'storefront/Terms'],
]);

test('the legal pages live at readable URLs and are listed in the sitemap', function () {
    expect(route('privacy', absolute: false))->toBe('/privacy-policy')
        ->and(route('terms', absolute: false))->toBe('/terms-and-conditions');

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('privacy'), false)
        ->assertSee(route('terms'), false);
});
