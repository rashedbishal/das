<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    /**
     * Anyone who may change settings may also see them, so settings.manage implies settings.view.
     */
    public function view(User $user): bool
    {
        return $user->hasPermission('settings.view')
            || $user->hasPermission('settings.manage')
            || $user->hasPermission('seo.manage');
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

    /**
     * Tracking snippets are raw, unescaped HTML/JS served to every visitor, so only the
     * Mother Admin may edit them; settings.manage is delegable and must not be enough.
     */
    public function manageTracking(User $user): bool
    {
        return $user->isMotherAdmin();
    }
}
