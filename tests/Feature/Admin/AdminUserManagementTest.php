<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

test('an admin holding admins.manage can create another admin with a role whose permissions they hold', function () {
    $permission = Permission::factory()->create(['slug' => 'products.view', 'group' => 'products']);
    $managerRole = Role::factory()->create();
    $managerRole->permissions()->attach([
        $permission->id,
        Permission::factory()->create(['slug' => 'admins.manage', 'group' => 'admins'])->id,
    ]);
    $actor = User::factory()->admin($managerRole)->create();

    $targetRole = Role::factory()->create();
    $targetRole->permissions()->attach($permission);

    $created = app(AdminUserService::class)->createAdmin($actor, [
        'name' => 'New Admin',
        'email' => 'new-admin@example.com',
        'phone' => null,
        'password' => 'secret-password',
        'role_id' => $targetRole->id,
    ]);

    expect($created->user_type)->toBe('admin')
        ->and($created->role_id)->toBe($targetRole->id)
        ->and($created->created_by)->toBe($actor->id);
});

test('an admin cannot create an admin whose role grants permissions they lack', function () {
    $adminsManage = Permission::factory()->create(['slug' => 'admins.manage', 'group' => 'admins']);
    $actorRole = Role::factory()->create();
    $actorRole->permissions()->attach($adminsManage);
    $actor = User::factory()->admin($actorRole)->create();

    $targetRole = Role::factory()->create();
    $targetRole->permissions()->attach(
        Permission::factory()->create(['slug' => 'settings.manage', 'group' => 'settings']),
    );

    app(AdminUserService::class)->createAdmin($actor, [
        'name' => 'New Admin',
        'email' => 'new-admin@example.com',
        'phone' => null,
        'password' => 'secret-password',
        'role_id' => $targetRole->id,
    ]);
})->throws(AuthorizationException::class);

test('the mother admin role can only be assigned through ownership transfer', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();

    app(AdminUserService::class)->createAdmin($motherAdmin, [
        'name' => 'New Admin',
        'email' => 'new-admin@example.com',
        'phone' => null,
        'password' => 'secret-password',
        'role_id' => $motherAdmin->role_id,
    ]);
})->throws(AuthorizationException::class, 'The Mother Admin role can only be assigned through ownership transfer.');

test('the mother admin account cannot be modified through the ordinary admin update path', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();
    $actor = User::factory()->admin(Role::factory()->create())->create();

    app(AdminUserService::class)->updateAdmin($actor, $motherAdmin, ['name' => 'Renamed']);
})->throws(AuthorizationException::class, 'The Mother Admin account cannot be modified.');

test('the mother admin account cannot be deactivated', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();
    $otherAdmin = User::factory()->admin(Role::factory()->create())->create();

    app(AdminUserService::class)->deactivateAdmin($otherAdmin, $motherAdmin);
})->throws(AuthorizationException::class);

test('an admin cannot deactivate their own account', function () {
    $admin = User::factory()->admin(Role::factory()->create())->create();

    app(AdminUserService::class)->deactivateAdmin($admin, $admin);
})->throws(AuthorizationException::class);

test('the mother admin can change their own name and password', function () {
    $motherAdmin = User::factory()->motherAdmin()->create(['password' => 'old-password']);

    $updated = app(AdminUserService::class)->updateAdmin($motherAdmin, $motherAdmin, [
        'name' => 'Renamed Mother Admin',
        'password' => 'new-password',
    ]);

    expect($updated->name)->toBe('Renamed Mother Admin')
        ->and(Hash::check('new-password', $updated->password))->toBeTrue();
});

test('self-editing mutates the authenticated actor instance in place, not just the database row', function () {
    // The controller passes two distinct object instances for the same user: $actor (request()->user())
    // and $admin (the route-bound model). Sanctum's AuthenticateSession middleware refreshes the
    // session's stored password hash from request()->user() after the response, so updateAdmin() must
    // mutate the $actor instance on self-edit, or the user's own session gets logged out next request.
    $admin = User::factory()->admin(Role::factory()->create())->create(['password' => 'old-password']);
    $actorInstance = User::find($admin->id);
    $routeBoundInstance = User::find($admin->id);

    $updated = app(AdminUserService::class)->updateAdmin($actorInstance, $routeBoundInstance, [
        'name' => 'Renamed',
        'password' => 'new-password',
    ]);

    expect($updated)->toBe($actorInstance)
        ->and($actorInstance->name)->toBe('Renamed')
        ->and(Hash::check('new-password', $actorInstance->password))->toBeTrue();
});

test('an admin can change their own name and password but not their role or email', function () {
    $role = Role::factory()->create();
    $admin = User::factory()->admin($role)->create(['email' => 'original@example.com', 'password' => 'old-password']);
    $otherRole = Role::factory()->create();

    $updated = app(AdminUserService::class)->updateAdmin($admin, $admin, [
        'name' => 'Renamed Admin',
        'password' => 'new-password',
        'email' => 'changed@example.com',
        'role_id' => $otherRole->id,
    ]);

    expect($updated->name)->toBe('Renamed Admin')
        ->and(Hash::check('new-password', $updated->password))->toBeTrue()
        ->and($updated->email)->toBe('original@example.com')
        ->and($updated->role_id)->toBe($role->id);
});

test('an admin cannot delete their own account', function () {
    $admin = User::factory()->admin(Role::factory()->create())->create();

    app(AdminUserService::class)->deleteAdmin($admin, $admin);
})->throws(AuthorizationException::class, 'This account cannot be deleted.');

test('the mother admin account cannot be deleted', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();
    $otherAdmin = User::factory()->admin(Role::factory()->create())->create();

    app(AdminUserService::class)->deleteAdmin($otherAdmin, $motherAdmin);
})->throws(AuthorizationException::class, 'This account cannot be deleted.');

test('an admin holding admins.manage can delete another non-mother admin', function () {
    $managerRole = Role::factory()->create();
    $managerRole->permissions()->attach(
        Permission::factory()->create(['slug' => 'admins.manage', 'group' => 'admins']),
    );
    $actor = User::factory()->admin($managerRole)->create();
    $target = User::factory()->admin(Role::factory()->create())->create();

    app(AdminUserService::class)->deleteAdmin($actor, $target);

    expect(User::query()->find($target->id))->toBeNull();
});

test('transferring mother admin requires the correct password', function () {
    $motherAdmin = User::factory()->motherAdmin()->create(['password' => 'current-password']);
    $successor = User::factory()->admin(Role::factory()->create())->create();

    app(AdminUserService::class)->transferMotherAdmin($motherAdmin, $successor, 'wrong-password');
})->throws(AuthorizationException::class, 'The provided password is incorrect.');

test('transferring mother admin moves the role and demotes the previous holder', function () {
    Role::factory()->create(['slug' => 'read-only-admin']);
    $motherAdmin = User::factory()->motherAdmin()->create(['password' => 'current-password']);
    $motherRoleId = $motherAdmin->role_id;
    $successor = User::factory()->admin(Role::factory()->create())->create();

    $promoted = app(AdminUserService::class)->transferMotherAdmin($motherAdmin, $successor, 'current-password');

    expect($promoted->role_id)->toBe($motherRoleId)
        ->and($promoted->fresh()->isMotherAdmin())->toBeTrue()
        ->and($motherAdmin->fresh()->isMotherAdmin())->toBeFalse();
});
