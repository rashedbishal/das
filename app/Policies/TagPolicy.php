<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tags.view');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->hasPermission('tags.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tags.manage');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->hasPermission('tags.manage');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->hasPermission('tags.manage');
    }
}
