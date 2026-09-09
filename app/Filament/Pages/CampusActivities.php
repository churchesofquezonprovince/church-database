<?php

namespace App\Filament\Pages;

use App\Support\LocalityOptions;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\CampusWorkActivity;
use App\Models\CampusWorkTerm;
use App\Models\School;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CampusActivities extends Page
{
    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.campus-activities';

    public function getTitle(): string
    {
        return 'Campus Activities';
    }

    public static function getNavigationLabel(): string
    {
        return 'Campus Activities';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Campus Work';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function terms(): Collection
    {
        return CampusWorkTerm::query()
            ->where('is_archived', false)
            ->orderByDesc('is_active')
            ->orderByDesc('academic_year')
            ->orderByDesc('id')
            ->get();
    }

    public function selectedTerm(): ?CampusWorkTerm
    {
        $termId = request()->integer('termId');

        if ($termId) {
            $term = CampusWorkTerm::query()->find($termId);

            if ($term) {
                return $term;
            }
        }

        return CampusWorkTerm::query()
            ->where('is_active', true)
            ->where('is_archived', false)
            ->latest('id')
            ->first()
            ?? CampusWorkTerm::query()
                ->where('is_archived', false)
                ->latest('id')
                ->first();
    }

    public function selectedType(): string
    {
        $type = (string) request('type', '');

        return array_key_exists(
            $type,
            CampusWorkActivity::typeOptions()
        )
            ? $type
            : '';
    }

    public function activities(): Collection
    {
        $term = $this->selectedTerm();

        return CampusWorkActivity::query()
            ->with([
                'term',
                'school',

                'attendanceSheet.sessions' => fn ($query) =>
                    $query
                        ->withCount([
                            'records',

                            'records as present_records_count' =>
                                fn ($query) =>
                                    $query->where(
                                        'is_present',
                                        true
                                    ),
                        ])
                        ->orderBy('session_date')
                        ->orderBy('id'),

                'attendanceSession.sheet',
            ])
            ->when(
                $term,
                fn ($query) =>
                    $query->where('campus_work_term_id', $term->id)
            )
            ->when(
                $this->selectedType(),
                fn ($query, $type) =>
                    $query->where('activity_type', $type)
            )
            ->orderByDesc('activity_date')
            ->orderByDesc('start_time')
            ->get();
    }

    public function activityTypeOptions(): array
    {
        return CampusWorkActivity::typeOptions();
    }

    public function termUrl(?CampusWorkTerm $term): string
    {
        $query = [];

        if ($term) {
            $query['termId'] = $term->id;
        }

        if ($this->selectedType()) {
            $query['type'] = $this->selectedType();
        }

        return static::getUrl()
            . ($query ? '?' . http_build_query($query) : '');
    }

    public function typeUrl(string $type): string
    {
        $query = [];

        if ($this->selectedTerm()) {
            $query['termId'] = $this->selectedTerm()->id;
        }

        if ($type !== '') {
            $query['type'] = $type;
        }

        return static::getUrl()
            . ($query ? '?' . http_build_query($query) : '');
    }

    public function schoolOptions(): Collection
    {
        return School::query()
            ->with('province')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function localityOptions(): array
    {
        return LocalityOptions::groupedActiveConfigured();
    }

    public function availableAttendanceSheets(): Collection
    {
        return AttendanceSheet::query()
            ->with([
                'sessions' => fn ($query) =>
                    $query
                        ->orderByDesc('session_date')
                        ->orderByDesc('id'),
                'localityRecord',
            ])
            ->where(
                'sheet_type',
                AttendanceSheet::TYPE_CUSTOM
            )
            ->whereDoesntHave(
                'campusActivity'
            )
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    public function attendanceSummary(
        CampusWorkActivity $activity
    ): array {
        $sheet = $activity->attendanceSheet;

        if (! $sheet) {
            return [
                'linked' => false,
                'mode' => null,
                'sessions' => 0,
                'marked' => 0,
                'present' => 0,
                'range' => null,
                'focus_session' => null,
                'focus_label' => null,
            ];
        }

        $sessions = $sheet->sessions
            ->sortBy([
                ['session_date', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $firstSession =
            $sessions->first();

        $lastSession =
            $sessions->last();

        $startDate =
            $sheet->start_date
            ?: $firstSession?->session_date;

        $endDate =
            $sheet->end_date
            ?: $lastSession?->session_date;

        $range = null;

        if ($startDate && $endDate) {
            $range =
                $startDate->isSameDay($endDate)
                    ? $startDate->format('M d, Y')
                    : $startDate->format('M d, Y')
                        . ' – '
                        . $endDate->format('M d, Y');
        }

        $today = now()->startOfDay();

        $nextSession =
            $sessions->first(
                fn ($session): bool =>
                    $session->session_date
                        && $session
                            ->session_date
                            ->greaterThanOrEqualTo(
                                $today
                            )
            );

        $latestSession =
            $sessions
                ->filter(
                    fn ($session): bool =>
                        $session->session_date
                        && $session
                            ->session_date
                            ->lessThanOrEqualTo(
                                $today
                            )
                )
                ->last();

        $focusSession =
            $nextSession
            ?: $latestSession
            ?: $firstSession;

        $focusLabel = null;

        if ($focusSession?->session_date) {
            $focusLabel =
                $nextSession
                    ? 'Open Next Session'
                    : 'Open Latest Session';
        }

        return [
            'linked' => true,

            'mode' =>
                $sheet->is_one_time
                    ? 'One-time'
                    : 'Recurring',

            'sessions' =>
                $sessions->count(),

            'marked' =>
                (int) $sessions->sum(
                    'records_count'
                ),

            'present' =>
                (int) $sessions->sum(
                    'present_records_count'
                ),

            'range' =>
                $range,

            'focus_session' =>
                $focusSession,

            'focus_label' =>
                $focusLabel,
        ];
    }

    public function attendanceSessionUrl(
        AttendanceSession $session
    ): string {
        return CheckAttendance::getUrl()
            . '?'
            . http_build_query([
                'sheetId' =>
                    $session->attendance_sheet_id,

                'sessionId' =>
                    $session->id,
            ]);
    }

    public function attendanceUrl(
        CampusWorkActivity $activity
    ): ?string {
        if (
            $activity->attendanceSheet
            && $activity->attendanceSession
        ) {
            return CheckAttendance::getUrl()
                . '?'
                . http_build_query([
                    'sheetId' =>
                        $activity->attendance_sheet_id,

                    'sessionId' =>
                        $activity->attendance_session_id,
                ]);
        }

        if ($activity->attendanceSheet) {
            return AttendanceSheets::getUrl()
                . '?'
                . http_build_query([
                    'sheetId' =>
                        $activity->attendance_sheet_id,
                ]);
        }

        return null;
    }
}
