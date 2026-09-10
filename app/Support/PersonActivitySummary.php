<?php

namespace App\Support;

use App\Models\Person;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class PersonActivitySummary
{
    private Person $person;

    private CarbonImmutable $from;

    private CarbonImmutable $to;

    private function __construct(Person $person)
    {
        $this->person = $person;

        $today = CarbonImmutable::today();

        /*
         * Default window for a Person profile heatmap:
         * the latest 365 calendar days, including today.
         */
        $this->from = $today
            ->subDays(364)
            ->startOfDay();

        $this->to = $today
            ->endOfDay();
    }

    public static function for(Person $person): self
    {
        return new self($person);
    }

    public function between(
        CarbonInterface|string $from,
        CarbonInterface|string $to
    ): self {
        $clone = clone $this;

        $clone->from = $from instanceof CarbonInterface
            ? CarbonImmutable::instance($from)
                ->startOfDay()
            : CarbonImmutable::parse($from)
                ->startOfDay();

        $clone->to = $to instanceof CarbonInterface
            ? CarbonImmutable::instance($to)
                ->endOfDay()
            : CarbonImmutable::parse($to)
                ->endOfDay();

        if ($clone->from->greaterThan($clone->to)) {
            throw new InvalidArgumentException(
                'Person activity start date must not be after end date.'
            );
        }

        return $clone;
    }

    public function summary(): array
    {
        return $this->summaryFromEvents(
            $this->events()
        );
    }

    public function snapshot(): array
    {
        /*
         * Build the canonical event collection once so
         * a Person profile does not repeat the same
         * Attendance / Shepherding queries for summary,
         * heatmap, and recent activity.
         */
        $events = $this->events();

        return [
            'summary' =>
                $this->summaryFromEvents(
                    $events
                ),

            'days' =>
                $this->daysFromEvents(
                    $events
                ),

            'events' =>
                $events,
        ];
    }

    public function events(): Collection
    {
        return $this
            ->attendanceEvents()
            ->concat(
                $this->shepherdingEvents()
            )
            ->sortBy([
                ['date', 'asc'],
                ['source', 'asc'],
                ['key', 'asc'],
            ])
            ->values();
    }

    public function activeDays(): Collection
    {
        return $this
            ->days()
            ->where(
                'intensity',
                '>',
                0
            )
            ->values();
    }

    public function days(): Collection
    {
        return $this->daysFromEvents(
            $this->events()
        );
    }

    private function summaryFromEvents(
        Collection $events
    ): array {
        return [
            'person_id' =>
                $this->person->id,

            'from' =>
                $this->from->toDateString(),

            'to' =>
                $this->to->toDateString(),

            'total_events' =>
                $events->count(),

            'active_days' =>
                $events
                    ->pluck('date')
                    ->unique()
                    ->count(),

            'attendance_events' =>
                $events
                    ->where(
                        'source',
                        'attendance'
                    )
                    ->count(),

            'shepherding_events' =>
                $events
                    ->where(
                        'source',
                        'shepherding'
                    )
                    ->count(),
        ];
    }

    private function daysFromEvents(
        Collection $events
    ): Collection {
        $eventsByDate =
            $events->groupBy('date');

        $days = collect();

        $date =
            $this->from
                ->startOfDay();

        $lastDate =
            $this->to
                ->startOfDay();

        while (
            $date->lessThanOrEqualTo(
                $lastDate
            )
        ) {
            $dateString =
                $date->toDateString();

            $dayEvents =
                $eventsByDate
                    ->get(
                        $dateString,
                        collect()
                    )
                    ->values();

            $days->push([
                'date' =>
                    $dateString,

                /*
                 * One canonical event contributes
                 * one heatmap point.
                 *
                 * Context, roles, Immich photos,
                 * and activity-type tags do not
                 * inflate intensity.
                 */
                'intensity' =>
                    $dayEvents->count(),

                'attendance_count' =>
                    $dayEvents
                        ->where(
                            'source',
                            'attendance'
                        )
                        ->count(),

                'shepherding_count' =>
                    $dayEvents
                        ->where(
                            'source',
                            'shepherding'
                        )
                        ->count(),

                'categories' =>
                    $dayEvents
                        ->flatMap(
                            fn (array $event): array =>
                                $event['categories']
                                ?? []
                        )
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),

                'events' =>
                    $dayEvents->all(),
            ]);

            $date =
                $date->addDay();
        }

        return $days;
    }

    private function attendanceEvents(): Collection
    {
        if (
            ! Schema::hasTable(
                'attendance_records'
            )
            ||
            ! Schema::hasTable(
                'attendance_sessions'
            )
            ||
            ! Schema::hasTable(
                'attendance_sheets'
            )
        ) {
            return collect();
        }

        $rows =
            DB::table(
                'attendance_records as r'
            )
                ->join(
                    'attendance_sessions as s',
                    's.id',
                    '=',
                    'r.attendance_session_id'
                )
                ->join(
                    'attendance_sheets as sh',
                    'sh.id',
                    '=',
                    's.attendance_sheet_id'
                )
                ->where(
                    'r.person_id',
                    $this->person->id
                )
                ->where(
                    'r.is_present',
                    true
                )
                ->where(
                    function ($query): void {
                        $query
                            ->where(
                                'r.attendance_source',
                                '!=',
                                'immich'
                            )
                            ->orWhere(
                                'r.immich_confirmed',
                                true
                            );
                    }
                )
                ->whereBetween(
                    's.session_date',
                    [
                        $this->from
                            ->toDateString(),

                        $this->to
                            ->toDateString(),
                    ]
                )
                ->orderBy(
                    's.session_date'
                )
                ->orderBy(
                    's.id'
                )
                ->get([
                    'r.id as record_id',
                    'r.status',
                    'r.prophesied',
                    'r.attendance_source',
                    'r.immich_confirmed',

                    's.id as session_id',
                    's.session_date',
                    's.session_time',
                    's.title as session_title',

                    'sh.id as sheet_id',
                    'sh.title as sheet_title',
                    'sh.sheet_type',
                    'sh.locality',
                ]);

        if ($rows->isEmpty()) {
            return collect();
        }

        $campusBySession =
            collect();

        $campusBySheet =
            collect();

        if (
            Schema::hasTable(
                'campus_work_activities'
            )
        ) {
            $sessionIds =
                $rows
                    ->pluck('session_id')
                    ->filter()
                    ->unique()
                    ->values();

            $sheetIds =
                $rows
                    ->pluck('sheet_id')
                    ->filter()
                    ->unique()
                    ->values();

            if (
                $sessionIds->isNotEmpty()
                ||
                $sheetIds->isNotEmpty()
            ) {
                $campusActivities =
                    DB::table(
                        'campus_work_activities'
                    )
                        ->where(
                            function (
                                $query
                            ) use (
                                $sessionIds,
                                $sheetIds
                            ): void {
                                if (
                                    $sessionIds
                                        ->isNotEmpty()
                                ) {
                                    $query->whereIn(
                                        'attendance_session_id',
                                        $sessionIds
                                            ->all()
                                    );
                                }

                                if (
                                    $sheetIds
                                        ->isNotEmpty()
                                ) {
                                    if (
                                        $sessionIds
                                            ->isNotEmpty()
                                    ) {
                                        $query->orWhereIn(
                                            'attendance_sheet_id',
                                            $sheetIds
                                                ->all()
                                        );
                                    } else {
                                        $query->whereIn(
                                            'attendance_sheet_id',
                                            $sheetIds
                                                ->all()
                                        );
                                    }
                                }
                            }
                        )
                        ->get([
                            'id',
                            'attendance_session_id',
                            'attendance_sheet_id',
                            'activity_type',
                            'other_activity_name',
                            'title',
                            'activity_date',
                            'school_id',
                            'venue',
                            'locality',
                            'locality_id',
                        ]);

                $campusBySession =
                    $campusActivities
                        ->filter(
                            fn ($activity): bool =>
                                filled(
                                    $activity
                                        ->attendance_session_id
                                )
                        )
                        ->keyBy(
                            fn ($activity): int =>
                                (int)
                                $activity
                                    ->attendance_session_id
                        );

                $campusBySheet =
                    $campusActivities
                        ->filter(
                            fn ($activity): bool =>
                                filled(
                                    $activity
                                        ->attendance_sheet_id
                                )
                        )
                        ->keyBy(
                            fn ($activity): int =>
                                (int)
                                $activity
                                    ->attendance_sheet_id
                        );
            }
        }

        return $rows
            ->map(
                function ($row) use (
                    $campusBySession,
                    $campusBySheet
                ): array {
                    $campus =
                        $campusBySession->get(
                            (int) $row->session_id
                        )
                        ??
                        $campusBySheet->get(
                            (int) $row->sheet_id
                        );

                    $categories = [
                        'Attendance',
                    ];

                    if ($campus) {
                        $categories[] =
                            'Campus';
                    }

                    $label =
                        filled(
                            $row->session_title
                        )
                            ? $row->session_title
                            : $row->sheet_title;

                    return [
                        'key' =>
                            'attendance:'
                            . $row->record_id,

                        'source' =>
                            'attendance',

                        'date' =>
                            CarbonImmutable::parse(
                                $row->session_date
                            )->toDateString(),

                        'label' =>
                            $label,

                        'categories' =>
                            collect(
                                $categories
                            )
                                ->unique()
                                ->values()
                                ->all(),

                        'details' => [
                            'record_id' =>
                                (int)
                                $row->record_id,

                            'session_id' =>
                                (int)
                                $row->session_id,

                            'sheet_id' =>
                                (int)
                                $row->sheet_id,

                            'sheet_title' =>
                                $row->sheet_title,

                            'sheet_type' =>
                                $row->sheet_type,

                            'locality' =>
                                $row->locality,

                            'session_time' =>
                                $row->session_time,

                            'status' =>
                                $row->status,

                            'prophesied' =>
                                (bool)
                                $row->prophesied,

                            'attendance_source' =>
                                $row
                                    ->attendance_source,

                            'immich_confirmed' =>
                                (bool)
                                $row
                                    ->immich_confirmed,

                            /*
                             * Campus activity is context for
                             * this Attendance event.
                             *
                             * It never becomes a second
                             * Person activity event.
                             */
                            'campus_activity' =>
                                $campus
                                    ? [
                                        'id' =>
                                            (int)
                                            $campus->id,

                                        'activity_type' =>
                                            $campus
                                                ->activity_type,

                                        'other_activity_name' =>
                                            $campus
                                                ->other_activity_name,

                                        'title' =>
                                            $campus
                                                ->title,

                                        'school_id' =>
                                            $campus
                                                ->school_id
                                                ? (int)
                                                    $campus
                                                        ->school_id
                                                : null,

                                        'venue' =>
                                            $campus
                                                ->venue,

                                        'locality' =>
                                            $campus
                                                ->locality,
                                    ]
                                    : null,
                        ],
                    ];
                }
            )
            ->values();
    }

    private function shepherdingEvents(): Collection
    {
        if (
            ! Schema::hasTable(
                'shepherding_contacts'
            )
        ) {
            return collect();
        }

        /*
         * One Person may be related to the same
         * ShepherdingContact through multiple roles.
         *
         * Example:
         * - direct contacted Person
         * - serving participant
         * - linked Campus Contact
         *
         * We collect every role but deduplicate by
         * Shepherding Contact ID.
         */
        $rolesByContact =
            collect();

        $addRole =
            function (
                Collection $contactIds,
                string $role
            ) use (
                &$rolesByContact
            ): void {
                foreach (
                    $contactIds
                        ->map(
                            fn ($id): int =>
                                (int) $id
                        )
                        ->unique()
                    as $contactId
                ) {
                    $roles =
                        collect(
                            $rolesByContact->get(
                                $contactId,
                                []
                            )
                        );

                    $roles->push($role);

                    $rolesByContact->put(
                        $contactId,
                        $roles
                            ->unique()
                            ->values()
                            ->all()
                    );
                }
            };

        if (
            Schema::hasTable(
                'shepherding_contact_people'
            )
        ) {
            $addRole(
                DB::table(
                    'shepherding_contact_people'
                )
                    ->where(
                        'person_id',
                        $this->person->id
                    )
                    ->pluck(
                        'shepherding_contact_id'
                    ),
                'Contacted Person'
            );
        }

        if (
            Schema::hasTable(
                'shepherding_contact_household_members'
            )
        ) {
            $addRole(
                DB::table(
                    'shepherding_contact_household_members'
                )
                    ->where(
                        'person_id',
                        $this->person->id
                    )
                    ->where(
                        'was_present',
                        true
                    )
                    ->pluck(
                        'shepherding_contact_id'
                    ),
                'Household Member Present'
            );
        }

        if (
            Schema::hasTable(
                'shepherding_contact_participants'
            )
        ) {
            $addRole(
                DB::table(
                    'shepherding_contact_participants'
                )
                    ->where(
                        'person_id',
                        $this->person->id
                    )
                    ->pluck(
                        'shepherding_contact_id'
                    ),
                'Participant'
            );
        }

        /*
         * Linked-contact resolver registry.
         *
         * This is intentionally table-driven rather
         * than hard-coding contact-model behavior
         * throughout the heatmap.
         *
         * Future Children Contacts can plug in here
         * when their tables are introduced:
         *
         * [
         *     'pivot' =>
         *         'shepherding_contact_children_contacts',
         *
         *     'target' =>
         *         'children_contacts',
         *
         *     'pivot_target_key' =>
         *         'children_contact_id',
         *
         *     'role' =>
         *         'Children Contact',
         * ],
         */
        foreach (
            $this->linkedContactTargets()
            as $target
        ) {
            if (
                ! Schema::hasTable(
                    $target['pivot']
                )
                ||
                ! Schema::hasTable(
                    $target['target']
                )
            ) {
                continue;
            }

            $pivotAlias = 'target_pivot';

            $targetAlias = 'target_record';

            $contactIds =
                DB::table(
                    $target['pivot']
                    . ' as '
                    . $pivotAlias
                )
                    ->join(
                        $target['target']
                        . ' as '
                        . $targetAlias,
                        $targetAlias
                            . '.id',
                        '=',
                        $pivotAlias
                            . '.'
                            . $target[
                                'pivot_target_key'
                            ]
                    )
                    ->where(
                        $targetAlias
                            . '.person_id',
                        $this->person->id
                    )
                    ->pluck(
                        $pivotAlias
                            . '.shepherding_contact_id'
                    );

            $addRole(
                $contactIds,
                $target['role']
            );
        }

        if ($rolesByContact->isEmpty()) {
            return collect();
        }

        $contactIds =
            $rolesByContact
                ->keys()
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->values();

        $contacts =
            DB::table(
                'shepherding_contacts'
            )
                ->whereIn(
                    'id',
                    $contactIds->all()
                )
                ->whereBetween(
                    'contact_date',
                    [
                        $this->from
                            ->toDateString(),

                        $this->to
                            ->toDateString(),
                    ]
                )
                ->orderBy(
                    'contact_date'
                )
                ->orderBy(
                    'id'
                )
                ->get([
                    'id',
                    'locality_id',
                    'contact_date',
                    'contact_time',
                    'outcome',
                    'notes',
                ]);

        if ($contacts->isEmpty()) {
            return collect();
        }

        $activityTypesByContact =
            collect();

        if (
            Schema::hasTable(
                'shepherding_contact_activities'
            )
            &&
            Schema::hasTable(
                'shepherding_activity_types'
            )
        ) {
            $activityTypesByContact =
                DB::table(
                    'shepherding_contact_activities as a'
                )
                    ->join(
                        'shepherding_activity_types as t',
                        't.id',
                        '=',
                        'a.shepherding_activity_type_id'
                    )
                    ->whereIn(
                        'a.shepherding_contact_id',
                        $contacts
                            ->pluck('id')
                            ->all()
                    )
                    ->orderBy(
                        't.sort_order'
                    )
                    ->orderBy(
                        't.id'
                    )
                    ->get([
                        'a.shepherding_contact_id',
                        't.id',
                        't.code',
                        't.name',
                        't.category',
                        't.description',
                    ])
                    ->groupBy(
                        'shepherding_contact_id'
                    );
        }

        return $contacts
            ->map(
                function (
                    $contact
                ) use (
                    $rolesByContact,
                    $activityTypesByContact
                ): array {
                    $types =
                        collect(
                            $activityTypesByContact
                                ->get(
                                    $contact->id,
                                    collect()
                                )
                        );

                    $categories =
                        $types
                            ->pluck('category')
                            ->filter()
                            ->unique()
                            ->values();

                    if (
                        $categories
                            ->isEmpty()
                    ) {
                        $categories->push(
                            'Shepherding'
                        );
                    }

                    $typeNames =
                        $types
                            ->pluck('name')
                            ->filter()
                            ->unique()
                            ->values();

                    $label =
                        $typeNames->isNotEmpty()
                            ? $typeNames
                                ->implode(', ')
                            : 'Shepherding Contact';

                    return [
                        'key' =>
                            'shepherding:'
                            . $contact->id,

                        'source' =>
                            'shepherding',

                        'date' =>
                            CarbonImmutable::parse(
                                $contact
                                    ->contact_date
                            )->toDateString(),

                        'label' =>
                            $label,

                        'categories' =>
                            $categories->all(),

                        'details' => [
                            'shepherding_contact_id' =>
                                (int)
                                $contact->id,

                            'locality_id' =>
                                $contact->locality_id
                                    ? (int)
                                        $contact
                                            ->locality_id
                                    : null,

                            'contact_time' =>
                                $contact
                                    ->contact_time,

                            'outcome' =>
                                $contact
                                    ->outcome,

                            'notes' =>
                                $contact
                                    ->notes,

                            /*
                             * Multiple roles describe one
                             * canonical event and therefore
                             * never increase heatmap intensity.
                             */
                            'roles' =>
                                $rolesByContact
                                    ->get(
                                        (int)
                                        $contact->id,
                                        []
                                    ),

                            'activity_types' =>
                                $types
                                    ->map(
                                        fn ($type): array => [
                                            'id' =>
                                                (int)
                                                $type->id,

                                            'code' =>
                                                $type->code,

                                            'name' =>
                                                $type->name,

                                            'category' =>
                                                $type
                                                    ->category,

                                            'description' =>
                                                $type
                                                    ->description,
                                        ]
                                    )
                                    ->values()
                                    ->all(),
                        ],
                    ];
                }
            )
            ->values();
    }

    private function linkedContactTargets(): array
    {
        return [
            [
                'pivot' =>
                    'shepherding_contact_campus_contacts',

                'target' =>
                    'campus_contacts',

                'pivot_target_key' =>
                    'campus_contact_id',

                'role' =>
                    'Campus Contact',
            ],

            [
                'pivot' =>
                    'shepherding_contact_gospel_contacts',

                'target' =>
                    'gospel_contacts',

                'pivot_target_key' =>
                    'gospel_contact_id',

                'role' =>
                    'Gospel Contact',
            ],
        ];
    }
}
