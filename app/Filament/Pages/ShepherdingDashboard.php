<?php

namespace App\Filament\Pages;

use App\Filament\Resources\People\PersonResource;
use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\Household;
use App\Models\MinistryBook;
use App\Models\Person;
use App\Models\ShepherdingActivityType;
use App\Models\ShepherdingContact;
use App\Support\LocalityOptions;
use App\Support\ShepherdingHistoryQuery;
use App\Support\WeeklyGowSummary;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ShepherdingDashboard extends Page
{
    protected string $view =
        'filament.pages.shepherding-dashboard';

    public ?string $locality = null;

    public ?string $week = null;

    public array $weeklyGow = [];

    public array $localities = [];

    public array $operationalStats = [];

    public array $peopleStats = [];

    public array $recentShepherding = [];

    public array $followUpItems = [];

    public array $followUpSummary = [];

    public array $activitySummary = [];

    public array $ministrySummary = [];

    public array $localitySummary = [];

    public array $peopleWithoutShepherd = [];

    public array $dormantPeople = [];

    public array $newOnes = [];

    public array $gospelFriends = [];

    public array $peopleWithoutService = [];

    public array $listUrls = [];

    public function mount(): void
    {
        $this->localities =
            LocalityOptions::primaryProvinceNamesWithPeople()
                ->values()
                ->all();

        $queryLocality =
            request()->query('locality');

        if (
            is_string($queryLocality)
            && in_array(
                $queryLocality,
                $this->localities,
                true
            )
        ) {
            $this->locality =
                $queryLocality;
        }

        $queryWeek =
            request()->query('week');

        $this->week =
            is_string($queryWeek)
                ? $queryWeek
                : null;

        $this->loadDashboard();
    }

    public function getTitle(): string
    {
        return 'Shepherding & GOW Dashboard';
    }

    public static function getNavigationLabel(): string
    {
        return 'Shepherding Dashboard';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shepherding';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-heart';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public function updatedLocality(): void
    {
        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        $localityId =
            $this->selectedLocalityId();

        $this->weeklyGow =
            WeeklyGowSummary::build(
                $this->week,
                $localityId
            );

        $this->week =
            $this->weeklyGow[
                'week_start'
            ];

        $monthStart =
            now()
                ->startOfMonth()
                ->toDateString();

        $monthEnd =
            now()
                ->endOfMonth()
                ->toDateString();

        /*
         * -----------------------------------------------------
         * Shepherding history for the selected month/locality.
         * -----------------------------------------------------
         */

        $monthHistory =
            ShepherdingHistoryQuery::make()
                ->from($monthStart)
                ->to($monthEnd);

        if ($localityId !== null) {
            $monthHistory->locality(
                $localityId
            );
        }

        $monthRows =
            $monthHistory->shepherdingRows();

        /*
         * -----------------------------------------------------
         * Shepherding records needing follow-up.
         * -----------------------------------------------------
         */

        $followUpOutcomeValues = [
            ShepherdingContact::OUTCOME_UNAVAILABLE,
            ShepherdingContact::OUTCOME_RESCHEDULE,
            ShepherdingContact::OUTCOME_DECLINED,
        ];

        $followUpRows =
            $monthRows
                ->filter(
                    fn (array $row): bool =>
                        in_array(
                            $row['outcome'],
                            $followUpOutcomeValues,
                            true
                        )
                )
                ->values();

        $this->followUpItems =
            $followUpRows
                ->take(6)
                ->all();

        $this->followUpSummary = [
            'unavailable' => [
                'label' =>
                    ShepherdingContact::OUTCOME_UNAVAILABLE,

                'count' =>
                    $followUpRows
                        ->where(
                            'outcome',
                            ShepherdingContact::OUTCOME_UNAVAILABLE
                        )
                        ->count(),
            ],

            'reschedule' => [
                'label' =>
                    ShepherdingContact::OUTCOME_RESCHEDULE,

                'count' =>
                    $followUpRows
                        ->where(
                            'outcome',
                            ShepherdingContact::OUTCOME_RESCHEDULE
                        )
                        ->count(),
            ],

            'declined' => [
                'label' =>
                    ShepherdingContact::OUTCOME_DECLINED,

                'count' =>
                    $followUpRows
                        ->where(
                            'outcome',
                            ShepherdingContact::OUTCOME_DECLINED
                        )
                        ->count(),
            ],
        ];

        /*
         * -----------------------------------------------------
         * Recent Shepherding Records.
         * -----------------------------------------------------
         */

        $recentHistory =
            ShepherdingHistoryQuery::make()
                ->limit(8);

        if ($localityId !== null) {
            $recentHistory->locality(
                $localityId
            );
        }

        $this->recentShepherding =
            $recentHistory
                ->shepherdingRows()
                ->map(
                    fn (array $row): array => [
                        ...$row,

                        'url' =>
                            ShepherdingHistory::getUrl([
                                'record' =>
                                    (int) $row['id'],
                            ])
                            . '#shepherding-contact-form',
                    ]
                )
                ->all();

        /*
         * -----------------------------------------------------
         * Activity summary.
         * -----------------------------------------------------
         */

        $activityCounts =
            $monthRows
                ->flatMap(
                    fn (array $row): array =>
                        $row['activity_codes']
                )
                ->countBy();

        $this->activitySummary =
            ShepherdingActivityType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get()
                ->map(
                    function (
                        ShepherdingActivityType $type
                    ) use (
                        $activityCounts,
                        $monthStart,
                        $monthEnd,
                        $localityId
                    ): array {
                        $params = [
                            'activity' =>
                                $type->code,

                            'from' =>
                                $monthStart,

                            'to' =>
                                $monthEnd,
                        ];

                        if ($localityId !== null) {
                            $params['locality'] =
                                $localityId;
                        }

                        return [
                            'code' =>
                                $type->code,

                            'name' =>
                                $type->name,

                            'count' =>
                                (int) (
                                    $activityCounts[
                                        $type->code
                                    ]
                                    ?? 0
                                ),

                            'url' =>
                                ShepherdingHistory::getUrl(
                                    $params
                                ),
                        ];
                    }
                )
                ->all();

        /*
         * -----------------------------------------------------
         * Ministry used during the selected month.
         *
         * Count distinct Shepherding Contact records that used
         * at least one lesson from each Ministry Book.
         * -----------------------------------------------------
         */

        $ministryUsage =
            DB::table(
                'shepherding_contact_ministry_lessons as pivot'
            )
                ->join(
                    'ministry_lessons as lesson',
                    'lesson.id',
                    '=',
                    'pivot.ministry_lesson_id'
                )
                ->join(
                    'shepherding_contacts as contact',
                    'contact.id',
                    '=',
                    'pivot.shepherding_contact_id'
                )
                ->whereBetween(
                    'contact.contact_date',
                    [
                        $monthStart,
                        $monthEnd,
                    ]
                )
                ->when(
                    $localityId !== null,
                    fn ($query) =>
                        $query->where(
                            'contact.locality_id',
                            $localityId
                        )
                )
                ->select(
                    'lesson.ministry_book_id'
                )
                ->selectRaw(
                    'COUNT(DISTINCT contact.id) as total'
                )
                ->groupBy(
                    'lesson.ministry_book_id'
                )
                ->pluck(
                    'total',
                    'lesson.ministry_book_id'
                );

        $ministryTopicUsage =
            DB::table(
                'shepherding_contact_ministry_lessons as pivot'
            )
                ->join(
                    'ministry_lessons as lesson',
                    'lesson.id',
                    '=',
                    'pivot.ministry_lesson_id'
                )
                ->join(
                    'shepherding_contacts as contact',
                    'contact.id',
                    '=',
                    'pivot.shepherding_contact_id'
                )
                ->whereBetween(
                    'contact.contact_date',
                    [
                        $monthStart,
                        $monthEnd,
                    ]
                )
                ->when(
                    $localityId !== null,
                    fn ($query) =>
                        $query->where(
                            'contact.locality_id',
                            $localityId
                        )
                )
                ->select([
                    'lesson.id',
                    'lesson.ministry_book_id',
                    'lesson.code',
                    'lesson.title',
                    'lesson.sort_order',
                ])
                ->selectRaw(
                    'COUNT(DISTINCT contact.id) as total'
                )
                ->groupBy([
                    'lesson.id',
                    'lesson.ministry_book_id',
                    'lesson.code',
                    'lesson.title',
                    'lesson.sort_order',
                ])
                ->orderBy(
                    'lesson.ministry_book_id'
                )
                ->orderBy(
                    'lesson.sort_order'
                )
                ->orderBy(
                    'lesson.code'
                )
                ->get()
                ->groupBy(
                    'ministry_book_id'
                );

        $this->ministrySummary =
            MinistryBook::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'sort_order'
                )
                ->orderBy(
                    'code'
                )
                ->get()
                ->map(
                    function (
                        MinistryBook $book
                    ) use (
                        $ministryUsage,
                        $ministryTopicUsage
                    ): array {
                        return [
                            'id' =>
                                (int) $book->id,

                            'code' =>
                                $book->code,

                            'title' =>
                                $book->title,

                            'count' =>
                                (int) (
                                    $ministryUsage[
                                        $book->id
                                    ]
                                    ?? 0
                                ),

                            'topics' =>
                                $ministryTopicUsage
                                    ->get(
                                        $book->id,
                                        collect()
                                    )
                                    ->map(
                                        fn ($row): array => [
                                            'id' =>
                                                (int) $row->id,

                                            'code' =>
                                                $row->code,

                                            'title' =>
                                                $row->title,

                                            'count' =>
                                                (int)
                                                $row->total,
                                        ]
                                    )
                                    ->values()
                                    ->all(),
                        ];
                    }
                )
                ->all();

        /*
         * -----------------------------------------------------
         * Locality activity summary.
         * -----------------------------------------------------
         */

        $this->localitySummary =
            ShepherdingContact::query()
                ->with('locality')
                ->whereBetween(
                    'contact_date',
                    [
                        $monthStart,
                        $monthEnd,
                    ]
                )
                ->when(
                    $localityId !== null,
                    fn (Builder $query) =>
                        $query->where(
                            'locality_id',
                            $localityId
                        )
                )
                ->select('locality_id')
                ->selectRaw(
                    'COUNT(*) as total'
                )
                ->groupBy('locality_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(
                    function (
                        ShepherdingContact $record
                    ) use (
                        $monthStart,
                        $monthEnd
                    ): array {
                        $recordLocalityId =
                            filled(
                                $record->locality_id
                            )
                                ? (int)
                                    $record->locality_id
                                : null;

                        return [
                            'locality_id' =>
                                $recordLocalityId,

                            'locality' =>
                                $record
                                    ->locality
                                    ?->name
                                ?? 'No Locality',

                            'count' =>
                                (int)
                                $record->total,

                            'url' =>
                                $recordLocalityId !== null
                                    ? ShepherdingHistory::getUrl([
                                        'locality' =>
                                            $recordLocalityId,

                                        'from' =>
                                            $monthStart,

                                        'to' =>
                                            $monthEnd,
                                    ])
                                    : null,
                        ];
                    }
                )
                ->all();

        /*
         * -----------------------------------------------------
         * Operational overview.
         * -----------------------------------------------------
         */

        $gospelQuery =
            GospelContact::query()
                ->when(
                    $localityId !== null,
                    fn (Builder $query) =>
                        $query->where(
                            'locality_id',
                            $localityId
                        )
                );

        $campusQuery =
            CampusContact::query()
                ->when(
                    $localityId !== null,
                    fn (Builder $query) =>
                        $query->where(
                            'locality_id',
                            $localityId
                        )
                );

        $householdQuery =
            Household::query()
                ->when(
                    $localityId !== null,
                    fn (Builder $query) =>
                        $query->where(
                            'locality_id',
                            $localityId
                        )
                );

        $peopleQuery =
            $this->basePeopleQuery();



        $this->operationalStats = [
            'records_this_month' => [
                'label' =>
                    'Records This Month',

                'count' =>
                    $monthRows->count(),

                'url' =>
                    ShepherdingHistory::getUrl([
                        'from' => $monthStart,
                        'to' => $monthEnd,
                    ]),
            ],

            'follow_up_outcomes' => [
                'label' =>
                    'Follow-up Outcomes',

                'count' =>
                    $followUpRows->count(),

                'url' =>
                    ShepherdingHistory::getUrl([
                        'mode' => 'follow-up',
                        'from' => $monthStart,
                        'to' => $monthEnd,
                    ]),
            ],

            'gospel_contacts' => [
                'label' =>
                    'Gospel Contacts',

                'count' =>
                    (clone $gospelQuery)
                        ->count(),

                'url' =>
                    GospelContacts::getUrl(),
            ],

            'unlinked_gospel' => [
                'label' =>
                    'Unlinked Gospel',

                'count' =>
                    (clone $gospelQuery)
                        ->whereNull('person_id')
                        ->count(),

                'url' =>
                    GospelContacts::getUrl([
                        'status' => 'unlinked',
                    ]),
            ],

            'campus_contacts' => [
                'label' =>
                    'Campus Contacts',

                'count' =>
                    (clone $campusQuery)
                        ->count(),

                'url' =>
                    CampusContacts::getUrl(),
            ],

            'unlinked_campus' => [
                'label' =>
                    'Unlinked Campus',

                'count' =>
                    (clone $campusQuery)
                        ->whereNull('person_id')
                        ->count(),

                'url' =>
                    CampusContacts::getUrl([
                        'status' => 'unlinked',
                    ]),
            ],

            'households' => [
                'label' =>
                    'Households',

                'count' =>
                    (clone $householdQuery)
                        ->count(),

                'url' =>
                    null,
            ],

            'people' => [
                'label' =>
                    'People',

                'count' =>
                    (clone $peopleQuery)
                        ->count(),

                'url' =>
                    $this->peopleTableUrl(),
            ],
        ];

        /*
         * -----------------------------------------------------
         * Existing People shepherding needs.
         * -----------------------------------------------------
         */

        $this->peopleStats = [
            'active' => [
                'label' => 'Active',
                'count' =>
                    (clone $peopleQuery)
                        ->whereHas(
                            'churchProfile',
                            fn (Builder $query) =>
                                $query->where(
                                    'status',
                                    'Active'
                                )
                        )
                        ->count(),

                'url' =>
                    $this->peopleTableUrl([
                        'church_status' =>
                            'Active',
                    ]),
            ],

            'new_ones' => [
                'label' => 'New Ones',
                'count' =>
                    (clone $peopleQuery)
                        ->whereHas(
                            'churchProfile',
                            fn (Builder $query) =>
                                $query->where(
                                    'status',
                                    'New One'
                                )
                        )
                        ->count(),

                'url' =>
                    $this->peopleTableUrl([
                        'church_status' =>
                            'New One',
                    ]),
            ],

            'gospel_friends' => [
                'label' =>
                    'Gospel Friends',

                'count' =>
                    (clone $peopleQuery)
                        ->whereHas(
                            'churchProfile',
                            fn (Builder $query) =>
                                $query->where(
                                    'status',
                                    'Gospel Friend'
                                )
                        )
                        ->count(),

                'url' =>
                    $this->peopleTableUrl([
                        'church_status' =>
                            'Gospel Friend',
                    ]),
            ],

            'dormant' => [
                'label' =>
                    'Dormant',

                'count' =>
                    (clone $peopleQuery)
                        ->whereHas(
                            'churchProfile',
                            fn (Builder $query) =>
                                $query->where(
                                    'status',
                                    'Dormant'
                                )
                        )
                        ->count(),

                'url' =>
                    $this->peopleTableUrl([
                        'church_status' =>
                            'Dormant',
                    ]),
            ],

            'without_shepherd' => [
                'label' =>
                    'No Shepherd',

                'count' =>
                    (clone $peopleQuery)
                        ->whereHas(
                            'churchProfile',
                            fn (Builder $query) =>
                                $query->whereNull(
                                    'shepherd_id'
                                )
                        )
                        ->count(),

                'url' =>
                    $this->peopleTableUrl([
                        'shepherd_status' =>
                            'without_shepherd',
                    ]),
            ],

            'without_service' => [
                'label' =>
                    'No Shepherding Group',

                'count' =>
                    (clone $peopleQuery)
                        ->whereHas(
                            'churchProfile',
                            fn (Builder $query) =>
                                $query
                                    ->whereNull(
                                        'service'
                                    )
                                    ->orWhere(
                                        'service',
                                        ''
                                    )
                                    ->orWhere('service', '[]')
                        )
                        ->count(),

                'url' =>
                    $this->peopleTableUrl([
                        'shepherding_group' =>
                            '__none',
                    ]),
            ],
        ];

        $this->listUrls = [
            'without_shepherd' =>
                $this->peopleTableUrl([
                    'shepherd_status' =>
                        'without_shepherd',
                ]),

            'dormant' =>
                $this->peopleTableUrl([
                    'church_status' =>
                        'Dormant',
                ]),

            'new_ones' =>
                $this->peopleTableUrl([
                    'church_status' =>
                        'New One',
                ]),

            'gospel_friends' =>
                $this->peopleTableUrl([
                    'church_status' =>
                        'Gospel Friend',
                ]),

            'without_service' =>
                $this->peopleTableUrl([
                    'shepherding_group' =>
                        '__none',
                ]),
        ];

        $this->peopleWithoutShepherd =
            $this->peopleQuery()
                ->whereHas(
                    'churchProfile',
                    fn (Builder $query) =>
                        $query->whereNull(
                            'shepherd_id'
                        )
                )
                ->limit(10)
                ->get()
                ->map(
                    fn (Person $person) =>
                        $this->personRow(
                            $person
                        )
                )
                ->all();

        $this->dormantPeople =
            $this->peopleQuery()
                ->whereHas(
                    'churchProfile',
                    fn (Builder $query) =>
                        $query->where(
                            'status',
                            'Dormant'
                        )
                )
                ->limit(10)
                ->get()
                ->map(
                    fn (Person $person) =>
                        $this->personRow(
                            $person
                        )
                )
                ->all();

        $this->newOnes =
            $this->peopleQuery()
                ->whereHas(
                    'churchProfile',
                    fn (Builder $query) =>
                        $query->where(
                            'status',
                            'New One'
                        )
                )
                ->limit(10)
                ->get()
                ->map(
                    fn (Person $person) =>
                        $this->personRow(
                            $person
                        )
                )
                ->all();

        $this->gospelFriends =
            $this->peopleQuery()
                ->whereHas(
                    'churchProfile',
                    fn (Builder $query) =>
                        $query->where(
                            'status',
                            'Gospel Friend'
                        )
                )
                ->limit(10)
                ->get()
                ->map(
                    fn (Person $person) =>
                        $this->personRow(
                            $person
                        )
                )
                ->all();

        $this->peopleWithoutService =
            $this->peopleQuery()
                ->whereHas(
                    'churchProfile',
                    fn (Builder $query) =>
                        $query
                            ->whereNull(
                                'service'
                            )
                            ->orWhere(
                                'service',
                                ''
                            )
                                    ->orWhere('service', '[]')
                )
                ->limit(10)
                ->get()
                ->map(
                    fn (Person $person) =>
                        $this->personRow(
                            $person
                        )
                )
                ->all();
    }

    private function selectedLocalityId(): ?int
    {
        if (blank($this->locality)) {
            return null;
        }

        $locality =
            LocalityOptions::primaryProvinceLocality(
                $this->locality
            );

        return $locality
            ? (int) $locality->id
            : 0;
    }

    private function basePeopleQuery(): Builder
    {
        $localityId =
            $this->selectedLocalityId();

        return Person::query()
            ->when(
                $localityId !== null,
                fn (Builder $query) =>
                    $query->where(
                        'locality_id',
                        $localityId
                    )
            );
    }

    private function peopleQuery(): Builder
    {
        return $this
            ->basePeopleQuery()
            ->with([
                'churchProfile.shepherd',
                'household',
            ])
            ->orderBy('lastname')
            ->orderBy('firstname');
    }

    private function personRow(
        Person $person
    ): array {
        return [
            'id' =>
                $person->id,

            'name' =>
                $person->display_name,

            'category' =>
                $person
                    ->churchProfile
                    ?->category
                ?? 'Unknown',

            'status' =>
                $person
                    ->churchProfile
                    ?->status
                ?? 'Unknown',

            'service' =>
                $this->serviceLabel(
                    $person
                        ->churchProfile
                        ?->service
                ),

            'shepherd' =>
                $person
                    ->churchProfile
                    ?->shepherd
                    ?->display_name
                ?? 'None recorded',

            'locality' =>
                $person->locality
                ?? 'None recorded',

            'url' =>
                PersonResource::getUrl(
                    'view',
                    [
                        'record' =>
                            $person->id,
                    ]
                ),
        ];
    }

    private function serviceLabel(
        mixed $service
    ): string {
        if (is_array($service)) {
            $values = collect($service)
                ->flatten()
                ->filter(
                    fn ($value): bool =>
                        filled($value)
                )
                ->map(
                    fn ($value): string =>
                        (string) $value
                )
                ->values();

            return $values->isNotEmpty()
                ? $values->join(', ')
                : 'None recorded';
        }

        return filled($service)
            ? (string) $service
            : 'None recorded';
    }

    private function peopleTableUrl(
        array $filters = []
    ): string {
        $queryFilters = [];

        if (filled($this->locality)) {
            $queryFilters['locality'] = [
                'value' =>
                    $this->locality,
            ];
        }

        foreach (
            $filters
            as $filter => $value
        ) {
            $queryFilters[$filter] = [
                'value' =>
                    (string) $value,
            ];
        }

        return PersonResource::getUrl(
            'index'
        )
            . '?'
            . http_build_query([
                'filters' =>
                    $queryFilters,
            ]);
    }

    public function followUpHistoryUrl(): string
    {
        $monthStart =
            now()
                ->startOfMonth()
                ->toDateString();

        $monthEnd =
            now()
                ->endOfMonth()
                ->toDateString();

        $params = [
            'mode' =>
                'follow-up',

            'from' =>
                $monthStart,

            'to' =>
                $monthEnd,
        ];

        $localityId =
            $this->selectedLocalityId();

        if ($localityId !== null) {
            $params['locality'] =
                $localityId;
        }

        return $this->sameOriginPath(
            ShepherdingHistory::getUrl(
                $params
            )
        );
    }

    public function weeklyGowUrl(
        string $week
    ): string {
        $params = [
            'week' => $week,
        ];

        if (filled($this->locality)) {
            $params['locality'] =
                $this->locality;
        }

        return $this->sameOriginPath(
            static::getUrl()
        )
            . '?'
            . http_build_query(
                $params
            );
    }

    public function campusActivitiesUrl(): string
    {
        return $this->sameOriginPath(
            CampusActivities::getUrl()
        );
    }

    public function gospelContactsUrl(): string
    {
        return $this->sameOriginPath(
            GospelContacts::getUrl()
        );
    }

    public function shepherdingRecordUrl(
        int $recordId
    ): string {
        return $this->sameOriginPath(
            ShepherdingHistory::getUrl([
                'record' =>
                    $recordId,
            ])
        )
            . '#shepherding-contact-form';
    }

    public function campusSessionUrl(
        int $sheetId,
        int $sessionId
    ): string {
        return $this->sameOriginPath(
            CheckAttendance::getUrl()
        )
            . '?'
            . http_build_query([
                'sheetId' =>
                    $sheetId,

                'sessionId' =>
                    $sessionId,
            ]);
    }

    private function sameOriginPath(
        string $url
    ): string {
        $path =
            parse_url(
                $url,
                PHP_URL_PATH
            );

        return is_string($path)
            && $path !== ''
                ? $path
                : $url;
    }

}
