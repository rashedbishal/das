<?php

use App\Models\EmailVerificationCode;
use App\Models\Product;
use App\Models\User;
use App\Notifications\VerifyEmailCodeNotification;
use App\Services\EmailVerificationCodeService;
use App\Services\SettingsService;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;

/**
 * Send a fresh code to the user and return it (read from the faked notification).
 */
function sendVerificationCode(User $user): string
{
    Notification::fake();
    app(EmailVerificationCodeService::class)->send($user);

    $code = null;
    Notification::assertSentTo($user, VerifyEmailCodeNotification::class, function (VerifyEmailCodeNotification $notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    return (string) $code;
}

test('registering e-mails a 6-digit code to the new customer', function () {
    Notification::fake();
    app(SettingsService::class)->set('auth', 'registration_enabled', true);

    $this->post(route('register.store'), [
        'name' => 'Rafiq Ahmed',
        'email' => 'rafiq@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'rafiq@example.com')->firstOrFail();

    $code = null;
    Notification::assertSentTo($user, VerifyEmailCodeNotification::class, function (VerifyEmailCodeNotification $notification) use (&$code) {
        $code = $notification->code;

        return preg_match('/^\d{6}$/', $notification->code) === 1;
    });

    $storedHash = EmailVerificationCode::query()->where('user_id', $user->id)->value('code_hash');
    expect($storedHash)->not->toBe($code)
        ->and(Hash::check((string) $code, (string) $storedHash))->toBeTrue();
});

test('the code e-mail is DAS-branded and shows the code', function () {
    $user = User::factory()->unverified()->make(['name' => 'Rafiq Ahmed']);

    $mail = (new VerifyEmailCodeNotification('482913', 15))->toMail($user);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('482913 is your '.config('app.name').' verification code')
        ->and($html)->toContain('482 913')
        ->and($html)->toContain('Welcome, Rafiq!')
        ->and($html)->toContain(asset('logo-email.png'));
});

test('the right code verifies the e-mail and continues to the page the customer wanted', function () {
    $user = User::factory()->unverified()->create();
    $product = Product::factory()->published()->create();
    $code = sendVerificationCode($user);

    $this->actingAs($user)
        ->get(route('products.show', $product->slug))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => substr($code, 0, 3).' '.substr($code, 3)])
        ->assertRedirect(route('products.show', $product->slug));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and(EmailVerificationCode::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('a wrong code is rejected and says how many tries are left', function () {
    $user = User::factory()->unverified()->create();
    $code = sendVerificationCode($user);
    $wrong = $code === '000000' ? '111111' : '000000';

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => $wrong])
        ->assertSessionHasErrors(['code' => "That code isn't right. You have 4 tries left."]);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('after five wrong codes even the right one stops working until a new code is sent', function () {
    $user = User::factory()->unverified()->create();
    $code = sendVerificationCode($user);
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, EmailVerificationCodeService::MAX_ATTEMPTS) as $attempt) {
        $this->actingAs($user)->post(route('verification.code'), ['code' => $wrong]);
    }

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => $code])
        ->assertSessionHasErrors(['code' => 'This code has expired. Please request a new one.']);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('an expired code is rejected', function () {
    $user = User::factory()->unverified()->create();
    $code = sendVerificationCode($user);

    $this->travel(EmailVerificationCodeService::CODE_LIFETIME_MINUTES + 1)->minutes();

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => $code])
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('sending a new code replaces the old one', function () {
    $user = User::factory()->unverified()->create();
    $oldCode = sendVerificationCode($user);

    do {
        $newCode = sendVerificationCode($user);
    } while ($newCode === $oldCode);

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => $oldCode])
        ->assertSessionHasErrors('code');

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => $newCode])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('an unverified customer who signs in is sent to the code screen', function () {
    User::factory()->unverified()->create(['email' => 'new@example.com', 'password' => 'password']);

    $this->post(route('login.store'), ['email' => 'new@example.com', 'password' => 'password'])
        ->assertRedirect(route('verification.notice'));
});

test('unverified customers do not see prices', function () {
    Product::factory()->published()->create(['regular_price' => 4500, 'show_price' => true]);

    $this->actingAs(User::factory()->unverified()->create())
        ->getJson(route('api.v1.products.index'))
        ->assertOk()
        ->assertJsonPath('data.0.regular_price', null)
        ->assertJsonPath('data.0.price_login_required', true);

    $this->actingAs(User::factory()->create())
        ->getJson(route('api.v1.products.index'))
        ->assertJsonPath('data.0.regular_price', '4500.00');
});

test('the API can verify the e-mail with the code and blocks product details until then', function () {
    $user = User::factory()->unverified()->create();
    $product = Product::factory()->published()->create();
    $code = sendVerificationCode($user);

    $this->actingAs($user)
        ->getJson(route('api.v1.products.show', $product->slug))
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(route('api.v1.auth.email.verify'), ['code' => $code])
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);

    $this->actingAs($user->fresh())
        ->getJson(route('api.v1.products.show', $product->slug))
        ->assertOk();
});

/**
 * Make every notification fail the way a broken SMTP server does.
 */
function breakTheMailServer(): void
{
    Exceptions::fake();
    test()->mock(NotificationDispatcher::class)
        ->shouldReceive('send')
        ->andThrow(new TransportException('Connection to smtp.example.com refused'));
}

test('registration still completes when the code e-mail cannot be sent, and says so', function () {
    app(SettingsService::class)->set('auth', 'registration_enabled', true);
    breakTheMailServer();

    $this->post(route('register.store'), [
        'name' => 'Rafiq Ahmed',
        'email' => 'rafiq@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'verification-code-failed');

    $this->assertAuthenticated();
    Exceptions::assertReported(TransportException::class);
});

test('"send a new code" reports a mail failure instead of claiming the code was sent', function () {
    $user = User::factory()->unverified()->create();
    breakTheMailServer();

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'verification-code-failed');

    $this->actingAs($user)
        ->postJson(route('api.v1.auth.email.resend'))
        ->assertStatus(503);
});

test('a password reset e-mail that cannot be sent shows a form error instead of crashing', function () {
    $user = User::factory()->create();
    breakTheMailServer();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors(['email' => 'We couldn’t send the reset email just now. Please try again in a few minutes.']);

    Exceptions::assertReported(TransportException::class);
});
