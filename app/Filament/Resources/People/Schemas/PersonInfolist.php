<?php

namespace App\Filament\Resources\People\Schemas;

use App\Domain\Family\FamilyRelationshipService;
use App\Filament\Pages\FamilyTree;
use App\Filament\Resources\Households\HouseholdResource;
use App\Filament\Resources\People\PersonResource;
use App\Models\ChurchProfile;
use App\Models\Household;
use App\Models\Person;
use App\Support\ChurchProfileOptions;
use Carbon\CarbonInterface;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class PersonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile Overview')
                    ->schema([
                        TextEntry::make('profile_overview')
                            ->label('Summary')
                            ->state(fn (Person $record): HtmlString => self::profileOverview($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('Personal Information')
                    ->schema([
                        TextEntry::make('display_name')
                            ->label('Name')
                            ->state(fn (Person $record): HtmlString => self::value(self::viewDisplayName($record), important: true))
                            ->html(),

                        TextEntry::make('sex')
                            ->label('Sex')
                            ->state(fn (Person $record): HtmlString => self::value($record->sex))
                            ->html(),

                        TextEntry::make('birthdate')
                            ->label('Birthdate')
                            ->state(fn (Person $record): HtmlString => self::dateValue($record->birthdate))
                            ->html(),

                        TextEntry::make('birthplace')
                            ->label('Birthplace')
                            ->state(fn (Person $record): HtmlString => self::value($record->birthplace))
                            ->html(),

                        TextEntry::make('contact_number')
                            ->label('Contact Number')
                            ->state(fn (Person $record): HtmlString => self::phoneValue($record->contact_number))
                            ->html(),

                        TextEntry::make('email')
                            ->label('Email')
                            ->state(fn (Person $record): HtmlString => self::emailValue($record->email))
                            ->html(),

                        TextEntry::make('facebook_account')
                            ->label('Facebook Account')
                            ->state(fn (Person $record): HtmlString => self::facebookValue($record->facebook_account))
                            ->html(),
                    ])
                    ->columns(2),

                Section::make('Family Summary')
                    ->schema([
                        TextEntry::make('family_tree_link')
                            ->label('Visual Family Tree')
                            ->state(fn (Person $record): HtmlString => self::familyTreeLink($record))
                            ->html(),

                        TextEntry::make('family_household')
                            ->label('Household')
                            ->state(fn (Person $record): HtmlString => self::householdLink($record->household))
                            ->html(),

                        TextEntry::make('family_spouse')
                            ->label('Spouse')
                            ->state(fn (Person $record): HtmlString => self::personLink($record->spouse))
                            ->html(),

                        TextEntry::make('family_father')
                            ->label('Father')
                            ->state(fn (Person $record): HtmlString => self::personLink(self::family()->father($record)))
                            ->html(),

                        TextEntry::make('family_mother')
                            ->label('Mother')
                            ->state(fn (Person $record): HtmlString => self::personLink(self::family()->mother($record)))
                            ->html(),

                        TextEntry::make('family_siblings')
                            ->label('Siblings')
                            ->state(fn (Person $record): HtmlString => self::peopleLinks(self::family()->siblings($record)))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('family_children')
                            ->label('Children')
                            ->state(fn (Person $record): HtmlString => self::peopleLinks(self::family()->children($record)))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Shepherding Responsibility')
                    ->schema([
                        TextEntry::make('care_summary')
                            ->label('Care Summary')
                            ->state(fn (Person $record): HtmlString => self::careSummary($record))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('view_people_shepherded')
                            ->label('Quick Filter: Shepherded')
                            ->state(fn (Person $record): HtmlString => self::peopleTableLink(
                                label: 'View all people shepherded by this person',
                                filters: [
                                    'shepherd_id' => $record->id,
                                ],
                            ))
                            ->html(),

                        TextEntry::make('view_people_introduced')
                            ->label('Quick Filter: Introduced')
                            ->state(fn (Person $record): HtmlString => self::peopleTableLink(
                                label: 'View all people introduced by this person',
                                filters: [
                                    'introduced_by_id' => $record->id,
                                ],
                            ))
                            ->html(),

                        TextEntry::make('people_shepherded')
                            ->label('People Shepherded')
                            ->state(fn (Person $record): HtmlString => self::shepherdedPeople($record))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('dormant_under_care')
                            ->label('Dormant Under Care')
                            ->state(fn (Person $record): HtmlString => self::shepherdedPeople($record, 'Dormant'))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('new_ones_under_care')
                            ->label('New Ones Under Care')
                            ->state(fn (Person $record): HtmlString => self::shepherdedPeople($record, 'New One'))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('gospel_friends_under_care')
                            ->label('Gospel Friends Under Care')
                            ->state(fn (Person $record): HtmlString => self::shepherdedPeople($record, 'Gospel Friend'))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('people_introduced')
                            ->label('People Introduced')
                            ->state(fn (Person $record): HtmlString => self::introducedPeople($record))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Address')
                    ->schema([
                        TextEntry::make('geocoordinates')
                            ->label('Geocoordinates')
                            ->state(fn (Person $record): HtmlString => self::value($record->geocoordinates))
                            ->html(),

                        TextEntry::make('home_address')
                            ->label('Home Address')
                            ->state(fn (Person $record): HtmlString => self::value($record->home_address))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('permanent_address')
                            ->label('Permanent Address')
                            ->state(fn (Person $record): HtmlString => self::value($record->permanent_address))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Church Information')
                    ->schema([
                        TextEntry::make('locality')
                            ->label('Locality')
                            ->state(fn (Person $record): HtmlString => self::value($record->locality))
                            ->html(),

                        TextEntry::make('churchProfile.category')
                            ->label('Category')
                            ->badge()
                            ->color(fn (?string $state): string => ChurchProfileOptions::categoryColor($state))
                            ->placeholder('None recorded'),

                        TextEntry::make('churchProfile.status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (?string $state): string => ChurchProfileOptions::statusColor($state))
                            ->placeholder('None recorded'),

                        TextEntry::make('churchProfile.baptism_date')
                            ->label('Baptism Date')
                            ->state(fn (Person $record): HtmlString => self::baptismDateValue($record->churchProfile))
                            ->html(),

                        TextEntry::make('churchProfile.service')
                            ->label('Shepherding Groups')
                            ->state(fn (Person $record): HtmlString => self::shepherdingGroupsValue($record->churchProfile?->service))
                            ->html(),

                        TextEntry::make('church_shepherd')
                            ->label('Shepherd')
                            ->state(fn (Person $record): HtmlString => self::personLink($record->churchProfile?->shepherd))
                            ->html(),

                        TextEntry::make('church_introduced_by')
                            ->label('Introduced By')
                            ->state(fn (Person $record): HtmlString => self::personLink($record->churchProfile?->introducedBy))
                            ->html(),
                    ])
                    ->columns(2),

                Section::make('Education / Work')
                    ->schema([
                        TextEntry::make('education_grade_level')
                            ->label('Grade Level')
                            ->state(fn (Person $record): HtmlString => self::value($record->educationProfile?->grade_level))
                            ->html(),

                        TextEntry::make('education_course_strand')
                            ->label('Course / Strand')
                            ->state(fn (Person $record): HtmlString => self::value($record->educationProfile?->course_strand))
                            ->html(),

                        TextEntry::make('education_occupation')
                            ->label('Occupation')
                            ->state(fn (Person $record): HtmlString => self::value($record->educationProfile?->occupation))
                            ->html(),

                        TextEntry::make('education_school')
                            ->label('School')
                            ->state(fn (Person $record): HtmlString => self::value(
                                $record->educationProfile?->school?->name
                            ))
                            ->html(),

                        TextEntry::make('education_workplace')
                            ->label('Workplace')
                            ->state(fn (Person $record): HtmlString => self::value($record->educationProfile?->workplace))
                            ->html(),
                    ])
                    ->columns(2),

                Section::make('Emergency Contact')
                    ->schema([
                        TextEntry::make('emergency_contact_person')
                            ->label('Emergency Contact')
                            ->state(fn (Person $record): HtmlString => self::personLink($record->emergencyContact))
                            ->html(),

                        TextEntry::make('emergency_contact_relationship')
                            ->label('Relationship')
                            ->state(fn (Person $record): HtmlString => self::value($record->emergency_contact_relationship))
                            ->html(),

                        TextEntry::make('emergency_contact_number')
                            ->label('Contact Number')
                            ->state(fn (Person $record): HtmlString => self::phoneValue($record->emergency_contact_number))
                            ->html(),
                    ])
                    ->columns(2),
            ]);
    }


    private static function viewDisplayName(Person $record): string
    {
        $middleInitials = collect(preg_split('/\s+/', trim((string) $record->middlename)))
            ->filter()
            ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)) . '.')
            ->implode(' ');

        return collect([
            filled($record->lastname) ? trim((string) $record->lastname) . ',' : null,
            $record->firstname,
            $middleInitials,
            $record->suffix,
        ])
            ->filter(fn ($part): bool => filled($part))
            ->implode(' ');
    }

    private static function family(): FamilyRelationshipService
    {
        return app(FamilyRelationshipService::class);
    }

    private static function profileOverview(Person $record): HtmlString
    {
        $name = self::viewDisplayName($record);
        $initials = collect(preg_split('/\s+/', trim((string) $name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)))
            ->join('');

        $status = $record->churchProfile?->status ?? 'Unknown';
        $category = $record->churchProfile?->category ?? 'Unknown';
        $service = self::shepherdingGroupsText($record->churchProfile?->service);
        $locality = $record->locality ?: 'No Locality';

        $treeUrl = FamilyTree::getUrl([
            'personId' => $record->id,
        ]);

        return new HtmlString(
            '<div class="rounded-2xl border border-primary-200 bg-gradient-to-br from-primary-50 to-white p-6 dark:border-primary-900 dark:from-gray-900 dark:to-gray-950">'
            . '<div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">'
            . '<div class="flex items-center gap-4">'
            . '<div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-600 text-xl font-bold text-white shadow-sm">'
            . e($initials ?: '?')
            . '</div>'
            . '<div>'
            . '<p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Person Profile</p>'
            . '<h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">' . e($name) . '</h2>'
            . '<p class="mt-1 text-sm text-gray-600 dark:text-gray-300">' . e($category) . ' • ' . e($locality) . '</p>'
            . '</div>'
            . '</div>'
            . '<a href="' . e($treeUrl) . '" class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500">'
            . 'Open visual family tree'
            . '</a>'
            . '</div>'
            . '<div class="mt-5 grid gap-3 md:grid-cols-3">'
            . self::summaryBox('Status', $status, self::statusTone($status))
            . self::summaryBox('Category', $category, 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200')
            . self::summaryBox('Shepherding Groups', $service, 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200')
            . '</div>'
            . '</div>'
        );
    }



    private static function baptismDateValue(?ChurchProfile $profile): HtmlString
    {
        if (! $profile || blank($profile->baptism_year)) {
            return self::none();
        }

        $year = (int) $profile->baptism_year;
        $month = filled($profile->baptism_month) ? (int) $profile->baptism_month : null;
        $day = filled($profile->baptism_day) ? (int) $profile->baptism_day : null;

        if ($month && $day) {
            return self::value(
                \Carbon\CarbonImmutable::create($year, $month, $day)->format('F j, Y')
            );
        }

        if ($month) {
            return self::value(
                \Carbon\CarbonImmutable::create($year, $month, 1)->format('F Y')
            );
        }

        return self::value((string) $year);
    }

    private static function shepherdingGroupsText(mixed $groups): string
    {
        if (blank($groups)) {
            return 'No Shepherding Groups';
        }

        if (is_array($groups)) {
            return collect($groups)
                ->map(fn ($group): string => trim((string) $group))
                ->filter()
                ->unique()
                ->implode(', ') ?: 'No Shepherding Groups';
        }

        $decoded = json_decode((string) $groups, true);

        if (is_array($decoded)) {
            return collect($decoded)
                ->map(fn ($group): string => trim((string) $group))
                ->filter()
                ->unique()
                ->implode(', ') ?: 'No Shepherding Groups';
        }

        return trim((string) $groups) ?: 'No Shepherding Groups';
    }

    private static function shepherdingGroupsValue(mixed $groups): HtmlString
    {
        $text = self::shepherdingGroupsText($groups);

        if ($text === 'No Shepherding Groups') {
            return self::none();
        }

        return self::value($text);
    }

    private static function summaryBox(string $label, mixed $value, string $tone): string
    {
        if (is_array($value)) {
            $value = collect($value)
                ->map(fn ($item): string => trim((string) $item))
                ->filter()
                ->unique()
                ->implode(', ');
        }

        if (! blank($value) && is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                $value = collect($decoded)
                    ->map(fn ($item): string => trim((string) $item))
                    ->filter()
                    ->unique()
                    ->implode(', ');
            }
        }

        return '<div class="rounded-xl border p-4 ' . e($tone) . '">'
            . '<p class="text-xs font-semibold uppercase tracking-wide opacity-75">' . e($label) . '</p>'
            . '<p class="mt-1 text-lg font-bold">' . e($value ?: 'Not recorded') . '</p>'
            . '</div>';
    }

    private static function value(?string $value, bool $important = false): HtmlString
    {
        if (blank($value)) {
            return self::none();
        }

        $class = $important
            ? 'block rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-base font-bold text-primary-800 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200'
            : 'block rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100';

        return new HtmlString('<span class="' . e($class) . '">' . e($value) . '</span>');
    }

    private static function dateValue(mixed $date): HtmlString
    {
        if (blank($date)) {
            return self::none();
        }

        $value = $date instanceof CarbonInterface
            ? $date->format('F j, Y')
            : (string) $date;

        return self::value($value);
    }

    private static function phoneValue(?string $number): HtmlString
    {
        if (blank($number)) {
            return self::none();
        }

        return new HtmlString(
            '<a href="tel:' . e($number) . '" class="block rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-bold text-primary-700 hover:bg-primary-100 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">'
            . e($number)
            . '</a>'
        );
    }

    private static function facebookValue(?string $facebook): HtmlString
    {
        if (blank($facebook)) {
            return self::none();
        }

        $value = trim((string) $facebook);
        $url = null;

        if (
            filter_var($value, FILTER_VALIDATE_URL)
            && in_array(
                strtolower((string) parse_url($value, PHP_URL_SCHEME)),
                ['http', 'https'],
                true
            )
        ) {
            $url = $value;
        } elseif (
            preg_match(
                '/^(?:www\.)?facebook\.com\//i',
                $value
            )
        ) {
            $url = 'https://' . $value;
        } elseif (
            preg_match(
                '/^@?([A-Za-z0-9.]+)$/',
                $value,
                $matches
            )
        ) {
            $url = 'https://www.facebook.com/' . $matches[1];
        }

        if (! $url) {
            return self::value($value);
        }

        return new HtmlString(
            '<a href="' . e($url) . '"'
            . ' target="_blank"'
            . ' rel="noopener noreferrer"'
            . ' class="block rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-bold text-blue-700 hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">'
            . e($value)
            . '</a>'
        );
    }


    private static function emailValue(?string $email): HtmlString
    {
        if (blank($email)) {
            return self::none();
        }

        return new HtmlString(
            '<a href="mailto:' . e($email) . '" class="block rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-bold text-primary-700 hover:bg-primary-100 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">'
            . e($email)
            . '</a>'
        );
    }

    private static function familyTreeLink(Person $person): HtmlString
    {
        $url = FamilyTree::getUrl([
            'personId' => $person->id,
        ]);

        return new HtmlString(
            '<a href="' . e($url) . '" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">'
            . 'Open visual family tree'
            . '</a>'
        );
    }

    private static function personLink(?Person $person): HtmlString
    {
        if (! $person) {
            return self::none();
        }

        $url = PersonResource::getUrl('view', [
            'record' => $person->id,
        ]);

        return new HtmlString(
            '<a href="' . e($url) . '" class="inline-flex rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-bold text-primary-700 hover:bg-primary-100 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">'
            . e($person->display_name)
            . '</a>'
        );
    }

    private static function peopleLinks(Collection $people): HtmlString
    {
        if ($people->isEmpty()) {
            return self::none();
        }

        $links = $people
            ->sortBy('lastname')
            ->map(function (Person $person): string {
                $url = PersonResource::getUrl('view', [
                    'record' => $person->id,
                ]);

                return '<a href="' . e($url) . '" class="inline-flex rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-100 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">'
                    . e($person->display_name)
                    . '</a>';
            })
            ->join('');

        return new HtmlString('<div class="flex flex-wrap gap-2">' . $links . '</div>');
    }

    private static function householdLink(?Household $household): HtmlString
    {
        if (! $household) {
            return self::none();
        }

        $url = HouseholdResource::getUrl('view', [
            'record' => $household->id,
        ]);

        return new HtmlString(
            '<a href="' . e($url) . '" class="inline-flex rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-bold text-primary-700 hover:bg-primary-100 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">'
            . e($household->display_name)
            . '</a>'
        );
    }

    private static function shepherdedPeople(Person $record, ?string $status = null): HtmlString
    {
        $people = ChurchProfile::query()
            ->with('person')
            ->where('shepherd_id', $record->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->get()
            ->pluck('person')
            ->filter()
            ->values();

        return self::peopleLinks($people);
    }

    private static function introducedPeople(Person $record): HtmlString
    {
        $people = ChurchProfile::query()
            ->with('person')
            ->where('introduced_by_id', $record->id)
            ->get()
            ->pluck('person')
            ->filter()
            ->values();

        return self::peopleLinks($people);
    }

    private static function careSummary(Person $record): HtmlString
    {
        $total = ChurchProfile::query()
            ->where('shepherd_id', $record->id)
            ->count();

        $dormant = ChurchProfile::query()
            ->where('shepherd_id', $record->id)
            ->where('status', 'Dormant')
            ->count();

        $newOnes = ChurchProfile::query()
            ->where('shepherd_id', $record->id)
            ->where('status', 'New One')
            ->count();

        $introduced = ChurchProfile::query()
            ->where('introduced_by_id', $record->id)
            ->count();

        return new HtmlString(
            '<div class="grid gap-3 md:grid-cols-4">'
            . self::metricBox('Shepherded', $total, 'border-primary-200 bg-primary-50 text-primary-800 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200')
            . self::metricBox('Dormant', $dormant, 'border-gray-300 bg-gray-50 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200')
            . self::metricBox('New Ones', $newOnes, 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200')
            . self::metricBox('Introduced', $introduced, 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200')
            . '</div>'
        );
    }

    private static function metricBox(string $label, int $count, string $tone): string
    {
        return '<div class="rounded-xl border p-4 text-center ' . e($tone) . '">'
            . '<p class="text-2xl font-bold">' . e((string) $count) . '</p>'
            . '<p class="mt-1 text-xs font-semibold uppercase tracking-wide opacity-75">' . e($label) . '</p>'
            . '</div>';
    }

    private static function peopleTableLink(string $label, array $filters): HtmlString
    {
        $queryFilters = [];

        foreach ($filters as $filter => $value) {
            $queryFilters[$filter] = [
                'value' => (string) $value,
            ];
        }

        $url = PersonResource::getUrl('index') . '?' . http_build_query([
            'filters' => $queryFilters,
        ]);

        return new HtmlString(
            '<a href="' . e($url) . '" class="inline-flex rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">'
            . e($label)
            . '</a>'
        );
    }

    private static function statusTone(?string $status): string
    {
        return match ($status) {
            'Active' => 'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200',
            'Full-Timer' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200',
            'New One' => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200',
            'Gospel Friend' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200',
            'Dormant' => 'border-gray-300 bg-gray-50 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200',
            'Moved' => 'border-purple-200 bg-purple-50 text-purple-800 dark:border-purple-900 dark:bg-purple-950 dark:text-purple-200',
            'Deceased' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200',
            default => 'border-gray-300 bg-gray-50 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200',
        };
    }

    private static function none(): HtmlString
    {
        return new HtmlString(
            '<span class="block rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">'
            . 'Not recorded'
            . '</span>'
        );
    }
}
