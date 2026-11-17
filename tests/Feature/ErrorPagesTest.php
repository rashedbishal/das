<?php

use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Support\Facades\Route;

test('an unknown url shows the storefront 404 page', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('storefront/NotFound'));
});

test('other not-found errors show the branded 404 page', function () {
    Route::get('/_missing', fn () => abort(404));

    $this->get('/_missing')
        ->assertNotFound()
        ->assertSee('This page isn’t in our catalogue.', false)
        ->assertSee('noindex, nofollow', false)
        ->assertSee(url('/catalogue'), false);
});

test('the maintenance page renders on its own and refreshes itself', function () {
    // Deploys pre-render it with `artisan down --render="errors::503"`, which
    // registers the error views and renders it just like this.
    (new RegisterErrorViewPaths)();
    $html = view('errors::503')->render();

    expect($html)
        ->toContain('We’re making a few improvements.')
        ->toContain('<meta http-equiv="refresh" content="30">')
        ->not->toContain('/build/');
});

test('maintenance mode serves the branded 503 page', function () {
    $this->app->maintenanceMode()->activate([]);

    $this->get('/')->assertStatus(503)->assertSee('We’re making a few improvements.', false);

    $this->app->maintenanceMode()->deactivate();
});

test('server errors show the branded 500 page when debug is off', function () {
    config(['app.debug' => false]);
    Route::get('/_boom', fn () => throw new RuntimeException('boom'));

    $this->get('/_boom')
        ->assertStatus(500)
        ->assertSee('Something went wrong on our side.', false)
        ->assertDontSee('boom');
});

test('error codes without their own page use the branded fallbacks', function (int $status, string $text) {
    config(['app.debug' => false]);
    Route::get("/_status/{$status}", fn () => abort($status));

    $this->get("/_status/{$status}")
        ->assertStatus($status)
        ->assertSee($text, false)
        ->assertSee((string) $status);
})->with([
    [405, 'We couldn’t complete that request.'],
    [413, 'We couldn’t complete that request.'],
    [502, 'Something went wrong on our side.'],
]);

test('each designed error page renders', function (int $status, string $text) {
    config(['app.debug' => false]);
    Route::get("/_page/{$status}", fn () => abort($status));

    $this->get("/_page/{$status}")->assertStatus($status)->assertSee($text, false);
})->with([
    [401, 'Please sign in to continue.'],
    [403, 'This page isn’t available to you.'],
    [419, 'This page timed out.'],
    [429, 'That was a lot of requests at once.'],
]);

test('api requests still get json errors', function () {
    $this->getJson('/api/v1/does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
});
