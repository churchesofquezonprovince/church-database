<?php

namespace App\Filament\Pages;

use App\Models\AttendanceSession;
use App\Models\CampusWorkActivity;
use App\Models\GospelContact;
use App\Models\ShepherdingContact;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Throwable;

class GowReport extends Page
{
    protected string $view =
        'filament.pages.gow-report';

    protected static ?string $slug =
        'gow-report';

    public string $weekStart;

    public function mount(): void
    {
        $requested = trim(
            (string) request()->query('week', '')
        );

        try {
            $date = filled($requested)
                ? CarbonImmutable::parse($requested)
                : CarbonImmutable::now();
        } catch (Throwable) {
            $date = CarbonImmutable::now();
        }

        $this->weekStart = $date
            ->startOfWeek(CarbonInterface::MONDAY)
            ->format('Y-m-d');
    }

    public function getTitle(): string
    {
        return 'GOW Report';
    }

    public static function getNavigationLabel(): string
    {
        return 'GOW Report';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shepherding';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar-square';
    }

    public static function getNavigationSort(): ?int
    {
        return 8;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }

    public function startDate(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $this->weekStart
        )->startOfDay();
    }

    public function endDate(): CarbonImmutable
    {
        return $this->startDate()
            ->addDays(6)
            ->endOfDay();
    }

    public function periodLabel(): string
    {
        return $this->startDate()->format('M d')
            . ' – '
            . $this->endDate()->format('M d, Y');
    }

    public function weekUrl(int $weeks): string
    {
        $date = $this->startDate()
            ->addWeeks($weeks)
            ->format('Y-m-d');

        return static::getUrl()
            . '?week='
            . urlencode($date);
    }

    public function currentWeekUrl(): string
    {
        $date = CarbonImmutable::now()
            ->startOfWeek(CarbonInterface::MONDAY)
            ->format('Y-m-d');

        return static::getUrl()
            . '?week='
            . urlencode($date);
    }

    public function isCurrentWeek(): bool
    {
        return $this->startDate()->isSameDay(
            CarbonImmutable::now()
                ->startOfWeek(
                    CarbonInterface::MONDAY
                )
        );
    }

    public function campusActivitySources()
    {
        return CampusWorkActivity::query()
            ->with([
                'school',
                'attendanceSheet',
                'attendanceSession',
            ])
            ->whereBetween(
                'activity_date',
                [
                    $this->startDate()->format('Y-m-d'),
                    $this->endDate()->format('Y-m-d'),
                ]
            )
            ->orderBy('activity_date')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();
    }

    public function newGospelContactSources()
    {
        return GospelContact::query()
            ->with([
                'person',
                'localityRecord',
            ])
            ->whereBetween(
                'created_at',
                [
                    $this->startDate(),
                    $this->endDate(),
                ]
            )
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function gospelWorkSources()
    {
        return ShepherdingContact::query()
            ->with([
                'activityTypes',
                'contactedPeople',
                'contactedHouseholds',
                'contactedCampusContacts',
                'contactedGospelContacts',
            ])
            ->whereBetween(
                'contact_date',
                [
                    $this->startDate()->format('Y-m-d'),
                    $this->endDate()->format('Y-m-d'),
                ]
            )
            ->whereHas(
                'activityTypes',
                fn ($query) =>
                    $query->where(
                        'category',
                        'Gospel Work'
                    )
            )
            ->orderBy('contact_date')
            ->orderBy('contact_time')
            ->orderBy('id')
            ->get();
    }

    public function shepherdingSources()
    {
        return ShepherdingContact::query()
            ->with([
                'activityTypes',
                'contactedPeople',
                'contactedHouseholds',
                'contactedCampusContacts',
                'contactedGospelContacts',
            ])
            ->whereBetween(
                'contact_date',
                [
                    $this->startDate()->format('Y-m-d'),
                    $this->endDate()->format('Y-m-d'),
                ]
            )
            ->orderBy('contact_date')
            ->orderBy('contact_time')
            ->orderBy('id')
            ->get();
    }

    public function campusAttendanceSources()
    {
        $startDate =
            $this->startDate()->format('Y-m-d');

        $endDate =
            $this->endDate()->format('Y-m-d');

        return AttendanceSession::query()
            ->with([
                'sheet.campusActivity',
            ])
            ->withCount([
                'records as attendees_count' =>
                    fn ($query) =>
                        $query->where(
                            'is_present',
                            true
                        ),
            ])
            ->whereBetween(
                'session_date',
                [$startDate, $endDate]
            )
            ->whereHas(
                'sheet.campusActivity'
            )
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->orderBy('id')
            ->get();
    }

    private function pagePath(
        string $url
    ): string {
        $path = parse_url(
            $url,
            PHP_URL_PATH
        );

        return is_string($path)
            && $path !== ''
                ? $path
                : $url;
    }

    public function campusActivitiesUrl(): string
    {
        return $this->pagePath(
            CampusActivities::getUrl()
        );
    }

    public function gospelContactsUrl(): string
    {
        return $this->pagePath(
            GospelContacts::getUrl()
        );
    }

    public function shepherdingHistoryUrl(): string
    {
        return $this->pagePath(
            ShepherdingHistory::getUrl()
        );
    }

    public function campusSessionUrl(
        AttendanceSession $session
    ): string {
        $path = parse_url(
            CheckAttendance::getUrl(),
            PHP_URL_PATH
        );

        return $path
            . '?'
            . http_build_query([
                'sheetId' =>
                    $session->attendance_sheet_id,

                'sessionId' =>
                    $session->id,
            ]);
    }

    public function report(): array
    {
        $start = $this->startDate();
        $end = $this->endDate();

        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');

        $newGospelContacts =
            GospelContact::query()
                ->whereBetween(
                    'created_at',
                    [$start, $end]
                )
                ->count();

        $shepherdingRecords =
            ShepherdingContact::query()
                ->whereBetween(
                    'contact_date',
                    [$startDate, $endDate]
                )
                ->count();

        $gospelWorkContacts =
            DB::table(
                'shepherding_contact_activities as sca'
            )
                ->join(
                    'shepherding_contacts as sc',
                    'sc.id',
                    '=',
                    'sca.shepherding_contact_id'
                )
                ->join(
                    'shepherding_activity_types as sat',
                    'sat.id',
                    '=',
                    'sca.shepherding_activity_type_id'
                )
                ->whereBetween(
                    'sc.contact_date',
                    [$startDate, $endDate]
                )
                ->where(
                    'sat.category',
                    'Gospel Work'
                )
                ->distinct()
                ->count(
                    'sca.shepherding_contact_id'
                );

        $activityRows =
            DB::table(
                'shepherding_contact_activities as sca'
            )
                ->join(
                    'shepherding_contacts as sc',
                    'sc.id',
                    '=',
                    'sca.shepherding_contact_id'
                )
                ->join(
                    'shepherding_activity_types as sat',
                    'sat.id',
                    '=',
                    'sca.shepherding_activity_type_id'
                )
                ->whereBetween(
                    'sc.contact_date',
                    [$startDate, $endDate]
                )
                ->whereIn(
                    'sat.code',
                    ['CO', 'PS', 'GT', 'EM']
                )
                ->select(
                    'sat.code',
                    'sat.name'
                )
                ->selectRaw(
                    'COUNT(*) as total'
                )
                ->groupBy(
                    'sat.code',
                    'sat.name'
                )
                ->get()
                ->keyBy('code');

        $activityDefinitions = [
            'CO' => 'Contact',
            'PS' => 'Pursuance',
            'GT' => 'Gospel Tract',
            'EM' => 'E-manna',
        ];

        $gospelActivities = collect(
            $activityDefinitions
        )
            ->map(
                fn (
                    string $name,
                    string $code
                ): array => [
                    'code' => $code,
                    'name' => $name,
                    'count' => (int) (
                        $activityRows
                            ->get($code)
                            ?->total
                        ?? 0
                    ),
                ]
            )
            ->values()
            ->all();

        $uniquePivotCount =
            function (
                string $table,
                string $column
            ) use (
                $startDate,
                $endDate
            ): int {
                return DB::table(
                    "{$table} as pivot"
                )
                    ->join(
                        'shepherding_contacts as sc',
                        'sc.id',
                        '=',
                        'pivot.shepherding_contact_id'
                    )
                    ->whereBetween(
                        'sc.contact_date',
                        [$startDate, $endDate]
                    )
                    ->distinct()
                    ->count(
                        "pivot.{$column}"
                    );
            };

        $campusActivities =
            DB::table(
                'campus_work_activities'
            )
                ->whereBetween(
                    'activity_date',
                    [$startDate, $endDate]
                )
                ->count();

        $campusSessions =
            $this->campusAttendanceSources();

        $presentMarks =
            (int) $campusSessions->sum(
                'attendees_count'
            );

        $campusSessionIds =
            $campusSessions->pluck('id');

        $uniquePeoplePresent = 0;

        if ($campusSessionIds->isNotEmpty()) {
            $uniquePeoplePresent =
                DB::table('attendance_records')
                    ->whereIn(
                        'attendance_session_id',
                        $campusSessionIds
                    )
                    ->where(
                        'is_present',
                        true
                    )
                    ->whereNotNull('person_id')
                    ->distinct()
                    ->count('person_id');
        }

        return [
            'gospel' => [
                'new_contacts' =>
                    $newGospelContacts,

                'work_contacts' =>
                    $gospelWorkContacts,

                'activities' =>
                    $gospelActivities,
            ],

            'shepherding' => [
                'records' =>
                    $shepherdingRecords,

                'people' =>
                    $uniquePivotCount(
                        'shepherding_contact_people',
                        'person_id'
                    ),

                'households' =>
                    $uniquePivotCount(
                        'shepherding_contact_households',
                        'household_id'
                    ),

                'campus_contacts' =>
                    $uniquePivotCount(
                        'shepherding_contact_campus_contacts',
                        'campus_contact_id'
                    ),

                'gospel_contacts' =>
                    $uniquePivotCount(
                        'shepherding_contact_gospel_contacts',
                        'gospel_contact_id'
                    ),
            ],

            'campus' => [
                'activities' =>
                    $campusActivities,

                'sessions' =>
                    $campusSessions->count(),

                'present_marks' =>
                    $presentMarks,

                'unique_people_present' =>
                    $uniquePeoplePresent,
            ],
        ];
    }
}
