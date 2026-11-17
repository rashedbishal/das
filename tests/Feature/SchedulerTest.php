<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Scheduled events whose command contains the given text and that would run now.
 *
 * @return list<Event>
 */
function dueScheduledCommands(string $command): array
{
    return collect(app(Schedule::class)->events())
        ->filter(fn (Event $event): bool => str_contains((string) $event->command, $command))
        ->filter(fn (Event $event): bool => $event->filtersPass(app()))
        ->values()
        ->all();
}

test('the scheduler runs a queue worker when shared hosting mode is on', function () {
    config(['queue.work_from_scheduler' => true]);

    expect(dueScheduledCommands('queue:work'))->toHaveCount(1);
});

test('the scheduler does not run a queue worker by default', function () {
    config(['queue.work_from_scheduler' => false]);

    expect(dueScheduledCommands('queue:work'))->toBeEmpty();
});

test('the ssr health check runs in production only while ssr is enabled', function () {
    app()->detectEnvironment(fn (): string => 'production');

    config(['inertia.ssr.enabled' => true]);
    expect(dueScheduledCommands('inertia:check-ssr'))->toHaveCount(1);

    config(['inertia.ssr.enabled' => false]);
    expect(dueScheduledCommands('inertia:check-ssr'))->toBeEmpty();
});
