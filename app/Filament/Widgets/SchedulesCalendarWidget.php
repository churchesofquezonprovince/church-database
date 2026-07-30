<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use App\Services\GoogleCalendarService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\FetchInfo;
use Guava\Calendar\ValueObjects\NoEventsClickInfo;
use Guava\Calendar\ValueObjects\DateSelectInfo;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class SchedulesCalendarWidget extends CalendarWidget
{
    protected bool $dateClickEnabled = true;

    protected bool $dateSelectEnabled = true;

    protected bool $eventClickEnabled = true;

    protected bool $noEventsClickEnabled = true;



    protected ?string $defaultEventClickAction = 'editSchedule';


    public function getHeaderActions(): array
    {
        return [
            Action::make('syncGoogleCalendar')
                ->label('Sync Google Calendar Now')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action('syncGoogleCalendar'),
        ];
    }

    public function syncGoogleCalendar(): void
    {
        $service = app(GoogleCalendarService::class);

        if (! $service->enabled()) {
            Notification::make()
                ->title('Google Calendar sync is disabled')
                ->body('Set GOOGLE_CALENDAR_ENABLED=true after adding the service account JSON and calendar IDs.')
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

            if (method_exists($this, 'refreshRecords')) {
                $this->refreshRecords();
            }
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Google Calendar sync failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function editScheduleAction(): EditAction
    {
        return $this
            ->editAction()
            ->modalHeading('Edit Schedule')
            ->extraModalFooterActions([
                Action::make('deleteSchedule')
                    ->label('Delete')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Schedule')
                    ->modalDescription('Are you sure you want to delete this schedule? This cannot be undone.')
                    ->action(function ($record): void {
                        $record->delete();

                        $this->refreshRecords();
                    }),
            ]);
    }

    public function createScheduleAction(): CreateAction
    {
        return $this
            ->createAction(Schedule::class)
            ->label('Add Schedule')
            ->modalHeading('Add Schedule')
            ->mutateDataUsing(function (array $data): array {
                $data['source'] = 'local';
                $data['created_by'] = auth()->id();
                $data['updated_by'] = auth()->id();

                return $data;
            });
    }

    public function defaultSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),

                Select::make('google_calendar_id')
                    ->label('Google Calendar')
                    ->options(fn (): array => app(GoogleCalendarService::class)->calendarOptions())
                    ->placeholder('Use first configured calendar')
                    ->searchable()
                    ->native(false)
                    ->helperText('Used when Google Calendar sync is enabled.'),

                Select::make('category')
                    ->label('Category')
                    ->options([
                        'Church Activity' => 'Church Activity',
                        'Campus Work' => 'Campus Work',
                        'Prayer Meeting' => 'Prayer Meeting',
                        "Lord's Table" => "Lord's Table",
                        'Training' => 'Training',
                        'Conference' => 'Conference',
                        'Service Meeting' => 'Service Meeting',
                        'Other' => 'Other',
                    ])
                    ->searchable()
                    ->native(false),

                TextInput::make('locality')
                    ->label('Locality')
                    ->maxLength(255),

                TextInput::make('location')
                    ->label('Location')
                    ->maxLength(255),

                DateTimePicker::make('starts_at')
                    ->label('Starts At')
                    ->required(),

                DateTimePicker::make('ends_at')
                    ->label('Ends At'),

                Toggle::make('is_all_day')
                    ->label('All-day schedule'),

                Textarea::make('description')
                    ->label('Description / Notes')
                    ->rows(4)
                    ->columnSpanFull(),

                Placeholder::make('google_sync_status_display')
                    ->label('Google Sync Status')
                    ->content(function (?Schedule $record): string {
                        if (! $record) {
                            return 'New schedule. Not synced yet.';
                        }

                        $status = $record->google_sync_status ?: 'pending';

                        $syncedAt = $record->synced_at
                            ? $record->synced_at->timezone(config('app.timezone'))->format('M d, Y h:i A')
                            : 'Not yet synced';

                        return strtoupper($status) . ' — ' . $syncedAt;
                    }),

                Placeholder::make('google_sync_error_display')
                    ->label('Sync Error')
                    ->content(fn (?Schedule $record): string => $record?->google_sync_error ?: 'No sync error.')
                    ->visible(fn (?Schedule $record): bool => filled($record?->google_sync_error)),
            ])
            ->columns(2);
    }






    protected function onDateClick(DateClickInfo $info): void
    {
        $this->mountAction('createSchedule');
    }

    protected function onDateSelect(DateSelectInfo $info): void
    {
        $this->mountAction('createSchedule');
    }

    protected function onNoEventsClick(NoEventsClickInfo $info): void
    {
        $this->mountAction('createSchedule');
    }

    protected function getDateClickContextMenuActions(): array
    {
        return [];
    }

    protected function getDateSelectContextMenuActions(): array
    {
        return [];
    }

    protected function getEventClickContextMenuActions(): array
    {
        return [];
    }

    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        return Schedule::query()
            ->where(function (Builder $query) use ($info): void {
                $query
                    ->whereBetween('starts_at', [$info->start, $info->end])
                    ->orWhereBetween('ends_at', [$info->start, $info->end])
                    ->orWhere(function (Builder $query) use ($info): void {
                        $query
                            ->where('starts_at', '<=', $info->start)
                            ->where(function (Builder $query) use ($info): void {
                                $query
                                    ->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $info->end);
                            });
                    });
            });
    }
}
