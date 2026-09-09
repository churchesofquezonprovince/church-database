<?php

namespace App\Support;

use App\Models\AttendanceSession;
use App\Models\CampusWorkActivity;
use App\Models\GospelContact;
use App\Models\ShepherdingContact;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final class WeeklyGowSummary
{
    public static function build(
        ?string $week = null,
        ?int $localityId = null
    ): array {
        $start = self::normalizeWeek($week);

        $end = $start
            ->addDays(6);

        $startDate =
            $start->format('Y-m-d');

        $endDate =
            $end->format('Y-m-d');

        /*
         * -----------------------------------------------------
         * New Gospel Contacts.
         * -----------------------------------------------------
         */

        $newGospelContacts =
            GospelContact::query()
                ->with([
                    'person',
                    'localityRecord',
                ])
                ->when(
                    $localityId !== null,
                    fn (Builder $query) =>
                        $query->where(
                            'locality_id',
                            $localityId
                        )
                )
                ->whereBetween(
                    'created_at',
                    [
                        $start->startOfDay(),
                        $end->endOfDay(),
                    ]
                )
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

        /*
         * -----------------------------------------------------
         * Shepherding / Gospel Work.
         * -----------------------------------------------------
         */

        $shepherdingRecords =
            ShepherdingContact::query()
                ->with([
                    'locality',
                    'activityTypes',
                    'contactedPeople',
                    'contactedHouseholds',
                    'contactedCampusContacts',
                    'contactedGospelContacts',
                ])
                ->when(
                    $localityId !== null,
                    fn (Builder $query) =>
                        $query->where(
                            'locality_id',
                            $localityId
                        )
                )
                ->whereBetween(
                    'contact_date',
                    [
                        $startDate,
                        $endDate,
                    ]
                )
                ->orderBy('contact_date')
                ->orderBy('contact_time')
                ->orderBy('id')
                ->get();

        $gospelWorkRecords =
            $shepherdingRecords
                ->filter(
                    fn (ShepherdingContact $record): bool =>
                        $record
                            ->activityTypes
                            ->contains(
                                fn ($type): bool =>
                                    $type->category
                                    === 'Gospel Work'
                            )
                )
                ->values();

        /*
         * -----------------------------------------------------
         * Campus Activities.
         * -----------------------------------------------------
         */

        $campusActivities =
            CampusWorkActivity::query()
                ->with([
                    'school',
                    'attendanceSheet',
                    'attendanceSession',
                ])
                ->when(
                    $localityId !== null,
                    fn (Builder $query) =>
                        $query->where(
                            'locality_id',
                            $localityId
                        )
                )
                ->whereBetween(
                    'activity_date',
                    [
                        $startDate,
                        $endDate,
                    ]
                )
                ->orderBy('activity_date')
                ->orderBy('start_time')
                ->orderBy('id')
                ->get();

        /*
         * -----------------------------------------------------
         * Physical Campus Attendance Sessions.
         * -----------------------------------------------------
         */

        $campusSessions =
            AttendanceSession::query()
                ->with([
                    'sheet',
                ])
                ->withCount([
                    'records as attendees_count' =>
                        fn (Builder $query) =>
                            $query->where(
                                'is_present',
                                true
                            ),
                ])
                ->whereBetween(
                    'session_date',
                    [
                        $startDate,
                        $endDate,
                    ]
                )
                ->whereHas(
                    'sheet.campusActivity',
                    function (
                        Builder $query
                    ) use (
                        $localityId
                    ): void {
                        if ($localityId !== null) {
                            $query->where(
                                'locality_id',
                                $localityId
                            );
                        }
                    }
                )
                ->orderBy('session_date')
                ->orderBy('session_time')
                ->orderBy('id')
                ->get();

        $sessionIds =
            $campusSessions
                ->pluck('id');

        $presentMarks =
            (int) $campusSessions
                ->sum('attendees_count');

        $uniquePeoplePresent = 0;

        if ($sessionIds->isNotEmpty()) {
            $uniquePeoplePresent =
                DB::table('attendance_records')
                    ->whereIn(
                        'attendance_session_id',
                        $sessionIds
                    )
                    ->where(
                        'is_present',
                        true
                    )
                    ->whereNotNull(
                        'person_id'
                    )
                    ->distinct()
                    ->count('person_id');
        }

        /*
         * -----------------------------------------------------
         * Source rows.
         * -----------------------------------------------------
         */

        $campusActivitySources =
            $campusActivities
                ->map(
                    fn (
                        CampusWorkActivity $activity
                    ): array => [
                        'id' =>
                            (int) $activity->id,

                        'date' =>
                            $activity
                                ->activity_date
                                ?->format('M d')
                            ?? '',

                        'title' =>
                            $activity->effective_title
                            ?: $activity->title
                            ?: 'Campus Activity',

                        'time' =>
                            $activity->time_label,

                        'school' =>
                            $activity
                                ->school
                                ?->name,
                    ]
                )
                ->values()
                ->all();

        $campusSessionSources =
            $campusSessions
                ->map(
                    fn (
                        AttendanceSession $session
                    ): array => [
                        'id' =>
                            (int) $session->id,

                        'sheet_id' =>
                            (int)
                            $session->attendance_sheet_id,

                        'date' =>
                            $session
                                ->session_date
                                ?->format('M d')
                            ?? '',

                        'time' =>
                            $session
                                ->sessionTimeLabel(),

                        'title' =>
                            $session
                                ->sheet
                                ?->title
                            ?: 'Campus Attendance',

                        'attendees' =>
                            (int)
                            $session->attendees_count,
                    ]
                )
                ->values()
                ->all();

        $newGospelContactSources =
            $newGospelContacts
                ->map(
                    function (
                        GospelContact $contact
                    ): array {
                        $personName =
                            trim(
                                implode(
                                    ' ',
                                    array_filter([
                                        $contact
                                            ->person
                                            ?->firstname,

                                        $contact
                                            ->person
                                            ?->lastname,
                                    ])
                                )
                            );

                        $contactName =
                            trim(
                                implode(
                                    ' ',
                                    array_filter([
                                        $contact->firstname,
                                        $contact->lastname,
                                    ])
                                )
                            );

                        return [
                            'id' =>
                                (int) $contact->id,

                            'date' =>
                                $contact
                                    ->created_at
                                    ?->format('M d')
                                ?? '',

                            'name' =>
                                $personName
                                ?: $contactName
                                ?: 'Unnamed Gospel Contact',

                            'locality' =>
                                $contact
                                    ->localityRecord
                                    ?->name
                                ?? $contact->locality,

                            'place' =>
                                $contact->contact_place,
                        ];
                    }
                )
                ->values()
                ->all();

        $gospelWorkSources =
            $gospelWorkRecords
                ->map(
                    fn (
                        ShepherdingContact $record
                    ): array =>
                        self::shepherdingSource(
                            $record
                        )
                )
                ->values()
                ->all();

        $shepherdingSources =
            $shepherdingRecords
                ->map(
                    fn (
                        ShepherdingContact $record
                    ): array =>
                        self::shepherdingSource(
                            $record
                        )
                )
                ->values()
                ->all();

        $currentWeek =
            CarbonImmutable::now()
                ->startOfWeek(
                    CarbonInterface::MONDAY
                )
                ->startOfDay();

        return [
            'week_start' =>
                $startDate,

            'week_end' =>
                $endDate,

            'previous_week' =>
                $start
                    ->subWeek()
                    ->format('Y-m-d'),

            'next_week' =>
                $start
                    ->addWeek()
                    ->format('Y-m-d'),

            'current_week' =>
                $currentWeek
                    ->format('Y-m-d'),

            'is_current_week' =>
                $start->equalTo(
                    $currentWeek
                ),

            'period' =>
                $start->format('M d')
                . ' – '
                . $end->format('M d, Y'),

            'gospel' => [
                'new_contacts' =>
                    $newGospelContacts->count(),

                'work_contacts' =>
                    $gospelWorkRecords->count(),
            ],

            'shepherding' => [
                'records' =>
                    $shepherdingRecords->count(),

                'people' =>
                    self::uniqueRelatedCount(
                        $shepherdingRecords,
                        'contactedPeople'
                    ),

                'households' =>
                    self::uniqueRelatedCount(
                        $shepherdingRecords,
                        'contactedHouseholds'
                    ),

                'campus_contacts' =>
                    self::uniqueRelatedCount(
                        $shepherdingRecords,
                        'contactedCampusContacts'
                    ),

                'gospel_contacts' =>
                    self::uniqueRelatedCount(
                        $shepherdingRecords,
                        'contactedGospelContacts'
                    ),
            ],

            'campus' => [
                'activities' =>
                    $campusActivities->count(),

                'sessions' =>
                    $campusSessions->count(),

                'present_marks' =>
                    $presentMarks,

                'unique_people_present' =>
                    (int)
                    $uniquePeoplePresent,
            ],

            'sources' => [
                'campus_activities' =>
                    $campusActivitySources,

                'campus_sessions' =>
                    $campusSessionSources,

                'new_gospel_contacts' =>
                    $newGospelContactSources,

                'gospel_work_records' =>
                    $gospelWorkSources,

                'shepherding_records' =>
                    $shepherdingSources,
            ],
        ];
    }

    private static function normalizeWeek(
        ?string $week
    ): CarbonImmutable {
        $fallback =
            CarbonImmutable::now()
                ->startOfWeek(
                    CarbonInterface::MONDAY
                )
                ->startOfDay();

        if (
            ! is_string($week)
            || ! preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $week
            )
        ) {
            return $fallback;
        }

        try {
            $date =
                CarbonImmutable::createFromFormat(
                    'Y-m-d',
                    $week
                );

            if (
                ! $date
                || $date->format('Y-m-d')
                    !== $week
            ) {
                return $fallback;
            }

            return $date
                ->startOfWeek(
                    CarbonInterface::MONDAY
                )
                ->startOfDay();
        } catch (Throwable) {
            return $fallback;
        }
    }

    private static function uniqueRelatedCount(
        Collection $records,
        string $relation
    ): int {
        return $records
            ->flatMap(
                fn (
                    ShepherdingContact $record
                ) =>
                    $record
                        ->{$relation}
                        ->pluck('id')
            )
            ->filter(
                fn ($id): bool =>
                    $id !== null
            )
            ->unique()
            ->count();
    }

    private static function shepherdingSource(
        ShepherdingContact $record
    ): array {
        return [
            'id' =>
                (int) $record->id,

            'date' =>
                $record
                    ->contact_date
                    ?->format('M d')
                ?? '',

            'locality' =>
                $record
                    ->locality
                    ?->name,

            'codes' =>
                $record
                    ->activityTypes
                    ->pluck('code')
                    ->filter()
                    ->values()
                    ->all(),

            'outcome' =>
                $record->outcome,

            'identity_count' =>
                $record
                    ->contactedPeople
                    ->count()
                + $record
                    ->contactedHouseholds
                    ->count()
                + $record
                    ->contactedCampusContacts
                    ->count()
                + $record
                    ->contactedGospelContacts
                    ->count(),
        ];
    }
}
