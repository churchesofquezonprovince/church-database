<?php

namespace App\Filament\Pages;

use App\Support\LocalityOptions;
use App\Filament\Resources\People\PersonResource;
use App\Models\CampusContact;
use App\Models\Person;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CampusContacts extends Page
{
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.campus-contacts';


    public string $existingPeopleSearch = '';

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
        /*
         * Campus Contacts is intentionally loaded normally.
         * Search, school filter, and People Database status filter
         * are handled client-side in the Blade for faster UI response.
         */
        return CampusContact::query()
            ->with([
                'person.churchProfile',
                'person.educationProfile',
            ])
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
                'educationProfile:id,person_id,school_workplace,course_strand,grade_level',
            ])
            ->whereDoesntHave('campusContact')
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
                            ->where('school_workplace', 'like', $like)
                            ->orWhere('course_strand', 'like', $like)
                            ->orWhere('grade_level', 'like', $like);
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
        $officialSchools = collect([
            'Southern Luzon State University',
            'Southern Luzon State University, Alabat Campus',
            'Southern Luzon State University, Catanauan Campus',
            'Southern Luzon State University, Gumaca Campus',
            'Southern Luzon State University, Infanta Campus',
            'Southern Luzon State University, Lucena Campus',
            'Southern Luzon State University, Polillo Campus',
            'Southern Luzon State University, Tagkawayan Campus',
            'Southern Luzon State University, Tayabas Campus',
            'Southern Luzon State University, Tiaong Campus',

            'Polytechnic University of the Philippines, Lopez Branch',
            'Polytechnic University of the Philippines, General Luna Campus',
            'Polytechnic University of the Philippines, Mulanay Campus',
            'Polytechnic University of the Philippines, Unisan Campus',

            'Dalubhasaan ng Lungsod ng Lucena',

            'Manuel S. Enverga University Foundation',
            'Manuel S. Enverga University Foundation, Inc. – Candelaria',
            'Manuel S. Enverga University Foundation, Inc. – Calauag',
            'Manuel S. Enverga University Foundation, Inc. – Catanauan',
            'Manuel S. Enverga Academy Foundation, Inc. – Sampaloc',
            'Manuel S. Enverga Institute Foundation, Inc. – San Antonio',

            'Sacred Heart College of Lucena City, Inc.',
            'Maryhill College',
            'Calayan Educational Foundation, Inc.',
            'St. Anne College Lucena, Inc.',
            'College of Sciences, Technology and Communications, Inc.',

            'Paaralang Sekundarya ng Lucban',
            'Nagsinamo National High School',
            'Luis Palad Integrated High School',
            'Quezon Science High School',
            'Quezon National High School',
            'Manuel S. Enverga Memorial School of Arts and Trades',
            'Dr. Maria D. Pastrana National High School',
            'Pagbilao National High School',
            'Talipan National High School',
            'Dr. Panfilo Castro National High School',
            'Bukal Sur National High School',
            'Sta. Catalina National High School',
            'Sariaya National High School',
            'Lutucan National High School',
            'Canda National High School',
            'Recto Memorial National High School',
            'Lusacan National High School',
            'San Antonio National High School',
            'Infanta National High School',
            'Polillo National High School',
            'Sampaloc National High School',
            'Alabat Island National High School',
            'Atimonan National Comprehensive High School',
            'Calauag National High School',
            'Guinayangan National High School',
            'Gumaca National High School',
            'Lopez National Comprehensive High School',
            'Tagkawayan National High School',
            'Catanauan National High School',
            'Bondoc Peninsula Agricultural High School',
            'Pitogo Community High School',
            'Unisan National High School',
        ]);

        $existingSchools = collect()
            ->merge(
                CampusContact::query()
                    ->whereNotNull('school_campus')
                    ->pluck('school_campus')
            )
            ->merge(
                Person::query()
                    ->whereHas('educationProfile')
                    ->with('educationProfile')
                    ->get()
                    ->pluck('educationProfile.school_workplace')
            );

        return $officialSchools
            ->merge($existingSchools)
            ->filter(fn ($school): bool => filled($school))
            ->map(fn ($school): string => trim((string) $school))
            ->unique(
                fn (string $school): string =>
                    mb_strtolower($school)
            )
            ->sort(
                fn (string $a, string $b): int =>
                    strcasecmp($a, $b)
            )
            ->values();
    }

    public function localityOptions(): Collection
    {
        return LocalityOptions::primaryProvinceNames();
    }

    public function personUrl(Person $person): string
    {
        return PersonResource::getUrl('view', [
            'record' => $person->id,
        ]);
    }
}
