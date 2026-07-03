<?php

namespace App\Filament\Pages;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Support\ChurchProfileOptions;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AttendanceReports extends Page
{
    protected string $view = 'filament.pages.attendance-reports';

    public function getTitle(): string
    {
        return 'Attendance Reports';
    }

    public static function getNavigationLabel(): string
    {
        return 'Attendance Reports';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar-square';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function reportTypes(): array
    {
        return [
            AttendanceSheet::TYPE_CUSTOM => 'Custom Attendance Sheets',
            AttendanceSheet::TYPE_LORDS_TABLE => "Lord's Table Meeting",
            AttendanceSheet::TYPE_PRAYER_MEETING => 'Prayer Meeting',
        ];
    }

    public function selectedReportType(): string
    {
        $type = request()->query('report_type');

        return in_array($type, array_keys($this->reportTypes()), true)
            ? (string) $type
            : AttendanceSheet::TYPE_CUSTOM;
    }

    public function selectedDateFrom(): ?string
    {
        $value = request()->query('date_from');

        return filled($value) ? (string) $value : null;
    }

    public function selectedDateTo(): ?string
    {
        $value = request()->query('date_to');

        return filled($value) ? (string) $value : null;
    }

    public function selectedCategory(): ?string
    {
        $value = request()->query('category');

        return filled($value) ? (string) $value : null;
    }

    public function selectedMeetingDayFilter(): ?int
    {
        $value = request()->query('meeting_day');

        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $day = (int) $value;

        return $day >= 0 && $day <= 6 ? $day : null;
    }

    public function dayOptions(): array
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    }

    public function categoryOptions(): array
    {
        return ChurchProfileOptions::categories();
    }

    public function sheets(): Collection
    {
        return AttendanceSheet::query()
            ->where('sheet_type', $this->selectedReportType())
            ->withCount(['sessions', 'participants'])
            ->orderByRaw('CASE WHEN locality IS NULL OR locality = "" THEN 1 ELSE 0 END')
            ->orderBy('locality')
            ->latest()
            ->get();
    }

    public function customSheets(): Collection
    {
        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
            ->withCount(['sessions', 'participants'])
            ->latest()
            ->get();
    }

    public function lordsTableSheets(): Collection
    {
        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_LORDS_TABLE)
            ->withCount(['sessions', 'participants'])
            ->orderByRaw('CASE WHEN locality IS NULL OR locality = "" THEN 1 ELSE 0 END')
            ->orderBy('locality')
            ->get();
    }

    public function prayerMeetingSheets(): Collection
    {
        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_PRAYER_MEETING)
            ->withCount(['sessions', 'participants'])
            ->orderByRaw('CASE WHEN locality IS NULL OR locality = "" THEN 1 ELSE 0 END')
            ->orderBy('locality')
            ->get();
    }

    public function selectedSheet(): ?AttendanceSheet
    {
        $sheetId = request()->integer('sheetId');

        $query = AttendanceSheet::query()
            ->where('sheet_type', $this->selectedReportType())
            ->withCount(['sessions', 'participants']);

        if ($sheetId) {
            return $query->find($sheetId);
        }

        return $query
            ->orderByRaw('CASE WHEN locality IS NULL OR locality = "" THEN 1 ELSE 0 END')
            ->orderBy('locality')
            ->latest()
            ->first();
    }

    public function meetingRows(): Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return collect();
        }

        return AttendanceSession::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->when($this->selectedDateFrom(), fn ($query, $date) => $query->whereDate('session_date', '>=', $date))
            ->when($this->selectedDateTo(), fn ($query, $date) => $query->whereDate('session_date', '<=', $date))
            ->when(! is_null($this->selectedMeetingDayFilter()), fn ($query) => $query->whereRaw('DAYOFWEEK(session_date) = ?', [$this->selectedMeetingDayFilter() + 1]))
            ->withCount([
                'records as present_count' => fn ($query) => $query
                    ->where('is_present', true)
                    ->when($this->selectedCategory(), fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category))),

                'records as absent_count' => fn ($query) => $query
                    ->where('is_present', false)
                    ->when($this->selectedCategory(), fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category))),

                'records as marked_count' => fn ($query) => $query
                    ->when($this->selectedCategory(), fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category))),
            ])
            ->orderBy('session_date')
            ->get()
            ->map(function (AttendanceSession $session) use ($sheet): array {
                $activeParticipants = $this->activeParticipantCountForDate(
                    sheetId: $sheet->id,
                    date: $session->session_date->format('Y-m-d'),
                );

                $rate = $activeParticipants > 0
                    ? round(($session->present_count / $activeParticipants) * 100, 1)
                    : 0;

                return [
                    'session' => $session,
                    'active_participants' => $activeParticipants,
                    'present' => $session->present_count,
                    'absent' => $session->absent_count,
                    'marked' => $session->marked_count,
                    'unmarked' => max($activeParticipants - $session->marked_count, 0),
                    'rate' => $rate,
                ];
            });
    }

    public function personRows(): Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return collect();
        }

        return AttendanceParticipant::query()
            ->with(['person.churchProfile'])
            ->where('attendance_sheet_id', $sheet->id)
            ->when($this->selectedCategory(), fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category)))
            ->get()
            ->sortBy(fn (AttendanceParticipant $participant): string => $participant->person?->display_name ?? '')
            ->values()
            ->map(function (AttendanceParticipant $participant) use ($sheet): array {
                $sessionIds = AttendanceSession::query()
                    ->where('attendance_sheet_id', $sheet->id)
                    ->when($participant->starts_on, fn ($query) => $query->whereDate('session_date', '>=', $participant->starts_on))
                    ->when($participant->ends_on, fn ($query) => $query->whereDate('session_date', '<=', $participant->ends_on))
                    ->when($this->selectedDateFrom(), fn ($query, $date) => $query->whereDate('session_date', '>=', $date))
                    ->when($this->selectedDateTo(), fn ($query, $date) => $query->whereDate('session_date', '<=', $date))
                    ->when(! is_null($this->selectedMeetingDayFilter()), fn ($query) => $query->whereRaw('DAYOFWEEK(session_date) = ?', [$this->selectedMeetingDayFilter() + 1]))
                    ->pluck('id');

                $expected = $sessionIds->count();

                $present = AttendanceRecord::query()
                    ->whereIn('attendance_session_id', $sessionIds)
                    ->where('person_id', $participant->person_id)
                    ->where('is_present', true)
                    ->count();

                $absent = AttendanceRecord::query()
                    ->whereIn('attendance_session_id', $sessionIds)
                    ->where('person_id', $participant->person_id)
                    ->where('is_present', false)
                    ->count();

                $marked = $present + $absent;

                $rate = $expected > 0
                    ? round(($present / $expected) * 100, 1)
                    : 0;

                return [
                    'participant' => $participant,
                    'person' => $participant->person,
                    'expected' => $expected,
                    'present' => $present,
                    'absent' => $absent,
                    'marked' => $marked,
                    'unmarked' => max($expected - $marked, 0),
                    'rate' => $rate,
                ];
            });
    }

    public function summary(): array
    {
        $meetingRows = $this->meetingRows();
        $personRows = $this->personRows();

        $expectedTotal = $personRows->sum('expected');
        $presentTotal = $personRows->sum('present');
        $absentTotal = $personRows->sum('absent');

        return [
            'meetings' => $meetingRows->count(),
            'participants' => $personRows->count(),
            'expected_total' => $expectedTotal,
            'present_total' => $presentTotal,
            'absent_total' => $absentTotal,
            'overall_rate' => $expectedTotal > 0
                ? round(($presentTotal / $expectedTotal) * 100, 1)
                : 0,
        ];
    }

    public function sheetUrl(AttendanceSheet $sheet): string
    {
        return self::getUrl() . '?' . http_build_query([
            'report_type' => $sheet->sheet_type,
            'sheetId' => $sheet->id,
            'date_from' => $this->selectedDateFrom(),
            'date_to' => $this->selectedDateTo(),
            'category' => $this->selectedCategory(),
            'meeting_day' => $this->selectedMeetingDayFilter(),
        ]);
    }

    public function reportTypeUrl(string $type): string
    {
        return self::getUrl() . '?' . http_build_query([
            'report_type' => $type,
        ]);
    }

    public function selectedReportTypeLabel(): string
    {
        return $this->reportTypes()[$this->selectedReportType()] ?? 'Attendance Reports';
    }

    public function reportPeriodLabel(?AttendanceSheet $sheet): string
    {
        if (! $sheet) {
            return 'No date range';
        }

        $from = $this->selectedDateFrom()
            ? CarbonImmutable::parse($this->selectedDateFrom())->format('M d, Y')
            : optional($sheet->start_date)->format('M d, Y');

        $to = $this->selectedDateTo()
            ? CarbonImmutable::parse($this->selectedDateTo())->format('M d, Y')
            : optional($sheet->end_date)->format('M d, Y');

        if (! $from) {
            $from = 'No start date';
        }

        if (! $to) {
            $to = 'Present';
        }

        return $from . ' to ' . $to;
    }

    public function activeFilterLabel(): string
    {
        $filters = [];

        if ($this->selectedDateFrom()) {
            $filters[] = 'from ' . CarbonImmutable::parse($this->selectedDateFrom())->format('M d, Y');
        }

        if ($this->selectedDateTo()) {
            $filters[] = 'to ' . CarbonImmutable::parse($this->selectedDateTo())->format('M d, Y');
        }

        if ($this->selectedCategory()) {
            $filters[] = 'category: ' . $this->selectedCategory();
        }

        if (! is_null($this->selectedMeetingDayFilter())) {
            $filters[] = 'meeting day: ' . ($this->dayOptions()[$this->selectedMeetingDayFilter()] ?? 'Unknown');
        }

        return $filters === []
            ? 'No active filters'
            : implode(' · ', $filters);
    }

    public function sheetLabel(AttendanceSheet $sheet): string
    {
        if ($sheet->sheet_type === AttendanceSheet::TYPE_CUSTOM) {
            return $sheet->title;
        }

        return $sheet->locality ?: 'No Locality';
    }

    public function clearFiltersUrl(): string
    {
        $sheet = $this->selectedSheet();

        return self::getUrl() . '?' . http_build_query([
            'report_type' => $this->selectedReportType(),
            'sheetId' => $sheet?->id,
        ]);
    }

    private function activeParticipantCountForDate(int $sheetId, string $date): int
    {
        return AttendanceParticipant::query()
            ->where('attendance_sheet_id', $sheetId)
            ->where('is_active', true)
            ->when($this->selectedCategory(), fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category)))
            ->where(function ($query) use ($date): void {
                $query->whereNull('starts_on')
                    ->orWhere('starts_on', '<=', $date);
            })
            ->where(function ($query) use ($date): void {
                $query->whereNull('ends_on')
                    ->orWhere('ends_on', '>=', $date);
            })
            ->count();
    }
}
