<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes activity log entries that are not plain model changes (those are logged
 * automatically by App\Models\Concerns\LogsModelActivity): stock adjustments,
 * account status changes, relation changes, sign-ins and sign-outs, etc.
 */
class ActivityLogService
{
    /** Action prefix => log name (the category filter on Admin -> Activity Log). */
    private const LOG_NAMES = [
        'auth' => 'auth',
        'product' => 'catalogue',
        'admin' => 'users',
        'role' => 'users',
        'customer' => 'users',
        'activity_log' => 'system',
    ];

    /**
     * @param  string  $action  e.g. "product.stock_added": the part before the dot picks the category, the rest is the event
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?Model $causer = null,
    ): ?Activity {
        [$prefix, $event] = explode('.', $action, 2) + [1 => $action];

        $logger = activity(self::LOG_NAMES[$prefix] ?? 'catalogue')
            ->event($event)
            ->withProperties($properties);

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        if ($causer !== null) {
            $logger->causedBy($causer);
        }

        /** @var Activity|null */
        return $logger->log($description ?? $action);
    }
}
