<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Config::set('das.mother_admin', [
        'name' => 'Ada Admin',
        'email' => 'ada@example.com',
        'password' => 'correct-horse-battery-staple',
    ]);
});

test('creates the mother admin and seeds roles when none exist yet', function () {
    $this->artisan('das:reset-mother-admin')->assertSuccessful();

    $admin = User::query()->where('email', 'ada@example.com')->firstOrFail();

    expect($admin->isMotherAdmin())->toBeTrue()
        ->and($admin->user_type)->toBe('admin')
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(Hash::check('correct-horse-battery-staple', $admin->password))->toBeTrue();
});

test('replaces the existing mother admin in place and signs it out', function () {
    $old = User::factory()->motherAdmin()->create([
        'email' => 'old@example.com',
        'password' => 'old-password-123',
    ]);
    $old->forceFill(['google_id' => 'google-123'])->save();
    $product = Product::factory()->create(['created_by' => $old->id]);
    DB::table('sessions')->insert([
        'id' => 'old-session', 'user_id' => $old->id, 'payload' => '', 'last_activity' => time(),
    ]);

    $this->artisan('das:reset-mother-admin', ['--force' => true])->assertSuccessful();

    $admin = $old->fresh();

    expect(User::query()->admins()->count())->toBe(1)
        ->and($admin->email)->toBe('ada@example.com')
        ->and($admin->name)->toBe('Ada Admin')
        ->and($admin->isMotherAdmin())->toBeTrue()
        ->and(Hash::check('correct-horse-battery-staple', $admin->password))->toBeTrue()
        ->and($admin->google_id)->toBeNull()
        ->and($product->fresh()->created_by)->toBe($admin->id)
        ->and(DB::table('sessions')->where('user_id', $admin->id)->exists())->toBeFalse();
});

test('asks before replacing and leaves the account alone when declined', function () {
    $old = User::factory()->motherAdmin()->create(['email' => 'old@example.com']);

    $this->artisan('das:reset-mother-admin')
        ->expectsConfirmation('Replace the existing Mother Admin account?', 'no')
        ->assertFailed();

    expect($old->fresh()->email)->toBe('old@example.com');
});

test('can keep the existing mother admin email', function () {
    $old = User::factory()->motherAdmin()->create(['email' => 'ada@example.com']);

    $this->artisan('das:reset-mother-admin', ['--force' => true])->assertSuccessful();

    expect(Hash::check('correct-horse-battery-staple', $old->fresh()->password))->toBeTrue();
});

test('refuses an email that belongs to another user', function () {
    $old = User::factory()->motherAdmin()->create(['email' => 'old@example.com']);
    User::factory()->create(['email' => 'ada@example.com']);

    $this->artisan('das:reset-mother-admin', ['--force' => true])->assertFailed();

    expect($old->fresh()->email)->toBe('old@example.com');
});
