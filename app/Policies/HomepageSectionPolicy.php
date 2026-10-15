<?php

namespace App\Policies;

use App\Models\HomepageSection;
use App\Models\User;

class HomepageSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('content.manage');
    }

    public function view(User $user, HomepageSection $homepageSection): bool
    {
        return $user->hasPermission('content.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('content.manage');
    }

    public function update(User $user, HomepageSection $homepageSection): bool
    {
        return $user->hasPermission('content.manage');
    }

    public function delete(User $user, HomepageSection $homepageSection): bool
    {
        return $user->hasPermission('content.manage');
    }
}
