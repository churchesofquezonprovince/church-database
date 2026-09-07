<?php

namespace App\Filament\Resources\People\Schemas;

use App\Models\Locality;
use App\Models\Person;
use App\Models\ProvinceSetting;
use App\Support\ChurchProfileOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PersonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->description('Basic identity and contact information of the person.')
                    ->schema([
                        TextInput::make('firstname')
                            ->label('First Name')
                            ->required()
                            ->live(onBlur: true)
                            ->maxLength(100)
                            ->placeholder('Juan'),

                        TextInput::make('middlename')
                            ->label('Middle Name')
                            ->maxLength(100)
                            ->placeholder('Reyes'),

                        TextInput::make('lastname')
                            ->label('Last Name')
                            ->required()
                            ->live(onBlur: true)
                            ->maxLength(100)
                            ->placeholder('Santos'),

                        TextInput::make('suffix')
                            ->label('Suffix')
                            ->maxLength(20)
                            ->placeholder('Jr., Sr., III'),

                        TextInput::make('nickname')
                            ->label('Nickname')
                            ->maxLength(100)
                            ->placeholder('Optional'),

                        Select::make('sex')
                            ->label('Sex')
                            ->options([
                                'Male' => 'Male',
                                'Female' => 'Female',
                            ])
                            ->required()
                            ->native(false),

                        DatePicker::make('birthdate')
                            ->label('Birthdate')
                            ->live()
                            ->maxDate(now())
                            ->helperText('Used to automatically calculate the church category and detect duplicate records.'),

                        Placeholder::make('duplicate_person_warning')
                            ->label('')
                            ->content(fn ($get, $livewire): HtmlString => self::duplicatePersonWarning($get, $livewire))
                            ->visible(fn ($get, $livewire): bool => self::duplicatePersonFromForm($get, $livewire) !== null)
                            ->columnSpanFull(),

                        TextInput::make('birthplace')
                            ->label('Birthplace')
                            ->maxLength(255)
                            ->placeholder('Lucena, Pagbilao, Tayabas'),

                        TextInput::make('contact_number')
                            ->label('Contact Number')
                            ->tel()
                            ->rules(['nullable', 'regex:/^[0-9+()\-\s]+$/'])
                            ->maxLength(50)
                            ->placeholder('09XXXXXXXXX'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('name@example.com'),

                        TextInput::make('facebook_account')
                            ->label('Facebook Account')
                            ->maxLength(255)
                            ->placeholder('Profile URL, username, or Facebook name')
                            ->helperText('Examples: facebook.com/juan.santos, @juan.santos, or Juan Santos'),
                    ])
                    ->columns(2),

                Section::make('Family and Household')
                    ->description('Link this person to their spouse, household, parents, or guardian.')
                    ->schema([
                        Select::make('spouse_id')
                            ->label('Spouse')
                            ->options(fn ($livewire): array => self::personOptionsExceptCurrent($livewire->record?->id))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select spouse if recorded'),

                        Select::make('household_id')
                            ->label('Household')
                            ->relationship(
                                name: 'household',
                                titleAttribute: 'household_name',
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select household'),

                        Repeater::make('parentRelationships')
                            ->label('Parents / Guardian')
                            ->relationship('parentRelationships')
                            ->schema([
                                Select::make('relationship')
                                    ->label('Relationship')
                                    ->options([
                                        'Father' => 'Father',
                                        'Mother' => 'Mother',
                                        'Guardian' => 'Guardian',
                                    ])
                                    ->required()
                                    ->native(false),

                                Select::make('parent_id')
                                    ->label('Existing Person')
                                    ->options(fn (): array => self::parentPersonSearchOptions())
                                    ->getSearchResultsUsing(fn (string $search): array => self::parentPersonSearchOptions($search))
                                    ->getOptionLabelUsing(fn ($value): ?string => self::parentPersonLabel($value))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('Search name, nickname, locality, or contact number')
                                    ->helperText('Search any part of the name, nickname, locality, or contact number.'),

                                TextInput::make('parent_name')
                                    ->label('Parent / Guardian Name')
                                    ->maxLength(255)
                                    ->helperText('Use this if the parent or guardian is not yet encoded.'),
                            ])
                            ->columns(3)
                            ->default([])
                            ->defaultItems(0)
                            ->addActionLabel('Add Parent / Guardian')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['relationship'] ?? 'Parent / Guardian')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Address')
                    ->description('Home address, permanent address, and optional map coordinates.')
                    ->schema([
                        TextInput::make('geocoordinates')
                            ->label('GPS Coordinates')
                            ->maxLength(255)
                            ->placeholder('14.0642, 121.5540'),

                        Textarea::make('home_address')
                            ->label('Home Address')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('permanent_address')
                            ->label('Permanent Address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Church Information')
                    ->description('Locality, church status, shepherding group, shepherd, and gospel contact information.')
                    ->schema([
                        Select::make('locality_id')
                            ->label('Locality')
                            ->options(function (): array {
                                $provinceId = ProvinceSetting::query()
                                    ->value('primary_province_id');

                                if (! $provinceId) {
                                    return [];
                                }

                                return Locality::query()
                                    ->where('province_id', $provinceId)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select locality')
                            ->helperText('Shows active Localities configured for the Primary Province.'),

                        Section::make('Church Profile')
                            ->relationship('churchProfile')
                            ->schema([
                                TextInput::make('category')
                                    ->label('Category')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Automatically calculated from birthdate / age after saving.'),

                                Select::make('status')
                                    ->label('Status')
                                    ->options(ChurchProfileOptions::statuses())
                                    ->default('Active')
                                    ->required()
                                    ->native(false),

                                Select::make('baptism_year')
                                    ->label('Baptism Year')
                                    ->options(self::yearOptions())
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('Unknown year')
                                    ->helperText('Use this if the exact baptism date is not known.'),

                                Select::make('baptism_month')
                                    ->label('Baptism Month')
                                    ->options(self::monthOptions())
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('Unknown month')
                                    ->disabled(fn ($get): bool => blank($get('baptism_year'))),

                                Select::make('baptism_day')
                                    ->label('Baptism Day')
                                    ->options(fn ($get): array => self::dayOptions($get('baptism_year'), $get('baptism_month')))
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('Unknown day')
                                    ->disabled(fn ($get): bool => blank($get('baptism_year')) || blank($get('baptism_month')))
                                    ->helperText('Optional. Leave blank if only the month or year is known.'),

                                Select::make('service')
                                    ->label('Shepherding Groups')
                                    ->options(ChurchProfileOptions::shepherdingServices())
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('Select one or more shepherding groups'),

                                Select::make('shepherd_id')
                                    ->label('Shepherd')
                                    ->options(fn (): array => self::personOptions())
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('Select shepherd'),

                                Select::make('introduced_by_id')
                                    ->label('Introduced By')
                                    ->options(fn (): array => self::personOptions())
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('Select introducer'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Education / Work')
                    ->description('School level, course or strand, occupation, and workplace.')
                    ->relationship('educationProfile')
                    ->schema([
                        Select::make('grade_level')
                            ->label('Grade Level')
                            ->options(self::gradeLevelOptions())
                            ->searchable()
                            ->native(false)
                            ->placeholder('Select grade / year level'),

                        TextInput::make('course_strand')
                            ->label('Course / Strand')
                            ->maxLength(100)
                            ->placeholder('STEM, ABM, BSEE, BSIT'),

                        TextInput::make('occupation')
                            ->label('Occupation')
                            ->maxLength(100)
                            ->placeholder('Student, Teacher, Engineer'),

                        TextInput::make('school_workplace')
                            ->label('School')
                            ->datalist([
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
                            ])
                            ->maxLength(255)
                            ->placeholder('Select or type school name'),

                        TextInput::make('workplace')
                            ->label('Workplace')
                            ->maxLength(255)
                            ->placeholder('Workplace or company name'),
                    ])
                    ->columns(2),

                Section::make('Emergency Contact')
                    ->description('Person to contact in case of emergency.')
                    ->schema([
                        Select::make('emergency_contact_id')
                            ->label('Emergency Contact')
                            ->options(fn ($livewire): array => self::personOptionsExceptCurrent($livewire->record?->id))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select emergency contact'),

                        TextInput::make('emergency_contact_relationship')
                            ->label('Relationship')
                            ->maxLength(100)
                            ->placeholder('Father, Mother, Spouse, Sibling'),

                        TextInput::make('emergency_contact_number')
                            ->label('Emergency Contact Number')
                            ->tel()
                            ->maxLength(50)
                            ->placeholder('09XXXXXXXXX'),
                    ])
                    ->columns(2),
            ]);
    }



    private static function parentPersonSearchOptions(?string $search = ''): array
    {
        $search = trim((string) $search);

        return Person::query()
            ->when(filled($search), function ($query) use ($search): void {
                collect(preg_split('/\s+/', $search))
                    ->filter()
                    ->each(function (string $term) use ($query): void {
                        $like = '%' . $term . '%';

                        $query->where(function ($query) use ($like): void {
                            $query
                                ->where('firstname', 'like', $like)
                                ->orWhere('middlename', 'like', $like)
                                ->orWhere('lastname', 'like', $like)
                                ->orWhere('suffix', 'like', $like)
                                ->orWhere('nickname', 'like', $like)
                                ->orWhere('locality', 'like', $like)
                                ->orWhere('contact_number', 'like', $like);
                        });
                    });
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(75)
            ->get()
            ->mapWithKeys(fn (Person $person): array => [
                $person->id => self::parentPersonOptionLabel($person),
            ])
            ->all();
    }

    private static function parentPersonLabel(mixed $id): ?string
    {
        if (blank($id)) {
            return null;
        }

        $person = Person::query()->find($id);

        return $person
            ? self::parentPersonOptionLabel($person)
            : null;
    }

    private static function parentPersonOptionLabel(Person $person): string
    {
        return collect([
            $person->display_name,
            filled($person->nickname) ? 'Nickname: ' . $person->nickname : null,
            $person->locality,
            $person->contact_number,
        ])
            ->filter(fn ($value): bool => filled($value))
            ->implode(' — ');
    }

    private static function duplicatePersonWarning($get, $livewire): HtmlString
    {
        $duplicate = self::duplicatePersonFromForm($get, $livewire);

        if (! $duplicate) {
            return new HtmlString('');
        }

        $name = e($duplicate->display_name);
        $url = e(\App\Filament\Resources\People\PersonResource::getUrl('edit', [
            'record' => $duplicate,
        ]));

        return new HtmlString(
            '<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">'
            . '<p class="font-bold">Possible duplicate person found.</p>'
            . '<p class="mt-1 text-sm">A person with the same first name, last name, and birthdate already exists: <strong>' . $name . '</strong></p>'
            . '<a href="' . $url . '" class="mt-3 inline-flex rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-500">Open existing record</a>'
            . '</div>'
        );
    }

    private static function duplicatePersonFromForm($get, $livewire): ?Person
    {
        $firstname = trim((string) $get('firstname'));
        $lastname = trim((string) $get('lastname'));
        $birthdate = $get('birthdate');

        if ($firstname === '' || $lastname === '' || blank($birthdate)) {
            return null;
        }

        $currentId = $livewire->record?->id ?? null;

        return Person::query()
            ->whereRaw('LOWER(firstname) = ?', [strtolower($firstname)])
            ->whereRaw('LOWER(lastname) = ?', [strtolower($lastname)])
            ->whereDate('birthdate', (string) $birthdate)
            ->when($currentId, fn ($query) => $query->where('id', '!=', $currentId))
            ->first();
    }

    private static function personOptions(): array
    {
        return Person::query()
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->mapWithKeys(fn (Person $person): array => [
                $person->id => $person->display_name,
            ])
            ->toArray();
    }

    private static function personOptionsExceptCurrent(?int $currentId): array
    {
        return Person::query()
            ->when($currentId, fn ($query) => $query->where('id', '!=', $currentId))
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->mapWithKeys(fn (Person $person): array => [
                $person->id => $person->display_name,
            ])
            ->toArray();
    }

    private static function gradeLevelOptions(): array
    {
        return collect([
            'Pre-School' => 'Pre-School',
            'Kinder I' => 'Kinder I',
            'Kinder II' => 'Kinder II',
        ])
            ->merge(
                collect(range(1, 12))
                    ->mapWithKeys(fn (int $i): array => ["Grade {$i}" => "Grade {$i}"])
            )
            ->merge([
                'College - Year 1' => 'College - Year 1',
                'College - Year 2' => 'College - Year 2',
                'College - Year 3' => 'College - Year 3',
                'College - Year 4' => 'College - Year 4',
                'College - Year 5' => 'College - Year 5',
                'Graduated' => 'Graduated',
                'Not Applicable' => 'Not Applicable',
            ])
            ->toArray();
    }
    private static function yearOptions(): array
    {
        $currentYear = (int) now()->format('Y');

        return collect(range($currentYear, 1900))
            ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
            ->toArray();
    }

    private static function monthOptions(): array
    {
        return [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    }

    private static function dayOptions(mixed $year, mixed $month): array
    {
        if (blank($year) || blank($month)) {
            return [];
        }

        $year = (int) $year;
        $month = (int) $month;

        if ($year < 1900 || $month < 1 || $month > 12) {
            return [];
        }

        $days = \Carbon\CarbonImmutable::create($year, $month, 1)->daysInMonth;

        return collect(range(1, $days))
            ->mapWithKeys(fn (int $day): array => [$day => (string) $day])
            ->toArray();
    }


}
