<?php

test('every page carries baseline security headers', function () {
    $response = $this->get(route('about'));

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeaderMissing('Content-Security-Policy');
});

test('admin pages are served with a content security policy that blocks third-party scripts', function () {
    $admin = adminUser(['settings.view']);

    $response = $this->actingAs($admin)->get(route('admin.settings.edit'));

    $response->assertOk();
    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("script-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'");
});
