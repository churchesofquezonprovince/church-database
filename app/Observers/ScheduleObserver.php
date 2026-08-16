<?php

namespace App\Observers;

use App\Models\Schedule;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ScheduleObserver
{
    public function saved(Schedule $schedule): void
    {
        $service = app(GoogleCalendarService::class);

        if (! $service->enabled()) {
            return;
        }

        try {
            $service->upsert($schedule);
        } catch (Throwable $exception) {
            Log::warning('Google Calendar schedule push failed.', [
                'schedule_id' => $schedule->id,
                'message' => $exception->getMessage(),
            ]);

            $schedule->forceFill([
                'google_sync_status' => 'failed',
                'google_sync_error' => Str::limit($exception->getMessage(), 1000),
            ])->saveQuietly();
        }
    }

    public function deleted(Schedule $schedule): void
    {
        $service = app(GoogleCalendarService::class);

        if (! $service->enabled()) {
            return;
        }

        try {
            $service->delete($schedule);
        } catch (Throwable $exception) {
            Log::warning('Google Calendar schedule delete failed.', [
                'schedule_id' => $schedule->id,
                'google_event_id' => $schedule->google_event_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
