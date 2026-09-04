<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use App\Models\AttendanceMeetingResponse;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\Person;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CheckAttendance extends Page
{
    protected string $view = 'filament.pages.check-attendance';

public ?int $selectedSessionId = null;

public function mount(): void
{
    $this->selectedSessionId = request()->integer('sessionId') ?: null;
}

    public function permanentMeetingLocalities(string $sheetType): Collection
    {
        $sheets = AttendanceSheet::query()
            ->where('sheet_type', $sheetType)
            ->where('is_active', true)
            ->withCount(['sessions', 'participants'])
            ->get()
            ->keyBy(fn (AttendanceSheet $sheet): string => $sheet->locality ?: '__no_locality');

        $localities = Person::query()
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->distinct()
            ->orderBy('locality')
            ->pluck('locality')
            ->values();

        $hasNoLocality = Person::query()
            ->where(fn ($query) => $query->whereNull('locality')->orWhere('locality', ''))
            ->exists();

        if ($hasNoLocality) {
            $localities->push('__no_locality');
        }

        return $localities
            ->unique()
            ->map(function (string $locality) use ($sheets): array {
                $sheet = $sheets->get($locality);

                return [
                    'locality' => $locality,
                    'label' => $this->localityLabel($locality),
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
            ->withCount(['sessions', 'participants'])
            ->orderByDesc('is_active')
            ->latest()
            ->get();
    }

    public function selectedSheet(): ?AttendanceSheet
    {
        $sheetId = request()->integer('sheetId');

        $query = AttendanceSheet::query()
            ->where('is_active', true)
            ->withCount(['sessions', 'participants']);

        if ($sheetId) {
            return $query->find($sheetId);
        }

        return $query->latest()->first();
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

    $sessionDate = $session->session_date->format('Y-m-d');

    $participants = AttendanceParticipant::query()
        ->with(['person.churchProfile'])
        ->where('attendance_sheet_id', $session->attendance_sheet_id)
        ->where('is_active', true)
        ->where(function ($query) use ($sessionDate): void {
            $query->whereNull('starts_on')
                ->orWhere('starts_on', '<=', $sessionDate);
        })
        ->where(function ($query) use ($sessionDate): void {
            $query->whereNull('ends_on')
                ->orWhere('ends_on', '>=', $sessionDate);
        })
        ->get();

    $recordPersonIds = AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->pluck('person_id');

    $recordPeople = Person::query()
        ->with('churchProfile')
        ->whereIn('id', $recordPersonIds)
        ->get();

    $rows = $participants->keyBy(
        fn (AttendanceParticipant $participant): int =>
            (int) $participant->person_id
    );

    foreach ($recordPeople as $person) {
        $personId = (int) $person->id;

        if ($rows->has($personId)) {
            continue;
        }

        $participant = new AttendanceParticipant([
            'person_id' => $personId,
        ]);

        $participant->setRelation('person', $person);

        $rows->put($personId, $participant);
    }

    return $rows
        ->sortBy(
            fn (AttendanceParticipant $participant): string =>
                $participant->person?->display_name ?? ''
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

}
