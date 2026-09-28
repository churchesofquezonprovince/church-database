<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AttendanceSheets;
use App\Models\AttendanceSession;
use App\Models\Schedule;
use App\Services\GoogleCalendarService;
use App\Services\ScheduleAttendanceService;
use App\Support\LocalityOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
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
use Illuminate\Support\Carbon;
use Throwable;

class SchedulesCalendarWidget extends CalendarWidget
{
    /*
     * Editable schedule-management calendar used by
     * Posts -> Schedules.
     *
     * ChurchDashboard explicitly excludes this widget and uses
     * DashboardSchedulesCalendarWidget instead.
     */
    protected bool $dateClickEnabled = true;

    protected bool $dateSelectEnabled = true;

    protected bool $eventClickEnabled = true;

    protected bool $noEventsClickEnabled = true;

    protected ?string $defaultEventClickAction = 'editSchedule';

    public ?string $pendingScheduleStartsAt = null;

    public ?string $pendingScheduleEndsAt = null;

    public bool $pendingScheduleIsAllDay = false;

    public bool $scheduleCalendarFilterIsActive = false;

    public array $visibleScheduleCalendarIds = [];



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

    public function getHeaderActions(): array
    {
        return [
            Action::make('filterCalendars')
                ->label('Filter Calendars')
                ->icon('heroicon-o-funnel')
                ->color($this->scheduleCalendarFilterIsActive ? 'warning' : 'gray')
                ->form([
                    CheckboxList::make('visibleScheduleCalendarIds')
                        ->label('Visible Calendars')
                        ->options(fn (): array => $this->calendarFilterOptions())
                        ->columns(2),
                ])
                ->fillForm(fn (): array => [
                    'visibleScheduleCalendarIds' => $this->activeCalendarFilterValues(),
                ])
                ->action(function (array $data): void {
                    $this->scheduleCalendarFilterIsActive = true;
                    $this->visibleScheduleCalendarIds = array_values($data['visibleScheduleCalendarIds'] ?? []);

                    if (method_exists($this, 'refreshRecords')) {
                        $this->refreshRecords();
                    }
                }),

            Action::make('resetCalendarFilters')
                ->label('Show All')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->visible(fn (): bool => $this->scheduleCalendarFilterIsActive)
                ->action(function (): void {
                    $this->scheduleCalendarFilterIsActive = false;
                    $this->visibleScheduleCalendarIds = [];

                    if (method_exists($this, 'refreshRecords')) {
                        $this->refreshRecords();
                    }
                }),

            Action::make('syncGoogleCalendar')
                ->label('Sync Google Calendar Now')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action('syncGoogleCalendar'),
        ];
    }

    protected function calendarFilterOptions(): array
    {
        return app(GoogleCalendarService::class)->calendarOptions()
            + ['__local__' => 'Local / Unsynced'];
    }

    protected function activeCalendarFilterValues(): array
    {
        if (! $this->scheduleCalendarFilterIsActive) {
            return array_keys($this->calendarFilterOptions());
        }

        return $this->visibleScheduleCalendarIds;
    }


    public function editScheduleAction(): EditAction
    {
        return $this
            ->editAction()
            ->modalHeading('Edit Schedule')
            ->extraModalFooterActions([
                
                // THE NEW GENERATE BUTTON
                Action::make('generateAttendance')
                    ->visible(
                        fn (Schedule $record): bool =>
                            $this->linkedAttendanceSession(
                                $record
                            ) === null
                    )
                    ->label('Generate Attendance Sheet')
                    ->color('success')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Attendance')
                    ->modalDescription(
                        'Choose an existing compatible Attendance Sheet, or leave the field blank to auto-detect/create one.'
                    )
                    ->schema([
                        Select::make('attendance_sheet_id')
                            ->label(
                                'Use Existing Attendance Sheet'
                            )
                            ->options(
                                fn (Schedule $record): array =>
                                    app(
                                        ScheduleAttendanceService::class
                                    )->attendanceSheetOptions(
                                        $record
                                    )
                            )
                            ->searchable()
                            ->native(false)
                            ->placeholder(
                                'Auto-detect or create a new Attendance Sheet'
                            )
                            ->helperText(
                                'Only compatible Attendance Sheets that already have a Session on this Schedule date are shown.'
                            ),
                    ])
                    ->action(function (
                        Schedule $record,
                        array $data
                    ): void {
                        $sheetId =
                            filled(
                                $data[
                                    'attendance_sheet_id'
                                ]
                                ?? null
                            )
                                ? (int) $data[
                                    'attendance_sheet_id'
                                ]
                                : null;

                        $this->generateAttendanceForSchedule(
                            $record,
                            'Attendance Generated Successfully',
                            $sheetId
                        );
                    }),

                /*
                 * When Attendance has already been linked,
                 * replace Generate Attendance with a direct link
                 * to that exact Attendance Sheet + Session.
                 */
                Action::make('openAttendanceSheet')
                    ->label('Attendance Sheet')
                    ->color('primary')
                    ->icon(
                        'heroicon-o-clipboard-document-check'
                    )
                    ->visible(
                        fn (Schedule $record): bool =>
                            $this->linkedAttendanceSession(
                                $record
                            ) !== null
                    )
                    ->url(
                        fn (Schedule $record): ?string =>
                            $this->attendanceSheetUrl(
                                $record
                            )
                    ),

                // YOUR EXISTING DELETE BUTTON
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
        })

        ->extraModalFooterActions(fn (Action $action): array => [
            $action
                ->makeModalSubmitAction(
                    'saveAndGenerateAttendance',
                    arguments: [
                        'generateAttendance' => true,
                    ],
                )
                ->label('Create & Generate Attendance Sheet')
                ->color('success')
                ->icon('heroicon-o-clipboard-document-check'),
        ])

        ->after(function (
            Schedule $record,
            array $arguments
        ): void {
            $this->refreshRecords();

            if (
                ! (
                    $arguments['generateAttendance']
                    ?? false
                )
            ) {
                return;
            }

            $this->generateAttendanceForSchedule(
                $record,
                'Schedule Saved & Attendance Generated'
            );
        });
}

    protected function linkedAttendanceSession(
        Schedule $schedule
    ): ?AttendanceSession {
        return AttendanceSession::query()
            ->where(
                'schedule_id',
                $schedule->id
            )
            ->first();
    }

    protected function attendanceSheetUrl(
        Schedule $schedule
    ): ?string {
        $session =
            $this->linkedAttendanceSession(
                $schedule
            );

        if (! $session) {
            return null;
        }

        return AttendanceSheets::getUrl()
            . '?'
            . http_build_query([
                'sheetId' =>
                    $session->attendance_sheet_id,

                'sessionId' =>
                    $session->id,
            ]);
    }

    protected function generateAttendanceForSchedule(
        Schedule $schedule,
        string $successTitle,
        ?int $attendanceSheetId = null
    ): void {
        try {
            app(
                ScheduleAttendanceService::class
            )->generate(
                $schedule,
                auth()->id(),
                $attendanceSheetId
            );

            Notification::make()
                ->title($successTitle)
                ->success()
                ->send();
        } catch (
            \Illuminate\Validation\ValidationException
            $exception
        ) {
            $message = collect(
                $exception->errors()
            )
                ->flatten()
                ->first()
                ?? 'Attendance could not be generated.';

            Notification::make()
                ->title(
                    'Attendance was not generated'
                )
                ->body($message)
                ->danger()
                ->send();
        }
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
                    ->options(
                        Schedule::categoryOptions()
                    )
                    ->searchable()
                    ->native(false),

                Select::make('locality_id')
                    ->label('Locality')
                    ->options(
                        fn (): array =>
                            LocalityOptions::groupedActiveConfigured()
                    )
                    ->searchable()
                    ->native(false)
                    ->placeholder('Select Locality')
                    ->helperText(
                        'Only active configured Localities can be selected.'
                    ),

                TextInput::make('location')
                    ->label('Location')
                    ->maxLength(255),

                DateTimePicker::make('starts_at')
                    ->label('Starts At')
                    ->default(fn (): ?string => $this->pendingScheduleStartsAt)
                    ->required(),

                DateTimePicker::make('ends_at')
                    ->label('Ends At')
                    ->default(fn (): ?string => $this->pendingScheduleEndsAt),

                Toggle::make('is_all_day')
                    ->label('All-day schedule')
                    ->default(fn (): bool => $this->pendingScheduleIsAllDay),

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
        $this->fillPendingScheduleDatesFromCalendarInfo($info);

        $this->mountAction('createSchedule');
    }

    protected function onDateSelect(DateSelectInfo $info): void
    {
        $this->fillPendingScheduleDatesFromCalendarInfo($info);

        $this->mountAction('createSchedule');
    }

    protected function onNoEventsClick(NoEventsClickInfo $info): void
    {
        $this->fillPendingScheduleDatesFromCalendarInfo($info);

        $this->mountAction('createSchedule');
    }

    protected function fillPendingScheduleDatesFromCalendarInfo(mixed $info): void
    {
        $startValue = $this->calendarInfoValue($info, 'start')
            ?? $this->calendarInfoValue($info, 'startStr')
            ?? $this->calendarInfoValue($info, 'date')
            ?? $this->calendarInfoValue($info, 'dateStr');

        $endValue = $this->calendarInfoValue($info, 'end')
            ?? $this->calendarInfoValue($info, 'endStr');

        $this->pendingScheduleIsAllDay = (bool) (
            $this->calendarInfoValue($info, 'allDay') ?? false
        );

        $this->pendingScheduleStartsAt = $this->calendarDateToString($startValue);
        $this->pendingScheduleEndsAt = $this->calendarDateToString($endValue);

        /*
         * For a single clicked date, Guava usually gives only a start/date value.
         * Fill Ends At on the same day so the Add Schedule popup is complete.
         */
        if ($this->pendingScheduleStartsAt && blank($this->pendingScheduleEndsAt)) {
            $start = Carbon::parse($this->pendingScheduleStartsAt)
                ->timezone(config('app.timezone'));

            $this->pendingScheduleEndsAt = $this->pendingScheduleIsAllDay
                ? $start->copy()->endOfDay()->format('Y-m-d H:i:s')
                : $start->copy()->addHour()->format('Y-m-d H:i:s');
        }
    }

    protected function calendarInfoValue(mixed $info, string $key): mixed
    {
        $value = data_get($info, $key);

        if ($value !== null) {
            return $value;
        }

        $rawData = data_get($info, 'data');

        if (is_array($rawData)) {
            return data_get($rawData, $key);
        }

        return null;
    }

    protected function calendarDateToString(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
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
        $visibleCalendarIds = $this->activeCalendarFilterValues();

        $showLocalSchedules = in_array('__local__', $visibleCalendarIds, true);

        $visibleGoogleCalendarIds = array_values(array_filter(
            $visibleCalendarIds,
            fn (string $calendarId): bool => $calendarId !== '__local__',
        ));

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
            })
            ->where(function (Builder $query) use ($visibleGoogleCalendarIds, $showLocalSchedules): void {
                if ($visibleGoogleCalendarIds !== []) {
                    $query->whereIn('google_calendar_id', $visibleGoogleCalendarIds);
                }

                if ($showLocalSchedules) {
                    $query->{$visibleGoogleCalendarIds === [] ? 'where' : 'orWhere'}(function (Builder $query): void {
                        $query
                            ->whereNull('google_calendar_id')
                            ->orWhere('google_calendar_id', '');
                    });
                }

                if ($visibleGoogleCalendarIds === [] && ! $showLocalSchedules) {
                    $query->whereRaw('1 = 0');
                }
            });
    }

}
