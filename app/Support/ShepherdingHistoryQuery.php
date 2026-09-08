<?php

namespace App\Support;

use App\Models\ShepherdingContact;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ShepherdingHistoryQuery
{
    private Builder $query;

    public function __construct()
    {
        $this->query = ShepherdingContact::query()
            ->with([
                'locality',
                'contactedPeople',
                'contactedHouseholds.head',
                'contactedCampusContacts.localityRecord',
                'contactedGospelContacts.localityRecord',
                'householdMembers',
                'activityTypes',
                'ministryLessons.book',
                'participants',
            ]);
    }

    public static function make(): self
    {
        return new self();
    }

    /*
     * ---------------------------------------------------------
     * Date filters
     * ---------------------------------------------------------
     */

    public function onDate(
        string|CarbonInterface $date
    ): self {
        $date = $this->dateString($date);

        $this->query->whereDate(
            'contact_date',
            $date
        );

        return $this;
    }

    public function from(
        string|CarbonInterface $date
    ): self {
        $date = $this->dateString($date);

        $this->query->whereDate(
            'contact_date',
            '>=',
            $date
        );

        return $this;
    }

    public function to(
        string|CarbonInterface $date
    ): self {
        $date = $this->dateString($date);

        $this->query->whereDate(
            'contact_date',
            '<=',
            $date
        );

        return $this;
    }

    /*
     * ---------------------------------------------------------
     * Target filters
     * ---------------------------------------------------------
     */

    public function person(
        int $personId
    ): self {
        /*
         * A Person may participate in Shepherding history
         * through several legitimate historical paths:
         *
         * 1. Direct People target.
         * 2. Present member of a contacted Household.
         * 3. A Campus Contact later linked/promoted to Person.
         * 4. A Gospel Contact later linked/promoted to Person.
         *
         * This preserves history after source records are
         * promoted to the People Database.
         */
        $this->query->where(
            function (Builder $query) use (
                $personId
            ): void {
                $query
                    ->whereHas(
                        'contactedPeople',
                        fn (Builder $query) =>
                            $query->where(
                                'persons.id',
                                $personId
                            )
                    )
                    ->orWhereHas(
                        'householdMembers',
                        fn (Builder $query) =>
                            $query
                                ->where(
                                    'persons.id',
                                    $personId
                                )
                                ->where(
                                    'shepherding_contact_household_members.was_present',
                                    true
                                )
                    )
                    ->orWhereHas(
                        'contactedCampusContacts',
                        fn (Builder $query) =>
                            $query->where(
                                'campus_contacts.person_id',
                                $personId
                            )
                    )
                    ->orWhereHas(
                        'contactedGospelContacts',
                        fn (Builder $query) =>
                            $query->where(
                                'gospel_contacts.person_id',
                                $personId
                            )
                    );
            }
        );

        return $this;
    }

    public function household(
        int $householdId
    ): self {
        $this->query->whereHas(
            'contactedHouseholds',
            fn (Builder $query) =>
                $query->where(
                    'households.id',
                    $householdId
                )
        );

        return $this;
    }

    public function campusContact(
        int $campusContactId
    ): self {
        $this->query->whereHas(
            'contactedCampusContacts',
            fn (Builder $query) =>
                $query->where(
                    'campus_contacts.id',
                    $campusContactId
                )
        );

        return $this;
    }

    public function gospelContact(
        int $gospelContactId
    ): self {
        $this->query->whereHas(
            'contactedGospelContacts',
            fn (Builder $query) =>
                $query->where(
                    'gospel_contacts.id',
                    $gospelContactId
                )
        );

        return $this;
    }

    /*
     * ---------------------------------------------------------
     * Shepherding / shepherding filters
     * ---------------------------------------------------------
     */

    public function locality(
        int $localityId
    ): self {
        $this->query->where(
            'locality_id',
            $localityId
        );

        return $this;
    }

    public function activity(
        string $code
    ): self {
        $code = trim($code);

        $this->query->whereHas(
            'activityTypes',
            fn (Builder $query) =>
                $query->where(
                    'shepherding_activity_types.code',
                    $code
                )
        );

        return $this;
    }

    public function ministryLesson(
        string $code
    ): self {
        $code = trim($code);

        $this->query->whereHas(
            'ministryLessons',
            fn (Builder $query) =>
                $query->where(
                    'ministry_lessons.code',
                    $code
                )
        );

        return $this;
    }

    public function ministryBook(
        string $bookCode
    ): self {
        $bookCode = trim($bookCode);

        $this->query->whereHas(
            'ministryLessons.book',
            fn (Builder $query) =>
                $query->where(
                    'ministry_books.code',
                    $bookCode
                )
        );

        return $this;
    }

    public function outcome(
        string $outcome
    ): self {
        $this->query->where(
            'outcome',
            $outcome
        );

        return $this;
    }

    public function servingSaint(
        int $personId
    ): self {
        $this->query->whereHas(
            'participants',
            fn (Builder $query) =>
                $query->where(
                    'persons.id',
                    $personId
                )
        );

        return $this;
    }

    /*
     * ---------------------------------------------------------
     * Results
     * ---------------------------------------------------------
     */

    public function limit(int $limit): self
    {
        $this->query->limit(
            max(1, $limit)
        );

        return $this;
    }

    public function query(): Builder
    {
        return clone $this->query;
    }

    public function get(): Collection
    {
        return $this->query()
            ->orderByDesc('contact_date')
            ->orderByDesc('contact_time')
            ->orderByDesc('id')
            ->get();
    }

    public function count(): int
    {
        return $this->query()
            ->count();
    }

    /*
     * Standardized Shepherding-style history rows.
     *
     * IMPORTANT:
     * - Activity codes remain owned by Shepherding Records.
     * - Ministry codes remain owned by Ministry Lessons.
     * - O is derived from the Shepherding outcome.
     * - LTM / PM / SG are NOT created here; those will
     *   eventually be derived from Attendance.
     * - BP is NOT created here; baptism remains owned
     *   by ChurchProfile.
     */
    public function shepherdingRows(): Collection
    {
        return $this->get()
            ->map(
                function (
                    ShepherdingContact $record
                ): array {
                    $activityCodes =
                        $record
                            ->activityTypes
                            ->pluck('code')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                    $ministry =
                        $record
                            ->ministryLessons
                            ->map(
                                function (
                                    $lesson
                                ): array {
                                    return [
                                        'book_code' =>
                                            $lesson
                                                ->book
                                                ?->code,

                                        'book_title' =>
                                            $lesson
                                                ->book
                                                ?->title,

                                        'lesson_code' =>
                                            $lesson
                                                ->code,

                                        'lesson_title' =>
                                            $lesson
                                                ->title,
                                    ];
                                }
                            )
                            ->values()
                            ->all();

                    $ministryCodes =
                        collect($ministry)
                            ->pluck(
                                'lesson_code'
                            )
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                    /*
                     * Old Shepherding O belongs to outcome,
                     * not activity vocabulary.
                     */
                    $outcomeCode =
                        $record->outcome
                            === ShepherdingContact::OUTCOME_UNAVAILABLE
                            ? 'O'
                            : null;

                    /*
                     * Convenient display/export codes.
                     *
                     * Ownership remains preserved:
                     * activity_codes, ministry_codes,
                     * and outcome_code remain available
                     * separately below.
                     */
                    $shepherdingCodes =
                        collect(
                            $activityCodes
                        )
                            ->concat(
                                $ministryCodes
                            )
                            ->when(
                                filled(
                                    $outcomeCode
                                ),
                                fn (
                                    Collection $codes
                                ): Collection =>
                                    $codes->push(
                                        $outcomeCode
                                    )
                            )
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                    $presentMembers =
                        $record
                            ->householdMembers
                            ->filter(
                                fn ($person): bool =>
                                    (bool)
                                    $person
                                        ->pivot
                                        ->was_present
                            )
                            ->pluck(
                                'display_name'
                            )
                            ->values()
                            ->all();

                    $absentMembers =
                        $record
                            ->householdMembers
                            ->reject(
                                fn ($person): bool =>
                                    (bool)
                                    $person
                                        ->pivot
                                        ->was_present
                            )
                            ->pluck(
                                'display_name'
                            )
                            ->values()
                            ->all();

                    return [
                        'id' =>
                            (int) $record->id,

                        'date' =>
                            $record
                                ->contact_date
                                ?->format(
                                    'Y-m-d'
                                ),

                        'time' =>
                            filled(
                                $record
                                    ->contact_time
                            )
                                ? substr(
                                    (string)
                                    $record
                                        ->contact_time,
                                    0,
                                    5
                                )
                                : null,

                        'locality_id' =>
                            filled(
                                $record
                                    ->locality_id
                            )
                                ? (int)
                                    $record
                                        ->locality_id
                                : null,

                        'locality' =>
                            $record
                                ->locality
                                ?->name,

                        'outcome' =>
                            $record
                                ->outcome,

                        'outcome_code' =>
                            $outcomeCode,

                        'targets' => [
                            'people' =>
                                $record
                                    ->contactedPeople
                                    ->pluck(
                                        'display_name'
                                    )
                                    ->values()
                                    ->all(),

                            'households' =>
                                $record
                                    ->contactedHouseholds
                                    ->pluck(
                                        'display_name'
                                    )
                                    ->values()
                                    ->all(),

                            'campus_contacts' =>
                                $record
                                    ->contactedCampusContacts
                                    ->pluck(
                                        'display_name'
                                    )
                                    ->values()
                                    ->all(),

                            'gospel_contacts' =>
                                $record
                                    ->contactedGospelContacts
                                    ->pluck(
                                        'display_name'
                                    )
                                    ->values()
                                    ->all(),
                        ],

                        'household_members' => [
                            'present' =>
                                $presentMembers,

                            'not_present' =>
                                $absentMembers,
                        ],

                        'activity_codes' =>
                            $activityCodes,

                        'ministry_codes' =>
                            $ministryCodes,

                        'ministry' =>
                            $ministry,

                        'shepherding_codes' =>
                            $shepherdingCodes,

                        'serving_saints' =>
                            $record
                                ->participants
                                ->pluck(
                                    'display_name'
                                )
                                ->values()
                                ->all(),

                        'notes' =>
                            $record->notes,
                    ];
                }
            )
            ->values();
    }

    /*
     * Daily aggregation for future Shepherding screens
     * and Person GitHub-style heatmaps.
     */
    public function dailySummary(): Collection
    {
        return $this
            ->shepherdingRows()
            ->groupBy('date')
            ->map(
                function (
                    Collection $rows,
                    string $date
                ): array {
                    return [
                        'date' =>
                            $date,

                        'record_count' =>
                            $rows->count(),

                        'record_ids' =>
                            $rows
                                ->pluck('id')
                                ->map(
                                    fn ($id): int =>
                                        (int) $id
                                )
                                ->values()
                                ->all(),

                        'activity_codes' =>
                            $rows
                                ->flatMap(
                                    fn (
                                        array $row
                                    ): array =>
                                        $row[
                                            'activity_codes'
                                        ]
                                )
                                ->filter()
                                ->unique()
                                ->sort()
                                ->values()
                                ->all(),

                        'ministry_codes' =>
                            $rows
                                ->flatMap(
                                    fn (
                                        array $row
                                    ): array =>
                                        $row[
                                            'ministry_codes'
                                        ]
                                )
                                ->filter()
                                ->unique()
                                ->sort()
                                ->values()
                                ->all(),

                        'shepherding_codes' =>
                            $rows
                                ->flatMap(
                                    fn (
                                        array $row
                                    ): array =>
                                        $row[
                                            'shepherding_codes'
                                        ]
                                )
                                ->filter()
                                ->unique()
                                ->sort()
                                ->values()
                                ->all(),

                        'outcomes' =>
                            $rows
                                ->pluck('outcome')
                                ->filter()
                                ->unique()
                                ->values()
                                ->all(),
                    ];
                }
            )
            ->sortKeys();
    }

    private function dateString(
        string|CarbonInterface $date
    ): string {
        if ($date instanceof CarbonInterface) {
            return $date->toDateString();
        }

        return trim($date);
    }
}
