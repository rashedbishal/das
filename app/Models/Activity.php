<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * An activity log entry (spatie/laravel-activitylog), plus the IP address and
 * browser of the request that caused it.
 *
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class Activity extends SpatieActivity
{
    protected static function booted(): void
    {
        static::creating(function (Activity $activity) {
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            $request = request();
            $activity->ip_address ??= $request->ip();
            $activity->user_agent ??= mb_substr((string) $request->userAgent(), 0, 500) ?: null;
        });
    }
}
