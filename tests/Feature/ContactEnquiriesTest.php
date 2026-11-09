<?php

use App\Models\ContactEnquiry;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

test('visitors can submit an enquiry which is saved as unread', function () {
    $response = $this->from(route('contact'))->post(route('contact.enquiries.store'), [
        'name' => 'Dr. Example',
        'business_name' => 'Example Dental',
        'phone' => '+8801712345678',
        'email' => 'doctor@example.test',
        'product_name' => 'Dental instrument',
        'message' => 'Please send more information.',
    ]);

    $response->assertRedirect(route('contact'));
    $this->assertDatabaseHas('contact_enquiries', [
        'name' => 'Dr. Example',
        'business_name' => 'Example Dental',
        'phone' => '+8801712345678',
        'email' => 'doctor@example.test',
        'product_name' => 'Dental instrument',
        'message' => 'Please send more information.',
        'is_read' => false,
    ]);
});

test('invalid enquiries are not stored', function () {
    $response = $this->from(route('contact'))->post(route('contact.enquiries.store'), [
        'name' => '',
        'phone' => '',
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors(['name', 'phone', 'email']);
    expect(ContactEnquiry::query()->count())->toBe(0);
});

test('public enquiry submissions are rate limited', function () {
    $payload = ['name' => 'Customer', 'phone' => '01712345678'];

    foreach (range(1, 5) as $attempt) {
        $this->post(route('contact.enquiries.store'), $payload)->assertRedirect();
    }

    $this->post(route('contact.enquiries.store'), $payload)->assertTooManyRequests();

    expect(ContactEnquiry::query()->count())->toBe(5);
});

test('an admin with enquiry view permission can read the inbox but cannot change records', function () {
    $enquiry = ContactEnquiry::query()->create([
        'name' => 'Customer',
        'phone' => '01712345678',
    ]);
    $admin = adminUser(['enquiries.view']);

    $this->actingAs($admin)
        ->get(route('admin.enquiries.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/enquiries/Index')
            ->where('unreadCount', 1)
            ->where('canManage', false)
            ->where('enquiries.data.0.id', $enquiry->id));

    $this->actingAs($admin)
        ->patch(route('admin.enquiries.update', $enquiry), ['is_read' => true])
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete(route('admin.enquiries.destroy', $enquiry))
        ->assertForbidden();
});

test('an admin with enquiry manage permission can mark enquiries read and unread and delete them', function () {
    $enquiry = ContactEnquiry::query()->create([
        'name' => 'Customer',
        'phone' => '01712345678',
    ]);
    $admin = adminUser(['enquiries.manage']);

    $this->actingAs($admin)
        ->patch(route('admin.enquiries.update', $enquiry), ['is_read' => true])
        ->assertRedirect();
    expect($enquiry->fresh()->is_read)->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.enquiries.update', $enquiry), ['is_read' => false])
        ->assertRedirect();
    expect($enquiry->fresh()->is_read)->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('admin.enquiries.destroy', $enquiry))
        ->assertRedirect();
    $this->assertDatabaseMissing('contact_enquiries', ['id' => $enquiry->id]);
});

test('an admin can bulk mark enquiries read and unread and delete them', function () {
    $enquiries = collect(range(1, 3))->map(
        fn (int $number) => ContactEnquiry::query()->create([
            'name' => "Customer {$number}",
            'phone' => '01712345678',
        ]),
    );
    $admin = adminUser(['enquiries.manage']);

    $this->actingAs($admin)
        ->post(route('admin.enquiries.bulk'), [
            'ids' => $enquiries->pluck('id')->all(),
            'action' => 'mark_read',
        ])
        ->assertRedirect();
    expect(ContactEnquiry::query()->whereIn('id', $enquiries->pluck('id'))->where('is_read', false)->count())
        ->toBe(0);

    $this->actingAs($admin)
        ->post(route('admin.enquiries.bulk'), [
            'ids' => $enquiries->pluck('id')->all(),
            'action' => 'mark_unread',
        ])
        ->assertRedirect();
    expect(ContactEnquiry::query()->whereIn('id', $enquiries->pluck('id'))->where('is_read', true)->count())
        ->toBe(0);

    $this->actingAs($admin)
        ->post(route('admin.enquiries.bulk'), [
            'ids' => $enquiries->pluck('id')->all(),
            'action' => 'delete',
        ])
        ->assertRedirect();
    expect(ContactEnquiry::query()->whereIn('id', $enquiries->pluck('id'))->count())->toBe(0);
});

test('bulk enquiry operations require manage permission and validate selection', function () {
    $enquiry = ContactEnquiry::query()->create([
        'name' => 'Customer',
        'phone' => '01712345678',
    ]);
    $viewer = adminUser(['enquiries.view']);

    $this->actingAs($viewer)
        ->post(route('admin.enquiries.bulk'), [
            'ids' => [$enquiry->id],
            'action' => 'mark_read',
        ])
        ->assertForbidden();

    $manager = adminUser(['enquiries.manage']);
    $this->actingAs($manager)
        ->post(route('admin.enquiries.bulk'), [
            'ids' => [$enquiry->id, $enquiry->id],
            'action' => 'delete',
        ])
        ->assertSessionHasErrors('ids.1');

    $this->actingAs($manager)
        ->post(route('admin.enquiries.bulk'), [
            'ids' => [],
            'action' => 'delete',
        ])
        ->assertSessionHasErrors('ids');

    expect($enquiry->fresh())->not->toBeNull();
});

test('customers cannot access the enquiry inbox', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.enquiries.index'))
        ->assertForbidden();
});

test('default admin roles receive appropriate enquiry permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $contentManager = Role::query()->where('slug', 'content-manager')->firstOrFail();
    $customerManager = Role::query()->where('slug', 'customer-manager')->firstOrFail();
    $readOnlyAdmin = Role::query()->where('slug', 'read-only-admin')->firstOrFail();

    expect($contentManager->hasPermission('enquiries.manage'))->toBeTrue()
        ->and($customerManager->hasPermission('enquiries.manage'))->toBeTrue()
        ->and($readOnlyAdmin->hasPermission('enquiries.view'))->toBeTrue()
        ->and($readOnlyAdmin->hasPermission('enquiries.manage'))->toBeFalse();
});
