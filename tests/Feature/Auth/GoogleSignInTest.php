<?php

use App\Models\User;
use App\Services\SettingsService;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

beforeEach(function () {
    config([
        'services.google.client_id' => 'test-client-id',
        'services.google.client_secret' => 'test-client-secret',
    ]);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function fakeGoogleUser(array $attributes = []): void
{
    Socialite::fake('google', GoogleUser::fake([
        'id' => 'google-123',
        'name' => 'Rahim Uddin',
        'email' => 'rahim@example.com',
        'email_verified' => true,
        ...$attributes,
    ]));
}

test('the redirect route sends the customer to google', function () {
    Socialite::fake('google');

    $this->get(route('auth.google.redirect'))->assertRedirect('https://socialite.fake/google/authorize');
});

test('a new google account becomes a verified, password-less customer and is signed in', function () {
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    $user = User::query()->where('email', 'rahim@example.com')->sole();
    expect($user->isCustomer())->toBeTrue()
        ->and($user->google_id)->toBe('google-123')
        ->and($user->name)->toBe('Rahim Uddin')
        ->and($user->password)->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('an existing customer with the same email is linked instead of duplicated', function () {
    $customer = User::factory()->unverified()->create(['email' => 'rahim@example.com']);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    expect(User::query()->where('email', 'rahim@example.com')->count())->toBe(1)
        ->and($customer->fresh()->google_id)->toBe('google-123')
        ->and($customer->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($customer);
});

test('a returning customer is matched by google id even after changing their email', function () {
    $customer = User::factory()->create(['email' => 'old@example.com']);
    $customer->forceFill(['google_id' => 'google-123'])->save();
    fakeGoogleUser(['email' => 'new@example.com']);

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($customer);
    expect(User::query()->count())->toBe(1);
});

test('admins cannot sign in with google, and their account is not linked', function () {
    $admin = adminUser();
    fakeGoogleUser(['email' => $admin->email]);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => "This Google account can't be used to sign in here."]);

    $this->assertGuest();
    expect($admin->fresh()->google_id)->toBeNull();
});

test('a google account without a verified email is rejected', function () {
    fakeGoogleUser(['email_verified' => false]);

    $this->get(route('auth.google.callback'))->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('a deactivated customer is told the account is deactivated', function () {
    User::factory()->create(['email' => 'rahim@example.com', 'is_active' => false]);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))
        ->assertSessionHasErrors(['email' => 'Your account has been deactivated. Please contact us if you think this is a mistake.']);

    $this->assertGuest();
});

test('new google sign-ups respect the registration setting but existing customers can still sign in', function () {
    app(SettingsService::class)->set('auth', 'registration_enabled', false);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertSessionHasErrors(['email' => 'Registration is currently disabled.']);
    $this->assertGuest();

    $customer = User::factory()->create(['email' => 'rahim@example.com']);
    $this->get(route('auth.google.callback'));
    $this->assertAuthenticatedAs($customer);
});

test('cancelling on google returns to the login page with a message', function () {
    $this->get(route('auth.google.callback', ['error' => 'access_denied']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'Google sign-in was cancelled.']);

    $this->assertGuest();
});

test('a customer returns to the product page they were viewing', function () {
    fakeGoogleUser();

    $this->withSession(['url.intended' => url('/products/dental-chair')])
        ->get(route('auth.google.callback'))
        ->assertRedirect(url('/products/dental-chair'));
});

test('google sign-in is hidden and unreachable until credentials are configured', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('googleSignIn', false));
    $this->get(route('auth.google.redirect'))->assertNotFound();
    $this->get(route('auth.google.callback'))->assertNotFound();
});

test('the login page offers google sign-in when configured', function () {
    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('googleSignIn', true));
});
