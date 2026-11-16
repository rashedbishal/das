<?php

use App\Models\Permission;
use App\Models\User;

test('admins receive their permission slugs so the ui can hide actions they cannot take', function () {
    $admin = adminUser(['categories.view', 'brands.manage']);

    $this->actingAs($admin)
        ->get(route('admin.categories.index'))
        ->assertInertia(fn ($page) => $page->where('auth.permissions', ['brands.manage', 'categories.view']));
});

test('the mother admin receives every permission slug', function () {
    Permission::query()->firstOrCreate(['slug' => 'tags.manage'], ['name' => 'Manage tags', 'group' => 'tags']);
    $motherAdmin = User::factory()->motherAdmin()->create();

    $this->actingAs($motherAdmin)
        ->get(route('admin.settings.edit'))
        ->assertInertia(fn ($page) => $page->where(
            'auth.permissions',
            Permission::query()->orderBy('slug')->pluck('slug')->all(),
        ));
});

test('customers and guests receive no permissions', function () {
    $this->get(route('about'))->assertInertia(fn ($page) => $page->where('auth.permissions', []));

    $this->actingAs(User::factory()->create())
        ->get(route('about'))
        ->assertInertia(fn ($page) => $page->where('auth.permissions', []));
});
