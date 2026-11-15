<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\SlugGenerator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;

class RoleService
{
    public function __construct(private readonly ActivityLogService $activity) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $permissionSlugs
     */
    public function create(User $actor, array $data, array $permissionSlugs): Role
    {
        $this->assertCanGrantPermissions($actor, $permissionSlugs);

        $slug = ! empty($data['slug'])
            ? SlugGenerator::unique('roles', $data['slug'])
            : SlugGenerator::unique('roles', $data['name']);

        // Logged once below, together with the permissions it was given.
        $role = activity()->withoutLogs(fn () => Role::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'is_system' => false,
        ]));

        $role->permissions()->sync(
            Permission::query()->whereIn('slug', $permissionSlugs)->pluck('id'),
        );

        $this->activity->log('role.created', $role, "Created role {$role->name}", [
            'attributes' => ['name' => $role->name, 'slug' => $role->slug],
            'permissions' => $role->permissions()->orderBy('slug')->pluck('slug')->all(),
        ]);

        return $role;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $permissionSlugs
     */
    public function update(User $actor, Role $role, array $data, array $permissionSlugs): Role
    {
        if ($role->is_system) {
            throw new AuthorizationException('The Mother Admin role cannot be modified.');
        }

        $this->assertCanGrantPermissions($actor, $permissionSlugs);

        $role->update(Arr::only($data, ['name', 'slug']));

        $result = $role->permissions()->sync(
            Permission::query()->whereIn('slug', $permissionSlugs)->pluck('id'),
        );

        if ($result['attached'] !== [] || $result['detached'] !== []) {
            $slugs = fn (array $ids) => Permission::query()->whereKey($ids)->orderBy('slug')->pluck('slug')->all();

            $this->activity->log('role.permissions_updated', $role, "Changed the permissions of role {$role->name}", [
                'granted' => $slugs($result['attached']),
                'revoked' => $slugs($result['detached']),
            ]);
        }

        return $role;
    }

    public function delete(Role $role): void
    {
        if ($role->is_system) {
            throw new AuthorizationException('The Mother Admin role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            throw new AuthorizationException('This role is still assigned to one or more admins and cannot be deleted.');
        }

        $role->delete();
    }

    /**
     * @param  array<int, string>  $permissionSlugs
     */
    private function assertCanGrantPermissions(User $actor, array $permissionSlugs): void
    {
        if ($actor->isMotherAdmin()) {
            return;
        }

        foreach ($permissionSlugs as $slug) {
            if (! $actor->hasPermission($slug)) {
                throw new AuthorizationException("You cannot grant the \"{$slug}\" permission because you do not hold it yourself.");
            }
        }
    }
}
