<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('an admin with admins.manage can create another admin through the api', function () {
    $permission = Permission::factory()->create(['slug' => 'products.view', 'group' => 'products']);
    $actorRole = Role::factory()->create();
    $actorRole->permissions()->attach([
        $permission->id,
        Permission::factory()->create(['slug' => 'admins.manage', 'group' => 'admins'])->id,
    ]);
    $actor = User::factory()->admin($actorRole)->create();

    $targetRole = Role::factory()->create();
    $targetRole->permissions()->attach($permission);

    $response = $this->actingAs($actor)->postJson(route('api.v1.admin.admins.store'), [
        'name' => 'New Admin',
        'email' => 'new-admin@example.com',
        'password' => 'secret-password',
        'role_id' => $targetRole->id,
    ]);

    $response->assertCreated()->assertJsonPath('data.email', 'new-admin@example.com');
});

test('an admin cannot create another admin with a role granting permissions they lack', function () {
    $actorRole = Role::factory()->create();
    $actorRole->permissions()->attach(Permission::factory()->create(['slug' => 'admins.manage', 'group' => 'admins']));
    $actor = User::factory()->admin($actorRole)->create();

    $targetRole = Role::factory()->create();
    $targetRole->permissions()->attach(Permission::factory()->create(['slug' => 'settings.manage', 'group' => 'settings']));

    $response = $this->actingAs($actor)->postJson(route('api.v1.admin.admins.store'), [
        'name' => 'New Admin',
        'email' => 'new-admin@example.com',
        'password' => 'secret-password',
        'role_id' => $targetRole->id,
    ]);

    $response->assertForbidden();
});

test('the mother admin cannot be deactivated through the api', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();
    $otherAdmin = adminUser(['admins.manage']);

    $response = $this->actingAs($otherAdmin)->postJson(route('api.v1.admin.admins.deactivate', $motherAdmin));

    $response->assertForbidden();
    expect($motherAdmin->fresh()->is_active)->toBeTrue();
});

test('an admin self-editing their password must confirm it correctly through the api', function () {
    $admin = User::factory()->admin(Role::factory()->create())->create();

    $response = $this->actingAs($admin)->putJson(route('api.v1.admin.admins.update', $admin), [
        'name' => $admin->name,
        'password' => 'new-secret-password',
        'password_confirmation' => 'does-not-match',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('password');
});

test('an admin self-editing their password succeeds when the confirmation matches', function () {
    $admin = User::factory()->admin(Role::factory()->create())->create();

    $response = $this->actingAs($admin)->putJson(route('api.v1.admin.admins.update', $admin), [
        'name' => $admin->name,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ]);

    $response->assertOk();
});

test('an admin cannot delete their own account through the api', function () {
    $admin = User::factory()->admin(Role::factory()->create())->create();

    $response = $this->actingAs($admin)->deleteJson(route('api.v1.admin.admins.destroy', $admin));

    $response->assertForbidden();
    expect(User::query()->find($admin->id))->not->toBeNull();
});

test('an admin with admins.manage can delete another admin through the api', function () {
    $actor = adminUser(['admins.manage']);
    $target = User::factory()->admin(Role::factory()->create())->create();

    $response = $this->actingAs($actor)->deleteJson(route('api.v1.admin.admins.destroy', $target));

    $response->assertNoContent();
    expect(User::query()->find($target->id))->toBeNull();
});

test('the mother admin cannot be deleted through the api', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();
    $actor = adminUser(['admins.manage']);

    $response = $this->actingAs($actor)->deleteJson(route('api.v1.admin.admins.destroy', $motherAdmin));

    $response->assertForbidden();
    expect(User::query()->find($motherAdmin->id))->not->toBeNull();
});

test('an admin with roles.manage can create a role through the api', function () {
    Permission::factory()->create(['slug' => 'products.view', 'group' => 'products']);
    $admin = adminUser(['roles.manage', 'products.view']);

    $response = $this->actingAs($admin)->postJson(route('api.v1.admin.roles.store'), [
        'name' => 'API Role',
        'permissions' => ['products.view'],
    ]);

    $response->assertCreated()->assertJsonPath('data.name', 'API Role');
});

test('the mother admin can rename a role and change its permissions through the api', function () {
    $kept = Permission::factory()->create(['slug' => 'products.view', 'group' => 'products']);
    $removed = Permission::factory()->create(['slug' => 'brands.view', 'group' => 'brands']);
    Permission::factory()->create(['slug' => 'tags.manage', 'group' => 'tags']);
    $role = Role::factory()->create(['name' => 'Old Name']);
    $role->permissions()->attach([$kept->id, $removed->id]);

    $response = $this->actingAs(User::factory()->motherAdmin()->create())
        ->putJson(route('api.v1.admin.roles.update', $role), [
            'name' => 'New Name',
            'permissions' => ['products.view', 'tags.manage'],
        ]);

    $response->assertOk()->assertJsonPath('data.name', 'New Name');
    expect($role->fresh()->permissions->pluck('slug')->sort()->values()->all())->toBe(['products.view', 'tags.manage']);
});

test('the mother admin role itself cannot be edited through the api', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();

    $this->actingAs($motherAdmin)
        ->putJson(route('api.v1.admin.roles.update', $motherAdmin->role), ['name' => 'Renamed', 'permissions' => []])
        ->assertForbidden();

    expect($motherAdmin->role->fresh()->name)->not->toBe('Renamed');
});

test('a customer manager can deactivate a customer through the api', function () {
    $admin = adminUser(['customers.manage']);
    $customer = User::factory()->create();

    $response = $this->actingAs($admin)->postJson(route('api.v1.admin.customers.deactivate', $customer));

    $response->assertOk();
    expect($customer->fresh()->is_active)->toBeFalse();
});

test('an admin can deactivate customers in bulk through the api', function () {
    $admin = adminUser(['customers.manage']);
    $customers = User::factory()->count(2)->create(['is_active' => true]);

    $response = $this->actingAs($admin)->postJson(route('api.v1.admin.customers.bulk'), [
        'ids' => $customers->modelKeys(),
        'action' => 'deactivate',
    ]);

    $response->assertOk();
    expect(User::query()->whereKey($customers->modelKeys())->where('is_active', true)->count())->toBe(0);
});

test('an admin with customers.manage can permanently delete a customer, signing them out', function () {
    $admin = adminUser(['customers.manage']);
    $customer = User::factory()->create(['email' => 'leaving@example.com']);
    DB::table('sessions')->insert([
        'id' => 'customer-session', 'user_id' => $customer->id, 'ip_address' => '127.0.0.1',
        'user_agent' => 'test', 'payload' => '', 'last_activity' => now()->timestamp,
    ]);
    DB::table('password_reset_tokens')->insert(['email' => 'leaving@example.com', 'token' => 'hash', 'created_at' => now()]);

    $this->actingAs($admin)
        ->deleteJson(route('api.v1.admin.customers.destroy', $customer))
        ->assertNoContent();

    expect(User::query()->find($customer->id))->toBeNull()
        ->and(DB::table('sessions')->where('user_id', $customer->id)->exists())->toBeFalse()
        ->and(DB::table('password_reset_tokens')->where('email', 'leaving@example.com')->exists())->toBeFalse();
});

test('deleting a customer needs customers.manage', function () {
    $customer = User::factory()->create();

    $this->actingAs(adminUser(['customers.view']))
        ->deleteJson(route('api.v1.admin.customers.destroy', $customer))
        ->assertForbidden();

    expect(User::query()->find($customer->id))->not->toBeNull();
});

test('the customer delete endpoint cannot delete an admin account', function () {
    $target = User::factory()->admin(Role::factory()->create())->create();

    $this->actingAs(adminUser(['customers.manage', 'admins.manage']))
        ->deleteJson(route('api.v1.admin.customers.destroy', $target))
        ->assertNotFound();

    expect(User::query()->find($target->id))->not->toBeNull();
});

test('an admin can delete customers in bulk, but not when an admin account is among them', function () {
    $admin = adminUser(['customers.manage', 'admins.manage']);
    $customers = User::factory()->count(2)->create();
    $otherAdmin = User::factory()->admin(Role::factory()->create())->create();

    $this->actingAs($admin)->postJson(route('api.v1.admin.customers.bulk'), [
        'ids' => [...$customers->modelKeys(), $otherAdmin->id],
        'action' => 'delete',
    ])->assertStatus(422);

    expect(User::query()->whereKey($customers->modelKeys())->count())->toBe(2);

    $this->actingAs($admin)->postJson(route('api.v1.admin.customers.bulk'), [
        'ids' => $customers->modelKeys(),
        'action' => 'delete',
    ])->assertOk()->assertJsonPath('message', '2 customers deleted.');

    expect(User::query()->whereKey($customers->modelKeys())->count())->toBe(0);
});

test('bulk admin deletion refuses to delete the current administrator', function () {
    $admin = adminUser(['admins.manage']);
    $target = User::factory()->admin(Role::factory()->create())->create();

    $response = $this->actingAs($admin)->postJson(route('api.v1.admin.admins.bulk'), [
        'ids' => [$admin->id, $target->id],
        'action' => 'delete',
    ]);

    $response->assertForbidden();
    expect(User::query()->whereKey([$admin->id, $target->id])->count())->toBe(2);
});

test('bulk role deletion refuses assigned roles without deleting any selected roles', function () {
    $admin = adminUser(['roles.manage']);
    $roles = Role::factory()->count(2)->create();
    User::factory()->admin($roles[1])->create();

    $response = $this->actingAs($admin)->postJson(route('api.v1.admin.roles.bulk'), [
        'ids' => $roles->modelKeys(),
        'action' => 'delete',
    ]);

    $response->assertForbidden();
    expect(Role::query()->whereKey($roles->modelKeys())->count())->toBe(2);
});

test('a guest receives a 401 from the admin roles api', function () {
    $this->getJson(route('api.v1.admin.roles.index'))->assertUnauthorized();
});

test('the mother admin can transfer ownership to another admin with the correct password', function () {
    Role::factory()->create(['slug' => 'read-only-admin']);
    $motherAdmin = User::factory()->motherAdmin()->create(['password' => 'current-password']);
    $motherRoleId = $motherAdmin->role_id;
    $successor = User::factory()->admin(Role::factory()->create())->create();

    $response = $this->actingAs($motherAdmin)->postJson(
        route('api.v1.admin.admins.transfer-mother-admin', $successor),
        ['password' => 'current-password'],
    );

    $response->assertOk()->assertJsonPath('data.role_id', $motherRoleId);
    expect($successor->fresh()->isMotherAdmin())->toBeTrue()
        ->and($motherAdmin->fresh()->isMotherAdmin())->toBeFalse();
});

test('transferring mother admin ownership rejects an incorrect password', function () {
    $motherAdmin = User::factory()->motherAdmin()->create(['password' => 'current-password']);
    $successor = User::factory()->admin(Role::factory()->create())->create();

    $response = $this->actingAs($motherAdmin)->postJson(
        route('api.v1.admin.admins.transfer-mother-admin', $successor),
        ['password' => 'wrong-password'],
    );

    $response->assertUnprocessable()->assertJsonValidationErrors('password');
    expect($motherAdmin->fresh()->isMotherAdmin())->toBeTrue();
});

test('a non mother admin cannot transfer mother admin ownership', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();
    $otherAdmin = adminUser(['admins.manage']);
    $successor = User::factory()->admin(Role::factory()->create())->create();

    $response = $this->actingAs($otherAdmin)->postJson(
        route('api.v1.admin.admins.transfer-mother-admin', $successor),
        ['password' => 'password'],
    );

    $response->assertForbidden();
    expect($motherAdmin->fresh()->isMotherAdmin())->toBeTrue();
});

test('an admin with admins.manage can set another admin\'s password, which signs that admin out', function () {
    $manager = adminUser(['admins.manage', 'products.view']);
    $subAdmin = adminUser(['products.view']);
    DB::table('sessions')->insert([
        'id' => 'sub-admin-session',
        'user_id' => $subAdmin->id,
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);
    config(['session.driver' => 'database']);

    $this->actingAs($manager)->putJson(route('api.v1.admin.admins.update', $subAdmin), [
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertOk();

    expect(Hash::check('brand-new-password', $subAdmin->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $subAdmin->id)->exists())->toBeFalse();
});

test('an admin cannot set the password of an admin whose role grants permissions they lack', function () {
    $manager = adminUser(['admins.manage']);
    $morePowerful = adminUser(['settings.manage']);
    $originalHash = $morePowerful->password;

    $this->actingAs($manager)->putJson(route('api.v1.admin.admins.update', $morePowerful), [
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertForbidden();

    expect($morePowerful->fresh()->password)->toBe($originalHash);
});

test('setting another admin\'s password requires a matching confirmation', function () {
    $manager = adminUser(['admins.manage']);
    $subAdmin = adminUser();

    $this->actingAs($manager)->putJson(route('api.v1.admin.admins.update', $subAdmin), [
        'password' => 'brand-new-password',
        'password_confirmation' => 'different',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});
