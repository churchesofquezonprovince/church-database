<?php

namespace App\Filament\Pages;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\Person;
use App\Support\ChurchProfileOptions;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AttendanceReports extends Page
{
    private const MAIN_ATTENDANCE_STATUSES = ['Active', 'New One'];

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

    public function selectedReportMonth(): ?string
    {
        $value = request()->query('report_month');

        return filled($value) ? (string) $value : null;
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

    public function trendPeriodOptions(): array
    {
        return [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
        ];
    }

    public function selectedTrendPeriod(): string
    {
        $value = request()->query('trend_period', 'weekly');

        return array_key_exists((string) $value, $this->trendPeriodOptions())
            ? (string) $value
            : 'weekly';
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

    public function hasInvalidDateRange(): bool
    {
        if (! $this->selectedDateFrom() || ! $this->selectedDateTo()) {
            return false;
        }

        return $this->selectedDateTo() < $this->selectedDateFrom();
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

        if (! $sheet || $this->hasInvalidDateRange()) {
            return collect();
        }

        return AttendanceSession::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->when($this->selectedDateFrom(), fn ($query, $date) => $query->whereDate('session_date', '>=', $date))
            ->when($this->selectedDateTo(), fn ($query, $date) => $query->whereDate('session_date', '<=', $date))
            ->when(! is_null($this->selectedMeetingDayFilter()), fn ($query) => $query->whereRaw('DAYOFWEEK(session_date) = ?', [$this->selectedMeetingDayFilter() + 1]))
            ->withCount([
                'records as present_count' => function ($query): void {
                    $query
                        ->where('is_present', true)
                        ->when($this->selectedCategory(), fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category)));
                },

                'records as absent_count' => function ($query): void {
                    $query
                        ->where('is_present', false)
                        ->when($this->selectedCategory(), fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category)));

                    $this->applyMainAttendanceStatusFilter($query);
                },
            ])
            ->orderBy('session_date')
            ->get()
            ->map(function (AttendanceSession $session) use ($sheet): array {
                $expected = $this->activeParticipantCountForDate(
                    sheetId: $sheet->id,
                    date: $session->session_date->format('Y-m-d'),
                );

                $present = (int) $session->present_count;
                $absent = (int) $session->absent_count;
                $marked = $present + $absent;

                return [
                    'session' => $session,
                    'active_participants' => $expected,
                    'present' => $present,
                    'absent' => $absent,
                    'marked' => $marked,
                    'unmarked' => max($expected - $marked, 0),
                    'rate' => $marked > 0
                        ? round(($present / $marked) * 100, 1)
                        : 0,
                ];
            });
    }

    public function attendanceTrendRows(): Collection
    {
        $meetingRows = $this->meetingRows();

        if ($meetingRows->isEmpty()) {
            return collect();
        }

        $period = $this->selectedTrendPeriod();

        $groups = $meetingRows
            ->groupBy(function (array $row) use ($period): string {
                $date = CarbonImmutable::parse($row['session']->session_date);

                return $period === 'monthly'
                    ? $date->format('Y-m')
                    : $date->startOfWeek(\Carbon\CarbonInterface::SUNDAY)->format('Y-m-d');
            })
            ->sortKeys();

        $previousRate = null;

        return $groups
            ->map(function (Collection $rows, string $key) use ($period, &$previousRate): array {
                if ($period === 'monthly') {
                    $periodStart = CarbonImmutable::parse($key . '-01');
                    $label = $periodStart->format('F Y');
                } else {
                    $periodStart = CarbonImmutable::parse($key);
                    $periodEnd = $periodStart->addDays(6);
                    $label = $periodStart->format('M d') . ' - ' . $periodEnd->format('M d, Y');
                }

                $expected = (int) $rows->sum('active_participants');
                $present = (int) $rows->sum('present');
                $absent = (int) $rows->sum('absent');
                $unmarked = (int) $rows->sum('unmarked');

                $marked = $present + $absent;

                $rate = $marked > 0
                    ? round(($present / $marked) * 100, 1)
                    : 0;

                $change = $previousRate === null
                    ? null
                    : round($rate - $previousRate, 1);

                $previousRate = $rate;

                return [
                    'period' => $key,
                    'label' => $label,
                    'meetings' => $rows->count(),
                    'expected' => $expected,
                    'present' => $present,
                    'absent' => $absent,
                    'unmarked' => $unmarked,
                    'rate' => $rate,
                    'change' => $change,
                ];
            })
            ->values();
    }

    public function personRows(): Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet || $this->hasInvalidDateRange()) {
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

                $absent = $this->hasMainAttendanceStatus($participant->person)
                    ? AttendanceRecord::query()
                        ->whereIn('attendance_session_id', $sessionIds)
                        ->where('person_id', $participant->person_id)
                        ->where('is_present', false)
                        ->count()
                    : 0;

                $marked = $present + $absent;

                return [
                    'participant' => $participant,
                    'person' => $participant->person,
                    'expected' => $expected,
                    'present' => $present,
                    'absent' => $absent,
                    'marked' => $marked,
                    'unmarked' => max($expected - $marked, 0),
                    'rate' => $marked > 0
                        ? round(($present / $marked) * 100, 1)
                        : 0,
                ];
            });
    }



    public function categorySummaryRows(): Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet || $this->hasInvalidDateRange()) {
            return collect();
        }

        $categories = AttendanceParticipant::query()
            ->with('person.churchProfile')
            ->where('attendance_sheet_id', $sheet->id)
            ->get()
            ->map(fn (AttendanceParticipant $participant): string => $participant->person?->churchProfile?->category ?: 'No category')
            ->unique()
            ->sort()
            ->values();

        return $categories
            ->map(function (string $category) use ($sheet): array {
                $participantQuery = AttendanceParticipant::query()
                    ->with('person.churchProfile')
                    ->where('attendance_sheet_id', $sheet->id)
                    ->where('is_active', true);

                if ($category === 'No category') {
                    $participantQuery->where(function ($query): void {
                        $query->whereDoesntHave('person.churchProfile')
                            ->orWhereHas('person.churchProfile', fn ($query) => $query->whereNull('category')->orWhere('category', ''));
                    });
                } else {
                    $participantQuery->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category));
                }

                $participants = $participantQuery->count();

                $sessions = AttendanceSession::query()
                    ->where('attendance_sheet_id', $sheet->id)
                    ->when($this->selectedDateFrom(), fn ($query, $date) => $query->whereDate('session_date', '>=', $date))
                    ->when($this->selectedDateTo(), fn ($query, $date) => $query->whereDate('session_date', '<=', $date))
                    ->when(! is_null($this->selectedMeetingDayFilter()), fn ($query) => $query->whereRaw('DAYOFWEEK(session_date) = ?', [$this->selectedMeetingDayFilter() + 1]))
                    ->get();

                $sessionIds = $sessions->pluck('id');

                $expectedTotal = $sessions->sum(function (AttendanceSession $session) use ($sheet, $category): int {
                    $date = $session->session_date->format('Y-m-d');

                    $query = AttendanceParticipant::query()
                        ->where('attendance_sheet_id', $sheet->id)
                        ->where('is_active', true)
                        ->where(function ($query) use ($date): void {
                            $query->whereNull('starts_on')
                                ->orWhere('starts_on', '<=', $date);
                        })
                        ->where(function ($query) use ($date): void {
                            $query->whereNull('ends_on')
                                ->orWhere('ends_on', '>=', $date);
                        });

                    if ($category === 'No category') {
                        $query->where(function ($query): void {
                            $query->whereDoesntHave('person.churchProfile')
                                ->orWhereHas('person.churchProfile', fn ($query) => $query->whereNull('category')->orWhere('category', ''));
                        });
                    } else {
                        $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category));
                    }

                    return $query->count();
                });

                $recordQuery = AttendanceRecord::query()
                    ->whereIn('attendance_session_id', $sessionIds);

                if ($category === 'No category') {
                    $recordQuery->where(function ($query): void {
                        $query->whereDoesntHave('person.churchProfile')
                            ->orWhereHas('person.churchProfile', fn ($query) => $query->whereNull('category')->orWhere('category', ''));
                    });
                } else {
                    $recordQuery->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category));
                }

                $presentTotal = (clone $recordQuery)
                    ->where('is_present', true)
                    ->count();

                $absentQuery = clone $recordQuery;
                $this->applyMainAttendanceStatusFilter($absentQuery);

                $absentTotal = $absentQuery
                    ->where('is_present', false)
                    ->count();

                $markedTotal = $presentTotal + $absentTotal;

                return [
                    'category' => $category,
                    'participants' => $participants,
                    'expected' => $expectedTotal,
                    'present' => $presentTotal,
                    'absent' => $absentTotal,
                    'unmarked' => max($expectedTotal - $markedTotal, 0),
                    'rate' => $markedTotal > 0
                        ? round(($presentTotal / $markedTotal) * 100, 1)
                        : 0,
                ];
            })
            ->values();
    }

    public function localitySummaryRows(): Collection
    {
        if ($this->hasInvalidDateRange()) {
            return collect();
        }

        if (! in_array($this->selectedReportType(), [
            AttendanceSheet::TYPE_LORDS_TABLE,
            AttendanceSheet::TYPE_PRAYER_MEETING,
        ], true)) {
            return collect();
        }

        return AttendanceSheet::query()
            ->where('sheet_type', $this->selectedReportType())
            ->withCount(['participants'])
            ->orderByRaw('CASE WHEN locality IS NULL OR locality = "" THEN 1 ELSE 0 END')
            ->orderBy('locality')
            ->get()
            ->map(function (AttendanceSheet $sheet): array {
                $sessions = AttendanceSession::query()
                    ->where('attendance_sheet_id', $sheet->id)
                    ->when($this->selectedDateFrom(), fn ($query, $date) => $query->whereDate('session_date', '>=', $date))
                    ->when($this->selectedDateTo(), fn ($query, $date) => $query->whereDate('session_date', '<=', $date))
                    ->when(! is_null($this->selectedMeetingDayFilter()), fn ($query) => $query->whereRaw('DAYOFWEEK(session_date) = ?', [$this->selectedMeetingDayFilter() + 1]))
                    ->withCount([
                        'records as present_count' => function ($query): void {
                            $query
                                ->where('is_present', true)
                                ->when($this->selectedCategory(), fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category)));
                        },

                        'records as absent_count' => function ($query): void {
                            $query
                                ->where('is_present', false)
                                ->when($this->selectedCategory(), fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category)));

                            $this->applyMainAttendanceStatusFilter($query);
                        },
                    ])
                    ->orderBy('session_date')
                    ->get();

                $participants = AttendanceParticipant::query()
                    ->where('attendance_sheet_id', $sheet->id)
                    ->where('is_active', true)
                    ->when($this->selectedCategory(), fn ($query, $category) => $query->whereHas('person.churchProfile', fn ($query) => $query->where('category', $category)))
                    ->count();

                $expectedTotal = $sessions->sum(function (AttendanceSession $session) use ($sheet): int {
                    return $this->activeParticipantCountForDate(
                        sheetId: $sheet->id,
                        date: $session->session_date->format('Y-m-d'),
                    );
                });

                $presentTotal = (int) $sessions->sum('present_count');
                $absentTotal = (int) $sessions->sum('absent_count');
                $markedTotal = $presentTotal + $absentTotal;

                return [
                    'sheet' => $sheet,
                    'locality' => $this->sheetLabel($sheet),
                    'meetings' => $sessions->count(),
                    'participants' => $participants,
                    'expected' => $expectedTotal,
                    'present' => $presentTotal,
                    'absent' => $absentTotal,
                    'unmarked' => max($expectedTotal - $markedTotal, 0),
                    'rate' => $markedTotal > 0
                        ? round(($presentTotal / $markedTotal) * 100, 1)
                        : 0,
                ];
            })
            ->values();
    }

    public function summary(): array
    {
        $meetingRows = $this->meetingRows();
        $personRows = $this->personRows();

        $expectedTotal = $personRows->sum('expected');
        $presentTotal = $personRows->sum('present');
        $absentTotal = $personRows->sum('absent');
        $unmarkedTotal = $personRows->sum('unmarked');

        return [
            'meetings' => $meetingRows->count(),
            'participants' => $personRows->count(),
            'expected_total' => $expectedTotal,
            'present_total' => $presentTotal,
            'absent_total' => $absentTotal,
            'unmarked_total' => $unmarkedTotal,
            'overall_rate' => ($presentTotal + $absentTotal) > 0
                ? round(($presentTotal / ($presentTotal + $absentTotal)) * 100, 1)
                : 0,
        ];
    }

    public function formatPercent(null|int|float $value): string
    {
        if ($value === null) {
            return '—';
        }

        $text = number_format((float) $value, 1);
        $text = rtrim(rtrim($text, '0'), '.');

        return $text . '%';
    }

    public function formatChangePercent(null|int|float $value): string
    {
        if ($value === null) {
            return '—';
        }

        $prefix = $value > 0 ? '+' : '';

        return $prefix . $this->formatPercent($value);
    }

    public function trendPeriodUrl(string $period): string
    {
        return self::getUrl() . '?' . http_build_query(array_filter([
            'report_type' => $this->selectedReportType(),
            'sheetId' => $this->selectedSheet()?->id,
            'report_month' => $this->selectedReportMonth(),
            'date_from' => $this->selectedDateFrom(),
            'date_to' => $this->selectedDateTo(),
            'category' => $this->selectedCategory(),
            'meeting_day' => $this->selectedMeetingDayFilter(),
            'trend_period' => $period,
        ], fn ($value): bool => $value !== null && $value !== ''));
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

    public function printUrl(): string
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return '#';
        }

        return route('quezonprovinceactivities.attendance-sheets.reports.print', [
            'report_type' => $this->selectedReportType(),
            'sheetId' => $sheet->id,
            'date_from' => $this->selectedDateFrom(),
            'date_to' => $this->selectedDateTo(),
            'category' => $this->selectedCategory(),
            'meeting_day' => $this->selectedMeetingDayFilter(),
        ]);
    }

    public function attendanceEntryUrl(): string
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return '#';
        }

        if ($sheet->sheet_type === AttendanceSheet::TYPE_CUSTOM) {
            return \App\Filament\Pages\CheckAttendance::getUrl() . '?' . http_build_query([
                'sheetId' => $sheet->id,
            ]);
        }

        $locality = $sheet->locality ?: '__no_locality';

        if ($sheet->sheet_type === AttendanceSheet::TYPE_LORDS_TABLE) {
            return \App\Filament\Pages\LordsTableMeeting::getUrl() . '?' . http_build_query([
                'locality' => $locality,
            ]);
        }

        if ($sheet->sheet_type === AttendanceSheet::TYPE_PRAYER_MEETING) {
            return \App\Filament\Pages\PrayerMeeting::getUrl() . '?' . http_build_query([
                'locality' => $locality,
                'meeting_day' => $sheet->meeting_day ?? 2,
            ]);
        }

        return '#';
    }

    public function attendanceEntryLabel(): string
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return 'Check Attendance';
        }

        return match ($sheet->sheet_type) {
            AttendanceSheet::TYPE_LORDS_TABLE => "Go to Lord's Table",
            AttendanceSheet::TYPE_PRAYER_MEETING => 'Go to Prayer Meeting',
            default => 'Check Attendance',
        };
    }

    public function exportUrl(): string
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return '#';
        }

        return route('quezonprovinceactivities.attendance-sheets.reports.export', [
            'report_type' => $this->selectedReportType(),
            'sheetId' => $sheet->id,
            'date_from' => $this->selectedDateFrom(),
            'date_to' => $this->selectedDateTo(),
            'category' => $this->selectedCategory(),
            'meeting_day' => $this->selectedMeetingDayFilter(),
        ]);
    }

    public function clearFiltersUrl(): string
    {
        $sheet = $this->selectedSheet();

        return self::getUrl() . '?' . http_build_query([
            'report_type' => $this->selectedReportType(),
            'sheetId' => $sheet?->id,
        ]);
    }

    private function separatesUnmarkedStatuses(): bool
    {
        return in_array($this->selectedReportType(), [
            AttendanceSheet::TYPE_LORDS_TABLE,
            AttendanceSheet::TYPE_PRAYER_MEETING,
        ], true);
    }

    private function applyMainAttendanceStatusFilter($query): void
    {
        if (! $this->separatesUnmarkedStatuses()) {
            return;
        }

        $query->whereHas(
            'person.churchProfile',
            fn ($query) => $query->whereIn('status', self::MAIN_ATTENDANCE_STATUSES),
        );
    }

    private function hasMainAttendanceStatus(?Person $person): bool
    {
        if (! $this->separatesUnmarkedStatuses()) {
            return true;
        }

        return in_array($person?->churchProfile?->status, self::MAIN_ATTENDANCE_STATUSES, true);
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
