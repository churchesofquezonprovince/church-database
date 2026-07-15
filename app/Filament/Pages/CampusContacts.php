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
        $school = trim((string) request('school', ''));
        $peopleStatus = trim((string) request('peopleStatus', ''));

        return CampusContact::query()
            ->with(['person'])
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $like = '%' . $search . '%';

                    $query->where(function ($query) use ($like): void {
                        $query
                            ->where('firstname', 'like', $like)
                            ->orWhere('lastname', 'like', $like)
                            ->orWhere('locality', 'like', $like)
                            ->orWhere('school_campus', 'like', $like)
                            ->orWhere('course_strand', 'like', $like)
                            ->orWhere('grade_level', 'like', $like)
                            ->orWhere('contact_number', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('facebook_account', 'like', $like);
                    });
                }
            )
            ->when(
                $school === '__no_school',
                fn ($query) =>
                    $query->where(
                        fn ($query) =>
                            $query
                                ->whereNull('school_campus')
                                ->orWhere('school_campus', '')
                    )
            )
            ->when(
                $school !== '' && $school !== '__no_school',
                fn ($query) => $query->where('school_campus', $school)
            )
            ->when(
                $peopleStatus === 'linked',
                fn ($query) => $query->whereNotNull('person_id')
            )
            ->when(
                $peopleStatus === 'unlinked',
                fn ($query) => $query->whereNull('person_id')
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
                    filled($contact->school_campus)
                        ? $contact->school_campus
                        : 'School not recorded'
            )
            ->sortKeysUsing(function (string $a, string $b): int {
                if ($a === 'School not recorded') {
                    return -1;
                }

                if ($b === 'School not recorded') {
                    return 1;
                }

                return strcasecmp($a, $b);
            });
    }

    public function summary(): array
    {
        $contacts = CampusContact::query()->get();

        return [
            'total' => $contacts->count(),

            'not_in_people' => $contacts
                ->whereNull('person_id')
                ->count(),

            'added_to_people' => $contacts
                ->whereNotNull('person_id')
                ->count(),

            'schools' => $contacts
                ->pluck('school_campus')
                ->filter()
                ->unique()
                ->count(),
        ];
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
