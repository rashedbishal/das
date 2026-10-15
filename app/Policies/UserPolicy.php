<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAnyAdmins(User $user): bool
    {
        return $user->hasPermission('admins.view');
    }

    public function viewAnyCustomers(User $user): bool
    {
        return $user->hasPermission('customers.view');
    }

    public function view(User $user, User $model): bool
    {
        return $model->isAdmin()
            ? $user->hasPermission('admins.view')
            : $user->hasPermission('customers.view');
    }

    public function createAdmin(User $user): bool
    {
        return $user->hasPermission('admins.manage');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return true;
        }

        if ($model->isMotherAdmin()) {
            return false;
        }

        return $model->isAdmin()
            ? $user->hasPermission('admins.manage')
            : $user->hasPermission('customers.manage');
    }

    public function deactivate(User $user, User $model): bool
    {
        if ($model->isMotherAdmin() || $user->is($model)) {
            return false;
        }

        return $model->isAdmin()
            ? $user->hasPermission('admins.manage')
            : $user->hasPermission('customers.manage');
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->isMotherAdmin() || $user->is($model)) {
            return false;
        }

        return $model->isAdmin()
            ? $user->hasPermission('admins.manage')
            : $user->hasPermission('customers.manage');
    }

    public function assignRole(User $user, User $model): bool
    {
        return ! $model->isMotherAdmin() && $user->hasPermission('admins.manage');
    }
}
