<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\Person;
use App\Support\LocalityOptions;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class CheckAttendance extends Page
{
    protected string $view = 'filament.pages.check-attendance';

    protected ?Collection $activeSchoolsCache = null;

#[Url(
    as: 'sheetId',
    history: true
)]
public ?int $selectedSheetId = null;

#[Url(
    as: 'sessionId',
    history: true
)]
public ?int $selectedSessionId = null;

#[Url(
    as: 'view',
    except: 'checklist',
    history: true
)]
public string $attendanceView = 'checklist';

public bool $gridEditMode = false;

public ?int $gridEditSessionId = null;

public function mount(): void
{
    $this->selectedSheetId =
        request()->integer(
            'sheetId'
        ) ?: null;

    $this->selectedSessionId =
        request()->integer(
            'sessionId'
        ) ?: null;

    /*
     * Session ID is the stronger identity.
     *
     * If a valid Session is supplied, derive its Sheet directly
     * instead of trusting potentially mismatched query parameters.
     *
     * Both values then remain Livewire component state during
     * Confirm / Confirm All and other Livewire actions.
     */
    if ($this->selectedSessionId) {
        $session =
            AttendanceSession::query()
                ->find(
                    $this->selectedSessionId
                );

        if ($session) {
            $this->selectedSheetId =
                (int)
                $session->attendance_sheet_id;
        } else {
            $this->selectedSessionId =
                null;
        }
    }
}

    public function permanentMeetingLocalities(string $sheetType): Collection
    {
        $sheets = AttendanceSheet::query()
            ->where('sheet_type', $sheetType)
            ->where('is_active', true)
            ->withCount('sessions')
            ->withDistinctParticipantCount(
                activeOnly: true
            )
            ->get()
            ->filter(fn (AttendanceSheet $sheet): bool => filled($sheet->locality))
            ->keyBy(
                fn (AttendanceSheet $sheet): string =>
                    mb_strtolower(trim((string) $sheet->locality))
            );

        return LocalityOptions::primaryProvinceNamesWithPeople()
            ->map(function (string $locality) use ($sheets): array {
                $sheet = $sheets->get(
                    mb_strtolower(trim($locality))
                );

                return [
                    'locality' => $locality,
                    'label' => $locality,
                    'sheet' => $sheet,
                    'sessions_count' => $sheet?->sessions_count ?? 0,
                    'participants_count' => $sheet?->participants_count ?? 0,
                ];
            })
            ->values();
    }

    public function localityLabel(?string $locality): string
    {
        return $locality === '__no_locality'
            ? 'No Locality'
            : (string) $locality;
    }

    public function permanentMeetingUrl(string $sheetType, string $locality, ?AttendanceSheet $sheet = null): string
    {
        if ($sheetType === AttendanceSheet::TYPE_LORDS_TABLE) {
            return LordsTableMeeting::getUrl() . '?' . http_build_query([
                'locality' => $locality,
                'meeting_date' => $this->nextDateForDay(0),
            ]);
        }

        $meetingDay = $sheet?->meeting_day ?? 2;

        return PrayerMeeting::getUrl() . '?' . http_build_query([
            'locality' => $locality,
            'meeting_day' => $meetingDay,
            'meeting_date' => $this->nextDateForDay((int) $meetingDay),
        ]);
    }

    private function nextDateForDay(int $day): string
    {
        $today = CarbonImmutable::today();
        $diff = ($day - $today->dayOfWeek + 7) % 7;

        return $today->addDays($diff)->toDateString();
    }

    public function getTitle(): string
    {
        return 'Check Attendance';
    }

    public static function getNavigationUrl(): string
    {
        $query = [];

        /*
         * Preserve Attendance Sheet / Session context when moving
         * between Attendance Sheets and Check Attendance through
         * the Filament navigation.
         */
        if (
            request()->routeIs(
                'filament.quezonprovinceactivities.pages.attendance-sheets'
            )
        ) {
            $sheetId =
                request()->integer(
                    'sheetId'
                );

            $sessionId =
                request()->integer(
                    'sessionId'
                );

            if ($sheetId) {
                $query['sheetId'] =
                    $sheetId;
            }

            if ($sessionId) {
                $query['sessionId'] =
                    $sessionId;
            }
        }

        return static::getUrl()
            . (
                $query
                    ? '?'
                        . http_build_query(
                            $query
                        )
                    : ''
            );
    }


    public static function getNavigationLabel(): string
    {
        return 'Check Attendance';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-check-badge';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function sheets(): Collection
    {
        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
            ->where('is_active', true)
            ->withCount('sessions')
            ->withDistinctParticipantCount(
                activeOnly: true
            )
            ->orderByRaw(
                'CASE
                    WHEN schedule_type = ? THEN 1
                    WHEN schedule_type = ? THEN 2
                    WHEN schedule_type = ? THEN 3
                    WHEN schedule_type = ? THEN 4
                    ELSE 5
                END',
                [
                    AttendanceSheet::SCHEDULE_ONE_TIME,
                    AttendanceSheet::SCHEDULE_CONSECUTIVE,
                    AttendanceSheet::SCHEDULE_MANUAL,
                    AttendanceSheet::SCHEDULE_RECURRING,
                ]
            )
            /*
             * Within each schedule type, show older meetings first.
             * Null dates are placed after dated sheets.
             */
            ->orderByRaw(
                'CASE WHEN start_date IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

    }

    public function selectedSheet(): ?AttendanceSheet
    {
        $query =
            AttendanceSheet::query()
                ->where(
                    'sheet_type',
                    AttendanceSheet::TYPE_CUSTOM
                )
                ->where(
                    'is_active',
                    true
                )
                ->withCount('sessions')
                ->withDistinctParticipantCount(
                    activeOnly: true
                );

        /*
         * Do not read request('sheetId') here.
         *
         * Livewire action requests do not reliably contain the
         * original page query string. selectedSheetId is persisted
         * as component state from mount().
         */
        if ($this->selectedSheetId) {
            $selectedSheet = $query->find(
                $this->selectedSheetId
            );

            if ($selectedSheet) {
                return $selectedSheet;
            }
        }

        return $query
            ->orderByRaw(
                'CASE
                    WHEN schedule_type = ? THEN 1
                    WHEN schedule_type = ? THEN 2
                    WHEN schedule_type = ? THEN 3
                    WHEN schedule_type = ? THEN 4
                    ELSE 5
                END',
                [
                    AttendanceSheet::SCHEDULE_ONE_TIME,
                    AttendanceSheet::SCHEDULE_CONSECUTIVE,
                    AttendanceSheet::SCHEDULE_MANUAL,
                    AttendanceSheet::SCHEDULE_RECURRING,
                ]
            )
            ->orderByRaw(
                'CASE WHEN start_date IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy('start_date')
            ->orderBy('id')
            ->first();
    }

    public function sessions(): Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return collect();
        }

        return AttendanceSession::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->orderBy('session_date')
            ->get();
    }

public function selectedSession(): ?AttendanceSession
{
    $sheet = $this->selectedSheet();

    if (! $sheet) {
        return null;
    }

    $query = AttendanceSession::query()
        ->where('attendance_sheet_id', $sheet->id);

    if ($this->selectedSessionId) {
        return $query->find($this->selectedSessionId);
    }

    return $query
        ->orderByRaw('CASE WHEN session_date >= CURDATE() THEN 0 ELSE 1 END')
        ->orderBy('session_date')
        ->first();
}


public function setAttendanceView(
    string $view
): void {
    $this->attendanceView =
        $view === 'grid'
            ? 'grid'
            : 'checklist';

    if ($this->attendanceView !== 'grid') {
        $this->gridEditMode = false;
        $this->gridEditSessionId = null;
    }
}

public function toggleGridEditMode(): void
{
    abort_unless(
        auth()->user()?->canManageRecords(),
        403
    );

    if (
        $this->attendanceView !== 'grid'
        || ! $this->selectedSheet()
    ) {
        $this->gridEditMode = false;
        $this->gridEditSessionId = null;

        return;
    }

    $this->gridEditMode =
        ! $this->gridEditMode;

    /*
     * Force an intentional date selection whenever
     * edit mode is entered.
     */
    $this->gridEditSessionId = null;
}

public function selectGridSession(
    int $sessionId
): void {
    $sheet =
        $this->selectedSheet();

    if (! $sheet) {
        return;
    }

    $session =
        AttendanceSession::query()
            ->where(
                'attendance_sheet_id',
                $sheet->id
            )
            ->find(
                $sessionId
            );

    if (! $session) {
        return;
    }

    $this->selectedSessionId =
        (int) $session->id;

    /*
     * Explicitly remain in Grid View.
     */
    $this->attendanceView =
        'grid';

    /*
     * Edit mode requires an intentional date-column
     * selection. Only that Session may then be changed.
     */
    if ($this->gridEditMode) {
        $this->gridEditSessionId =
            $session->is_no_meeting
                ? null
                : (int) $session->id;
    }
}

public function toggleGridAttendance(
    int $sessionId,
    int $personId
): void {
    abort_unless(
        auth()->user()?->canManageRecords(),
        403
    );

    /*
     * Grid cells can only be changed while:
     *
     * 1. Grid View is active,
     * 2. Edit Mode is active, and
     * 3. this exact Session column was intentionally selected.
     */
    abort_unless(
        $this->attendanceView === 'grid'
        && $this->gridEditMode
        && $this->gridEditSessionId === $sessionId,
        403
    );

    $sheet =
        $this->selectedSheet();

    abort_unless(
        $sheet
        && $sheet->sheet_type
            === AttendanceSheet::TYPE_CUSTOM
        && $sheet->is_active,
        404
    );

    $session =
        AttendanceSession::query()
            ->where(
                'attendance_sheet_id',
                $sheet->id
            )
            ->findOrFail(
                $sessionId
            );

    /*
     * A NO MEETING Session must never accept attendance,
     * even if a client attempts to call this Livewire action
     * directly.
     */
    abort_if(
        (bool) $session->is_no_meeting,
        403
    );

    $sessionDate =
        $session
            ->session_date
            ->toDateString();

    /*
     * IMPORTANT:
     *
     * Check Attendance is NOT allowed to add Participants.
     *
     * Attendance Sheets remains responsible for participant
     * membership. If the Person is not rostered for this date,
     * the Grid cell remains locked.
     */
    $isParticipant =
        AttendanceParticipant::query()
            ->where(
                'attendance_sheet_id',
                $sheet->id
            )
            ->where(
                'person_id',
                $personId
            )
            ->activeOn(
                $sessionDate
            )
            ->exists();

    abort_unless(
        $isParticipant,
        403
    );

    $record =
        AttendanceRecord::query()
            ->where(
                'attendance_session_id',
                $session->id
            )
            ->where(
                'person_id',
                $personId
            )
            ->first();

    /*
     * Spreadsheet-style toggle:
     *
     * No record  -> Present
     * Present    -> Absent
     * Absent     -> Present
     *
     * We deliberately do not delete an AttendanceRecord to
     * recreate "Not recorded". Once an administrator edits the
     * cell, it becomes an explicit attendance decision.
     */
    $isPresent =
        $record
            ? ! (bool) $record->is_present
            : true;

    AttendanceRecord::query()
        ->updateOrCreate(
            [
                'attendance_session_id' =>
                    $session->id,

                'person_id' =>
                    $personId,
            ],
            [
                'status' =>
                    $isPresent
                        ? AttendanceRecord::STATUS_PRESENT
                        : AttendanceRecord::STATUS_ABSENT,

                'is_present' =>
                    $isPresent,

                /*
                 * Match the existing Attendance Checklist:
                 * an administrator edit becomes MANUAL.
                 */
                'attendance_source' =>
                    AttendanceRecord::SOURCE_MANUAL,

                'immich_confirmed' =>
                    false,

                'immich_confirmed_at' =>
                    null,

                'immich_confirmed_by_id' =>
                    null,

                'marked_by_id' =>
                    auth()->id(),

                'marked_at' =>
                    now(),
            ]
        );
}

public function attendanceGrid(
    AttendanceSheet $sheet,
    Collection $sessions
): array {
    /*
     * The Grid is a whole-Sheet historical view.
     *
     * Do not use participantRows() here because that method is
     * intentionally scoped to only the selected Session date.
     *
     * A grid row may therefore come from:
     *
     * 1. a participant period on this Sheet, or
     * 2. an historical AttendanceRecord that still exists even if
     *    its participant-period row was later removed.
     */
    if (
        $sheet->is_one_time
        || $sessions->count() <= 1
    ) {
        return [
            'sessions' => collect(),
            'rows' => collect(),
        ];
    }

    $sessionIds =
        $sessions
            ->pluck('id')
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->values();

    if ($sessionIds->isEmpty()) {
        return [
            'sessions' => collect(),
            'rows' => collect(),
        ];
    }

    $participantPeriods =
        AttendanceParticipant::query()
            ->with([
                'person.churchProfile',
            ])
            ->where(
                'attendance_sheet_id',
                $sheet->id
            )
            ->where(
                'is_active',
                true
            )
            ->get()
            ->groupBy(
                fn (
                    AttendanceParticipant $participant
                ): int =>
                    (int) $participant->person_id
            );

    $records =
        AttendanceRecord::query()
            ->with([
                'person.churchProfile',
            ])
            ->whereIn(
                'attendance_session_id',
                $sessionIds
            )
            ->get();

    /*
     * One AttendanceRecord exists at most once for a
     * Session + Person pair.
     */
    $recordsByCell =
        $records->keyBy(
            fn (
                AttendanceRecord $record
            ): string =>
                $record->attendance_session_id
                . ':'
                . $record->person_id
        );

    /*
     * Build the union of everybody who has ever belonged to
     * the Sheet and everybody who has historical attendance.
     */
    $people =
        collect();

    $participantPeriods
        ->flatten(1)
        ->each(
            function (
                AttendanceParticipant $participant
            ) use ($people): void {
                if ($participant->person) {
                    $people->put(
                        (int) $participant->person_id,
                        $participant->person
                    );
                }
            }
        );

    $records->each(
        function (
            AttendanceRecord $record
        ) use ($people): void {
            if ($record->person) {
                $people->put(
                    (int) $record->person_id,
                    $record->person
                );
            }
        }
    );

    $rows =
        $people
            ->sortBy(
                fn (Person $person): string =>
                    mb_strtolower(
                        $person->display_name
                        ?? ''
                    )
            )
            ->map(
                function (
                    Person $person
                ) use (
                    $sessions,
                    $participantPeriods,
                    $recordsByCell
                ): array {
                    $personPeriods =
                        $participantPeriods->get(
                            (int) $person->id,
                            collect()
                        );

                    $cells =
                        $sessions
                            ->mapWithKeys(
                                function (
                                    AttendanceSession $session
                                ) use (
                                    $person,
                                    $personPeriods,
                                    $recordsByCell
                                ): array {
                                    $sessionDate =
                                        $session
                                            ->session_date
                                            ->format('Y-m-d');

                                    $record =
                                        $recordsByCell->get(
                                            $session->id
                                            . ':'
                                            . $person->id
                                        );

                                    /*
                                     * Participant membership and attendance
                                     * history are deliberately separate.
                                     *
                                     * A historical record may still exist
                                     * even when the Person is no longer
                                     * rostered for this Session date.
                                     */
                                    $active =
                                        $personPeriods
                                            ->contains(
                                                function (
                                                    AttendanceParticipant $participant
                                                ) use (
                                                    $sessionDate
                                                ): bool {
                                                    if (
                                                        ! $participant
                                                            ->is_active
                                                    ) {
                                                        return false;
                                                    }

                                                    $startsOn =
                                                        $participant
                                                            ->starts_on
                                                            ?->format(
                                                                'Y-m-d'
                                                            );

                                                    $endsOn =
                                                        $participant
                                                            ->ends_on
                                                            ?->format(
                                                                'Y-m-d'
                                                            );

                                                    return (
                                                        blank(
                                                            $startsOn
                                                        )
                                                        ||
                                                        $startsOn
                                                            <=
                                                        $sessionDate
                                                    )
                                                    &&
                                                    (
                                                        blank(
                                                            $endsOn
                                                        )
                                                        ||
                                                        $endsOn
                                                            >=
                                                        $sessionDate
                                                    );
                                                }
                                            );

                                    /*
                                     * Historical attendance still wins for
                                     * display, but only active roster cells
                                     * may be edited.
                                     */
                                    if ($record) {
                                        $status =
                                            $record->is_present
                                                ? 'present'
                                                : 'absent';
                                    } else {
                                        $status =
                                            $active
                                                ? 'not_recorded'
                                                : 'not_roster';
                                    }

                                    return [
                                        (int) $session->id => [
                                            'status' =>
                                                $status,

                                            'on_roster' =>
                                                $active,

                                            'source' =>
                                                $record
                                                    ?->attendance_source,

                                            'immich_pending' =>
                                                $record
                                                    &&
                                                $record
                                                    ->attendance_source
                                                ===
                                                AttendanceRecord
                                                    ::SOURCE_IMMICH
                                                &&
                                                $record
                                                    ->is_present
                                                &&
                                                ! $record
                                                    ->immich_confirmed,
                                        ],
                                    ];
                                }
                            )
                            ->all();

                    return [
                        'person' => $person,
                        'cells' => $cells,
                    ];
                }
            )
            ->values();

    return [
        'sessions' => $sessions,
        'rows' => $rows,
    ];
}

public function participantRows(): Collection
{
    $session = $this->selectedSession();

    if (! $session) {
        return collect();
    }

    $sessionDate =
        $session
            ->session_date
            ->format('Y-m-d');

    /*
     * AttendanceParticipant is the active roster.
     *
     * AttendanceRecord remains historical attendance
     * and must NOT automatically resurrect somebody
     * who was removed from the Sheet roster.
     */
    return AttendanceParticipant::query()
        ->with([
            'person.churchProfile',
        ])
        ->where(
            'attendance_sheet_id',
            $session->attendance_sheet_id
        )
        ->where(
            'is_active',
            true
        )
        ->where(
            function ($query) use (
                $sessionDate
            ): void {
                $query
                    ->whereNull('starts_on')
                    ->orWhere(
                        'starts_on',
                        '<=',
                        $sessionDate
                    );
            }
        )
        ->where(
            function ($query) use (
                $sessionDate
            ): void {
                $query
                    ->whereNull('ends_on')
                    ->orWhere(
                        'ends_on',
                        '>=',
                        $sessionDate
                    );
            }
        )
        ->get()
        ->unique(
            fn (
                AttendanceParticipant $participant
            ): int =>
                (int) $participant->person_id
        )
        ->sortBy(
            fn (
                AttendanceParticipant $participant
            ): string =>
                $participant
                    ->person
                    ?->display_name
                ?? ''
        )
        ->values();
}

public function presentPersonIds(): array
    {
        $session = $this->selectedSession();

        if (! $session) {
            return [];
        }

        return AttendanceRecord::query()
            ->where('attendance_session_id', $session->id)
            ->where('is_present', true)
            ->pluck('person_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function recordCounts(): array
    {
        $session = $this->selectedSession();

        if (! $session) {
            return [
                'present' => 0,
                'absent' => 0,
                'marked' => 0,
            ];
        }

        return [
            'present' => AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->where('is_present', true)
                ->count(),

            'absent' => AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->where('is_present', false)
                ->count(),

            'marked' => AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->count(),
        ];
    }

    public function sheetUrl(AttendanceSheet $sheet): string
    {
        return self::getUrl() . '?sheetId=' . $sheet->id;
    }

    public function sessionUrl(AttendanceSheet $sheet, AttendanceSession $session): string
    {
        return self::getUrl() . '?sheetId=' . $sheet->id . '&sessionId=' . $session->id;
    }

public function attendanceRecords(): Collection
{
    $session = $this->selectedSession();

    if (! $session) {
        return collect();
    }

    return AttendanceRecord::query()
        ->with(['person', 'immichConfirmedBy'])
        ->where('attendance_session_id', $session->id)
        ->get()
        ->keyBy('person_id');
}

public function immichConfirmationCounts(): array
{
    $records = $this->attendanceRecords()
        ->filter(
            fn (AttendanceRecord $record): bool =>
                $record->attendance_source === AttendanceRecord::SOURCE_IMMICH
                && $record->is_present
        );

    return [
        'detected' => $records->count(),
        'pending' => $records
            ->where('immich_confirmed', false)
            ->count(),
        'confirmed' => $records
            ->where('immich_confirmed', true)
            ->count(),
    ];
}

public function confirmImmichAttendance(int $personId): void
{
    $session = $this->selectedSession();

    if (! $session) {
        Notification::make()
            ->title('No attendance session selected')
            ->warning()
            ->send();

        return;
    }

$record = AttendanceRecord::query()
    ->where('attendance_session_id', $session->id)
    ->where('person_id', $personId)
    ->where('attendance_source', AttendanceRecord::SOURCE_IMMICH)
    ->where('is_present', true)
    ->where('immich_confirmed', false)
    ->first();

    if (! $record) {
        Notification::make()
            ->title('Immich attendance record not found')
            ->warning()
            ->send();

        return;
    }

    $record->update([
        'immich_confirmed' => true,
        'immich_confirmed_at' => now(),
        'immich_confirmed_by_id' => auth()->id(),
    ]);

    Notification::make()
        ->title('Immich attendance confirmed')
        ->success()
        ->send();
}

public function confirmAllImmichAttendance(): void
{
    $session = $this->selectedSession();

    if (! $session) {
        Notification::make()
            ->title('No attendance session selected')
            ->warning()
            ->send();

        return;
    }

    $count = AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->where('attendance_source', AttendanceRecord::SOURCE_IMMICH)
        ->where('is_present', true)
        ->where('immich_confirmed', false)
        ->update([
            'immich_confirmed' => true,
            'immich_confirmed_at' => now(),
            'immich_confirmed_by_id' => auth()->id(),
        ]);

    Notification::make()
        ->title('Immich attendance reviewed')
        ->body("{$count} Immich attendance record(s) confirmed.")
        ->success()
        ->send();
}

}
