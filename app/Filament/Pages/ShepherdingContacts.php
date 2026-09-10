<?php

namespace App\Filament\Pages;

use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\Household;
use App\Models\Locality;
use App\Models\MinistryBook;
use App\Models\ProvinceSetting;
use App\Models\Person;
use App\Models\ShepherdingActivityType;
use App\Models\ShepherdingContact;
use App\Support\ActivityLogger;
use App\Support\LocalityOptions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public array $ministryLessonIds = [];

    public array $participantIds = [];

    public string $notes = '';

    public function mount(): void
    {
        $this->contactDate =
            now()->toDateString();

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
        }
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
        return 3;
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

        $this->refreshLocalityFromTargets();
    }

    public function gospelContacts(): Collection
    {
        $search = trim($this->targetSearch);

        return GospelContact::query()
            ->with('localityRecord')
            ->whereNull('person_id')
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use ($search): void {
                            $like = "%{$search}%";

                            $query
                                ->where('firstname', 'like', $like)
                                ->orWhere('lastname', 'like', $like)
                                ->orWhere('locality', 'like', $like)
                                ->orWhere('contact_place', 'like', $like)
                                ->orWhere('contact_number', 'like', $like)
                                ->orWhere('email', 'like', $like)
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

    public function selectedGospelContacts(): Collection
    {
        if ($this->contactedGospelContactIds === []) {
            return collect();
        }

        /*
         * Do not filter person_id here.
         * Historical Shepherding Records must continue
         * showing a Gospel Contact after promotion.
         */
        return GospelContact::query()
            ->with('localityRecord')
            ->whereIn(
                'id',
                collect($this->contactedGospelContactIds)
                    ->map(fn ($id) => (int) $id)
                    ->all()
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function removeContactedGospelContact(
        int $gospelContactId
    ): void {
        $this->contactedGospelContactIds =
            collect($this->contactedGospelContactIds)
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
            collect($this->contactedGospelContactIds)
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

    public function activityTypes(): Collection
    {
        return ShepherdingActivityType::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
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
                'locality',
                'activityTypes',
                'ministryLessons.book',
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
            'householdMemberPresence' => [
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

        $ministryIds = collect(
            $data['ministryLessonIds'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

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

        if (
            $data['outcome']
            === ShepherdingContact::OUTCOME_UNAVAILABLE
        ) {
            $activityIds = [];
            $ministryIds = [];
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
                    'locality',
                    'activityTypes',
                    'ministryLessons',
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
                $householdMemberSync,
                $activityIds,
                $ministryIds,
                $participantIds
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

                        'participants' =>
                            $contact
                                ->participants
                                ->pluck('display_name')
                                ->all(),

                        'notes' =>
                            $contact->notes,
                    ]
                    : [];

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
                        $contactedGospelContactIds
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
                    ->participants()
                    ->sync($participantIds);

                $contact->load([
                    'contactedPeople',
                    'contactedHouseholds',
                    'contactedCampusContacts',
                    'contactedGospelContacts',
                    'householdMembers',
                    'locality',
                    'activityTypes',
                    'ministryLessons.book',
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
                'activityTypes',
                'ministryLessons',
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

        $this->ministryLessonIds =
            $contact
                ->ministryLessons
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

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
         * Remove snapshot rows belonging to a Household
         * that has just been unchecked.
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
         * For a newly-selected Household, snapshot all
         * CURRENT Household members and assume Present.
         *
         * The user can immediately uncheck anyone who
         * was not there.
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

    private function resetContactForm(): void
    {
        $this->editingContactId = null;

        $this->targetSearch = '';
        $this->householdMemberSearch = '';
        $this->participantSearch = '';

        $this->contactedPersonIds = [];
        $this->contactedHouseholdIds = [];
        $this->contactedCampusContactIds = [];
        $this->contactedGospelContactIds = [];

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
        $this->ministryLessonIds = [];
        $this->participantIds = [];

        $this->notes = '';
    }

}

