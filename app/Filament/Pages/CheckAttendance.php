<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use App\Models\AttendanceMeetingResponse;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\Person;
use App\Models\School;
use App\Support\LocalityOptions;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CheckAttendance extends Page
{
    protected string $view = 'filament.pages.check-attendance';

    protected ?Collection $activeSchoolsCache = null;

public ?int $selectedSheetId = null;

public ?int $selectedSessionId = null;

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
            ->withCount([
            'sessions',
            'participants as participants_count' =>
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    ),
        ])
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
            ||
            request()->routeIs(
                'filament.quezonprovinceactivities.pages.check-attendance'
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
        return 30;
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
            ->withCount([
            'sessions',
            'participants as participants_count' =>
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    ),
        ])
            ->orderByDesc('is_active')
            ->latest()
            ->get();
    }

    public function selectedSheet(): ?AttendanceSheet
    {
        $query =
            AttendanceSheet::query()
                ->where(
                    'is_active',
                    true
                )
                ->withCount([
                    'sessions',

                    'participants as participants_count' =>
                        fn ($query) =>
                            $query->where(
                                'is_active',
                                true
                            ),
                ]);

        /*
         * Do not read request('sheetId') here.
         *
         * Livewire action requests do not reliably contain the
         * original page query string. selectedSheetId is persisted
         * as component state from mount().
         */
        if ($this->selectedSheetId) {
            return $query->find(
                $this->selectedSheetId
            );
        }

        return $query
            ->latest()
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

public function meetingResponses(): Collection
{
    $session = $this->selectedSession();

    if (! $session) {
        return collect();
    }

    return AttendanceMeetingResponse::query()
        ->with([
            'person',
            'campusContact',
        ])
        ->where(
            'attendance_session_id',
            $session->id
        )
        ->orderByDesc('responded_at')
        ->orderByDesc('id')
        ->get();
}

public function selectedPreListedFilter(): string
{
    $filter = (string) request(
        'prelisted',
        'all',
    );

    return in_array(
        $filter,
        array_keys($this->preListedFilterOptions()),
        true,
    )
        ? $filter
        : 'all';
}

private function activeSchools(): Collection
{
    return $this->activeSchoolsCache ??= School::query()
        ->with('province:id,name')
        ->where('is_active', true)
        ->orderBy('name')
        ->get();
}

public function schoolOptions(): array
{
    return $this->activeSchools()
        ->mapWithKeys(function (School $school): array {
            $location = collect([
                $school->city_municipality,
                $school->province?->name,
            ])
                ->filter()
                ->implode(', ');

            $label = $school->name;

            if (filled($location)) {
                $label .= ' — ' . $location;
            }

            return [$school->id => $label];
        })
        ->all();
}

public function schoolIdForName(?string $name): ?int
{
    $name = trim((string) $name);

    if ($name === '') {
        return null;
    }

    $matches = $this->activeSchools()
        ->filter(
            fn (School $school): bool =>
                strcasecmp($school->name, $name) === 0
                || (
                    filled($school->short_name)
                    && strcasecmp($school->short_name, $name) === 0
                )
        )
        ->values();

    return $matches->count() === 1
        ? (int) $matches->first()->id
        : null;
}

public function preListedFilterOptions(): array
{
    return [
        'all' => 'All',
        'needs_action' => 'Needs Action',
        'yes' => 'YES',
        'no' => 'NO',
        'needs_identity_review' => 'Needs Identity Review',
        'ready_for_participant' => 'Ready for Participant',
        'participant_covered' => 'Participant Covered',
        'participant_review' => 'Participant Review',
    ];
}

public function preListedFilterUrl(string $filter): string
{
    $query = request()->query();

    if ($filter === 'all') {
        unset($query['prelisted']);
    } else {
        $query['prelisted'] = $filter;
    }

    return self::getUrl()
        . ($query
            ? '?' . http_build_query($query)
            : '');
}

/**
 * Derive admin workflow state for each pre-listed response.
 *
 * This does not modify the response, participant enrollment,
 * or actual attendance.
 */
public function meetingResponseWorkflowStatuses(
    Collection $responses,
): Collection {
    $session = $this->selectedSession();

    if (! $session) {
        return collect();
    }

    $sessionDate =
        $session->session_date->format('Y-m-d');

    $personIds = $responses
        ->filter(
            fn (AttendanceMeetingResponse $response): bool =>
                $response->respondent_type
                    === AttendanceMeetingResponse::RESPONDENT_PERSON
                &&
                filled($response->person_id)
        )
        ->pluck('person_id')
        ->map(fn ($id): int => (int) $id)
        ->unique()
        ->values();

    $participants = AttendanceParticipant::query()
        ->where(
            'attendance_sheet_id',
            $session->attendance_sheet_id,
        )
        ->whereIn('person_id', $personIds)
        ->get()
        ->keyBy(
            fn (AttendanceParticipant $participant): int =>
                (int) $participant->person_id
        );

    return $responses->mapWithKeys(
        function (
            AttendanceMeetingResponse $response
        ) use (
            $participants,
            $sessionDate,
        ): array {
            /*
             * Guest / Campus has not yet reached a canonical Person.
             *
             * This remains an identity-review action even if the
             * response itself is NO.
             */
            if (
                $response->respondent_type
                    !== AttendanceMeetingResponse::RESPONDENT_PERSON
                ||
                blank($response->person_id)
            ) {
                return [
                    $response->id => [
                        'key' => 'needs_identity_review',
                        'label' => 'Needs Identity Review',
                        'needs_action' => true,
                        'detail' => null,
                    ],
                ];
            }

            /*
             * A canonical Person who answered NO does not need to
             * be enrolled from the pre-listed response.
             */
            if (
                $response->response
                === AttendanceMeetingResponse::RESPONSE_NO
            ) {
                return [
                    $response->id => [
                        'key' => 'no_participant_needed',
                        'label' => 'No Participant Needed',
                        'needs_action' => false,
                        'detail' => null,
                    ],
                ];
            }

            $participant = $participants->get(
                (int) $response->person_id
            );

            if (! $participant) {
                return [
                    $response->id => [
                        'key' => 'ready_for_participant',
                        'label' => 'Ready for Participant',
                        'needs_action' => true,
                        'detail' => null,
                    ],
                ];
            }

            if (! $participant->is_active) {
                return [
                    $response->id => [
                        'key' => 'participant_review',
                        'label' => 'Participant Review',
                        'needs_action' => true,
                        'detail' => 'Existing participant record is inactive.',
                    ],
                ];
            }

            $startsOn =
                $participant->starts_on?->format('Y-m-d');

            $endsOn =
                $participant->ends_on?->format('Y-m-d');

            $coversSession =
                (blank($startsOn) || $startsOn <= $sessionDate)
                &&
                (blank($endsOn) || $endsOn >= $sessionDate);

            if ($coversSession) {
                return [
                    $response->id => [
                        'key' => 'participant_covered',
                        'label' => 'Participant Covered',
                        'needs_action' => false,
                        'detail' => null,
                    ],
                ];
            }

            return [
                $response->id => [
                    'key' => 'participant_review',
                    'label' => 'Participant Review',
                    'needs_action' => true,
                    'detail' =>
                        'Existing participant range does not cover this meeting.',
                ],
            ];
        }
    );
}

public function meetingResponseMatchesPreListedFilter(
    AttendanceMeetingResponse $response,
    array $workflow,
    string $filter,
): bool {
    $workflowKey =
        $workflow['key'] ?? null;

    return match ($filter) {
        'needs_action' =>
            (bool) ($workflow['needs_action'] ?? false),

        'yes' =>
            $response->response
            === AttendanceMeetingResponse::RESPONSE_YES,

        'no' =>
            $response->response
            === AttendanceMeetingResponse::RESPONSE_NO,

        'needs_identity_review' =>
            $workflowKey === 'needs_identity_review',

        'ready_for_participant' =>
            $workflowKey === 'ready_for_participant',

        'participant_covered' =>
            $workflowKey === 'participant_covered',

        'participant_review' =>
            $workflowKey === 'participant_review',

        default => true,
    };
}

}
