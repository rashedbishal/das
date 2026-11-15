<?php

use App\Models\Role;
use App\Models\User;

test('the customer list includes every customer, newest first, across pages', function () {
    $viewer = adminUser(['customers.view']);
    $customers = collect(range(1, 30))->map(fn (int $day) => User::factory()->create(['created_at' => now()->subDays($day)]));

    $first = $this->actingAs($viewer)->getJson(route('api.v1.admin.customers.index'))
        ->assertOk()
        ->assertJsonPath('total', 30)
        ->assertJsonPath('last_page', 2)
        ->assertJsonCount(25, 'data');

    expect($first->json('data.0.id'))->toBe($customers->first()->id);

    $second = $this->actingAs($viewer)->getJson(route('api.v1.admin.customers.index', ['page' => 2]))
        ->assertJsonCount(5, 'data');

    expect($second->json('data.4.id'))->toBe($customers->last()->id)
        ->and(collect([...$first->json('data'), ...$second->json('data')])->pluck('id')->all())
        ->toBe($customers->pluck('id')->all());
});

test('customer search matches name, email or phone in any format, and never returns admins', function (string $term) {
    $viewer = adminUser(['customers.view']);
    $match = User::factory()->create(['name' => 'Nabila Rahman', 'email' => 'nabila@clinic.test', 'phone' => '01712345678']);
    User::factory()->create(['name' => 'Someone Else', 'email' => 'other@example.test', 'phone' => '01898765432']);
    User::factory()->admin(Role::factory()->create())->create(['name' => 'Nabila Admin', 'email' => 'nabila-admin@clinic.test']);

    $this->actingAs($viewer)->getJson(route('api.v1.admin.customers.index', ['search' => $term]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.id', $match->id);
})->with([
    'partial name, any case' => ['nabILA'],
    'email' => ['nabila@clinic'],
    'local phone' => ['01712345678'],
    'phone with country code and spaces' => ['+880 1712-345678'],
    'partial phone' => ['345678'],
]);

test('search treats % and _ literally and an unmatched search returns nothing', function () {
    $viewer = adminUser(['customers.view']);
    User::factory()->create(['name' => 'Plain Name']);
    $underscored = User::factory()->create(['email' => 'first_last@example.test']);

    $this->actingAs($viewer)->getJson(route('api.v1.admin.customers.index', ['search' => '%']))
        ->assertJsonPath('total', 0);

    $this->actingAs($viewer)->getJson(route('api.v1.admin.customers.index', ['search' => 'first_']))
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.id', $underscored->id);

    $this->actingAs($viewer)->getJson(route('api.v1.admin.customers.index', ['search' => 'zzz-nobody']))
        ->assertJsonPath('total', 0)
        ->assertJsonCount(0, 'data');
});

test('the admin list includes every admin, newest first, and can be searched', function () {
    $viewer = adminUser(['admins.view']);
    $role = Role::factory()->create();
    $older = User::factory()->admin($role)->create(['name' => 'Arif Older', 'created_at' => now()->subDays(3)]);
    $newer = User::factory()->admin($role)->create(['name' => 'Zara Newer', 'created_at' => now()->subDay()]);
    User::factory()->create(['name' => 'Zara Customer']);

    $this->actingAs($viewer)->getJson(route('api.v1.admin.admins.index'))
        ->assertOk()
        ->assertJsonPath('total', 3)
        ->assertJsonPath('data.0.id', $viewer->id)
        ->assertJsonPath('data.1.id', $newer->id)
        ->assertJsonPath('data.2.id', $older->id);

    $this->actingAs($viewer)->getJson(route('api.v1.admin.admins.index', ['search' => 'zara']))
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.0.role.id', $role->id);
});

test('the page size is capped at 100', function () {
    $viewer = adminUser(['customers.view']);

    $this->actingAs($viewer)->getJson(route('api.v1.admin.customers.index', ['per_page' => 5000]))
        ->assertJsonPath('per_page', 100);
});
