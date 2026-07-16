<?php

namespace App\Filament\Pages;

use App\Filament\Resources\People\PersonResource;
use App\Models\CampusContact;
use App\Models\Person;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CampusContacts extends Page
{
    protected string $view = 'filament.pages.campus-contacts';

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
        return 3;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function contacts(): Collection
    {
        $search = trim((string) request('q', ''));

        $school = trim(
            (string) request('school', '')
        );

        $peopleStatus = trim(
            (string) request('peopleStatus', '')
        );

        $contacts = CampusContact::query()
            ->with([
                'person.churchProfile',
                'person.educationProfile',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $like = '%' . $search . '%';

                    $query->where(
                        function ($query) use ($like): void {
                            /*
                             * Search the Campus Contact mirror.
                             */
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
                                    'school_campus',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'course_strand',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'grade_level',
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
                                )

                                /*
                                 * Also search the linked Person,
                                 * because Person is the source of truth
                                 * after linking.
                                 */
                                ->orWhereHas(
                                    'person',
                                    function ($personQuery) use (
                                        $like
                                    ): void {
                                        $personQuery
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
                                )

                                /*
                                 * Also search linked Education Profile.
                                 */
                                ->orWhereHas(
                                    'person.educationProfile',
                                    function (
                                        $educationQuery
                                    ) use ($like): void {
                                        $educationQuery
                                            ->where(
                                                'school_workplace',
                                                'like',
                                                $like
                                            )
                                            ->orWhere(
                                                'course_strand',
                                                'like',
                                                $like
                                            )
                                            ->orWhere(
                                                'grade_level',
                                                'like',
                                                $like
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $peopleStatus === 'linked',
                fn ($query) =>
                    $query->whereNotNull('person_id')
            )
            ->when(
                $peopleStatus === 'unlinked',
                fn ($query) =>
                    $query->whereNull('person_id')
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        /*
         * Filter using effective school value:
         *
         * Linked contact:
         * Person Education Profile is source of truth.
         *
         * Unlinked contact:
         * Campus Contact school_campus is used.
         */
        if ($school === '__no_school') {
            return $contacts
                ->filter(
                    fn (CampusContact $contact): bool =>
                        blank(
                            $contact->effective_school_campus
                        )
                )
                ->values();
        }

        if ($school !== '') {
            return $contacts
                ->filter(
                    fn (CampusContact $contact): bool =>
                        strcasecmp(
                            trim(
                                (string)
                                $contact
                                    ->effective_school_campus
                            ),
                            $school
                        ) === 0
                )
                ->values();
        }

        return $contacts;
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
        $contacts = CampusContact::query()
            ->with([
                'person.educationProfile',
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
                        fn (
                            CampusContact $contact
                        ): ?string =>
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


    public function existingPeopleSearch(): string
    {
        return trim(
            (string) request(
                'existingPeopleQ',
                ''
            )
        );
    }

    public function availableExistingPeople(): Collection
    {
        $search = $this->existingPeopleSearch();

        return Person::query()
            ->with([
                'churchProfile',
                'educationProfile',
            ])

            /*
             * Do not show People already linked
             * to a Campus Contact.
             */
            ->whereDoesntHave('campusContact')

            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $like = '%' . $search . '%';

                    $query->where(
                        function ($query) use (
                            $like
                        ): void {
                            $query
                                ->where(
                                    'firstname',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'middlename',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'lastname',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'nickname',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'locality',
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
                                )

                                ->orWhereHas(
                                    'churchProfile',
                                    function (
                                        $profileQuery
                                    ) use ($like): void {
                                        $profileQuery
                                            ->where(
                                                'status',
                                                'like',
                                                $like
                                            );
                                    }
                                )

                                ->orWhereHas(
                                    'educationProfile',
                                    function (
                                        $educationQuery
                                    ) use ($like): void {
                                        $educationQuery
                                            ->where(
                                                'school_workplace',
                                                'like',
                                                $like
                                            )
                                            ->orWhere(
                                                'course_strand',
                                                'like',
                                                $like
                                            )
                                            ->orWhere(
                                                'grade_level',
                                                'like',
                                                $like
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')

            /*
             * Keep the page responsive.
             * User can search when there are many People.
             */
            ->limit(100)
            ->get();
    }

    public function linkablePeople(): Collection
    {
        return Person::query()
            ->with([
                'churchProfile',
                'educationProfile',
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
        $excludedSchools = [
            'cefi',
            'city government of lucena',
            'house of representatives',
            'lac / fast',
            'lgu-pagbilao',
            'lgu pagbilao',
            'slsu-lubcan',
        ];

        $personSchools = Person::query()
            ->whereHas(
                'educationProfile',
                fn ($query) =>
                    $query
                        ->whereNotNull('school_workplace')
                        ->where('school_workplace', '!=', '')
            )
            ->with(['educationProfile'])
            ->get()
            ->map(
                fn (Person $person): ?string =>
                    $person->educationProfile?->school_workplace
            );

        $contactSchools = CampusContact::query()
            ->whereNotNull('school_campus')
            ->where('school_campus', '!=', '')
            ->pluck('school_campus');

        return $personSchools
            ->merge($contactSchools)
            ->filter()
            ->reject(
                fn (string $school): bool =>
                    in_array(
                        mb_strtolower(trim($school)),
                        $excludedSchools,
                        true
                    )
            )
            ->unique()
            ->sort()
            ->values();
    }

    public function localityOptions(): Collection
    {
        return Person::query()
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->distinct()
            ->orderBy('locality')
            ->pluck('locality');
    }

    public function personUrl(Person $person): string
    {
        return PersonResource::getUrl('view', [
            'record' => $person->id,
        ]);
    }
}
