<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Records every create, update, delete (and restore, for soft-deleting models) in
 * the activity log, with the changed fields' old and new values.
 *
 * A model may define:
 * - `protected string $activityLogName` – the log/category (default "catalogue").
 * - `protected array $activityLogExcept` – fillable fields never written to the log.
 * - `activityLabel(): string` – how the record is named in descriptions.
 */
trait LogsModelActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['created_by', 'updated_by', ...($this->activityLogExcept ?? [])])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->activityLogName ?? 'catalogue')
            ->setDescriptionForEvent(fn (string $eventName) => $this->activityDescription($eventName));
    }

    /**
     * e.g. "Updated product Dental Chair X1".
     */
    protected function activityDescription(string $eventName): string
    {
        $type = $this->activityType();
        $label = $this->activityLabel();
        $verb = ucfirst($eventName);

        if ($eventName === 'deleted' && method_exists($this, 'isForceDeleting')) {
            $verb = $this->isForceDeleting() ? 'Permanently deleted' : 'Moved to trash:';
        }

        return trim("{$verb} {$type} {$label}");
    }

    /**
     * The kind of record, e.g. "homepage section".
     */
    protected function activityType(): string
    {
        return Str::of(class_basename($this))->snake(' ')->toString();
    }

    protected function activityLabel(): string
    {
        foreach (['name', 'title', 'sku'] as $attribute) {
            $value = $this->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '#'.$this->getKey();
    }
}
