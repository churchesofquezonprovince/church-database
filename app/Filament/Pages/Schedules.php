<?php

namespace App\Filament\Pages;

use App\Services\GoogleCalendarService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

class Schedules extends Page
{
    protected static ?string $slug = 'schedules';

    protected string $view = 'filament.pages.schedules';

    public string $upcomingScheduleRange = '30_days';

    public string $upcomingScheduleSearch = '';

    public string $upcomingScheduleCalendarId = 'all';

    public function setUpcomingScheduleRange(string $range): void
    {
        if (! in_array($range, ['today', '7_days', '30_days', 'all'], true)) {
            return;
        }

        $this->upcomingScheduleRange = $range;
    }


    protected static string | \UnitEnum | null $navigationGroup = 'Posts';

    protected static ?string $navigationLabel = 'Schedules';

    protected static ?string $title = 'Schedules';

    protected static ?int $navigationSort = 10;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function syncGoogleCalendar(): void
    {
        $service = app(GoogleCalendarService::class);

        if (! $service->enabled()) {
            Notification::make()
                ->title('Google Calendar sync is disabled')
                ->body('Set GOOGLE_CALENDAR_ENABLED=true after adding the service account JSON and calendar ID.')
                ->warning()
                ->send();

            return;
        }

        try {
            $stats = $service->pull();

            Notification::make()
                ->title('Google Calendar synced')
                ->body(sprintf(
                    'Created: %d, Updated: %d, Deleted: %d, Skipped: %d.',
                    $stats['created'] ?? 0,
                    $stats['updated'] ?? 0,
                    $stats['deleted'] ?? 0,
                    $stats['skipped'] ?? 0,
                ))
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Google Calendar sync failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
