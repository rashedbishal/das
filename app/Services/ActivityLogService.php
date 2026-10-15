<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, ?Model $subject = null, ?string $description = null, array $properties = []): AdminActivityLog
    {
        return AdminActivityLog::query()->create([
            'user_id' => $this->request->user()?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
