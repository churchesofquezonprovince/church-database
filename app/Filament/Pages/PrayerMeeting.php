<?php

namespace App\Filament\Pages;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\Person;
use App\Support\LocalityOptions;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class PrayerMeeting extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    private const MAIN_ATTENDANCE_STATUSES = ['Active', 'New One'];

    protected string $view = 'filament.pages.prayer-meeting';

    public function otherLocalityCandidates(): \Illuminate\Support\Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return collect();
        }

        $visiblePersonIds = $this->people()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $alreadyPresentPersonIds = $this->presentPersonIds();

        return Person::query()
            ->with(['churchProfile'])
            ->when($visiblePersonIds !== [], fn ($query) => $query->whereNotIn('id', $visiblePersonIds))
            ->when($alreadyPresentPersonIds !== [], fn ($query) => $query->whereNotIn('id', $alreadyPresentPersonIds))
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function getTitle(): string
    {
        return 'Prayer Meeting';
    }

    public static function getNavigationLabel(): string
    {
        return 'Prayer Meeting';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-hand-raised';
    }

    public static function getNavigationSort(): ?int
    {
        return 50;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function localities(): Collection
    {
        return LocalityOptions::primaryProvinceNamesWithPeople();
    }

    public function selectedLocality(): ?string
    {
        $locality = request()->query('locality');

        if (filled($locality)) {
            $localityRecord = LocalityOptions::primaryProvinceLocality(
                (string) $locality
            );

            if ($localityRecord) {
                return $localityRecord->name;
            }
        }

        return $this->localities()->first();
    }

    public function selectedSheet(): ?AttendanceSheet
    {
        $localityRecord = LocalityOptions::primaryProvinceLocality(
            $this->selectedLocality()
        );

        if (! $localityRecord) {
            return null;
        }

        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_PRAYER_MEETING)
            ->where('locality_id', $localityRecord->id)
            ->first();
    }

    public function selectedMeetingDay(): int
    {
        $meetingDay = request()->query('meeting_day');

        if (filled($meetingDay)) {
            return (int) $meetingDay;
        }

        return (int) ($this->selectedSheet()?->meeting_day ?? 2);
    }

    public function selectedMeetingDate(): string
    {
        $date = request()->query('meeting_date');

        if (filled($date)) {
            return CarbonImmutable::parse((string) $date)->toDateString();
        }

        $meetingDay = $this->selectedMeetingDay();
        $today = CarbonImmutable::today();

        return $today->dayOfWeek === $meetingDay
            ? $today->toDateString()
            : $today->next($meetingDay)->toDateString();
    }

    public function people(): Collection
    {
        $locality = $this->selectedLocality();

        if (! $locality) {
            return collect();
        }

        $localityRecord = LocalityOptions::primaryProvinceLocality($locality);

        if (! $localityRecord) {
            return collect();
        }

        return Person::query()
            ->with(['churchProfile'])
            ->where('locality_id', $localityRecord->id)
            ->whereHas(
                'churchProfile',
                fn ($query) => $query->whereIn('status', self::MAIN_ATTENDANCE_STATUSES)
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function hiddenStatusPeople(): Collection
    {
        $locality = $this->selectedLocality();

        if (! $locality) {
            return collect();
        }

        $localityRecord = LocalityOptions::primaryProvinceLocality($locality);

        if (! $localityRecord) {
            return collect();
        }

        return Person::query()
            ->with(['churchProfile'])
            ->where('locality_id', $localityRecord->id)
            ->where(function ($query): void {
                $query
                    ->whereDoesntHave('churchProfile')
                    ->orWhereHas(
                        'churchProfile',
                        fn ($query) => $query->whereNotIn('status', self::MAIN_ATTENDANCE_STATUSES)
                    );
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function selectedSession(): ?AttendanceSession
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return null;
        }

        return AttendanceSession::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->whereDate('session_date', $this->selectedMeetingDate())
            ->first();
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


    public function absentPersonIds(): array
    {
        $session = $this->selectedSession();

        if (! $session) {
            return [];
        }

        return AttendanceRecord::query()
            ->where('attendance_session_id', $session->id)
            ->where('is_present', false)
            ->pluck('person_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function counts(): array
    {
        $session = $this->selectedSession();
        $people = $this->people();

        $visiblePersonIds = $people
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (! $session) {
            return [
                'present' => 0,
                'absent' => 0,
                'people' => $people->count(),
                'other_status' => method_exists($this, 'hiddenStatusPeople')
                    ? $this->hiddenStatusPeople()->count()
                    : 0,
            ];
        }

        $visiblePresent = $visiblePersonIds === []
            ? 0
            : AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->whereIn('person_id', $visiblePersonIds)
                ->where('is_present', true)
                ->count();

        $visibleAbsent = $visiblePersonIds === []
            ? 0
            : AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->whereIn('person_id', $visiblePersonIds)
                ->where('is_present', false)
                ->count();

        return [
            'present' => $visiblePresent + $this->otherLocalityPresentRecords()->count(),
            'absent' => $visibleAbsent,
            'people' => $people->count(),
            'other_status' => method_exists($this, 'hiddenStatusPeople')
                ? $this->hiddenStatusPeople()->count()
                : 0,
        ];
    }


    public function otherLocalityPresentRecords(): Collection
    {
        $session = $this->selectedSession();
        $sheet = $this->selectedSheet();

        if (! $session || ! $sheet) {
            return collect();
        }

        $visiblePersonIds = $this->people()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return AttendanceRecord::query()
            ->with('person.churchProfile')
            ->where('attendance_session_id', $session->id)
            ->where('is_present', true)
            ->whereNotNull('person_id')
            ->when($visiblePersonIds !== [], fn ($query) => $query->whereNotIn('person_id', $visiblePersonIds))
            ->orderBy('marked_at')
            ->get();
    }

    public function localityLabel(?string $locality = null): string
    {
        $locality ??= $this->selectedLocality();

        return $locality === '__no_locality'
            ? 'No Locality'
            : (string) $locality;
    }

    public function dayLabel(int $day): string
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ][$day] ?? 'Tuesday';
    }
}
