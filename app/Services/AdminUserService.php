<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    public function __construct(private readonly ActivityLogService $activity) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAdmin(User $actor, array $data): User
    {
        $role = Role::query()->findOrFail((int) $data['role_id']);
        $this->assertCanAssignRole($actor, $role);

        $admin = User::query()->create([
            ...Arr::only($data, ['name', 'email', 'phone']),
            'password' => $data['password'],
            'user_type' => 'admin',
            'role_id' => $role->id,
            'is_active' => true,
            'email_verified_at' => now(),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->activity->log('admin.created', $admin, "Created admin {$admin->name}");

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAdmin(User $actor, User $admin, array $data): User
    {
        $isSelf = $actor->is($admin);

        if ($admin->isMotherAdmin() && ! $isSelf) {
            throw new AuthorizationException('The Mother Admin account cannot be modified.');
        }

        if ($isSelf) {
            // Operate on $actor (the in-memory authenticated user instance), not the separately
            // route-bound $admin: Sanctum's AuthenticateSession middleware refreshes the current
            // session's stored password hash from $request->user() after the response, so mutating
            // a different object instance would leave the current session logged out on password change.
            $admin = $actor;

            // Admins editing their own account (Mother Admin included) may only change their name and password.
            $admin->fill(Arr::only($data, ['name']));

            if (! empty($data['password'])) {
                $admin->password = $data['password'];
            }
        } else {
            if (array_key_exists('role_id', $data) && $data['role_id'] !== $admin->role_id) {
                $role = Role::query()->findOrFail((int) $data['role_id']);
                $this->assertCanAssignRole($actor, $role);
            }

            $admin->fill(Arr::only($data, ['name', 'email', 'phone', 'role_id']));
        }

        $admin->updated_by = $actor->id;
        $admin->save();

        $this->activity->log('admin.updated', $admin, "Updated admin {$admin->name}");

        return $admin;
    }

    public function deleteAdmin(User $actor, User $admin): void
    {
        if ($admin->isMotherAdmin() || $actor->is($admin)) {
            throw new AuthorizationException('This account cannot be deleted.');
        }

        $this->activity->log('admin.deleted', $admin, "Deleted admin {$admin->name}");

        $admin->delete();
    }

    public function deactivateAdmin(User $actor, User $admin): User
    {
        if ($admin->isMotherAdmin() || $actor->is($admin)) {
            throw new AuthorizationException('This account cannot be deactivated.');
        }

        $admin->update(['is_active' => false, 'updated_by' => $actor->id]);

        $this->activity->log('admin.deactivated', $admin, "Deactivated admin {$admin->name}");

        return $admin;
    }

    public function reactivateAdmin(User $actor, User $admin): User
    {
        $admin->update(['is_active' => true, 'updated_by' => $actor->id]);

        $this->activity->log('admin.reactivated', $admin, "Reactivated admin {$admin->name}");

        return $admin;
    }

    public function transferMotherAdmin(User $currentMotherAdmin, User $newMotherAdmin, string $confirmationPassword): User
    {
        if (! $currentMotherAdmin->isMotherAdmin()) {
            throw new AuthorizationException('Only the current Mother Admin can transfer ownership.');
        }

        if (! Hash::check($confirmationPassword, $currentMotherAdmin->password)) {
            throw new AuthorizationException('The provided password is incorrect.');
        }

        if (! $newMotherAdmin->isAdmin() || ! $newMotherAdmin->is_active) {
            throw new AuthorizationException('Mother Admin can only be transferred to an active administrator account.');
        }

        $motherRoleId = $currentMotherAdmin->role_id;
        $fallbackRoleId = Role::query()->where('slug', 'read-only-admin')->value('id');

        DB::transaction(function () use ($currentMotherAdmin, $newMotherAdmin, $motherRoleId, $fallbackRoleId) {
            $newMotherAdmin->update(['role_id' => $motherRoleId]);
            $currentMotherAdmin->update(['role_id' => $fallbackRoleId]);
        });

        $this->activity->log(
            'admin.mother_admin_transferred',
            $newMotherAdmin,
            "Mother Admin transferred from {$currentMotherAdmin->name} to {$newMotherAdmin->name}",
        );

        return $newMotherAdmin->fresh();
    }

    private function assertCanAssignRole(User $actor, Role $role): void
    {
        if ($role->isMotherAdmin()) {
            throw new AuthorizationException('The Mother Admin role can only be assigned through ownership transfer.');
        }

        if ($actor->isMotherAdmin()) {
            return;
        }

        foreach ($role->permissions as $permission) {
            if (! $actor->hasPermission($permission->slug)) {
                throw new AuthorizationException("You cannot assign the \"{$role->name}\" role because it grants the \"{$permission->slug}\" permission, which you do not hold.");
            }
        }
    }
}
