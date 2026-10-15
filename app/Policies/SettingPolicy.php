<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    public function view(User $user): bool
    {
        return $user->hasPermission('settings.view') || $user->hasPermission('seo.manage');
    }

    public function manageGeneral(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }

    public function manageAuth(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }

    public function manageWhatsapp(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }

    public function manageMessenger(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }

    public function manageCatalogSeo(User $user): bool
    {
        return $user->hasPermission('settings.manage') || $user->hasPermission('seo.manage');
    }

    public function manageTracking(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }
}
