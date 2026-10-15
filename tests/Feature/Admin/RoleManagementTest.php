<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Auth\Access\AuthorizationException;

test('mother admin can create a role and grant any permission', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();
    $permission = Permission::factory()->create(['slug' => 'products.create', 'group' => 'products']);

    $role = app(RoleService::class)->create($motherAdmin, ['name' => 'Product Manager', 'slug' => 'product-manager'], [$permission->slug]);

    expect($role->permissions->pluck('slug'))->toContain('products.create');
});

test('an admin cannot grant a permission they do not hold', function () {
    $heldPermission = Permission::factory()->create(['slug' => 'products.view', 'group' => 'products']);
    $unheldPermission = Permission::factory()->create(['slug' => 'admins.manage', 'group' => 'admins']);

    $role = Role::factory()->create();
    $role->permissions()->attach($heldPermission);
    $admin = User::factory()->admin($role)->create();

    app(RoleService::class)->create($admin, ['name' => 'New Role', 'slug' => 'new-role'], [$unheldPermission->slug]);
})->throws(AuthorizationException::class);

test('an admin can grant a role containing only permissions they already hold', function () {
    $permission = Permission::factory()->create(['slug' => 'products.view', 'group' => 'products']);

    $role = Role::factory()->create();
    $role->permissions()->attach($permission);
    $admin = User::factory()->admin($role)->create();

    $newRole = app(RoleService::class)->create($admin, ['name' => 'Viewer', 'slug' => 'viewer'], [$permission->slug]);

    expect($newRole->permissions->pluck('slug'))->toContain('products.view');
});

test('the mother admin role cannot be updated', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();

    app(RoleService::class)->update($motherAdmin, $motherAdmin->role, ['name' => 'Renamed'], []);
})->throws(AuthorizationException::class, 'The Mother Admin role cannot be modified.');

test('the mother admin role cannot be deleted', function () {
    $motherAdmin = User::factory()->motherAdmin()->create();

    app(RoleService::class)->delete($motherAdmin->role);
})->throws(AuthorizationException::class, 'The Mother Admin role cannot be deleted.');

test('a role still assigned to an admin cannot be deleted', function () {
    $role = Role::factory()->create();
    User::factory()->admin($role)->create();

    app(RoleService::class)->delete($role);
})->throws(AuthorizationException::class);

test('an admin without the roles.manage permission cannot create roles', function () {
    $role = Role::factory()->create();
    $admin = User::factory()->admin($role)->create();

    expect($admin->can('create', Role::class))->toBeFalse();
});
