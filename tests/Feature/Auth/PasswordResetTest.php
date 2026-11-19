<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
        $response = $this->get(route('password.reset', $notification->token));

        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('the reset password email is DAS-branded and links to the reset page', function () {
    $user = User::factory()->make(['name' => 'Rafiq Ahmed', 'email' => 'rafiq@example.com']);

    $mail = (new ResetPasswordNotification('test-token'))->toMail($user);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('Reset your '.config('app.name').' password')
        ->and($mail->greeting)->toBe('Hello Rafiq,')
        ->and($mail->actionUrl)->toBe(route('password.reset', ['token' => 'test-token', 'email' => 'rafiq@example.com']))
        ->and($html)->toContain(asset('logo-email.png'))
        ->and($html)->toContain('Dental Machine &amp; Accessories Source')
        ->and($html)->toContain('#2f3190');
});

test('admins are not sent a reset link, but the form looks the same as for anyone else', function () {
    Notification::fake();

    $admin = User::factory()->motherAdmin()->create();

    $this->post(route('password.email'), ['email' => $admin->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    Notification::assertNothingSent();
});

test('an admin password cannot be reset even with a valid token', function () {
    $admin = User::factory()->motherAdmin()->create(['password' => 'original-password']);
    $token = Password::broker()->createToken($admin);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $admin->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('original-password', $admin->fresh()->password))->toBeTrue();
});
