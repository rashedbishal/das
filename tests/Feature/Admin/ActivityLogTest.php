<?php

use App\Models\Activity;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Services\AdminUserService;
use App\Services\RoleService;

/**
 * @return array<string, mixed>
 */
function productUpdatePayload(Product $product, array $overrides = []): array
{
    return [
        'name' => $product->name,
        'sku' => $product->sku,
        'status' => $product->status,
        'stock_status' => $product->stock_status,
        'category_id' => $product->category_id,
        ...$overrides,
    ];
}

test('updating a record logs who changed which fields, with old and new values', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create(['name' => 'Old Chair', 'category_id' => Category::factory()]);
    Activity::query()->delete();

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), productUpdatePayload($product, ['name' => 'New Chair']))
        ->assertSessionHasNoErrors();

    $activity = Activity::query()->where('event', 'updated')->sole();
    expect($activity->log_name)->toBe('catalogue')
        ->and($activity->description)->toBe('Updated product New Chair')
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($product->id)
        ->and($activity->properties['old'])->toBe(['name' => 'Old Chair'])
        ->and($activity->properties['attributes'])->toBe(['name' => 'New Chair'])
        ->and($activity->ip_address)->toBe('127.0.0.1');
});

test('saving a record without changes logs nothing', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create(['category_id' => Category::factory()]);
    Activity::query()->delete();

    $this->actingAs($admin)->put(route('admin.products.update', $product), productUpdatePayload($product));

    expect(Activity::query()->count())->toBe(0);
});

test('relation-only changes to a product are logged', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create(['category_id' => Category::factory()]);
    $tag = Tag::factory()->create();
    Activity::query()->delete();

    $this->actingAs($admin)->put(route('admin.products.update', $product), productUpdatePayload($product, ['tag_ids' => [$tag->id]]));

    $activity = Activity::query()->sole();
    expect($activity->event)->toBe('relations_updated')
        ->and($activity->description)->toBe("Updated tags of product {$product->name}");
});

test('creating, trashing, restoring and permanently deleting are each logged', function () {
    $admin = adminUser(['products.delete']);
    $product = Product::factory()->create(['name' => 'Scaler']);

    $this->actingAs($admin)->delete(route('admin.products.destroy', $product));
    $this->actingAs($admin)->post(route('admin.products.restore', $product));
    $this->actingAs($admin)->delete(route('admin.products.destroy', $product));
    $this->actingAs($admin)->delete(route('admin.products.force-destroy', $product));

    expect(Activity::query()->where('subject_id', $product->id)->where('subject_type', $product->getMorphClass())->orderBy('id')->pluck('description')->all())
        ->toBe([
            'Created product Scaler',
            'Moved to trash: product Scaler',
            'Restored product Scaler',
            'Moved to trash: product Scaler',
            'Permanently deleted product Scaler',
        ]);
});

test('passwords are never written to the log, only that one changed', function () {
    $admin = User::factory()->admin(Role::factory()->create())->create();
    Activity::query()->delete();

    $this->actingAs($admin)->putJson(route('api.v1.admin.admins.update', $admin), [
        'name' => $admin->name,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ])->assertOk();

    expect(Activity::query()->pluck('event')->all())->toBe(['password_changed'])
        ->and(Activity::query()->get()->toJson())->not->toContain('new-secret-password')
        ->and(Activity::query()->get()->pluck('properties')->toJson())->not->toContain('password":');
});

test('account status changes and role permission changes are logged once, with their details', function () {
    $mother = User::factory()->motherAdmin()->create();
    $admin = User::factory()->admin(Role::factory()->create())->create(['name' => 'Rafi']);
    Activity::query()->delete();

    app(AdminUserService::class)->deactivateAdmin($mother, $admin);

    expect(Activity::query()->pluck('description')->all())->toBe(['Deactivated admin Rafi']);

    Permission::factory()->create(['slug' => 'products.view', 'group' => 'products']);
    $mother->role->permissions()->sync(Permission::query()->pluck('id'));
    Activity::query()->delete();
    $this->actingAs($mother);
    $role = app(RoleService::class)->create($mother, ['name' => 'Editors'], ['products.view']);
    app(RoleService::class)->update($mother, $role, ['name' => 'Editors'], []);

    $entries = Activity::query()->orderBy('id')->get();
    expect($entries->pluck('event')->all())->toBe(['created', 'permissions_updated'])
        ->and($entries[0]->properties['permissions'])->toBe(['products.view'])
        ->and($entries[1]->properties['revoked'])->toBe(['products.view'])
        ->and($entries[1]->properties['granted'])->toBe([]);
});

test('admin and customer sign-ins and sign-outs are logged', function (string $userType, string $loginRoute) {
    $user = $userType === 'admin'
        ? User::factory()->admin(Role::factory()->create())->create()
        : User::factory()->create();

    $this->post(route($loginRoute), ['email' => $user->email, 'password' => 'password']);
    $this->assertAuthenticatedAs($user);
    $this->post(route('logout'));

    $entries = Activity::query()->where('log_name', 'auth')->orderBy('id')->get();
    expect($entries->pluck('event')->all())->toBe(['login', 'logout'])
        ->and($entries->pluck('causer_id')->unique()->all())->toBe([$user->id])
        ->and($entries[0]->description)->toBe(ucfirst($userType)." {$user->name} signed in")
        ->and($entries[0]->ip_address)->not->toBeNull();
})->with([
    'admin' => ['admin', 'admin.login.store'],
    'customer' => ['customer', 'login.store'],
]);

test('failed sign-ins log the identifier but never the password', function () {
    User::factory()->create(['email' => 'doctor@example.test']);

    $this->post(route('login.store'), ['email' => 'doctor@example.test', 'password' => 'wrong-password-123']);
    $this->postJson(route('api.v1.auth.login'), ['identifier' => 'nobody@example.test', 'password' => 'wrong-password-456']);

    $entries = Activity::query()->where('event', 'login_failed')->orderBy('id')->get();
    expect($entries)->toHaveCount(2)
        ->and($entries[0]->description)->toBe('Failed sign-in attempt for doctor@example.test')
        ->and($entries[1]->properties['identifier'])->toBe('nobody@example.test')
        ->and($entries->toJson())->not->toContain('wrong-password');
});

test('customer registration and enquiry submissions are logged', function () {
    $this->post(route('register.store'), [
        'name' => 'Dr Nabila',
        'email' => 'nabila@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
    $this->post(route('logout'));

    $this->post(route('contact.enquiries.store'), [
        'name' => 'Dr. Example',
        'phone' => '+8801712345678',
        'email' => 'doctor@example.test',
        'message' => 'Please send more information.',
    ]);

    expect(Activity::query()->where('description', 'Customer Dr Nabila registered an account')->exists())->toBeTrue()
        ->and(Activity::query()->where('log_name', 'enquiries')->where('event', 'created')->exists())->toBeTrue()
        ->and(Activity::query()->toBase()->pluck('properties')->implode(''))->not->toContain('"password"');
});

test('settings changes are logged under content', function () {
    $admin = adminUser(['settings.manage']);

    $this->actingAs($admin)->put(route('admin.settings.general.update'), [
        'site_name' => 'DAS Dental',
        'currency' => 'BDT',
        'timezone' => 'Asia/Dhaka',
    ]);

    expect(Activity::query()->where('log_name', 'content')->where('description', 'Created setting general.site_name')->exists())->toBeTrue();
});

test('the activity log page lists entries with their changes and filters by category and search', function () {
    $admin = adminUser(['activity_logs.view']);
    $product = Product::factory()->create(['name' => 'Old Chair']);
    $product->update(['name' => 'New Chair']);
    activity('auth')->event('login')->log('Customer Someone signed in');

    $this->actingAs($admin)
        ->get(route('admin.activity-log.index', ['category' => 'catalogue', 'search' => 'new chair']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/activity-log/Index')
            ->where('activities.total', 1)
            ->where('activities.data.0.description', 'Updated product New Chair')
            ->where('activities.data.0.changes.0', ['field' => 'Name', 'old' => 'Old Chair', 'new' => 'New Chair'])
            ->where('filters.category', 'catalogue')
            ->where('canClear', false));

    $this->actingAs($admin)
        ->get(route('admin.activity-log.index', ['category' => 'auth']))
        ->assertInertia(fn ($page) => $page->where('activities.total', 1));
});

test('the activity log page requires activity_logs.view', function () {
    $this->actingAs(adminUser(['products.view']))->get(route('admin.activity-log.index'))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('admin.activity-log.index'))->assertForbidden();
});

test('only the Mother Admin can delete all logs, which leaves one entry recording it', function () {
    Product::factory()->count(3)->create();
    $viewer = adminUser(['activity_logs.view']);
    $total = Activity::query()->count();

    $this->actingAs($viewer)->delete(route('admin.activity-log.destroy'))->assertForbidden();
    expect(Activity::query()->count())->toBe($total);

    $mother = User::factory()->motherAdmin()->create();
    $total = Activity::query()->count();

    $this->actingAs($mother)
        ->delete(route('admin.activity-log.destroy'))
        ->assertRedirect(route('admin.activity-log.index'));

    $remaining = Activity::query()->sole();
    expect($remaining->event)->toBe('cleared')
        ->and($remaining->causer_id)->toBe($mother->id)
        ->and($remaining->properties['deleted'])->toBe($total);

    $this->actingAs($mother)
        ->get(route('admin.activity-log.index'))
        ->assertInertia(fn ($page) => $page->where('canClear', true));
});

test('the nightly clean-up removes entries older than 365 days', function () {
    activity()->createdAt(now()->subDays(366))->log('Old entry');
    activity()->createdAt(now()->subDays(364))->log('Recent entry');

    $this->artisan('activitylog:clean', ['--force' => true])->assertSuccessful();

    expect(Activity::query()->pluck('description')->all())->toBe(['Recent entry']);
});

test('the dashboard shows recent activity from the log to admins who may view it', function () {
    $admin = adminUser(['activity_logs.view']);
    activity()->causedBy($admin)->log('Did something');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('recentActivity.0.description', 'Did something')
            ->where('recentActivity.0.user.name', $admin->name));
});
