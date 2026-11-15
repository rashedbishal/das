<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin -> Activity Log: every recorded change, sign-in and sign-out, newest first.
 */
class ActivityLogController extends Controller
{
    private const PER_PAGE = 50;

    /** Log name => label, in filter order. */
    public const CATEGORIES = [
        'auth' => 'Sign-ins',
        'catalogue' => 'Catalogue',
        'content' => 'Content & settings',
        'users' => 'Users & roles',
        'enquiries' => 'Enquiries',
        'system' => 'System',
    ];

    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Activity::class);

        $filters = [
            'search' => trim($request->string('search')->toString()),
            'category' => array_key_exists($request->string('category')->toString(), self::CATEGORIES)
                ? $request->string('category')->toString()
                : '',
            'from' => $this->date($request->string('from')->toString()),
            'to' => $this->date($request->string('to')->toString()),
        ];

        $activities = Activity::query()
            ->with('causer')
            ->when($filters['category'] !== '', fn (Builder $query) => $query->where('log_name', $filters['category']))
            ->when($filters['from'] !== '', fn (Builder $query) => $query->where('created_at', '>=', CarbonImmutable::parse($filters['from'])->startOfDay()))
            ->when($filters['to'] !== '', fn (Builder $query) => $query->where('created_at', '<=', CarbonImmutable::parse($filters['to'])->endOfDay()))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['search']).'%';

                $query->where(fn (Builder $inner) => $inner
                    ->whereRaw("description like ? escape '!'", [$pattern])
                    ->orWhereRaw("ip_address like ? escape '!'", [$pattern])
                    ->orWhereHasMorph('causer', [User::class], fn (Builder $causer) => $causer
                        ->whereRaw("name like ? escape '!'", [$pattern])
                        ->orWhereRaw("email like ? escape '!'", [$pattern])));
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Activity $activity) => $this->present($activity));

        return Inertia::render('admin/activity-log/Index', [
            'activities' => $activities,
            'filters' => $filters,
            'categories' => collect(self::CATEGORIES)->map(fn (string $label, string $value) => compact('value', 'label'))->values(),
            'canClear' => $request->user()?->can('clear', Activity::class) ?? false,
        ]);
    }

    /**
     * Deletes every entry, then records who cleared the log and how many entries went.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authorize('clear', Activity::class);

        $deleted = Activity::query()->delete();

        $this->activity->log('activity_log.cleared', null, "Cleared the activity log ({$deleted} entries deleted)", [
            'deleted' => $deleted,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Activity log cleared ({$deleted} entries deleted)."]);

        return to_route('admin.activity-log.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Activity $activity): array
    {
        $properties = $activity->properties?->toArray() ?? [];
        $new = is_array($properties['attributes'] ?? null) ? $properties['attributes'] : [];
        $old = is_array($properties['old'] ?? null) ? $properties['old'] : [];

        $changes = collect(array_unique([...array_keys($old), ...array_keys($new)]))
            ->map(fn (string $field) => [
                'field' => Str::of($field)->replace('_', ' ')->ucfirst()->toString(),
                'old' => array_key_exists($field, $old) ? $this->display($old[$field]) : null,
                'new' => array_key_exists($field, $new) ? $this->display($new[$field]) : null,
            ])
            ->values();

        $details = collect($properties)
            ->except(['attributes', 'old'])
            ->map(fn (mixed $value, string $key) => [
                'label' => Str::of($key)->replace('_', ' ')->ucfirst()->toString(),
                'value' => $this->display($value),
            ])
            ->values();

        $causer = $activity->causer instanceof User ? $activity->causer : null;

        return [
            'id' => $activity->id,
            'category' => $activity->log_name,
            'event' => $activity->event,
            'description' => $activity->description,
            'causer' => $causer ? [
                'name' => $causer->name,
                'type' => $causer->user_type,
            ] : null,
            'subject' => $activity->subject_type ? [
                'type' => Str::of(class_basename($activity->subject_type))->snake(' ')->toString(),
                'id' => $activity->subject_id,
            ] : null,
            'ip_address' => $activity->ip_address,
            'user_agent' => $activity->user_agent,
            'changes' => $changes,
            'details' => $details,
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    private function display(mixed $value): string
    {
        $text = match (true) {
            $value === null => '—',
            is_bool($value) => $value ? 'Yes' : 'No',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };

        return Str::limit($text, 300);
    }

    private function date(string $value): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && strtotime($value) !== false ? $value : '';
    }
}
