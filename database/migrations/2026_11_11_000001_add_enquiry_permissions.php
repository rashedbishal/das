<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            'enquiries.view' => 'View Enquiries',
            'enquiries.manage' => 'Manage Enquiries',
        ];

        foreach ($permissions as $slug => $name) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'group' => 'enquiries', 'created_at' => $now, 'updated_at' => $now],
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_keys($permissions))
            ->pluck('id', 'slug');
        $rolePermissions = [
            'content-manager' => ['enquiries.view', 'enquiries.manage'],
            'customer-manager' => ['enquiries.view', 'enquiries.manage'],
            'read-only-admin' => ['enquiries.view'],
        ];

        foreach ($rolePermissions as $roleSlug => $permissionSlugs) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if ($roleId === null) {
                continue;
            }

            foreach ($permissionSlugs as $permissionSlug) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionIds[$permissionSlug],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['enquiries.view', 'enquiries.manage'])
            ->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
