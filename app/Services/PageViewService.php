<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * First-party daily page-view counter for the storefront (no third-party analytics).
 * Days follow the site timezone from Admin -> Settings -> General.
 */
class PageViewService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Adds one view to today's row, creating it if needed. A single upsert keeps
     * concurrent requests from losing increments.
     */
    public function record(): void
    {
        $now = now();

        DB::table('daily_page_views')->upsert(
            [['date' => $this->today()->toDateString(), 'views' => 1, 'created_at' => $now, 'updated_at' => $now]],
            ['date'],
            ['views' => DB::raw('views + 1'), 'updated_at' => $now],
        );
    }

    /**
     * Views per day for the last $days days (oldest first), including days with none.
     *
     * @return array<int, array{date: string, label: string, value: int}>
     */
    public function lastDays(int $days): array
    {
        $today = $this->today();
        $start = $today->subDays($days - 1);

        $counts = DB::table('daily_page_views')
            ->whereBetween('date', [$start->toDateString(), $today->toDateString()])
            ->pluck('views', 'date')
            ->mapWithKeys(fn (mixed $views, mixed $date) => [substr((string) $date, 0, 10) => (int) $views]);

        $series = [];

        for ($day = $start; $day->lte($today); $day = $day->addDay()) {
            $series[] = [
                'date' => $day->toDateString(),
                'label' => $day->format('j M'),
                'value' => $counts->get($day->toDateString(), 0),
            ];
        }

        return $series;
    }

    private function today(): CarbonImmutable
    {
        $timezone = (string) $this->settings->get('general', 'timezone', 'Asia/Dhaka');

        return CarbonImmutable::now($timezone)->startOfDay();
    }
}
