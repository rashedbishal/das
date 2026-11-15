<?php

namespace App\Policies;

use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('activity_logs.view');
    }

    /**
     * Wiping the audit trail is reserved for the Mother Admin.
     */
    public function clear(User $user): bool
    {
        return $user->isMotherAdmin();
    }
}
