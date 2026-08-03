<?php

use App\Services\ChurchDatabaseBackupCleanupService;

use App\Services\ChurchDatabaseBackupService;

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



Artisan::command('backups:run {--no-external}', function (): int {
    try {
        $backup = app(ChurchDatabaseBackupService::class)->run(
            copyToExternal: ! $this->option('no-external'),
        );

        $this->info('Backup completed.');
        $this->line('File: ' . $backup->filename);
        $this->line('Local: ' . $backup->local_path);
        $this->line('External: ' . ($backup->external_status ?? 'pending'));

        if ($backup->external_path) {
            $this->line('External path: ' . $backup->external_path);
        }

        $this->line('Google Drive: ' . ($backup->google_drive_status ?? 'disabled'));

        if ($backup->google_drive_file_id) {
            $this->line('Google Drive file ID: ' . $backup->google_drive_file_id);
        }

        return 0;
    } catch (Throwable $exception) {
        $this->error('Backup failed: ' . $exception->getMessage());

        return 1;
    }
})->purpose('Create a local and external database backup');


if (config('backup.schedule.enabled', true)) {
    \Illuminate\Support\Facades\Schedule::command('backups:run')
        ->dailyAt((string) config('backup.schedule.daily_at', '02:00'))
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/backup-scheduler.log'));
}


Artisan::command('backups:cleanup {--dry-run}', function (): int {
    if (! config('backup.retention.enabled', true)) {
        $this->warn('Backup retention cleanup is disabled.');

        return 0;
    }

    $stats = app(ChurchDatabaseBackupCleanupService::class)->cleanup(
        dryRun: (bool) $this->option('dry-run'),
    );

    $this->info(($stats['dry_run'] ? 'Dry run complete.' : 'Cleanup complete.'));
    $this->line('Checked: ' . $stats['checked']);
    $this->line('Local deleted: ' . $stats['local_deleted']);
    $this->line('External deleted: ' . $stats['external_deleted']);
    $this->line('Google Drive deleted: ' . $stats['google_drive_deleted']);
    $this->line('Errors: ' . $stats['errors']);

    return $stats['errors'] > 0 ? 1 : 0;
})->purpose('Clean old database backup files according to retention settings');


if (config('backup.retention.enabled', true)) {
    \Illuminate\Support\Facades\Schedule::command('backups:cleanup')
        ->dailyAt((string) config('backup.retention.cleanup_daily_at', '03:00'))
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/backup-cleanup-scheduler.log'));
}
