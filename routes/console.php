<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Requires the scheduler cron entry on the server (see docs/deployment.md):
| * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
*/

// Nightly database + uploaded-media backup (config/backup.php), then prune old backups.
Schedule::command('backup:clean')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('03:00');

// Page titles and meta tags are only in the HTML while the Inertia SSR server runs, and
// Inertia silently falls back to client rendering when it is down. Log loudly instead.
Schedule::command('inertia:check-ssr')
    ->everyFiveMinutes()
    ->environments(['production'])
    ->when(fn (): bool => (bool) config('inertia.ssr.enabled'))
    ->onFailure(fn () => Log::critical('The Inertia SSR server is not responding; pages are being served without server-rendered meta tags.'));

// Shared hosting has no supervised queue worker: start a short-lived one every minute
// that drains waiting jobs (e.g. enquiry notification mail) and exits before the next run.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->when(fn (): bool => (bool) config('queue.work_from_scheduler'));

// Activity log entries older than config('activitylog.delete_records_older_than_days') (365).
Schedule::command('activitylog:clean --force')->dailyAt('02:00')->withoutOverlapping();
