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
    ->onFailure(fn () => Log::critical('The Inertia SSR server is not responding; pages are being served without server-rendered meta tags.'));
