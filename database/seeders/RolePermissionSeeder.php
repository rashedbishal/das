<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $permissions = [
        'products' => ['products.view', 'products.create', 'products.update', 'products.delete', 'products.publish'],
        'categories' => ['categories.view', 'categories.manage'],
        'brands' => ['brands.view', 'brands.manage'],
        'tags' => ['tags.view', 'tags.manage'],
        'media' => ['media.manage'],
        'customers' => ['customers.view', 'customers.manage'],
        'admins' => ['admins.view', 'admins.manage'],
        'roles' => ['roles.view', 'roles.manage'],
        'settings' => ['settings.view', 'settings.manage'],
        'content' => ['content.manage'],
        'seo' => ['seo.manage'],
        'activity_logs' => ['activity_logs.view'],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private array $roles = [
        'product-manager' => [
            'products.view', 'products.create', 'products.update', 'products.delete', 'products.publish',
            'categories.view', 'brands.view', 'tags.view', 'media.manage',
        ],
        'catalog-editor' => [
            'products.view', 'products.update', 'categories.view', 'categories.manage',
            'brands.view', 'brands.manage', 'tags.view', 'tags.manage', 'media.manage',
        ],
        'content-manager' => ['content.manage', 'seo.manage', 'settings.view'],
        'customer-manager' => ['customers.view', 'customers.manage'],
        'seo-manager' => ['seo.manage', 'products.view', 'categories.view'],
        'read-only-admin' => [
            'products.view', 'categories.view', 'brands.view', 'tags.view',
            'customers.view', 'admins.view', 'roles.view', 'settings.view', 'activity_logs.view',
        ],
    ];

    public function run(): void
    {
        foreach ($this->permissions as $group => $slugs) {
            foreach ($slugs as $slug) {
                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $this->humanize($slug), 'group' => $group],
                );
            }
        }

        $motherAdmin = Role::query()->updateOrCreate(
            ['slug' => Role::MOTHER_ADMIN],
            ['name' => 'Mother Admin', 'is_system' => true],
        );
        $motherAdmin->permissions()->sync(Permission::query()->pluck('id'));

        foreach ($this->roles as $slug => $permissionSlugs) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => ucwords(str_replace('-', ' ', $slug)), 'is_system' => false],
            );

            $role->permissions()->sync(
                Permission::query()->whereIn('slug', $permissionSlugs)->pluck('id'),
            );
        }
    }

    /**
     * Turn a "resource.action" permission slug into a human-readable label,
     * e.g. "products.create" -> "Create Products", "activity_logs.view" -> "View Activity Logs".
     */
    private function humanize(string $slug): string
    {
        [$resource, $action] = explode('.', $slug, 2);

        $resourceLabel = $resource === 'seo' ? 'SEO' : ucwords(str_replace('_', ' ', $resource));

        return ucfirst($action).' '.$resourceLabel;
    }
}
