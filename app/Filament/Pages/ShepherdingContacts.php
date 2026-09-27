<?php

namespace App\Filament\Pages;

use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\HomeMeetingScheduleEntry;
use App\Models\Household;
use App\Models\Hymn;
use App\Models\HymnSource;
use App\Support\HymnLyricsNormalizer;
use App\Support\HymnSearchRanker;
use App\Support\RecoveryVersionBible;
use App\Models\HymnAdditionRequest;
use App\Models\Locality;
use App\Models\MinistryBook;
use App\Models\MinistryLesson;
use App\Models\MorningRevivalWeek;
use App\Models\ProvinceSetting;
use App\Models\Person;
use App\Models\PublicShepherdingSubmission;
use App\Models\ShepherdingActivityType;
use App\Models\ShepherdingContact;
use App\Support\ActivityLogger;
use App\Support\LocalityOptions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShepherdingContacts extends Page
{
    protected string $view = 'filament.pages.shepherding-contacts';

    protected static ?string $slug = 'shepherding-records';

    public ?int $editingContactId = null;

    public string $targetSearch = '';

    public string $householdMemberSearch = '';

    public string $participantSearch = '';

    public array $contactedPersonIds = [];

    public array $contactedHouseholdIds = [];

    public array $contactedCampusContactIds = [];

    public array $contactedGospelContactIds = [];

    /*
     * Campus Contact targets automatically introduced by
     * a selected Household.
     *
     * contact_id => household_id
     *
     * Standalone/manual Campus targets are intentionally
     * absent from this map.
     */
    public array $householdCampusContactHouseholdIds = [];

    /*
     * Gospel Contact Household attendance snapshot.
     *
     * gospel_contact_id => bool
     * gospel_contact_id => household_id
     */
    public array $householdGospelMemberPresence = [];

    public array $householdGospelMemberHouseholdIds = [];

    /*
     * Pending Gospel Contacts entered directly through
     * Search Contact Targets.
     *
     * Names are stored in canonical:
     * Last Name, First Name
     *
     * They are not created until saveContact() succeeds.
     */
    public array $newGospelContactNames = [];

    /*
     * person_id => bool
     *
     * true  = present / contacted
     * false = not present
     */
    public array $householdMemberPresence = [];

    /*
     * person_id => historical household_id
     */
    public array $householdMemberHouseholdIds = [];

    /*
     * Tracks Household selections whose members have
     * already been snapshotted into this form.
     */
    public array $initializedHouseholdIds = [];

    public ?int $localityId = null;

    public string $localitySource = '';

    public string $contactDate = '';

    public string $contactTime = '';

    public string $outcome =
        ShepherdingContact::OUTCOME_COMPLETED;

    public array $activityTypeIds = [];

    /*
     * Exact Morning Revival reading selected for
     * this Shepherding Contact.
     */
    public ?int $morningRevivalWeekId = null;

    public ?int $morningRevivalDay = null;

    /*
     * Full historical search is intentionally separate
     * from the compact recent-week selector.
     */
    public bool $showMorningRevivalArchive = false;

    public string $morningRevivalArchiveSearch = '';

    /*
     * Ordered Bible portions read during this contact.
     *
     * Each row:
     * [
     *     'reference' => string,
     * ]
     */
    public array $bibleReadingRows = [];

    public array $ministryLessonIds = [];

    /*
     * Display preference only.
     * The selected canonical Ministry Lesson IDs do not change.
     */
    public bool $showTagalogMinistry = false;

    /*
     * Ordered Hymns sung during this contact.
     *
     * Each row:
     * [
     *     'hymn_id' => int|null,
     *     'search' => string,
     * ]
     */
    public array $hymnRows = [];

    /*
     * Missing Hymn request forms keyed by
     * the Hymn row index.
     */
    public array $hymnRequestForms = [];

    public array $participantIds = [];

    public string $notes = '';


    public ?int $reviewingPublicSubmissionId = null;

    public string $publicReviewReference = '';

    public string $publicReviewSubmittedBy = '';

    public string $publicReviewSubmitterContact = '';

    public string $publicReviewTargets = '';

    public string $publicReviewParticipants = '';

    public string $publicReviewHymns = '';

    public string $publicReviewMorningRevival = '';


    /*
     * Public Contact Target auto-match review.
     *
     * Exact + unambiguous matches are checked in the
     * normal canonical Shepherding Contact selectors.
     */
    public array $publicReviewTargetMatches = [];

    public array $publicReviewTargetNeedsReview = [];

    public function mount(): void
    {
        $this->showTagalogMinistry =
            (bool) session(
                'shepherding_ministry_tagalog',
                false
            );

        $this->contactDate =
            now()->toDateString();

        /*
         * Existing edit-record handoff has priority.
         */
        $requestedRecordId =
            (int) request()->query(
                'record',
                0
            );

        if (
            $requestedRecordId > 0
            && ShepherdingContact::query()
                ->whereKey(
                    $requestedRecordId
                )
                ->exists()
        ) {
            $this->editContact(
                $requestedRecordId
            );

            return;
        }

        /*
         * Home Meeting Schedule handoff.
         */
        $homeMeetingScheduleId =
            (int) request()->query(
                'home_meeting_schedule',
                0
            );

        if ($homeMeetingScheduleId > 0) {
            $this->prefillFromHomeMeetingSchedule(
                $homeMeetingScheduleId
            );
        }
    }


    private function prefillFromHomeMeetingSchedule(
        int $scheduleId
    ): void {
        $schedule =
            HomeMeetingScheduleEntry::query()
                ->where('is_active', true)
                ->find($scheduleId);

        if (! $schedule) {
            return;
        }

        /*
         * This is always a new Shepherding Record.
         */
        $this->editingContactId = null;

        $this->contactDate =
            now()->toDateString();

        $this->contactTime =
            substr(
                (string) $schedule->meeting_time,
                0,
                5
            );

        $this->localityId =
            (int) $schedule->locality_id;

        $this->localitySource =
            'Auto-filled from Home Meeting Schedule.';

        /*
         * Serving Ones remain intentionally empty.
         * They are optional and should be selected
         * manually for each actual meeting.
         */
        $this->participantIds = [];

        /*
         * Automatically mark Home Meeting.
         */
        $homeMeetingActivityId =
            ShepherdingActivityType::query()
                ->where('code', 'HM')
                ->where('is_active', true)
                ->value('id');

        $this->activityTypeIds =
            $homeMeetingActivityId
                ? [(int) $homeMeetingActivityId]
                : [];

        /*
         * The Shepherding handoff requires a linked
         * Household. Unlinked schedule entries do not
         * receive the Add Shepherding Record button.
         */
        if (! $schedule->household_id) {
            return;
        }

        $householdId =
            (int) $schedule->household_id;

        $this->contactedHouseholdIds = [
            $householdId,
        ];

        /*
         * Reuse the normal Shepherding form behavior:
         * snapshot all current Household members and
         * initially mark them Present.
         */
        $this->syncHouseholdMemberSnapshot();

        /*
         * The schedule's Locality remains authoritative
         * for this prefilled Home Meeting.
         */
        $this->localityId =
            (int) $schedule->locality_id;

        $this->localitySource =
            'Auto-filled from Home Meeting Schedule.';

        /*
         * Advance to the next active Ministry Lesson
         * based on the Household's latest Shepherding
         * Record that actually used Ministry material.
         */
        $nextLessonId =
            $this
                ->nextHomeMeetingMinistryLessonId(
                    $householdId
                );

        $this->ministryLessonIds =
            $nextLessonId
                ? [$nextLessonId]
                : [];
    }


    private function nextHomeMeetingMinistryLessonId(
        int $householdId
    ): ?int {
        /*
         * Build the canonical active Ministry sequence:
         *
         * Book sort order
         *     -> Lesson sort order
         *     -> Lesson code
         *
         * Books with no active lessons are skipped.
         */
        $lessonSequence =
            MinistryBook::query()
                ->where('is_active', true)
                ->whereHas(
                    'lessons',
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            true
                        )
                )
                ->with([
                    'lessons' =>
                        fn ($query) =>
                            $query
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->orderBy(
                                    'sort_order'
                                )
                                ->orderBy('code'),
                ])
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get()
                ->flatMap(
                    fn (MinistryBook $book) =>
                        $book->lessons
                )
                ->values();

        if ($lessonSequence->isEmpty()) {
            return null;
        }

        /*
         * Most recent Shepherding Record for this
         * Household that contains Ministry Used.
         */
        $lastContact =
            ShepherdingContact::query()
                ->whereHas(
                    'contactedHouseholds',
                    fn ($query) =>
                        $query->whereKey(
                            $householdId
                        )
                )
                ->whereHas(
                    'ministryLessons'
                )
                ->with(
                    'ministryLessons'
                )
                ->orderByDesc(
                    'contact_date'
                )
                ->orderByDesc(
                    'contact_time'
                )
                ->orderByDesc('id')
                ->first();

        /*
         * No previous Ministry reading:
         * begin with the first active lesson.
         */
        if (! $lastContact) {
            return (int) $lessonSequence
                ->first()
                ->id;
        }

        /*
         * A Shepherding Record may contain more than
         * one Ministry Lesson. Treat the furthest
         * lesson in canonical order as the last one
         * completed during that record.
         */
        $positions =
            $lastContact
                ->ministryLessons
                ->map(
                    fn (MinistryLesson $lesson) =>
                        $lessonSequence->search(
                            fn (
                                MinistryLesson $candidate
                            ): bool =>
                                (int) $candidate->id
                                ===
                                (int) $lesson->id
                        )
                )
                ->filter(
                    fn ($position): bool =>
                        $position !== false
                )
                ->map(
                    fn ($position): int =>
                        (int) $position
                )
                ->values();

        /*
         * Historical Ministry may have since been
         * disabled. If none of the previous lessons
         * are currently active, begin at the first
         * active lesson instead of guessing.
         */
        if ($positions->isEmpty()) {
            return (int) $lessonSequence
                ->first()
                ->id;
        }

        $nextPosition =
            ((int) $positions->max()) + 1;

        $nextLesson =
            $lessonSequence->get(
                $nextPosition
            );

        /*
         * Null means the Household has reached the
         * end of all currently active Ministry lessons.
         */
        return $nextLesson
            ? (int) $nextLesson->id
            : null;
    }


    public function updatedShowTagalogMinistry(
        bool $value
    ): void {
        session()->put(
            'shepherding_ministry_tagalog',
            $value
        );
    }

    public function getTitle(): string
    {
        return 'Shepherding Records';
    }

    public static function getNavigationLabel(): string
    {
        return 'Shepherding Records';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shepherding';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-hand-raised';
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public function people(): Collection
    {
        return Person::query()
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function contactPeople(): Collection
    {
        $search = trim($this->targetSearch);

        $householdMemberIds = collect(
            array_keys(
                $this->householdMemberHouseholdIds
            )
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return Person::query()
            ->when(
                $householdMemberIds !== [],
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $householdMemberIds
                    )
            )
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'firstname',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'middlename',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'lastname',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'nickname',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(75)
            ->get();
    }

    public function selectedContactPeople(): Collection
    {
        if ($this->contactedPersonIds === []) {
            return collect();
        }

        return Person::query()
            ->whereIn(
                'id',
                collect($this->contactedPersonIds)
                    ->map(fn ($id) => (int) $id)
                    ->all()
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function removeContactedPerson(
        int $personId
    ): void {
        $this->contactedPersonIds = collect(
            $this->contactedPersonIds
        )
            ->map(fn ($id) => (int) $id)
            ->reject(
                fn ($id): bool =>
                    $id === $personId
            )
            ->values()
            ->all();

        $this->refreshLocalityFromTargets();
    }

    public function households(): Collection
    {
        $search = trim(
            $this->targetSearch
        );

        return Household::query()
            ->with([
                'head',
                'localityRecord',
            ])
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'household_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'locality',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'head',
                                    function ($query) use ($search): void {
                                        $query
                                            ->where(
                                                'firstname',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'lastname',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->orderBy('household_name')
            ->limit(75)
            ->get();
    }

    public function campusContacts(): Collection
    {
        $search = trim(
            $this->targetSearch
        );

        /*
         * If a Campus Contact is already linked to a
         * Person record, use the People selector instead.
         */
        return CampusContact::query()
            ->with([
                'localityRecord',
                'school',
            ])
            ->whereNull('person_id')
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'firstname',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'lastname',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'school_campus',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'locality',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(75)
            ->get();
    }

    public function selectedCampusContacts(): Collection
    {
        if (
            $this->contactedCampusContactIds === []
        ) {
            return collect();
        }

        /*
         * Do not require person_id to remain NULL here.
         * This preserves historical records if a Campus
         * Contact is later promoted to People.
         */
        return CampusContact::query()
            ->with([
                'localityRecord',
                'school',
            ])
            ->whereIn(
                'id',
                collect(
                    $this->contactedCampusContactIds
                )
                    ->map(fn ($id) => (int) $id)
                    ->all()
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function removeContactedCampusContact(
        int $campusContactId
    ): void {
        $this->contactedCampusContactIds =
            collect(
                $this->contactedCampusContactIds
            )
                ->map(fn ($id) => (int) $id)
                ->reject(
                    fn ($id): bool =>
                        $id === $campusContactId
                )
                ->values()
                ->all();

        unset(
            $this->householdCampusContactHouseholdIds[
                $campusContactId
            ]
        );

        $this->refreshLocalityFromTargets();
    }

    public function updatedContactedCampusContactIds(): void
    {
        $this->contactedCampusContactIds =
            collect(
                $this->contactedCampusContactIds
            )
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

        $selectedIds = collect(
            $this->contactedCampusContactIds
        );

        $this->householdCampusContactHouseholdIds =
            collect(
                $this
                    ->householdCampusContactHouseholdIds
            )
                ->filter(
                    fn (
                        $householdId,
                        $contactId
                    ): bool =>
                        $selectedIds->contains(
                            (int) $contactId
                        )
                )
                ->map(
                    fn ($householdId): int =>
                        (int) $householdId
                )
                ->all();

        $this->refreshLocalityFromTargets();
    }

    public function gospelContacts(): Collection
    {
        $search =
            trim($this->targetSearch);

        $householdGospelMemberIds = collect(
            array_keys(
                $this
                    ->householdGospelMemberHouseholdIds
            )
        )
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $candidate =
            $this->gospelContactCreationCandidate();

        return GospelContact::query()
            ->with('localityRecord')
            ->whereNull('person_id')
            ->when(
                $householdGospelMemberIds !== [],
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $householdGospelMemberIds
                    )
            )
            ->when(
                filled($search),
                function ($query) use (
                    $search,
                    $candidate
                ): void {
                    /*
                     * When the search follows:
                     *
                     * Last Name, First Name
                     *
                     * search the two name columns
                     * independently so existing records still
                     * appear before the Create option.
                     */
                    if ($candidate['valid']) {
                        $query
                            ->where(
                                'lastname',
                                'like',
                                '%'
                                . $candidate['lastname']
                                . '%'
                            )
                            ->where(
                                'firstname',
                                'like',
                                '%'
                                . $candidate['firstname']
                                . '%'
                            );

                        return;
                    }

                    $query->where(
                        function ($query) use ($search): void {
                            $like =
                                "%{$search}%";

                            $query
                                ->where(
                                    'firstname',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'lastname',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'locality',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'contact_place',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'contact_number',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'facebook_account',
                                    'like',
                                    $like
                                );
                        }
                    );
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(75)
            ->get();
    }


    public function gospelContactCreationCandidate(): array
    {
        $parsed =
            $this->parseGospelContactName(
                $this->targetSearch
            );

        if (! $parsed) {
            return [
                'valid' => false,

                'raw' =>
                    trim(
                        preg_replace(
                            '/\\s+/',
                            ' ',
                            $this->targetSearch
                        )
                    ),

                'firstname' => '',
                'lastname' => '',
                'display_name' => '',
            ];
        }

        return [
            'valid' => true,

            'raw' =>
                $parsed['display_name'],

            'firstname' =>
                $parsed['firstname'],

            'lastname' =>
                $parsed['lastname'],

            'display_name' =>
                $parsed['display_name'],
        ];
    }


    public function toggleNewGospelContactCandidate(): void
    {
        $candidate =
            $this->gospelContactCreationCandidate();

        if (! $candidate['valid']) {
            Notification::make()
                ->title(
                    'Use Last Name, First Name'
                )
                ->body(
                    'Example: Adona, Maria'
                )
                ->warning()
                ->send();

            return;
        }

        $name =
            $candidate['display_name'];

        $existingIndex =
            collect(
                $this->newGospelContactNames
            )
                ->search(
                    fn ($selected): bool =>
                        mb_strtolower(
                            trim(
                                (string) $selected
                            )
                        )
                        ===
                        mb_strtolower($name)
                );

        if ($existingIndex !== false) {
            $this->newGospelContactNames =
                collect(
                    $this->newGospelContactNames
                )
                    ->forget(
                        $existingIndex
                    )
                    ->values()
                    ->all();

            return;
        }

        $this->newGospelContactNames =
            collect(
                $this->newGospelContactNames
            )
                ->push($name)
                ->unique(
                    fn ($selected): string =>
                        mb_strtolower(
                            trim(
                                (string) $selected
                            )
                        )
                )
                ->values()
                ->all();
    }


    public function removeNewGospelContactCandidate(
        int $index
    ): void {
        $this->newGospelContactNames =
            collect(
                $this->newGospelContactNames
            )
                ->forget($index)
                ->values()
                ->all();
    }


    public function selectedGospelContacts(): Collection
    {
        if ($this->contactedGospelContactIds === []) {
            return collect();
        }

        $householdGospelMemberIds = collect(
            array_keys(
                $this
                    ->householdGospelMemberHouseholdIds
            )
        )
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        /*
         * Do not filter person_id here.
         *
         * Historical standalone Gospel Contact targets must
         * remain visible after promotion to People.
         *
         * Gospel Contacts represented by a selected Household
         * are shown instead under Household Members Present.
         */
        return GospelContact::query()
            ->with('localityRecord')
            ->whereIn(
                'id',
                collect(
                    $this->contactedGospelContactIds
                )
                    ->map(fn ($id) => (int) $id)
                    ->all()
            )
            ->when(
                $householdGospelMemberIds !== [],
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $householdGospelMemberIds
                    )
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function removeContactedGospelContact(
        int $gospelContactId
    ): void {
        $this->contactedGospelContactIds =
            collect(
                $this->contactedGospelContactIds
            )
                ->map(fn ($id) => (int) $id)
                ->reject(
                    fn ($id): bool =>
                        $id === $gospelContactId
                )
                ->values()
                ->all();

        $this->refreshLocalityFromTargets();
    }

    public function updatedContactedGospelContactIds(): void
    {
        $this->contactedGospelContactIds =
            collect(
                $this->contactedGospelContactIds
            )
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

        $this->refreshLocalityFromTargets();
    }

    public function householdMembers(): Collection
    {
        $ids = collect(
            array_keys(
                $this->householdMemberHouseholdIds
            )
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $search = trim(
            $this->householdMemberSearch
        );

        return Person::query()
            ->whereIn(
                'id',
                $ids->all()
            )
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'firstname',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'middlename',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'lastname',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'nickname',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function householdGospelMembers(): Collection
    {
        $ids = collect(
            array_keys(
                $this
                    ->householdGospelMemberHouseholdIds
            )
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $search = trim(
            $this->householdMemberSearch
        );

        /*
         * Do not filter person_id here.
         *
         * A historical Shepherding Record must continue
         * showing this Gospel Contact attendance snapshot
         * even if the contact is later promoted to People.
         */
        return GospelContact::query()
            ->whereIn(
                'id',
                $ids->all()
            )
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'firstname',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'lastname',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }


    public function selectedContactedHouseholds(): Collection
    {
        if (
            $this->contactedHouseholdIds === []
        ) {
            return collect();
        }

        return Household::query()
            ->with([
                'head',
                'localityRecord',
            ])
            ->whereIn(
                'id',
                collect(
                    $this->contactedHouseholdIds
                )
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->all()
            )
            ->orderBy('household_name')
            ->get();
    }

    public function removeContactedHousehold(
        int $householdId
    ): void {
        $this->contactedHouseholdIds =
            collect(
                $this->contactedHouseholdIds
            )
                ->map(
                    fn ($id) => (int) $id
                )
                ->reject(
                    fn ($id): bool =>
                        $id === $householdId
                )
                ->values()
                ->all();

        $this->syncHouseholdMemberSnapshot();

        $this->refreshLocalityFromTargets();
    }

    public function participantPeople(): Collection
    {
        $search = trim(
            $this->participantSearch
        );

        /*
         * People already represented as Contact targets
         * must not appear again as Serving Saints.
         */
        $excludedPersonIds = collect(
            $this->contactedPersonIds
        )
            ->map(fn ($id) => (int) $id)
            ->concat(
                collect(
                    array_keys(
                        $this->householdMemberHouseholdIds
                    )
                )
                    ->map(
                        fn ($id) => (int) $id
                    )
            )
            ->unique()
            ->values()
            ->all();

        return Person::query()
            ->when(
                $excludedPersonIds !== [],
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $excludedPersonIds
                    )
            )
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'firstname',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'middlename',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'lastname',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'nickname',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(75)
            ->get();
    }

    public function selectedParticipants(): Collection
    {
        if ($this->participantIds === []) {
            return collect();
        }

        return Person::query()
            ->whereIn(
                'id',
                collect($this->participantIds)
                    ->map(fn ($id) => (int) $id)
                    ->all()
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function removeParticipant(int $personId): void
    {
        $this->participantIds = collect(
            $this->participantIds
        )
            ->map(fn ($id) => (int) $id)
            ->reject(
                fn ($id) => $id === $personId
            )
            ->values()
            ->all();
    }

    public function localityGroups(): array
    {
        $groups =
            LocalityOptions::groupedActiveConfigured();

        /*
         * Active configured Localities are already grouped as:
         *
         * Quezon
         * Outside — Batangas
         * Outside — Laguna
         * ...
         *
         * If an existing historical Shepherding Contact points
         * to an archived Locality, keep that saved Locality visible
         * while editing the record.
         */
        if (filled($this->localityId)) {
            $selectedId = (int) $this->localityId;

            $alreadyIncluded = collect($groups)
                ->contains(
                    fn (array $options): bool =>
                        array_key_exists(
                            $selectedId,
                            $options
                        )
                );

            if (! $alreadyIncluded) {
                $locality = Locality::query()
                    ->with('province')
                    ->find($selectedId);

                if ($locality) {
                    $primaryProvinceId =
                        ProvinceSetting::query()
                            ->value(
                                'primary_province_id'
                            );

                    $isPrimary =
                        $primaryProvinceId
                        && (int) $locality->province_id
                            === (int) $primaryProvinceId;

                    $provinceName =
                        $locality->province?->name
                        ?? 'Other Province';

                    $groupLabel = $isPrimary
                        ? $provinceName
                        : 'Outside — '
                            . $provinceName;

                    $groups[$groupLabel] ??= [];

                    $groups[$groupLabel][
                        $locality->id
                    ] =
                        $locality->name
                        . ' (Archived)';
                }
            }
        }

        return $groups;
    }

    public function updatedContactedPersonIds(): void
    {
        $this->contactedPersonIds =
            collect(
                $this->contactedPersonIds
            )
                ->map(
                    fn ($id) => (int) $id
                )
                ->unique()
                ->values()
                ->all();

        /*
         * A contacted Person should not simultaneously
         * remain selected as a Serving Saint.
         */
        $contacted = collect(
            $this->contactedPersonIds
        );

        $this->participantIds =
            collect($this->participantIds)
                ->map(
                    fn ($id) => (int) $id
                )
                ->reject(
                    fn ($id): bool =>
                        $contacted->contains($id)
                )
                ->unique()
                ->values()
                ->all();

        $this->refreshLocalityFromTargets();
    }

    public function updatedContactedHouseholdIds(): void
    {
        $this->contactedHouseholdIds =
            collect(
                $this->contactedHouseholdIds
            )
                ->map(
                    fn ($id) => (int) $id
                )
                ->unique()
                ->values()
                ->all();

        $this->syncHouseholdMemberSnapshot();

        $this->refreshLocalityFromTargets();
    }

    public function updatedLocalityId(): void
    {
        $this->localitySource =
            filled($this->localityId)
                ? 'Manually selected.'
                : 'No Locality selected.';
    }

    public function updatedActivityTypeIds(): void
    {
        if (
            $this->isHymnSingingSelected()
            && $this->hymnRows === []
        ) {
            $this->addHymnRow();
        }

        if (
            $this->isBibleReadingSelected()
            && $this->bibleReadingRows === []
        ) {
            $this->addBibleReadingRow();
        }

        if (! $this->isBibleReadingSelected()) {
            $this->bibleReadingRows = [];
        }

        if (
            $this->isMorningRevivalSelected()
        ) {
            /*
             * Only default when no reading has already
             * been chosen. Manual choices remain intact.
             */
            if (
                ! $this->morningRevivalWeekId
                || ! $this->morningRevivalDay
            ) {
                $this->useTodaysMorningRevival(
                    false
                );
            }

            return;
        }

        $this->clearMorningRevivalSelection();
    }

    public function activityTypes(): Collection
    {
        return ShepherdingActivityType::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function morningRevivalActivityId(): ?int
    {
        $id =
            ShepherdingActivityType::query()
                ->where(
                    'code',
                    'MR'
                )
                ->value('id');

        return $id
            ? (int) $id
            : null;
    }

    public function isMorningRevivalSelected(): bool
    {
        $activityId =
            $this->morningRevivalActivityId();

        if (! $activityId) {
            return false;
        }

        return collect(
            $this->activityTypeIds
        )
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->contains(
                $activityId
            );
    }

    public function recentMorningRevivalWeeks(): Collection
    {
        /*
         * Normal UX shows only a small current window.
         * Historical records use the dedicated archive
         * search instead.
         */
        return MorningRevivalWeek::query()
            ->with('publication')
            ->where(
                'is_active',
                true
            )
            ->whereHas(
                'publication',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
            )
            ->whereDate(
                'start_date',
                '<=',
                now()->toDateString()
            )
            ->orderByDesc(
                'start_date'
            )
            ->limit(8)
            ->get();
    }

    public function selectedMorningRevivalWeek(): ?MorningRevivalWeek
    {
        if (
            ! $this->morningRevivalWeekId
        ) {
            return null;
        }

        return MorningRevivalWeek::query()
            ->with('publication')
            ->find(
                $this->morningRevivalWeekId
            );
    }

    public function morningRevivalArchiveResults(): Collection
    {
        if (
            ! $this->showMorningRevivalArchive
        ) {
            return collect();
        }

        $search =
            trim(
                $this->morningRevivalArchiveSearch
            );

        if ($search === '') {
            return collect();
        }

        $like =
            '%' . $search . '%';

        return MorningRevivalWeek::query()
            ->with('publication')
            ->where(
                function ($query) use (
                    $search,
                    $like
                ): void {
                    $query
                        ->where(
                            'title',
                            'like',
                            $like
                        )
                        ->orWhereRaw(
                            'CAST(week_number AS CHAR) LIKE ?',
                            [
                                $like,
                            ]
                        )
                        ->orWhereHas(
                            'publication',
                            function ($publicationQuery) use (
                                $like
                            ): void {
                                $publicationQuery
                                    ->where(
                                        'source_title',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'general_subject',
                                        'like',
                                        $like
                                    );
                            }
                        );

                    if (
                        preg_match(
                            '/^\d{4}$/',
                            $search
                        )
                    ) {
                        $query->orWhereYear(
                            'start_date',
                            (int) $search
                        );
                    }
                }
            )
            ->orderByDesc(
                'start_date'
            )
            ->limit(30)
            ->get();
    }

    public function useTodaysMorningRevival(
        bool $notify = true
    ): void {
        $today =
            now()->startOfDay();

        /*
         * A valid Day 1-6 week must have started
         * between today and five days earlier.
         * This naturally excludes Lord's Day.
         */
        $week =
            MorningRevivalWeek::query()
                ->with('publication')
                ->where(
                    'is_active',
                    true
                )
                ->whereHas(
                    'publication',
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            true
                        )
                )
                ->whereDate(
                    'start_date',
                    '>=',
                    $today
                        ->copy()
                        ->subDays(5)
                        ->toDateString()
                )
                ->whereDate(
                    'start_date',
                    '<=',
                    $today->toDateString()
                )
                ->orderByDesc(
                    'start_date'
                )
                ->get()
                ->first(
                    fn (
                        MorningRevivalWeek $week
                    ): bool =>
                        $week->dayNumberForDate(
                            $today
                        ) !== null
                );

        if (! $week) {
            $this->morningRevivalWeekId =
                null;

            $this->morningRevivalDay =
                null;

            if ($notify) {
                Notification::make()
                    ->title(
                        'No Morning Revival reading today'
                    )
                    ->body(
                        'Today has no scheduled Day 1–6 '
                        . 'reading. Choose a week and day '
                        . 'manually if needed.'
                    )
                    ->warning()
                    ->send();
            }

            return;
        }

        $this->morningRevivalWeekId =
            (int) $week->id;

        $this->morningRevivalDay =
            $week->dayNumberForDate(
                $today
            );

        $this->showMorningRevivalArchive =
            false;

        $this->morningRevivalArchiveSearch =
            '';

        if ($notify) {
            Notification::make()
                ->title(
                    'Using today’s Morning Revival'
                )
                ->body(
                    'Week '
                    . $week->week_number
                    . ' · Day '
                    . $this->morningRevivalDay
                )
                ->success()
                ->send();
        }
    }

    public function updatedMorningRevivalWeekId(
        mixed $value
    ): void {
        $weekId =
            filled($value)
                ? (int) $value
                : null;

        $exists =
            $weekId
                ? MorningRevivalWeek::query()
                    ->whereKey(
                        $weekId
                    )
                    ->exists()
                : false;

        $this->morningRevivalWeekId =
            $exists
                ? $weekId
                : null;

        if (
            ! $this->morningRevivalWeekId
        ) {
            $this->morningRevivalDay =
                null;

            return;
        }

        if (
            ! $this->morningRevivalDay
            || $this->morningRevivalDay < 1
            || $this->morningRevivalDay > 6
        ) {
            $this->morningRevivalDay = 1;
        }
    }

    public function updatedMorningRevivalDay(
        mixed $value
    ): void {
        $day =
            filled($value)
                ? (int) $value
                : null;

        $this->morningRevivalDay =
            (
                $day !== null
                && $day >= 1
                && $day <= 6
            )
                ? $day
                : null;
    }

    public function openMorningRevivalArchive(): void
    {
        $this->showMorningRevivalArchive =
            true;

        $this->morningRevivalArchiveSearch =
            '';
    }

    public function closeMorningRevivalArchive(): void
    {
        $this->showMorningRevivalArchive =
            false;

        $this->morningRevivalArchiveSearch =
            '';
    }

    public function selectMorningRevivalArchiveWeek(
        int $weekId
    ): void {
        $week =
            MorningRevivalWeek::query()
                ->find($weekId);

        if (! $week) {
            return;
        }

        $this->morningRevivalWeekId =
            (int) $week->id;

        if (
            ! $this->morningRevivalDay
            || $this->morningRevivalDay < 1
            || $this->morningRevivalDay > 6
        ) {
            $this->morningRevivalDay = 1;
        }

        $this->closeMorningRevivalArchive();
    }

    private function clearMorningRevivalSelection(): void
    {
        $this->morningRevivalWeekId =
            null;

        $this->morningRevivalDay =
            null;

        $this->showMorningRevivalArchive =
            false;

        $this->morningRevivalArchiveSearch =
            '';
    }

    public function bibleReadingActivityId(): ?int
    {
        $id =
            ShepherdingActivityType::query()
                ->where('code', 'BR')
                ->value('id');

        return $id
            ? (int) $id
            : null;
    }

    public function isBibleReadingSelected(): bool
    {
        $activityId =
            $this->bibleReadingActivityId();

        if (! $activityId) {
            return false;
        }

        return collect(
            $this->activityTypeIds
        )
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->contains($activityId);
    }

    public function addBibleReadingRow(): void
    {
        $this->bibleReadingRows[] = [
            'reference' => '',
        ];
    }

    public function removeBibleReadingRow(
        int $index
    ): void {
        if (
            ! array_key_exists(
                $index,
                $this->bibleReadingRows
            )
        ) {
            return;
        }

        unset(
            $this->bibleReadingRows[$index]
        );

        $this->bibleReadingRows =
            array_values(
                $this->bibleReadingRows
            );
    }

    public function hymnSingingActivityId(): ?int
    {
        $id =
            ShepherdingActivityType::query()
                ->where('code', 'HS')
                ->value('id');

        return $id
            ? (int) $id
            : null;
    }

    public function isHymnSingingSelected(): bool
    {
        $activityId =
            $this->hymnSingingActivityId();

        if (! $activityId) {
            return false;
        }

        return collect(
            $this->activityTypeIds
        )
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->contains($activityId);
    }

    public function openHymnRequest(
        int $index
    ): void {
        if (
            ! array_key_exists(
                $index,
                $this->hymnRows
            )
        ) {
            return;
        }

        $search =
            trim(
                (string) (
                    $this->hymnRows[
                        $index
                    ]['search']
                    ?? ''
                )
            );

        $this->hymnRequestForms[
            $index
        ] = [
            'title' =>
                $search,

            'language' =>
                '',

            'lyrics' =>
                '',

            'source_url' =>
                '',

            'book_name' =>
                '',

            'hymn_number' =>
                '',
        ];
    }

    public function closeHymnRequest(
        int $index
    ): void {
        unset(
            $this->hymnRequestForms[
                $index
            ]
        );
    }

    public function submitHymnRequest(
        int $index
    ): void {
        if (
            ! array_key_exists(
                $index,
                $this->hymnRows
            )
            || ! array_key_exists(
                $index,
                $this->hymnRequestForms
            )
        ) {
            return;
        }

        $form =
            $this->hymnRequestForms[
                $index
            ];

        $title =
            trim(
                (string) (
                    $form['title']
                    ?? ''
                )
            );

        if ($title === '') {
            $this->addError(
                "hymnRequestForms.{$index}.title",
                'The hymn title is required.'
            );

            return;
        }

        $sourceUrl =
            trim(
                (string) (
                    $form['source_url']
                    ?? ''
                )
            );

        if (
            $sourceUrl !== ''
            && ! filter_var(
                $sourceUrl,
                FILTER_VALIDATE_URL
            )
        ) {
            $this->addError(
                "hymnRequestForms.{$index}.source_url",
                'Enter a valid source link.'
            );

            return;
        }

        $request =
            HymnAdditionRequest::query()
                ->where(
                    'status',
                    HymnAdditionRequest::STATUS_PENDING
                )
                ->whereRaw(
                    'LOWER(title) = ?',
                    [
                        mb_strtolower(
                            $title
                        ),
                    ]
                )
                ->first();

        $reusedExisting =
            (bool) $request;

        if (! $request) {
            $request =
                HymnAdditionRequest::query()
                    ->create([
                        'requested_by_id' =>
                            auth()->id(),

                        'title' =>
                            $title,

                        'language' =>
                            filled(
                                $form['language']
                                    ?? null
                            )
                                ? trim(
                                    (string)
                                    $form['language']
                                )
                                : null,

                        'lyrics' =>
                            filled(
                                $form['lyrics']
                                    ?? null
                            )
                                ? trim(
                                    (string)
                                    $form['lyrics']
                                )
                                : null,

                        'source_url' =>
                            $sourceUrl !== ''
                                ? $sourceUrl
                                : null,

                        'book_name' =>
                            filled(
                                $form['book_name']
                                    ?? null
                            )
                                ? trim(
                                    (string)
                                    $form['book_name']
                                )
                                : null,

                        'hymn_number' =>
                            filled(
                                $form['hymn_number']
                                    ?? null
                            )
                                ? trim(
                                    (string)
                                    $form['hymn_number']
                                )
                                : null,

                        'status' =>
                            HymnAdditionRequest::STATUS_PENDING,
                    ]);
        }

        /*
         * Keep the pending request inside the Hymn row.
         *
         * The actual Shepherding-record association is
         * created when saveContact() persists the record.
         * This also works while creating a brand-new
         * Shepherding record that has no ID yet.
         */
        $this->hymnRows[
            $index
        ] = [
            'hymn_id' =>
                null,

            'request_id' =>
                (int) $request->id,

            'request_title' =>
                (string) $request->title,

            'search' =>
                '',
        ];

        unset(
            $this->hymnRequestForms[
                $index
            ]
        );

        Notification::make()
            ->title(
                $reusedExisting
                    ? 'Pending hymn request selected'
                    : 'Hymn addition requested'
            )
            ->body(
                $reusedExisting
                    ? 'An Admin is already reviewing this hymn. Save the Shepherding Record to keep this request attached.'
                    : 'The request was sent for Admin approval. Save the Shepherding Record to keep this pending hymn attached.'
            )
            ->success()
            ->send();
    }

    public function addHymnRow(): void
    {
        $this->hymnRows[] = [
            'hymn_id' => null,
            'request_id' => null,
            'request_title' => null,
            'search' => '',
        ];
    }

    public function removeHymnRow(
        int $index
    ): void {
        if (
            ! array_key_exists(
                $index,
                $this->hymnRows
            )
        ) {
            return;
        }

        unset(
            $this->hymnRows[$index],
            $this->hymnRequestForms[$index]
        );

        $this->hymnRows =
            array_values(
                $this->hymnRows
            );
    }

    private function buildHymnSyncs(
        array $rows
    ): array {
        $requestIds =
            collect($rows)
                ->pluck('request_id')
                ->filter()
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values();

        $requests =
            $requestIds->isEmpty()
                ? collect()
                : HymnAdditionRequest::query()
                    ->whereIn(
                        'id',
                        $requestIds->all()
                    )
                    ->get([
                        'id',
                        'status',
                        'created_hymn_id',
                    ])
                    ->keyBy('id');

        $hymnSync = [];
        $requestSync = [];

        foreach (
            array_values($rows)
            as $index => $row
        ) {
            $sortOrder =
                $index + 1;

            $hymnId =
                filled(
                    $row['hymn_id']
                        ?? null
                )
                    ? (int) $row[
                        'hymn_id'
                    ]
                    : null;

            if ($hymnId) {
                if (
                    ! array_key_exists(
                        $hymnId,
                        $hymnSync
                    )
                ) {
                    $hymnSync[
                        $hymnId
                    ] = [
                        'sort_order' =>
                            $sortOrder,
                    ];
                }

                continue;
            }

            $requestId =
                filled(
                    $row['request_id']
                        ?? null
                )
                    ? (int) $row[
                        'request_id'
                    ]
                    : null;

            if (! $requestId) {
                continue;
            }

            $request =
                $requests->get(
                    $requestId
                );

            if (! $request) {
                continue;
            }

            if (
                $request->status
                === HymnAdditionRequest::STATUS_PENDING
            ) {
                if (
                    ! array_key_exists(
                        $requestId,
                        $requestSync
                    )
                ) {
                    $requestSync[
                        $requestId
                    ] = [
                        'sort_order' =>
                            $sortOrder,
                    ];
                }

                continue;
            }

            /*
             * An Admin may approve the request while
             * this Shepherding form is still open.
             *
             * In that case, save the resolved canonical
             * Hymn directly instead of reviving a stale
             * pending association.
             */
            if (
                $request->status
                    === HymnAdditionRequest::STATUS_APPROVED
                && filled(
                    $request->created_hymn_id
                )
            ) {
                $resolvedHymnId =
                    (int)
                    $request
                        ->created_hymn_id;

                if (
                    ! array_key_exists(
                        $resolvedHymnId,
                        $hymnSync
                    )
                ) {
                    $hymnSync[
                        $resolvedHymnId
                    ] = [
                        'sort_order' =>
                            $sortOrder,
                    ];
                }
            }
        }

        return [
            $hymnSync,
            $requestSync,
        ];
    }

    public function selectedHymns(): Collection
    {
        $ids = collect(
            $this->hymnRows
        )
            ->pluck('hymn_id')
            ->filter()
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Hymn::query()
            ->with([
                'bookEntries.hymnBook',

                'sources' =>
                    fn ($query) =>
                        $query->whereIn(
                            'provider',
                            [
                                HymnSource::PROVIDER_YOUTUBE,
                                HymnSource::PROVIDER_SOUNDCLOUD,
                                HymnSource::PROVIDER_OTHER,
                            ]
                        ),
            ])
            ->whereIn(
                'id',
                $ids->all()
            )
            ->get()
            ->keyBy('id');
    }

    public function hymnSearchResults(
        int $index
    ): Collection {
        if (
            ! array_key_exists(
                $index,
                $this->hymnRows
            )
        ) {
            return collect();
        }

        $search =
            trim(
                (string) (
                    $this->hymnRows[
                        $index
                    ]['search']
                    ?? ''
                )
            );

        if ($search === '') {
            return collect();
        }

        $selectedElsewhere =
            collect(
                $this->hymnRows
            )
                ->except($index)
                ->pluck('hymn_id')
                ->filter()
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values()
                ->all();

        return Hymn::query()
            ->with([
                'bookEntries.hymnBook',
            ])
            ->where(
                'is_active',
                true
            )
            ->when(
                $selectedElsewhere !== [],
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $selectedElsewhere
                    )
            )
            ->where(
                function ($query) use (
                    $search
                ): void {
                    $like =
                        "%{$search}%";

                    $query
                        ->where(
                            'title',
                            'like',
                            $like
                        )
                        ->orWhereHas(
                            'sources',
                            function (
                                $sourceQuery
                            ) use (
                                $like
                            ): void {
                                $sourceQuery->where(
                                    function (
                                        $lyricsQuery
                                    ) use (
                                        $like
                                    ): void {
                                        $lyricsQuery
                                            ->where(
                                                'first_line_search',
                                                'like',
                                                HymnLyricsNormalizer::containsPattern($like)
                                            )
                                            ->orWhere(
                                                'lyrics_search',
                                                'like',
                                                HymnLyricsNormalizer::containsPattern($like)
                                            );
                                    }
                                );
                            }
                        )
                        ->orWhereHas(
                            'bookEntries',
                            fn ($entryQuery) =>
                                $entryQuery
                                    ->where(
                                        'number',
                                        'like',
                                        $like
                                    )
                        );
                }
            )
            ->when(
                $search !== '',
                fn ($query) =>
                    HymnSearchRanker::apply(
                        $query,
                        $search,
                        true
                    )
            )
            ->limit(30)
            ->get();
    }

    public function selectHymn(
        int $index,
        int $hymnId
    ): void {
        if (
            ! array_key_exists(
                $index,
                $this->hymnRows
            )
        ) {
            return;
        }

        $exists = Hymn::query()
            ->whereKey($hymnId)
            ->where(
                'is_active',
                true
            )
            ->exists();

        if (! $exists) {
            Notification::make()
                ->title(
                    'Hymn is unavailable'
                )
                ->warning()
                ->send();

            return;
        }

        $alreadySelected =
            collect(
                $this->hymnRows
            )
                ->except($index)
                ->pluck('hymn_id')
                ->filter()
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->contains($hymnId);

        if ($alreadySelected) {
            Notification::make()
                ->title(
                    'Hymn already selected'
                )
                ->warning()
                ->send();

            return;
        }

        $this->hymnRows[
            $index
        ] = [
            'hymn_id' =>
                $hymnId,

            'request_id' =>
                null,

            'request_title' =>
                null,

            'search' =>
                '',
        ];
    }

    public function ministryBooks(): Collection
    {
        return MinistryBook::query()
            ->where('is_active', true)
            ->with([
                'lessons' => fn ($query) =>
                    $query
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('code'),
            ])
            ->whereHas(
                'lessons',
                fn ($query) =>
                    $query->where('is_active', true)
            )
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    public function removeMinistryLesson(
        int $lessonId
    ): void {
        $this->ministryLessonIds =
            collect(
                $this->ministryLessonIds
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->reject(
                    fn (int $id): bool =>
                        $id === $lessonId
                )
                ->unique()
                ->values()
                ->all();
    }

    public function clearMinistryLessons(): void
    {
        $this->ministryLessonIds = [];
    }

    public function outcomeOptions(): array
    {
        return ShepherdingContact::outcomeOptions();
    }

    public function publicShepherdingSubmissions(): Collection
    {
        if (! auth()->user()?->isAdmin()) {
            return collect();
        }

        return PublicShepherdingSubmission::query()
            ->with('locality')
            ->where(
                'status',
                PublicShepherdingSubmission::STATUS_PENDING
            )
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function reviewPublicSubmission(
        int $submissionId
    ): void {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $submission =
            PublicShepherdingSubmission::query()
                ->where(
                    'status',
                    PublicShepherdingSubmission::STATUS_PENDING
                )
                ->findOrFail(
                    $submissionId
                );

        $this->resetContactForm();

        $this->reviewingPublicSubmissionId =
            $submission->id;

        $this->publicReviewReference =
            (string) $submission->public_id;

        $this->publicReviewSubmittedBy =
            (string) $submission->submitted_by_name;

        $this->publicReviewSubmitterContact =
            (string) (
                $submission->submitted_by_contact
                ?? ''
            );

        $this->publicReviewTargets =
            (string) $submission->contact_targets_text;

        $this->publicReviewParticipants =
            (string) (
                $submission->participant_names_text
                ?? ''
            );

        $this->publicReviewHymns =
            (string) (
                $submission->hymns_text
                ?? ''
            );

        $this->publicReviewMorningRevival =
            (string) (
                $submission->morning_revival_text
                ?? ''
            );

        $this->contactDate =
            $submission
                ->contact_date
                ?->format('Y-m-d')
            ?? now()->toDateString();

        $this->contactTime =
            (string) (
                $submission->contact_time
                ?? ''
            );

        $this->localityId =
            $submission->locality_id
                ? (int) $submission->locality_id
                : null;

        $this->localitySource =
            'Public Shepherding Dashboard';

        $this->outcome =
            (string) $submission->outcome;

        $this->activityTypeIds =
            collect(
                $submission->activity_type_ids
                ?? []
            )
                ->map(
                    fn ($id) => (int) $id
                )
                ->unique()
                ->values()
                ->all();

        $this->ministryLessonIds =
            collect(
                $submission->ministry_lesson_ids
                ?? []
            )
                ->map(
                    fn ($id) => (int) $id
                )
                ->unique()
                ->values()
                ->all();

        $this->bibleReadingRows =
            collect(
                $submission->bible_references
                ?? []
            )
                ->map(
                    fn ($reference): array => [
                        'reference' =>
                            (string) $reference,
                    ]
                )
                ->values()
                ->all();

        $this->notes =
            (string) (
                $submission->notes
                ?? ''
            );

        /*
         * Automatically parse the public Contact Targets
         * and check only exact, unambiguous canonical
         * matches. Anything uncertain remains for Admin
         * review.
         */
        $this->parseAndCheckPublicContactTargets(
            false
        );

        Notification::make()
            ->title(
                'Public submission loaded for review'
            )
            ->body(
                count(
                    $this->publicReviewTargetMatches
                )
                . ' Contact Target(s) checked automatically. '
                . count(
                    $this->publicReviewTargetNeedsReview
                )
                . ' target(s) need review. '
                . 'Also resolve serving saints, Hymns, '
                . 'and Morning Revival as needed.'
            )
            ->warning()
            ->send();
    }

    public function parseAndCheckPublicContactTargets(
        bool $notify = true
    ): void {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        if (
            ! $this->reviewingPublicSubmissionId
        ) {
            return;
        }

        $lines =
            $this->publicContactTargetLines(
                $this->publicReviewTargets
            );

        $this->publicReviewTargetMatches = [];
        $this->publicReviewTargetNeedsReview = [];

        if ($lines === []) {
            if ($notify) {
                Notification::make()
                    ->title(
                        'No Public Contact Targets found'
                    )
                    ->warning()
                    ->send();
            }

            return;
        }

        /*
         * Build one exact-name index across all four
         * canonical target sources.
         *
         * Linked Campus / Gospel Contacts are intentionally
         * excluded because Shepherding Records already
         * requires those to be selected through People.
         */
        $index = [];

        $addCandidate =
            function (
                array $candidate,
                array $keys
            ) use (
                &$index
            ): void {
                foreach (
                    array_unique($keys)
                    as $key
                ) {
                    if ($key === '') {
                        continue;
                    }

                    $candidateKey =
                        $candidate['type']
                        . ':'
                        . $candidate['id'];

                    $index[$key][
                        $candidateKey
                    ] = $candidate;
                }
            };

        /*
         * People Database.
         */
        Person::query()
            ->get([
                'id',
                'firstname',
                'middlename',
                'lastname',
                'nickname',
            ])
            ->each(
                function ($person) use (
                    $addCandidate
                ): void {
                    $addCandidate(
                        [
                            'type' => 'person',
                            'type_label' => 'Person',
                            'id' => (int) $person->id,
                            'label' =>
                                $person->display_name,
                        ],
                        $this->publicPersonNameKeys(
                            $person->firstname,
                            $person->middlename,
                            $person->lastname,
                            $person->nickname
                        )
                    );
                }
            );

        /*
         * Households.
         */
        Household::query()
            ->get([
                'id',
                'household_name',
            ])
            ->each(
                function ($household) use (
                    $addCandidate
                ): void {
                    $name =
                        $this->normalizePublicTargetName(
                            (string)
                            $household
                                ->household_name
                        );

                    $keys = [
                        $name,
                    ];

                    if ($name !== '') {
                        $keys[] =
                            $name
                            . ' household';

                        $keys[] =
                            $name
                            . ' family';

                        if (
                            str_ends_with(
                                $name,
                                ' household'
                            )
                        ) {
                            $keys[] =
                                trim(
                                    substr(
                                        $name,
                                        0,
                                        -10
                                    )
                                );
                        }

                        if (
                            str_ends_with(
                                $name,
                                ' family'
                            )
                        ) {
                            $keys[] =
                                trim(
                                    substr(
                                        $name,
                                        0,
                                        -7
                                    )
                                );
                        }
                    }

                    $addCandidate(
                        [
                            'type' =>
                                'household',

                            'type_label' =>
                                'Household',

                            'id' =>
                                (int)
                                $household->id,

                            'label' =>
                                $household
                                    ->display_name,
                        ],
                        $keys
                    );
                }
            );

        /*
         * Unlinked Campus Contacts.
         */
        CampusContact::query()
            ->whereNull('person_id')
            ->get([
                'id',
                'firstname',
                'lastname',
            ])
            ->each(
                function ($contact) use (
                    $addCandidate
                ): void {
                    $addCandidate(
                        [
                            'type' =>
                                'campus',

                            'type_label' =>
                                'Campus Contact',

                            'id' =>
                                (int)
                                $contact->id,

                            'label' =>
                                $contact
                                    ->display_name,
                        ],
                        $this->publicPersonNameKeys(
                            $contact->firstname,
                            null,
                            $contact->lastname,
                            null
                        )
                    );
                }
            );

        /*
         * Unlinked Gospel Contacts.
         */
        GospelContact::query()
            ->whereNull('person_id')
            ->get([
                'id',
                'firstname',
                'lastname',
            ])
            ->each(
                function ($contact) use (
                    $addCandidate
                ): void {
                    $addCandidate(
                        [
                            'type' =>
                                'gospel',

                            'type_label' =>
                                'Gospel Contact',

                            'id' =>
                                (int)
                                $contact->id,

                            'label' =>
                                $contact
                                    ->display_name,
                        ],
                        $this->publicPersonNameKeys(
                            $contact->firstname,
                            null,
                            $contact->lastname,
                            null
                        )
                    );
                }
            );

        $matches = [];
        $needsReview = [];

        foreach ($lines as $rawLine) {
            $key =
                $this->normalizePublicTargetName(
                    $rawLine
                );

            $candidates =
                array_values(
                    $index[$key]
                    ?? []
                );

            if (count($candidates) === 1) {
                $candidate =
                    $candidates[0];

                $matches[] = [
                    'raw' => $rawLine,
                    ...$candidate,
                ];

                continue;
            }

            if (count($candidates) > 1) {
                $needsReview[] = [
                    'raw' =>
                        $rawLine,

                    'reason' =>
                        'Multiple exact matches found.',

                    'candidates' =>
                        array_map(
                            fn (
                                array $candidate
                            ): string =>
                                $candidate[
                                    'type_label'
                                ]
                                . ' · '
                                . $candidate[
                                    'label'
                                ],
                            $candidates
                        ),
                ];

                continue;
            }

            $needsReview[] = [
                'raw' =>
                    $rawLine,

                'reason' =>
                    'No exact match found.',

                'candidates' =>
                    [],
            ];
        }

        /*
         * Add exact matches to the current selections.
         * Existing Admin selections are preserved.
         */
        foreach ($matches as $match) {
            $id =
                (int) $match['id'];

            match ($match['type']) {
                'person' =>
                    $this->contactedPersonIds[] =
                        $id,

                'household' =>
                    $this->contactedHouseholdIds[] =
                        $id,

                'campus' =>
                    $this->contactedCampusContactIds[] =
                        $id,

                'gospel' =>
                    $this->contactedGospelContactIds[] =
                        $id,

                default => null,
            };
        }

        $this->contactedPersonIds =
            collect(
                $this->contactedPersonIds
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values()
                ->all();

        $this->contactedHouseholdIds =
            collect(
                $this->contactedHouseholdIds
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values()
                ->all();

        $this->contactedCampusContactIds =
            collect(
                $this
                    ->contactedCampusContactIds
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values()
                ->all();

        $this->contactedGospelContactIds =
            collect(
                $this
                    ->contactedGospelContactIds
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values()
                ->all();

        /*
         * Selecting a Household in the normal form also
         * snapshots its current members. Preserve exactly
         * that behavior for an auto-match.
         */
        $this->syncHouseholdMemberSnapshot();

        /*
         * A contacted Person cannot simultaneously remain
         * a Serving Saint.
         */
        $contactedPersonIds =
            collect(
                $this->contactedPersonIds
            );

        $this->participantIds =
            collect(
                $this->participantIds
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->reject(
                    fn ($id): bool =>
                        $contactedPersonIds
                            ->contains($id)
                )
                ->unique()
                ->values()
                ->all();

        /*
         * Preserve a Locality explicitly submitted through
         * the Public Dashboard. If Public did not provide
         * one, derive it using the normal Shepherding rules.
         */
        if (! $this->localityId) {
            $this->refreshLocalityFromTargets();
        }

        $this->publicReviewTargetMatches =
            $matches;

        $this->publicReviewTargetNeedsReview =
            $needsReview;

        if ($notify) {
            Notification::make()
                ->title(
                    'Public Contact Targets parsed'
                )
                ->body(
                    count($matches)
                    . ' exact target(s) checked. '
                    . count($needsReview)
                    . ' target(s) need review.'
                )
                ->success()
                ->send();
        }
    }

    public function clearPublicTargetParsedChecks(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        /*
         * Remove only IDs added by the most recent parser.
         * Admin selections that were made separately remain.
         */
        $matchedByType =
            collect(
                $this->publicReviewTargetMatches
            )
                ->groupBy('type');

        $personIds =
            $matchedByType
                ->get(
                    'person',
                    collect()
                )
                ->pluck('id')
                ->map(
                    fn ($id): int =>
                        (int) $id
                );

        $householdIds =
            $matchedByType
                ->get(
                    'household',
                    collect()
                )
                ->pluck('id')
                ->map(
                    fn ($id): int =>
                        (int) $id
                );

        $campusIds =
            $matchedByType
                ->get(
                    'campus',
                    collect()
                )
                ->pluck('id')
                ->map(
                    fn ($id): int =>
                        (int) $id
                );

        $gospelIds =
            $matchedByType
                ->get(
                    'gospel',
                    collect()
                )
                ->pluck('id')
                ->map(
                    fn ($id): int =>
                        (int) $id
                );

        $this->contactedPersonIds =
            collect(
                $this->contactedPersonIds
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->reject(
                    fn ($id): bool =>
                        $personIds
                            ->contains($id)
                )
                ->values()
                ->all();

        $this->contactedHouseholdIds =
            collect(
                $this->contactedHouseholdIds
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->reject(
                    fn ($id): bool =>
                        $householdIds
                            ->contains($id)
                )
                ->values()
                ->all();

        $this->contactedCampusContactIds =
            collect(
                $this
                    ->contactedCampusContactIds
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->reject(
                    fn ($id): bool =>
                        $campusIds
                            ->contains($id)
                )
                ->values()
                ->all();

        $this->contactedGospelContactIds =
            collect(
                $this
                    ->contactedGospelContactIds
            )
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->reject(
                    fn ($id): bool =>
                        $gospelIds
                            ->contains($id)
                )
                ->values()
                ->all();

        $this->syncHouseholdMemberSnapshot();

        $this->publicReviewTargetMatches = [];
        $this->publicReviewTargetNeedsReview = [];

        Notification::make()
            ->title(
                'Parsed Contact Target checks cleared'
            )
            ->success()
            ->send();
    }

    private function publicContactTargetLines(
        string $text
    ): array {
        return collect(
            preg_split(
                '/\R+/u',
                $text
            )
            ?: []
        )
            ->map(
                function ($line): string {
                    $line =
                        trim(
                            (string) $line
                        );

                    /*
                     * Accept numbered and bulleted lists:
                     *
                     * 1. Juan Dela Cruz
                     * 2) Maria Santos
                     * - Pedro Reyes
                     * • Ana Cruz
                     */
                    $line =
                        preg_replace(
                            '/^\s*(?:\d+\s*[\.\)\-:]|[-*•]+)\s*/u',
                            '',
                            $line
                        );

                    return trim(
                        (string) $line
                    );
                }
            )
            ->filter(
                fn (string $line): bool =>
                    $line !== ''
            )
            ->unique(
                fn (string $line): string =>
                    $this
                        ->normalizePublicTargetName(
                            $line
                        )
            )
            ->values()
            ->all();
    }

    private function publicPersonNameKeys(
        ?string $firstname,
        ?string $middlename,
        ?string $lastname,
        ?string $nickname
    ): array {
        $firstname =
            trim(
                (string) $firstname
            );

        $middlename =
            trim(
                (string) $middlename
            );

        $lastname =
            trim(
                (string) $lastname
            );

        $nickname =
            trim(
                (string) $nickname
            );

        if (
            $firstname === ''
            || $lastname === ''
        ) {
            return [];
        }

        $rawKeys = [
            $firstname
                . ' '
                . $lastname,

            $lastname
                . ' '
                . $firstname,

            $lastname
                . ', '
                . $firstname,
        ];

        if ($middlename !== '') {
            $rawKeys[] =
                $firstname
                . ' '
                . $middlename
                . ' '
                . $lastname;

            $rawKeys[] =
                $lastname
                . ' '
                . $firstname
                . ' '
                . $middlename;

            $rawKeys[] =
                $lastname
                . ', '
                . $firstname
                . ' '
                . $middlename;
        }

        if ($nickname !== '') {
            $rawKeys[] =
                $nickname
                . ' '
                . $lastname;

            $rawKeys[] =
                $lastname
                . ' '
                . $nickname;

            $rawKeys[] =
                $lastname
                . ', '
                . $nickname;
        }

        return collect($rawKeys)
            ->map(
                fn (string $key): string =>
                    $this
                        ->normalizePublicTargetName(
                            $key
                        )
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizePublicTargetName(
        string $value
    ): string {
        $value =
            preg_replace(
                '/^\s*(?:\d+\s*[\.\)\-:]|[-*•]+)\s*/u',
                '',
                trim($value)
            );

        $value =
            mb_strtolower(
                (string) $value
            );

        $value =
            Str::ascii($value);

        /*
         * Ignore common informal titles if Public users
         * include them.
         */
        $value =
            preg_replace(
                '/\b(?:brother|bro|sister|sis)\.?\b/i',
                ' ',
                $value
            );

        $value =
            preg_replace(
                '/[^a-z0-9]+/',
                ' ',
                (string) $value
            );

        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                (string) $value
            )
        );
    }

    public function cancelPublicSubmissionReview(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $this->resetContactForm();

        Notification::make()
            ->title(
                'Public submission review cancelled'
            )
            ->success()
            ->send();
    }

    public function rejectPublicSubmission(
        int $submissionId
    ): void {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $updated =
            PublicShepherdingSubmission::query()
                ->whereKey(
                    $submissionId
                )
                ->where(
                    'status',
                    PublicShepherdingSubmission::STATUS_PENDING
                )
                ->update([
                    'status' =>
                        PublicShepherdingSubmission::STATUS_REJECTED,

                    'reviewed_by_id' =>
                        auth()->id(),

                    'reviewed_at' =>
                        now(),

                    'review_note' =>
                        'Rejected from Shepherding Records.',
                ]);

        if ($updated !== 1) {
            Notification::make()
                ->title(
                    'Submission was not rejected'
                )
                ->body(
                    'It may already have been reviewed.'
                )
                ->warning()
                ->send();

            return;
        }

        if (
            $this->reviewingPublicSubmissionId
            === $submissionId
        ) {
            $this->resetContactForm();
        }

        Notification::make()
            ->title(
                'Public submission rejected'
            )
            ->success()
            ->send();
    }

    public function recentContacts(): Collection
    {
        return ShepherdingContact::query()
            ->with([
                'contactedPeople',
                'contactedHouseholds.head',
                'contactedCampusContacts.school',
                'contactedCampusContacts.localityRecord',
                'contactedGospelContacts.localityRecord',
                'householdMembers',
                'householdGospelMembers',
                'locality',
                'activityTypes',
                'ministryLessons.book',
                'morningRevivalWeek.publication',
                'hymns.bookEntries.hymnBook',
                'hymns.sources',
                'hymnAdditionRequests',
                'participants',
            ])
            ->orderByDesc(
                'contact_date'
            )
            ->orderByDesc(
                'contact_time'
            )
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    public function saveContact(): void
    {
        $data = $this->validate([
            'contactedPersonIds' => [
                'array',
            ],
            'contactedPersonIds.*' => [
                'integer',
                'exists:persons,id',
            ],
            'contactedHouseholdIds' => [
                'array',
            ],
            'contactedHouseholdIds.*' => [
                'integer',
                'exists:households,id',
            ],
            'contactedCampusContactIds' => [
                'array',
            ],
            'contactedCampusContactIds.*' => [
                'integer',
                'exists:campus_contacts,id',
            ],
            'contactedGospelContactIds' => [
                'array',
            ],
            'contactedGospelContactIds.*' => [
                'integer',
                'exists:gospel_contacts,id',
            ],
            'newGospelContactNames' => [
                'array',
            ],
            'newGospelContactNames.*' => [
                'string',
                'max:255',
            ],
            'householdMemberPresence' => [
                'array',
            ],
            'householdGospelMemberPresence' => [
                'array',
            ],
            'localityId' => [
                'nullable',
                'integer',
                'exists:localities,id',
            ],
            'contactDate' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'contactTime' => [
                'nullable',
                'date_format:H:i',
            ],
            'outcome' => [
                'required',
                Rule::in(
                    array_keys(
                        ShepherdingContact::outcomeOptions()
                    )
                ),
            ],
            'activityTypeIds' => [
                'array',
            ],
            'activityTypeIds.*' => [
                'integer',
                'exists:shepherding_activity_types,id',
            ],
            'ministryLessonIds' => [
                'array',
            ],
            'ministryLessonIds.*' => [
                'integer',
                'exists:ministry_lessons,id',
            ],
            'morningRevivalWeekId' => [
                'nullable',
                'integer',
                'exists:morning_revival_weeks,id',
            ],
            'morningRevivalDay' => [
                'nullable',
                'integer',
                'between:1,6',
            ],
            'bibleReadingRows' => [
                'array',
            ],
            'bibleReadingRows.*.reference' => [
                'nullable',
                'string',
                'max:150',
            ],
            'hymnRows' => [
                'array',
            ],
            'hymnRows.*.hymn_id' => [
                'nullable',
                'integer',
                'exists:hymns,id',
            ],
            'hymnRows.*.search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'hymnRows.*.request_id' => [
                'nullable',
                'integer',
                'exists:hymn_addition_requests,id',
            ],
            'hymnRows.*.request_title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'participantIds' => [
                'array',
            ],
            'participantIds.*' => [
                'integer',
                'exists:persons,id',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        $contactedHouseholdIds = collect(
            $data['contactedHouseholdIds'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $contactedCampusContactIds = collect(
            $data['contactedCampusContactIds'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $contactedGospelContactIds = collect(
            $data['contactedGospelContactIds'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $newGospelContacts = [];

        foreach (
            $data['newGospelContactNames'] ?? []
            as $name
        ) {
            $parsed =
                $this->parseGospelContactName(
                    (string) $name
                );

            if (! $parsed) {
                $this->addError(
                    'newGospelContactNames',
                    'New Gospel Contacts must use Last Name, First Name.'
                );

                Notification::make()
                    ->title(
                        'Invalid Gospel Contact name'
                    )
                    ->body(
                        'Use the format Last Name, First Name.'
                    )
                    ->warning()
                    ->send();

                return;
            }

            $key =
                mb_strtolower(
                    $parsed['lastname']
                    . '|'
                    . $parsed['firstname']
                );

            $newGospelContacts[
                $key
            ] =
                $parsed;
        }

        $newGospelContacts =
            array_values(
                $newGospelContacts
            );

        /*
         * A newly-created Gospel Contact must inherit
         * the Contact Locality selected on this form.
         */
        if (
            $newGospelContacts !== []
            && blank(
                $data['localityId']
                    ?? null
            )
        ) {
            $this->addError(
                'localityId',
                'Select the Contact Locality before creating a Gospel Contact.'
            );

            Notification::make()
                ->title(
                    'Contact Locality required'
                )
                ->body(
                    'The selected Locality will also be saved to the new Gospel Contact.'
                )
                ->warning()
                ->send();

            return;
        }

        $householdMemberSync = [];

        foreach (
            $this->householdMemberHouseholdIds
            as $personId => $householdId
        ) {
            $personId = (int) $personId;
            $householdId = (int) $householdId;

            if (
                ! in_array(
                    $householdId,
                    $contactedHouseholdIds,
                    true
                )
            ) {
                continue;
            }

            $householdMemberSync[
                $personId
            ] = [
                'household_id' =>
                    $householdId,
                'was_present' =>
                    (bool) (
                        $this
                            ->householdMemberPresence[
                                $personId
                            ]
                        ?? false
                    ),
            ];
        }

        $householdGospelMemberSync = [];

        foreach (
            $this->householdGospelMemberHouseholdIds
            as $gospelContactId => $householdId
        ) {
            $gospelContactId =
                (int) $gospelContactId;

            $householdId =
                (int) $householdId;

            if (
                ! in_array(
                    $householdId,
                    $contactedHouseholdIds,
                    true
                )
            ) {
                continue;
            }

            $householdGospelMemberSync[
                $gospelContactId
            ] = [
                'household_id' =>
                    $householdId,

                'was_present' =>
                    (bool) (
                        $this
                            ->householdGospelMemberPresence[
                                $gospelContactId
                            ]
                        ?? false
                    ),
            ];
        }

        $householdGospelMemberIds = array_map(
            'intval',
            array_keys(
                $householdGospelMemberSync
            )
        );

        /*
         * Match People Contacted behavior.
         *
         * A Gospel Contact represented by a selected
         * Household belongs to the Household attendance
         * snapshot, not the standalone Gospel target list.
         *
         * A Gospel Contact manually selected before the
         * Household remains in component state so it can
         * reappear if the Household is later removed, but
         * it is not duplicated when this record is saved.
         */
        $contactedGospelContactIds =
            collect(
                $contactedGospelContactIds
            )
                ->map(fn ($id): int => (int) $id)
                ->reject(
                    fn ($id): bool =>
                        in_array(
                            $id,
                            $householdGospelMemberIds,
                            true
                        )
                )
                ->unique()
                ->values()
                ->all();


        $householdMemberIds = array_map(
            'intval',
            array_keys(
                $householdMemberSync
            )
        );

        $contactedPersonIds = collect(
            $data['contactedPersonIds'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->reject(
                fn ($id): bool =>
                    in_array(
                        $id,
                        $householdMemberIds,
                        true
                    )
            )
            ->unique()
            ->values()
            ->all();

        if (
            $contactedPersonIds === []
            && $contactedHouseholdIds === []
            && $contactedCampusContactIds === []
            && $contactedGospelContactIds === []
            && $newGospelContacts === []
        ) {
            $this->addError(
                'contactedPersonIds',
                'Select at least one Person, Household, Campus Contact, or Gospel Contact.'
            );

            Notification::make()
                ->title('Contact target required')
                ->body(
                    'Select at least one Person, Household, Campus Contact, or Gospel Contact.'
                )
                ->warning()
                ->send();

            return;
        }

        $activityIds = collect(
            $data['activityTypeIds'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $morningRevivalWeekIdForSave =
            filled(
                $data[
                    'morningRevivalWeekId'
                ]
                ?? null
            )
                ? (int) $data[
                    'morningRevivalWeekId'
                ]
                : null;

        $morningRevivalDayForSave =
            filled(
                $data[
                    'morningRevivalDay'
                ]
                ?? null
            )
                ? (int) $data[
                    'morningRevivalDay'
                ]
                : null;

        $morningRevivalActivityId =
            $this->morningRevivalActivityId();

        $morningRevivalSelected =
            $morningRevivalActivityId
            && in_array(
                $morningRevivalActivityId,
                $activityIds,
                true
            );

        if (! $morningRevivalSelected) {
            $morningRevivalWeekIdForSave =
                null;

            $morningRevivalDayForSave =
                null;
        } elseif (
            $data['outcome']
            !== ShepherdingContact::OUTCOME_UNAVAILABLE
            && (
                ! $morningRevivalWeekIdForSave
                || ! $morningRevivalDayForSave
            )
        ) {
            $this->addError(
                'morningRevivalWeekId',
                'Choose the Morning Revival week and day.'
            );

            Notification::make()
                ->title(
                    'Morning Revival reading required'
                )
                ->body(
                    'Choose a Week and Day 1–6 '
                    . 'for the Morning Revival activity.'
                )
                ->warning()
                ->send();

            return;
        }

        $bibleReadingRowsForSave =
            array_values(
                $data['bibleReadingRows']
                    ?? []
            );

        $bibleReadingActivityId =
            $this->bibleReadingActivityId();

        $bibleReadingSelected =
            $bibleReadingActivityId
            && in_array(
                $bibleReadingActivityId,
                $activityIds,
                true
            );

        $bibleReadingsForSave = [];

        if (! $bibleReadingSelected) {
            $bibleReadingRowsForSave = [];
        } elseif (
            $data['outcome']
            !== ShepherdingContact::OUTCOME_UNAVAILABLE
        ) {
            foreach (
                $bibleReadingRowsForSave
                as $index => $row
            ) {
                $reference =
                    trim(
                        (string) (
                            $row['reference']
                            ?? ''
                        )
                    );

                if ($reference === '') {
                    continue;
                }

                try {
                    $parsed =
                        RecoveryVersionBible::parse(
                            $reference
                        );
                } catch (
                    \InvalidArgumentException $exception
                ) {
                    $this->addError(
                        "bibleReadingRows.{$index}.reference",
                        $exception->getMessage()
                    );

                    Notification::make()
                        ->title(
                            'Invalid Bible reference'
                        )
                        ->body(
                            $exception->getMessage()
                        )
                        ->warning()
                        ->send();

                    return;
                }

                $parsed['sort_order'] =
                    count(
                        $bibleReadingsForSave
                    ) + 1;

                $bibleReadingsForSave[] =
                    $parsed;
            }

            if ($bibleReadingsForSave === []) {
                $this->addError(
                    'bibleReadingRows',
                    'Enter at least one Bible reference.'
                );

                Notification::make()
                    ->title(
                        'Bible Reading required'
                    )
                    ->body(
                        'Enter at least one Bible '
                        . 'portion for the Bible '
                        . 'Reading activity.'
                    )
                    ->warning()
                    ->send();

                return;
            }
        }

        $ministryIds = collect(
            $data['ministryLessonIds'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $hymnRowsForSave =
            array_values(
                $data['hymnRows']
                    ?? []
            );

        $participantIds = collect(
            $data['participantIds'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $overlap = array_values(
            array_intersect(
                $contactedPersonIds,
                $participantIds
            )
        );

        if ($overlap !== []) {
            Notification::make()
                ->title('Invalid Serving Saint')
                ->body(
                    'A standalone Person Contacted cannot also be selected as a Serving Saint in the same record.'
                )
                ->warning()
                ->send();

            return;
        }

        $hymnSingingActivityId =
            $this->hymnSingingActivityId();

        if (
            ! $hymnSingingActivityId
            || ! in_array(
                $hymnSingingActivityId,
                $activityIds,
                true
            )
        ) {
            $hymnRowsForSave = [];
        }

        if (
            $data['outcome']
            === ShepherdingContact::OUTCOME_UNAVAILABLE
        ) {
            $activityIds = [];
            $ministryIds = [];
            $hymnRowsForSave = [];
            $bibleReadingsForSave = [];

            $morningRevivalWeekIdForSave =
                null;

            $morningRevivalDayForSave =
                null;
        }

        [
            $hymnSync,
            $hymnRequestSync,
        ] =
            $this->buildHymnSyncs(
                $hymnRowsForSave
            );

        $publicSubmissionId =
            $this->reviewingPublicSubmissionId;

        if ($publicSubmissionId !== null) {
            abort_unless(
                auth()->user()?->isAdmin(),
                403
            );

            if ($this->editingContactId !== null) {
                throw \Illuminate\Validation\ValidationException
                    ::withMessages([
                        'public_submission' =>
                            'A Public Shepherding Submission '
                            . 'cannot be approved while editing '
                            . 'an existing Shepherding Record.',
                    ]);
            }

            PublicShepherdingSubmission::query()
                ->whereKey(
                    $publicSubmissionId
                )
                ->where(
                    'status',
                    PublicShepherdingSubmission::STATUS_PENDING
                )
                ->firstOrFail();
        }

        $isEditing = filled(
            $this->editingContactId
        );

        $contact = $isEditing
            ? ShepherdingContact::query()
                ->with([
                    'contactedPeople',
                    'contactedHouseholds',
                    'contactedCampusContacts',
                    'contactedGospelContacts',
                    'householdMembers',
                    'householdGospelMembers',
                    'locality',
                    'activityTypes',
                    'ministryLessons',
                    'hymns',
                    'participants',
                ])
                ->findOrFail(
                    (int) $this->editingContactId
                )
            : new ShepherdingContact();

        DB::transaction(
            function () use (
                $contact,
                $isEditing,
                $data,
                $contactedPersonIds,
                $contactedHouseholdIds,
                $contactedCampusContactIds,
                $contactedGospelContactIds,
                $newGospelContacts,
                $householdMemberSync,
                $householdGospelMemberSync,
                $activityIds,
                $ministryIds,
                $morningRevivalWeekIdForSave,
                $morningRevivalDayForSave,
                $bibleReadingsForSave,
                $hymnSync,
                $hymnRequestSync,
                $participantIds,
                $publicSubmissionId
            ): void {
                $oldValues = $contact->exists
                    ? [
                        'people_contacted' =>
                            $contact
                                ->contactedPeople
                                ->pluck('display_name')
                                ->all(),

                        'households_contacted' =>
                            $contact
                                ->contactedHouseholds
                                ->pluck('display_name')
                                ->all(),

                        'campus_contacts_contacted' =>
                            $contact
                                ->contactedCampusContacts
                                ->pluck('display_name')
                                ->all(),

                        'gospel_contacts_contacted' =>
                            $contact
                                ->contactedGospelContacts
                                ->pluck('display_name')
                                ->all(),

                        'household_members_present' =>
                            $contact
                                ->householdMembers
                                ->filter(
                                    fn ($person): bool =>
                                        (bool) $person
                                            ->pivot
                                            ->was_present
                                )
                                ->pluck('display_name')
                                ->all(),

                        'household_members_not_present' =>
                            $contact
                                ->householdMembers
                                ->reject(
                                    fn ($person): bool =>
                                        (bool) $person
                                            ->pivot
                                            ->was_present
                                )
                                ->pluck('display_name')
                                ->all(),

                        'locality' =>
                            $contact->locality?->name,

                        'contact_date' =>
                            $contact
                                ->contact_date
                                ?->format('Y-m-d'),

                        'contact_time' =>
                            $contact->contact_time,

                        'outcome' =>
                            $contact->outcome,

                        'activities' =>
                            $contact
                                ->activityTypes
                                ->pluck('code')
                                ->all(),

                        'ministry_lessons' =>
                            $contact
                                ->ministryLessons
                                ->pluck('code')
                                ->all(),

                        'hymns' =>
                            $contact
                                ->hymns
                                ->pluck('title')
                                ->all(),

                        'participants' =>
                            $contact
                                ->participants
                                ->pluck('display_name')
                                ->all(),

                        'notes' =>
                            $contact->notes,
                    ]
                    : [];

                /*
                 * Create selected Search Contact Target
                 * candidates only when the entire Shepherding
                 * Record is being saved.
                 *
                 * If an unlinked Gospel Contact with the
                 * exact same name + Locality already exists,
                 * reuse it instead of creating a duplicate.
                 */
                $resolvedGospelContactIds =
                    $contactedGospelContactIds;

                if ($newGospelContacts !== []) {
                    $newGospelLocalityId =
                        (int) $data[
                            'localityId'
                        ];

                    $newGospelLocality =
                        Locality::query()
                            ->findOrFail(
                                $newGospelLocalityId
                            );

                    foreach (
                        $newGospelContacts
                        as $candidate
                    ) {
                        $gospelContact =
                            GospelContact::query()
                                ->whereNull(
                                    'person_id'
                                )
                                ->where(
                                    'locality_id',
                                    $newGospelLocalityId
                                )
                                ->when(
                                    filled(
                                        $candidate[
                                            'lastname'
                                        ]
                                    ),
                                    fn ($query) =>
                                        $query->whereRaw(
                                            'LOWER(lastname) = ?',
                                            [
                                                mb_strtolower(
                                                    $candidate[
                                                        'lastname'
                                                    ]
                                                ),
                                            ]
                                        ),
                                    fn ($query) =>
                                        $query->whereNull(
                                            'lastname'
                                        )
                                )
                                ->whereRaw(
                                    'LOWER(firstname) = ?',
                                    [
                                        mb_strtolower(
                                            $candidate[
                                                'firstname'
                                            ]
                                        ),
                                    ]
                                )
                                ->first();

                        if (! $gospelContact) {
                            $gospelContact =
                                GospelContact::query()
                                    ->create([
                                        'firstname' =>
                                            $candidate[
                                                'firstname'
                                            ],

                                        'lastname' =>
                                            $candidate[
                                                'lastname'
                                            ],

                                        'locality_id' =>
                                            $newGospelLocalityId,
                                    ]);

                            ActivityLogger::log(
                                action:
                                    'gospel_contact.created',

                                subject:
                                    $gospelContact,

                                description:
                                    'Created a Gospel Contact from Shepherding Records.',

                                newValues: [
                                    'firstname' =>
                                        $gospelContact
                                            ->firstname,

                                    'lastname' =>
                                        $gospelContact
                                            ->lastname,

                                    'locality' =>
                                        $newGospelLocality
                                            ->name,
                                ],
                            );
                        }

                        $resolvedGospelContactIds[] =
                            (int)
                            $gospelContact->id;
                    }

                    $resolvedGospelContactIds =
                        collect(
                            $resolvedGospelContactIds
                        )
                            ->map(
                                fn ($id): int =>
                                    (int) $id
                            )
                            ->unique()
                            ->values()
                            ->all();
                }

                $contact->fill([
                    'locality_id' =>
                        filled(
                            $data['localityId']
                                ?? null
                        )
                            ? (int) $data[
                                'localityId'
                            ]
                            : null,

                    'contact_date' =>
                        $data['contactDate'],

                    'contact_time' =>
                        filled(
                            $data['contactTime']
                                ?? null
                        )
                            ? $data['contactTime']
                            : null,

                    'outcome' =>
                        $data['outcome'],

                    'morning_revival_week_id' =>
                        $morningRevivalWeekIdForSave,

                    'morning_revival_day' =>
                        $morningRevivalDayForSave,

                    'notes' =>
                        filled(
                            $data['notes']
                                ?? null
                        )
                            ? trim($data['notes'])
                            : null,
                ]);

                $contact->save();

                $contact
                    ->contactedPeople()
                    ->sync(
                        $contactedPersonIds
                    );

                $contact
                    ->contactedHouseholds()
                    ->sync(
                        $contactedHouseholdIds
                    );

                $contact
                    ->contactedCampusContacts()
                    ->sync(
                        $contactedCampusContactIds
                    );

                $contact
                    ->contactedGospelContacts()
                    ->sync(
                        $resolvedGospelContactIds
                    );

                $contact
                    ->householdGospelMembers()
                    ->sync(
                        $householdGospelMemberSync
                    );

                $contact
                    ->householdMembers()
                    ->sync(
                        $householdMemberSync
                    );

                $contact
                    ->activityTypes()
                    ->sync($activityIds);

                $contact
                    ->ministryLessons()
                    ->sync($ministryIds);

                $contact
                    ->bibleReadings()
                    ->delete();

                if ($bibleReadingsForSave !== []) {
                    $contact
                        ->bibleReadings()
                        ->createMany(
                            $bibleReadingsForSave
                        );
                }

                $contact
                    ->hymns()
                    ->sync($hymnSync);

                $contact
                    ->hymnAdditionRequests()
                    ->sync(
                        $hymnRequestSync
                    );

                $contact
                    ->participants()
                    ->sync($participantIds);

                $contact->load([
                    'contactedPeople',
                    'contactedHouseholds',
                    'contactedCampusContacts',
                    'contactedGospelContacts',
                    'householdMembers',
                    'householdGospelMembers',
                    'locality',
                    'activityTypes',
                    'ministryLessons.book',
                    'bibleReadings',
                    'hymns.bookEntries.hymnBook',
                    'participants',
                ]);

                $newValues = [
                    'people_contacted' =>
                        $contact
                            ->contactedPeople
                            ->pluck('display_name')
                            ->all(),

                    'households_contacted' =>
                        $contact
                            ->contactedHouseholds
                            ->pluck('display_name')
                            ->all(),

                    'campus_contacts_contacted' =>
                        $contact
                            ->contactedCampusContacts
                            ->pluck('display_name')
                            ->all(),

                    'gospel_contacts_contacted' =>
                        $contact
                            ->contactedGospelContacts
                            ->pluck('display_name')
                            ->all(),

                    'household_members_present' =>
                        $contact
                            ->householdMembers
                            ->filter(
                                fn ($person): bool =>
                                    (bool) $person
                                        ->pivot
                                        ->was_present
                            )
                            ->pluck('display_name')
                            ->all(),

                    'household_members_not_present' =>
                        $contact
                            ->householdMembers
                            ->reject(
                                fn ($person): bool =>
                                    (bool) $person
                                        ->pivot
                                        ->was_present
                            )
                            ->pluck('display_name')
                            ->all(),

                    'locality' =>
                        $contact->locality?->name,

                    'contact_date' =>
                        $contact
                            ->contact_date
                            ?->format('Y-m-d'),

                    'contact_time' =>
                        $contact->contact_time,

                    'outcome' =>
                        $contact->outcome,

                    'activities' =>
                        $contact
                            ->activityTypes
                            ->pluck('code')
                            ->all(),

                    'ministry_lessons' =>
                        $contact
                            ->ministryLessons
                            ->pluck('code')
                            ->all(),

                    'hymns' =>
                        $contact
                            ->hymns
                            ->pluck('title')
                            ->all(),

                    'participants' =>
                        $contact
                            ->participants
                            ->pluck('display_name')
                            ->all(),

                    'notes' =>
                        $contact->notes,
                ];

                ActivityLogger::log(
                    action: $isEditing
                        ? 'shepherding_contact.updated'
                        : 'shepherding_contact.created',
                    subject: $contact,
                    description: $isEditing
                        ? 'Updated a Shepherding Record.'
                        : 'Recorded a Shepherding Record.',
                    oldValues: $oldValues,
                    newValues: $newValues,
                );


                if ($publicSubmissionId !== null) {
                    $approved =
                        PublicShepherdingSubmission::query()
                            ->whereKey(
                                $publicSubmissionId
                            )
                            ->where(
                                'status',
                                PublicShepherdingSubmission::STATUS_PENDING
                            )
                            ->update([
                                'status' =>
                                    PublicShepherdingSubmission::STATUS_APPROVED,

                                'reviewed_by_id' =>
                                    auth()->id(),

                                'reviewed_at' =>
                                    now(),

                                'review_note' =>
                                    'Approved and recorded '
                                    . 'from Shepherding Records.',

                                'shepherding_contact_id' =>
                                    $contact->id,
                            ]);

                    if ($approved !== 1) {
                        throw \Illuminate\Validation\ValidationException
                            ::withMessages([
                                'public_submission' =>
                                    'This Public Shepherding '
                                    . 'Submission was already '
                                    . 'reviewed by another request.',
                            ]);
                    }
                }
            }
        );

        $this->resetContactForm();

        Notification::make()
            ->title(
                $isEditing
                    ? 'Shepherding Record updated'
                    : 'Shepherding Record recorded'
            )
            ->success()
            ->send();
    }

    public function editContact(
        int $contactId
    ): void {
        $contact = ShepherdingContact::query()
            ->with([
                'contactedPeople',
                'contactedHouseholds',
                'contactedCampusContacts',
                'contactedGospelContacts',
                'householdMembers',
                'householdGospelMembers',
                'activityTypes',
                'ministryLessons',
                'bibleReadings',
                'hymns.bookEntries.hymnBook',
                'hymnAdditionRequests',
                'participants',
            ])
            ->findOrFail($contactId);

        $this->editingContactId =
            $contact->id;

        $this->contactedPersonIds =
            $contact
                ->contactedPeople
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $this->contactedHouseholdIds =
            $contact
                ->contactedHouseholds
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $this->contactedCampusContactIds =
            $contact
                ->contactedCampusContacts
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $this->contactedGospelContactIds =
            $contact
                ->contactedGospelContacts
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $this->householdCampusContactHouseholdIds = [];

        $this->householdGospelMemberPresence = [];
        $this->householdGospelMemberHouseholdIds = [];

        $this->newGospelContactNames = [];

        $this->householdMemberPresence = [];
        $this->householdMemberHouseholdIds = [];

        foreach (
            $contact->householdMembers
            as $person
        ) {
            $personId = (int) $person->id;

            $this->householdMemberPresence[
                $personId
            ] =
                (bool) $person
                    ->pivot
                    ->was_present;

            $this->householdMemberHouseholdIds[
                $personId
            ] =
                (int) $person
                    ->pivot
                    ->household_id;
        }

        foreach (
            $contact->householdGospelMembers
            as $gospelContact
        ) {
            $gospelContactId =
                (int) $gospelContact->id;

            $this
                ->householdGospelMemberHouseholdIds[
                    $gospelContactId
                ] =
                    (int) $gospelContact
                        ->pivot
                        ->household_id;

            $this
                ->householdGospelMemberPresence[
                    $gospelContactId
                ] =
                    (bool) $gospelContact
                        ->pivot
                        ->was_present;
        }

        $this->initializedHouseholdIds =
            $this->contactedHouseholdIds;

        $this->localityId =
            filled($contact->locality_id)
                ? (int) $contact->locality_id
                : null;

        $this->localitySource =
            filled($contact->locality_id)
                ? 'Saved historical Locality.'
                : 'No historical Locality recorded.';

        $this->contactDate =
            $contact
                ->contact_date
                ->format('Y-m-d');

        $this->contactTime =
            filled($contact->contact_time)
                ? substr(
                    (string) $contact
                        ->contact_time,
                    0,
                    5
                )
                : '';

        $this->outcome =
            $contact->outcome;

        $this->activityTypeIds =
            $contact
                ->activityTypes
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $this->morningRevivalWeekId =
            filled(
                $contact
                    ->morning_revival_week_id
            )
                ? (int)
                    $contact
                        ->morning_revival_week_id
                : null;

        $this->morningRevivalDay =
            filled(
                $contact
                    ->morning_revival_day
            )
                ? (int)
                    $contact
                        ->morning_revival_day
                : null;

        $this->showMorningRevivalArchive =
            false;

        $this->morningRevivalArchiveSearch =
            '';

        $this->bibleReadingRows =
            $contact
                ->bibleReadings
                ->map(
                    fn ($reading): array => [
                        'reference' =>
                            $reading
                                ->referenceLabel(),
                    ]
                )
                ->values()
                ->all();

        if (
            $this->isBibleReadingSelected()
            && $this->bibleReadingRows === []
        ) {
            $this->addBibleReadingRow();
        }

        $this->ministryLessonIds =
            $contact
                ->ministryLessons
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $canonicalHymnRows =
            $contact
                ->hymns
                ->map(
                    fn ($hymn): array => [
                        'sort_order' =>
                            (int)
                            $hymn
                                ->pivot
                                ->sort_order,

                        'hymn_id' =>
                            (int) $hymn->id,

                        'request_id' =>
                            null,

                        'request_title' =>
                            null,

                        'search' =>
                            '',
                    ]
                );

        $pendingHymnRows =
            $contact
                ->hymnAdditionRequests
                ->filter(
                    fn ($request): bool =>
                        $request->status
                            === HymnAdditionRequest::STATUS_PENDING
                )
                ->map(
                    fn ($request): array => [
                        'sort_order' =>
                            (int)
                            $request
                                ->pivot
                                ->sort_order,

                        'hymn_id' =>
                            null,

                        'request_id' =>
                            (int)
                            $request->id,

                        'request_title' =>
                            (string)
                            $request->title,

                        'search' =>
                            '',
                    ]
                );

        $this->hymnRows =
            $canonicalHymnRows
                ->concat(
                    $pendingHymnRows
                )
                ->sortBy(
                    'sort_order'
                )
                ->map(
                    fn (array $row): array => [
                        'hymn_id' =>
                            $row['hymn_id'],

                        'request_id' =>
                            $row['request_id'],

                        'request_title' =>
                            $row['request_title'],

                        'search' =>
                            $row['search'],
                    ]
                )
                ->values()
                ->all();

        if (
            $this->isHymnSingingSelected()
            && $this->hymnRows === []
        ) {
            $this->addHymnRow();
        }

        $this->participantIds =
            $contact
                ->participants
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $this->notes =
            (string) ($contact->notes ?? '');

        $this->targetSearch = '';
        $this->householdMemberSearch = '';
        $this->participantSearch = '';
    }

    public function cancelEditing(): void
    {
        $this->resetContactForm();
    }

    public function deleteContact(
        int $contactId
    ): void {
        $contact = ShepherdingContact::query()
            ->with([
                'contactedPeople',
                'contactedHouseholds',
                'locality',
            ])
            ->findOrFail($contactId);

        DB::transaction(
            function () use ($contact): void {
                ActivityLogger::log(
                    action:
                        'shepherding_contact.deleted',
                    subject: $contact,
                    description:
                        'Deleted a Shepherding Contact.',
                    oldValues: [
                        'people_contacted' =>
                            $contact
                                ->contactedPeople
                                ->pluck(
                                    'display_name'
                                )
                                ->all(),
                        'households_contacted' =>
                            $contact
                                ->contactedHouseholds
                                ->pluck(
                                    'display_name'
                                )
                                ->all(),
                        'locality' =>
                            $contact
                                ->locality?->name,
                        'contact_date' =>
                            $contact
                                ->contact_date
                                ?->format(
                                    'Y-m-d'
                                ),
                        'outcome' =>
                            $contact->outcome,
                    ],
                    newValues: [],
                );

                $contact->delete();
            }
        );

        Notification::make()
            ->title(
                'Shepherding Contact deleted'
            )
            ->success()
            ->send();
    }

    private function syncHouseholdMemberSnapshot(): void
    {
        $selectedHouseholdIds = collect(
            $this->contactedHouseholdIds
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        /*
         * Remove People snapshot rows belonging to a
         * Household that has just been unchecked.
         */
        foreach (
            $this->householdMemberHouseholdIds
            as $personId => $householdId
        ) {
            if (
                ! $selectedHouseholdIds->contains(
                    (int) $householdId
                )
            ) {
                unset(
                    $this
                        ->householdMemberHouseholdIds[
                            $personId
                        ],
                    $this
                        ->householdMemberPresence[
                            $personId
                        ]
                );
            }
        }

        /*
         * Remove historical Gospel Household attendance
         * state belonging to a Household that has just
         * been unchecked.
         *
         * This is separate from target provenance:
         * a manually selected Gospel target may remain a
         * target even after its Household is removed.
         */
        foreach (
            $this->householdGospelMemberHouseholdIds
            as $gospelContactId => $householdId
        ) {
            if (
                ! $selectedHouseholdIds->contains(
                    (int) $householdId
                )
            ) {
                unset(
                    $this
                        ->householdGospelMemberHouseholdIds[
                            $gospelContactId
                        ],
                    $this
                        ->householdGospelMemberPresence[
                            $gospelContactId
                        ]
                );
            }
        }

        /*
         * Remove only Campus/Gospel targets that were
         * automatically introduced by a Household which
         * is no longer selected.
         *
         * Manually selected targets have no tracking-map
         * entry and therefore remain selected.
         */
        $campusIdsToRemove = collect(
            $this->householdCampusContactHouseholdIds
        )
            ->filter(
                fn ($householdId): bool =>
                    ! $selectedHouseholdIds->contains(
                        (int) $householdId
                    )
            )
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($campusIdsToRemove->isNotEmpty()) {
            $this->contactedCampusContactIds =
                collect(
                    $this->contactedCampusContactIds
                )
                    ->map(fn ($id) => (int) $id)
                    ->reject(
                        fn ($id): bool =>
                            $campusIdsToRemove
                                ->contains($id)
                    )
                    ->unique()
                    ->values()
                    ->all();
        }

        $this->householdCampusContactHouseholdIds =
            collect(
                $this
                    ->householdCampusContactHouseholdIds
            )
                ->filter(
                    fn ($householdId): bool =>
                        $selectedHouseholdIds
                            ->contains(
                                (int) $householdId
                            )
                )
                ->map(
                    fn ($householdId): int =>
                        (int) $householdId
                )
                ->all();

        $this->initializedHouseholdIds =
            collect(
                $this->initializedHouseholdIds
            )
                ->map(fn ($id) => (int) $id)
                ->filter(
                    fn ($id): bool =>
                        $selectedHouseholdIds
                            ->contains($id)
                )
                ->unique()
                ->values()
                ->all();

        $newHouseholdIds =
            $selectedHouseholdIds
                ->diff(
                    $this
                        ->initializedHouseholdIds
                )
                ->values();

        /*
         * For a newly-selected Household:
         *
         * - snapshot all CURRENT People members
         * - assume those People are Present
         * - add unlinked Campus Contacts as targets
         * - snapshot unlinked Gospel Contacts for attendance
         */
        if ($newHouseholdIds->isNotEmpty()) {
            $members = Person::query()
                ->whereIn(
                    'household_id',
                    $newHouseholdIds->all()
                )
                ->get([
                    'id',
                    'household_id',
                ]);

            foreach ($members as $member) {
                $personId =
                    (int) $member->id;

                $this
                    ->householdMemberHouseholdIds[
                        $personId
                    ] =
                        (int) $member
                            ->household_id;

                if (
                    ! array_key_exists(
                        $personId,
                        $this
                            ->householdMemberPresence
                    )
                ) {
                    $this
                        ->householdMemberPresence[
                            $personId
                        ] = true;
                }
            }

            /*
             * Existing manual targets must stay manual.
             * Only IDs newly introduced here are entered
             * into the Household tracking maps.
             */
            $selectedCampusIds = collect(
                $this->contactedCampusContactIds
            )
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $campusContacts =
                CampusContact::query()
                    ->whereNull('person_id')
                    ->whereIn(
                        'household_id',
                        $newHouseholdIds->all()
                    )
                    ->get([
                        'id',
                        'household_id',
                    ]);

            foreach (
                $campusContacts
                as $campusContact
            ) {
                $campusContactId =
                    (int) $campusContact->id;

                if (
                    $selectedCampusIds->contains(
                        $campusContactId
                    )
                ) {
                    continue;
                }

                $this
                    ->contactedCampusContactIds[] =
                        $campusContactId;

                $this
                    ->householdCampusContactHouseholdIds[
                        $campusContactId
                    ] =
                        (int) $campusContact
                            ->household_id;

                $selectedCampusIds->push(
                    $campusContactId
                );
            }

            /*
             * Gospel Contacts belonging to the Household are
             * represented only in Household Members Present.
             *
             * They are not added to the standalone Gospel
             * Contact target list.
             */
            $gospelContacts =
                GospelContact::query()
                    ->whereNull('person_id')
                    ->whereIn(
                        'household_id',
                        $newHouseholdIds->all()
                    )
                    ->get([
                        'id',
                        'household_id',
                    ]);

            foreach (
                $gospelContacts
                as $gospelContact
            ) {
                $gospelContactId =
                    (int) $gospelContact->id;

                $this
                    ->householdGospelMemberHouseholdIds[
                        $gospelContactId
                    ] =
                        (int) $gospelContact
                            ->household_id;

                if (
                    ! array_key_exists(
                        $gospelContactId,
                        $this
                            ->householdGospelMemberPresence
                    )
                ) {
                    $this
                        ->householdGospelMemberPresence[
                            $gospelContactId
                        ] = true;
                }
            }


            $this->contactedCampusContactIds =
                collect(
                    $this->contactedCampusContactIds
                )
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

            $this->contactedGospelContactIds =
                collect(
                    $this->contactedGospelContactIds
                )
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

            $this->initializedHouseholdIds =
                collect(
                    $this->initializedHouseholdIds
                )
                    ->concat(
                        $newHouseholdIds
                    )
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->unique()
                    ->values()
                    ->all();
        }

        /*
         * Any Person now represented by a selected
         * Household must disappear from standalone
         * People Contacted.
         */
        $householdMemberIds = collect(
            array_keys(
                $this->householdMemberHouseholdIds
            )
        )
            ->map(fn ($id) => (int) $id);

        $this->contactedPersonIds =
            collect(
                $this->contactedPersonIds
            )
                ->map(
                    fn ($id) => (int) $id
                )
                ->reject(
                    fn ($id): bool =>
                        $householdMemberIds
                            ->contains($id)
                )
                ->unique()
                ->values()
                ->all();
    }

    private function refreshLocalityFromTargets(): void
    {
        $personIds = collect(
            $this->contactedPersonIds
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $householdIds = collect(
            $this->contactedHouseholdIds
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $campusContactIds = collect(
            $this->contactedCampusContactIds
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $gospelContactIds = collect(
            $this->contactedGospelContactIds
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (
            $personIds->isEmpty()
            && $householdIds->isEmpty()
            && $campusContactIds->isEmpty()
            && $gospelContactIds->isEmpty()
        ) {
            $this->localityId = null;
            $this->localitySource = '';

            return;
        }

        $personResolutions =
            $personIds->map(
                fn (int $personId): array =>
                    $this->resolveDefaultLocality(
                        $personId
                    )
            );

        $campusResolutions =
            $campusContactIds->map(
                fn (int $campusContactId): array =>
                    $this->resolveCampusContactLocality(
                        $campusContactId
                    )
            );

        $ambiguous =
            $personResolutions
                ->concat($campusResolutions)
                ->contains(
                    fn (array $resolution): bool =>
                        str_starts_with(
                            $resolution['source'],
                            'Multiple Student Center'
                        )
                );

        if ($ambiguous) {
            $this->localityId = null;
            $this->localitySource =
                'Multiple Student Center Localities detected. Select the Contact Locality manually.';

            return;
        }

        $localityIds =
            $personResolutions
                ->pluck('id')
                ->concat(
                    $campusResolutions
                        ->pluck('id')
                )
                ->filter()
                ->map(fn ($id) => (int) $id);

        $householdLocalityIds =
            Household::query()
                ->whereIn(
                    'id',
                    $householdIds->all()
                )
                ->whereNotNull('locality_id')
                ->pluck('locality_id')
                ->map(fn ($id) => (int) $id);

        $gospelLocalityIds =
            GospelContact::query()
                ->whereIn(
                    'id',
                    $gospelContactIds->all()
                )
                ->whereNotNull('locality_id')
                ->pluck('locality_id')
                ->map(fn ($id) => (int) $id);

        $localityIds =
            $localityIds
                ->concat(
                    $householdLocalityIds
                )
                ->concat(
                    $gospelLocalityIds
                )
                ->unique()
                ->values();

        if ($localityIds->count() > 1) {
            $this->localityId = null;
            $this->localitySource =
                'Multiple Localities detected among the selected targets. Select the Contact Locality manually.';

            return;
        }

        if ($localityIds->isEmpty()) {
            $this->localityId = null;
            $this->localitySource =
                'No default Locality found. Select one manually.';

            return;
        }

        $this->localityId =
            $localityIds->first();

        $totalTargets =
            $personIds->count()
            + $householdIds->count()
            + $campusContactIds->count()
            + $gospelContactIds->count();

        if ($totalTargets > 1) {
            $this->localitySource =
                'Auto-filled because all selected targets resolve to the same Locality.';

            return;
        }

        if ($personIds->count() === 1) {
            $this->localitySource =
                $personResolutions
                    ->first()['source'];

            return;
        }

        if ($householdIds->count() === 1) {
            $this->localitySource =
                'Auto-filled from Household Locality.';

            return;
        }

        if ($campusContactIds->count() === 1) {
            $this->localitySource =
                $campusResolutions
                    ->first()['source'];

            return;
        }

        $this->localitySource =
            'Auto-filled from Gospel Contact Locality.';
    }

    private function resolveCampusContactLocality(
        int $campusContactId
    ): array {
        $contact = CampusContact::query()
            ->select([
                'id',
                'locality_id',
            ])
            ->find($campusContactId);

        if (! $contact) {
            return [
                'id' => null,
                'source' =>
                    'No default Locality found.',
            ];
        }

        $centerLocalityIds = DB::table(
            'campus_work_student_center_members as memberships'
        )
            ->join(
                'campus_work_student_centers as centers',
                'centers.id',
                '=',
                'memberships.campus_work_student_center_id'
            )
            ->where(
                'memberships.campus_contact_id',
                $campusContactId
            )
            ->whereNotNull(
                'centers.locality_id'
            )
            ->distinct()
            ->pluck(
                'centers.locality_id'
            )
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($centerLocalityIds->count() === 1) {
            return [
                'id' =>
                    $centerLocalityIds->first(),
                'source' =>
                    'Auto-filled from Student Center Locality.',
            ];
        }

        if ($centerLocalityIds->count() > 1) {
            return [
                'id' => null,
                'source' =>
                    'Multiple Student Center Localities found for this Campus Contact.',
            ];
        }

        if (filled($contact->locality_id)) {
            return [
                'id' =>
                    (int) $contact->locality_id,
                'source' =>
                    'Auto-filled from Campus Contact Locality.',
            ];
        }

        return [
            'id' => null,
            'source' =>
                'No default Locality found. Select one manually.',
        ];
    }

    private function resolveDefaultLocality(
        int $personId
    ): array {
        $person = Person::query()
            ->select([
                'id',
                'locality_id',
            ])
            ->find($personId);

        if (! $person) {
            return [
                'id' => null,
                'source' =>
                    'No default Locality found.',
            ];
        }

        $centerLocalityIds = DB::table(
            'campus_work_student_center_members as memberships'
        )
            ->join(
                'campus_contacts as contacts',
                'contacts.id',
                '=',
                'memberships.campus_contact_id'
            )
            ->join(
                'campus_work_student_centers as centers',
                'centers.id',
                '=',
                'memberships.campus_work_student_center_id'
            )
            ->where(
                'contacts.person_id',
                $personId
            )
            ->whereNotNull(
                'centers.locality_id'
            )
            ->distinct()
            ->pluck('centers.locality_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($centerLocalityIds->count() === 1) {
            return [
                'id' =>
                    $centerLocalityIds->first(),
                'source' =>
                    'Auto-filled from Student Center Locality.',
            ];
        }

        if ($centerLocalityIds->count() > 1) {
            $personLocalityId =
                filled($person->locality_id)
                    ? (int) $person->locality_id
                    : null;

            if (
                $personLocalityId
                && $centerLocalityIds->contains(
                    $personLocalityId
                )
            ) {
                return [
                    'id' => $personLocalityId,
                    'source' =>
                        'Multiple Student Centers found. Person Locality matches one center; please review.',
                ];
            }

            return [
                'id' => $personLocalityId,
                'source' =>
                    'Multiple Student Center Localities found. Defaulted to Person Locality; please review.',
            ];
        }

        if (filled($person->locality_id)) {
            return [
                'id' =>
                    (int) $person->locality_id,
                'source' =>
                    'Auto-filled from Person Locality.',
            ];
        }

        return [
            'id' => null,
            'source' =>
                'No default Locality found. Select one manually.',
        ];
    }

    private function parseGospelContactName(
        string $value
    ): ?array {
        $value =
            trim(
                preg_replace(
                    '/\\s+/',
                    ' ',
                    $value
                )
            );

        if ($value === '') {
            return null;
        }

        /*
         * One-part input:
         *
         * Ado
         *
         * means First Name only.
         */
        if (
            ! str_contains(
                $value,
                ','
            )
        ) {
            if (
                mb_strlen($value) > 255
            ) {
                return null;
            }

            return [
                'lastname' =>
                    null,

                'firstname' =>
                    $value,

                'display_name' =>
                    $value,
            ];
        }

        /*
         * Comma input:
         *
         * Adona, Ado
         *
         * means Last Name, First Name.
         */
        [
            $lastname,
            $firstname,
        ] =
            array_map(
                'trim',
                explode(
                    ',',
                    $value,
                    2
                )
            );

        if (
            $lastname === ''
            || $firstname === ''
        ) {
            return null;
        }

        $displayName =
            $lastname
            . ', '
            . $firstname;

        if (
            mb_strlen(
                $displayName
            ) > 255
        ) {
            return null;
        }

        return [
            'lastname' =>
                $lastname,

            'firstname' =>
                $firstname,

            'display_name' =>
                $displayName,
        ];
    }


    private function resetContactForm(): void
    {

        $this->morningRevivalWeekId = null;
        $this->morningRevivalDay = null;
        $this->showMorningRevivalArchive = false;
        $this->morningRevivalArchiveSearch = '';

        $this->editingContactId = null;

        $this->reviewingPublicSubmissionId = null;
        $this->publicReviewReference = '';
        $this->publicReviewSubmittedBy = '';
        $this->publicReviewSubmitterContact = '';
        $this->publicReviewTargets = '';
        $this->publicReviewParticipants = '';
        $this->publicReviewHymns = '';
        $this->publicReviewMorningRevival = '';
        $this->publicReviewTargetMatches = [];
        $this->publicReviewTargetNeedsReview = [];


        $this->targetSearch = '';
        $this->householdMemberSearch = '';
        $this->participantSearch = '';

        $this->contactedPersonIds = [];
        $this->contactedHouseholdIds = [];
        $this->contactedCampusContactIds = [];
        $this->contactedGospelContactIds = [];
        $this->householdCampusContactHouseholdIds = [];
        $this->householdGospelMemberPresence = [];
        $this->householdGospelMemberHouseholdIds = [];
        $this->newGospelContactNames = [];

        $this->householdMemberPresence = [];
        $this->householdMemberHouseholdIds = [];
        $this->initializedHouseholdIds = [];

        $this->localityId = null;
        $this->localitySource = '';

        $this->contactDate =
            now()->toDateString();

        $this->contactTime = '';

        $this->outcome =
            ShepherdingContact::OUTCOME_COMPLETED;

        $this->activityTypeIds = [];
        $this->bibleReadingRows = [];
        $this->ministryLessonIds = [];
        $this->hymnRows = [];
        $this->hymnRequestForms = [];
        $this->participantIds = [];

        $this->notes = '';
    }

}

