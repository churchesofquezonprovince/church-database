<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('schedules:sync-google', function (): void {
    $service = app(\App\Services\GoogleCalendarService::class);

    if (! $service->enabled()) {
        $this->warn('Google Calendar sync is disabled. Enable GOOGLE_CALENDAR_ENABLED first.');

        return;
    }

    $stats = $service->pull();

    $this->info(sprintf(
        'Google Calendar pull sync complete. Created: %d, Updated: %d, Deleted: %d, Skipped: %d.',
        $stats['created'] ?? 0,
        $stats['updated'] ?? 0,
        $stats['deleted'] ?? 0,
        $stats['skipped'] ?? 0,
    ));
})->purpose('Pull Google Calendar events into local schedules');

\Illuminate\Support\Facades\Schedule::command('schedules:sync-google')->hourly();

