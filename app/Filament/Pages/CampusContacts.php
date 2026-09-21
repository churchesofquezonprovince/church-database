<?php

namespace App\Filament\Pages;

use App\Support\LocalityOptions;
use App\Filament\Resources\People\PersonResource;
use App\Models\CampusContact;
use App\Models\CampusWorkTerm;
use App\Models\Person;
use App\Models\School;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CampusContacts extends Page
{
    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.campus-contacts';


    public string $existingPeopleSearch = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        $status = (string) request()->query(
            'status',
            'all'
        );

        $this->statusFilter = in_array(
            $status,
            [
                'all',
                'linked',
                'unlinked',
            ],
            true
        )
            ? $status
            : 'all';
    }

    public function getTitle(): string
    {
        return 'Campus Contacts';
    }

    public static function getNavigationLabel(): string
    {
        return 'Campus Contacts';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Campus Work';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-user-plus';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
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

    public function archivedTerms(): Collection
    {
        return CampusWorkTerm::query()
            ->where('is_archived', true)
            ->withCount('campusContactMemberships')
            ->orderByDesc('academic_year')
            ->orderByDesc('id')
            ->get();
    }

    public function copySourceTerms(): Collection
    {
        $selectedTerm = $this->selectedTerm();

        if (! $selectedTerm) {
            return collect();
        }

        return CampusWorkTerm::query()
            ->whereKeyNot($selectedTerm->id)
            ->withCount('campusContactMemberships')
            ->orderByDesc('academic_year')
            ->orderByDesc('id')
            ->get();
    }

    public function selectedTerm(): ?CampusWorkTerm
    {
        $termId = request()->integer('termId');

        if ($termId) {
            $selectedTerm = CampusWorkTerm::query()
                ->find($termId);

            if ($selectedTerm) {
                return $selectedTerm;
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
                ->first()
            ?? CampusWorkTerm::query()
                ->latest('id')
                ->first();
    }

    public function termUrl(CampusWorkTerm $term): string
    {
        return static::getUrl() . '?' . http_build_query([
            'termId' => $term->id,
        ]);
    }

    public function contacts(): Collection
    {
        $term = $this->selectedTerm();

        if (! $term) {
            return collect();
        }

        /*
         * Search, school filter, and People Database status filter
         * remain client-side in the Blade.
         *
         * The Academic Term itself is server-side so historical
         * Campus Contact allocations remain isolated by term.
         */
        return CampusContact::query()
            ->whereHas(
                'termMemberships',
                fn ($query) =>
                    $query->where(
                        'campus_work_term_id',
                        $term->id
                    )
            )
            ->with([
                'person.churchProfile',
                'person.educationProfile',
                'termMemberships.term',
            ])
            ->when(
                $this->statusFilter === 'linked',
                fn ($query) =>
                    $query->whereNotNull('person_id')
            )
            ->when(
                $this->statusFilter === 'unlinked',
                fn ($query) =>
                    $query->whereNull('person_id')
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function groupedContacts(): Collection
    {
        return $this->contacts()
            ->groupBy(
                fn (CampusContact $contact): string =>
                    filled(
                        $contact->effective_school_campus
                    )
                        ? $contact->effective_school_campus
                        : 'School not recorded'
            )
            ->sortKeysUsing(
                function (
                    string $a,
                    string $b
                ): int {
                    if (
                        $a === 'School not recorded'
                    ) {
                        return -1;
                    }

                    if (
                        $b === 'School not recorded'
                    ) {
                        return 1;
                    }

                    return strcasecmp($a, $b);
                }
            );
    }

    public function summary(): array
    {
        $term = $this->selectedTerm();

        if (! $term) {
            return [
                'total' => 0,
                'not_in_people' => 0,
                'added_to_people' => 0,
                'schools' => 0,
            ];
        }

        $contacts = CampusContact::query()
            ->whereHas(
                'termMemberships',
                fn ($query) =>
                    $query->where(
                        'campus_work_term_id',
                        $term->id
                    )
            )
            ->with([
                'person.educationProfile',
                'school',
            ])
            ->get();

        return [
            'total' =>
                $contacts->count(),

            'not_in_people' =>
                $contacts
                    ->whereNull('person_id')
                    ->count(),

            'added_to_people' =>
                $contacts
                    ->whereNotNull('person_id')
                    ->count(),

            'schools' =>
                $contacts
                    ->map(
                        fn (CampusContact $contact): ?string =>
                            $contact
                                ->effective_school_campus
                    )
                    ->filter()
                    ->unique(
                        fn (string $school): string =>
                            mb_strtolower(
                                trim($school)
                            )
                    )
                    ->count(),
        ];
    }



    public function availableExistingPeople(): Collection
    {
        $search = trim($this->existingPeopleSearch);

        /*
         * Future-proof People Database search:
         * - Do not load thousands of people when the search box is empty.
         * - Start searching only after 2 characters.
         * - Limit results so Livewire remains fast even with 5,000+ People.
         */
        if (mb_strlen($search) < 2) {
            return collect();
        }

        $term = $this->selectedTerm();

        if (! $term) {
            return collect();
        }

        $like = '%' . $search . '%';

        return Person::query()
            ->select([
                'id',
                'firstname',
                'middlename',
                'lastname',
                'nickname',
                'locality',
                'contact_number',
                'email',
                'facebook_account',
            ])
            ->with([
                'churchProfile:id,person_id,status,category',
                'educationProfile:id,person_id,school_id,course_strand,grade_level',
                'educationProfile.school:id,name,short_name',
            ])
            ->whereDoesntHave(
                'campusContact.termMemberships',
                fn ($membershipQuery) =>
                    $membershipQuery->where(
                        'campus_work_term_id',
                        $term->id
                    )
            )
            ->where(function ($query) use ($like): void {
                $query
                    ->where('firstname', 'like', $like)
                    ->orWhere('middlename', 'like', $like)
                    ->orWhere('lastname', 'like', $like)
                    ->orWhere('nickname', 'like', $like)
                    ->orWhere('locality', 'like', $like)
                    ->orWhere('contact_number', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('facebook_account', 'like', $like)
                    ->orWhereHas('churchProfile', function ($profileQuery) use ($like): void {
                        $profileQuery
                            ->where('status', 'like', $like)
                            ->orWhere('category', 'like', $like);
                    })
                    ->orWhereHas('educationProfile', function ($educationQuery) use ($like): void {
                        $educationQuery
                            ->where('course_strand', 'like', $like)
                            ->orWhere('grade_level', 'like', $like)
                            ->orWhereHas('school', function ($schoolQuery) use ($like): void {
                                $schoolQuery
                                    ->where('name', 'like', $like)
                                    ->orWhere('short_name', 'like', $like);
                            });
                    });
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(50)
            ->get();
    }


    public function linkablePeople(): Collection
    {
        return Person::query()
            ->with([
                'churchProfile',
                'educationProfile.school',
            ])

            /*
             * A Person may belong to only one
             * Campus Contact.
             */
            ->whereDoesntHave('campusContact')

            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
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

    public function unallocatedContacts(): Collection
    {
        return CampusContact::query()
            ->doesntHave('termMemberships')
            ->with([
                'person.churchProfile',
                'person.educationProfile.school',
                'school',
            ])
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function personUrl(Person $person): string
    {
        return PersonResource::getUrl('view', [
            'record' => $person->id,
        ]);
    }
}
