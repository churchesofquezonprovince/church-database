<?php

namespace App\Filament\Resources\People\Schemas;

use App\Filament\Resources\People\Pages\CreatePerson;
use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\Locality;
use App\Models\Person;
use App\Models\Province;
use App\Models\ProvinceSetting;
use App\Models\School;
use App\Support\ChurchProfileOptions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
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
                            ->helperText('Used to automatically calculate the church category.'),

                        Placeholder::make('duplicate_person_warning')
                            ->label('')
                            ->content(fn ($get, $livewire): HtmlString => self::duplicatePersonWarning($get, $livewire))
                            ->visible(fn ($get, $livewire): bool => self::hasDuplicateRecordFromForm($get, $livewire))
                            ->columnSpanFull(),

                        Checkbox::make('create_anyway')
                            ->label('Create this person anyway')
                            ->helperText(
                                'Use only when you have confirmed that this is a different person despite having the same first name and last name.'
                            )
                            ->default(false)
                            ->visible(
                                fn ($get, $livewire): bool =>
                                    $livewire instanceof CreatePerson
                                    && self::hasDuplicateRecordFromForm(
                                        $get,
                                        $livewire
                                    )
                            )
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
                                    ->options(
                                        fn ($livewire): array =>
                                            self::parentPersonSearchOptions(
                                                '',
                                                $livewire->record?->id
                                            )
                                    )
                                    ->getSearchResultsUsing(
                                        fn (
                                            string $search,
                                            $livewire
                                        ): array =>
                                            self::parentPersonSearchOptions(
                                                $search,
                                                $livewire->record?->id
                                            )
                                    )
                                    ->getOptionLabelUsing(fn ($value): ?string => self::parentPersonLabel($value))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(
                                        function (
                                            $state,
                                            $set
                                        ): void {
                                            if (blank($state)) {
                                                return;
                                            }

                                            $set(
                                                'gospel_contact_id',
                                                null
                                            );

                                            $set(
                                                'parent_name',
                                                null
                                            );
                                        }
                                    )
                                    ->placeholder('Search name, nickname, locality, or contact number')
                                    ->helperText('Search any part of the name, nickname, locality, or contact number.'),

                                Select::make('gospel_contact_id')
                                    ->label('Gospel Contact')
                                    ->options(
                                        fn ($livewire): array =>
                                            self::parentGospelContactSearchOptions(
                                                '',
                                                $livewire->record?->id
                                            )
                                    )
                                    ->getSearchResultsUsing(
                                        fn (
                                            string $search,
                                            $livewire
                                        ): array =>
                                            self::parentGospelContactSearchOptions(
                                                $search,
                                                $livewire->record?->id
                                            )
                                    )
                                    ->getOptionLabelUsing(
                                        fn ($value): ?string =>
                                            self::parentGospelContactLabel(
                                                $value
                                            )
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(
                                        function (
                                            $state,
                                            $set
                                        ): void {
                                            if (blank($state)) {
                                                return;
                                            }

                                            $contact =
                                                GospelContact::query()
                                                    ->find($state);

                                            if (! $contact) {
                                                return;
                                            }

                                            if (
                                                filled(
                                                    $contact->person_id
                                                )
                                            ) {
                                                $set(
                                                    'parent_id',
                                                    $contact->person_id
                                                );

                                                $set(
                                                    'gospel_contact_id',
                                                    null
                                                );

                                                $set(
                                                    'parent_name',
                                                    null
                                                );

                                                return;
                                            }

                                            $set(
                                                'parent_id',
                                                null
                                            );
                                        }
                                    )
                                    ->placeholder(
                                        'Search Gospel Contacts'
                                    )
                                    ->helperText(
                                        'Use this when the parent or guardian is not yet in the People Database.'
                                    ),

                                Hidden::make('parent_name'),

                                Placeholder::make(
                                    'legacy_parent_name_display'
                                )
                                    ->label(
                                        'Previously Recorded Parent / Guardian'
                                    )
                                    ->content(
                                        fn ($get): string =>
                                            trim(
                                                (string)
                                                    $get(
                                                        'parent_name'
                                                    )
                                            )
                                    )
                                    ->visible(
                                        fn ($get): bool =>
                                            blank(
                                                $get(
                                                    'parent_id'
                                                )
                                            )
                                            &&
                                            blank(
                                                $get(
                                                    'gospel_contact_id'
                                                )
                                            )
                                            &&
                                            filled(
                                                $get(
                                                    'parent_name'
                                                )
                                            )
                                    )
                                    ->helperText(
                                        'Legacy imported name. Select an Existing Person or Gospel Contact when the identity becomes known.'
                                    )
                                    ->columnSpanFull(),
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
                                $settings = ProvinceSetting::query()
                                    ->with('primaryProvince')
                                    ->first();

                                if (! $settings?->primary_province_id) {
                                    return [];
                                }

                                $options = [];

                                $primaryLocalities = Locality::query()
                                    ->where(
                                        'province_id',
                                        $settings->primary_province_id
                                    )
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();

                                if ($primaryLocalities !== []) {
                                    $options[
                                        $settings->primaryProvince?->name
                                            ?? 'Primary Province'
                                    ] = $primaryLocalities;
                                }

                                $outsideProvinces = Province::query()
                                    ->with([
                                        'localities' => fn ($query) =>
                                            $query
                                                ->where('is_active', true)
                                                ->orderBy('name'),
                                    ])
                                    ->where(
                                        'id',
                                        '!=',
                                        $settings->primary_province_id
                                    )
                                    ->whereHas(
                                        'localities',
                                        fn ($query) =>
                                            $query->where('is_active', true)
                                    )
                                    ->orderBy('name')
                                    ->get();

                                foreach ($outsideProvinces as $province) {
                                    $localities = $province->localities
                                        ->pluck('name', 'id')
                                        ->all();

                                    if ($localities === []) {
                                        continue;
                                    }

                                    $options[
                                        'Outside — ' . $province->name
                                    ] = $localities;
                                }

                                return $options;
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select locality')
                            ->helperText('Primary Province and configured outside-province Localities are grouped separately.'),

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
                                    ->helperText(
                                        'Optional. Year, month, and day may be recorded independently.'
                                    ),

                                Select::make('baptism_month')
                                    ->label('Baptism Month')
                                    ->options(self::monthOptions())
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('Unknown month')
                                    ->helperText(
                                        'Optional. A baptism month may be recorded even when the year is unknown.'
                                    ),

                                Select::make('baptism_day')
                                    ->label('Baptism Day')
                                    ->options(
                                        collect(range(1, 31))
                                            ->mapWithKeys(
                                                fn (int $day): array => [
                                                    $day => (string) $day,
                                                ]
                                            )
                                            ->all()
                                    )
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('Unknown day')
                                    ->helperText(
                                        'Optional. A baptism day may be recorded even when the year or month is unknown.'
                                    ),

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

                                Section::make('Contact Origin')
                                    ->description(
                                        'How this person first came into contact with the church or gospel work.'
                                    )
                                    ->schema([
                                        Select::make('contact_origin')
                                            ->label('How First Contacted')
                                            ->options(
                                                ChurchProfileOptions::contactOrigins()
                                            )
                                            ->searchable()
                                            ->native(false)
                                            ->placeholder('Select contact origin'),

                                        DatePicker::make('first_contact_date')
                                            ->label('First Contact Date')
                                            ->maxDate(now())
                                            ->helperText(
                                                'Optional. For a Church Kid, leave blank if there is no meaningful first contact date.'
                                            ),

                                        Select::make('introduced_by_id')
                                            ->label('Introduced By')
                                            ->options(
                                                fn ($livewire): array =>
                                                    self::personOptionsExceptCurrent(
                                                        $livewire->record?->id
                                                    )
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->placeholder('Select introducer')
                                            ->helperText(
                                                'Optional. The current person cannot be selected as their own introducer.'
                                            ),

                                        Textarea::make('contact_origin_details')
                                            ->label('Origin Details')
                                            ->rows(3)
                                            ->placeholder(
                                                'Optional details about how, where, or through whom the first contact happened.'
                                            )
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
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

                        Select::make('school_id')
                            ->label('School')
                            ->options(fn (): array => self::schoolOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select school'),

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



    private static function parentPersonSearchOptions(
        ?string $search = '',
        ?int $excludePersonId = null
    ): array {
        $search = trim((string) $search);

        return Person::query()
            ->when(
                $excludePersonId,
                fn ($query) =>
                    $query->where(
                        'id',
                        '!=',
                        $excludePersonId
                    )
            )
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

    private static function parentGospelContactSearchOptions(
        ?string $search = '',
        ?int $excludePersonId = null
    ): array {
        $search = trim(
            (string) $search
        );

        return GospelContact::query()
            ->with('person')
            ->when(
                $excludePersonId,
                fn ($query) =>
                    $query->where(
                        function ($query) use (
                            $excludePersonId
                        ): void {
                            $query
                                ->whereNull('person_id')
                                ->orWhere(
                                    'person_id',
                                    '!=',
                                    $excludePersonId
                                );
                        }
                    )
            )
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    collect(
                        preg_split(
                            '/\\s+/',
                            $search
                        )
                    )
                        ->filter()
                        ->each(
                            function (
                                string $term
                            ) use (
                                $query
                            ): void {
                                $like =
                                    '%' . $term . '%';

                                $query->where(
                                    function (
                                        $query
                                    ) use (
                                        $like
                                    ): void {
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
                                                'contact_number',
                                                'like',
                                                $like
                                            )
                                            ->orWhereHas(
                                                'person',
                                                function (
                                                    $personQuery
                                                ) use (
                                                    $like
                                                ): void {
                                                    $personQuery
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
                                                        );
                                                }
                                            );
                                    }
                                );
                            }
                        );
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(75)
            ->get()
            ->mapWithKeys(
                fn (
                    GospelContact $contact
                ): array => [
                    $contact->id =>
                        self::parentGospelContactOptionLabel(
                            $contact
                        ),
                ]
            )
            ->all();
    }

    private static function parentGospelContactLabel(
        mixed $id
    ): ?string {
        if (blank($id)) {
            return null;
        }

        $contact =
            GospelContact::query()
                ->with('person')
                ->find($id);

        return $contact
            ? self::parentGospelContactOptionLabel(
                $contact
            )
            : null;
    }

    private static function parentGospelContactOptionLabel(
        GospelContact $contact
    ): string {
        return collect([
            $contact->display_name,

            filled(
                $contact->effective_locality
            )
                ? $contact->effective_locality
                : null,

            filled(
                $contact->effective_contact_number
            )
                ? $contact->effective_contact_number
                : null,

            filled($contact->person_id)
                ? 'Already linked to People'
                : 'Gospel Contact',
        ])
            ->filter(
                fn ($value): bool =>
                    filled($value)
            )
            ->implode(' — ');
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

    private static function duplicatePersonWarning(
        $get,
        $livewire
    ): HtmlString {
        $matches =
            self::duplicateRecordsFromForm(
                $get,
                $livewire
            );

        if (
            ! $matches['person']
            && $matches['gospel']->isEmpty()
            && $matches['campus']->isEmpty()
        ) {
            return new HtmlString('');
        }

        $items = [];

        if ($matches['person']) {
            $person = $matches['person'];

            $name = e(
                $person->display_name
            );

            $url = e(
                \App\Filament\Resources\People\PersonResource::getUrl(
                    'edit',
                    [
                        'record' => $person,
                    ]
                )
            );

            $items[] =
                '<li>'
                . '<strong>People Database:</strong> '
                . $name
                . ' '
                . '<a href="'
                . $url
                . '" class="font-semibold underline">'
                . 'Open existing record'
                . '</a>'
                . '</li>';
        }

        foreach (
            $matches['gospel']
            as $contact
        ) {
            $items[] =
                '<li>'
                . '<strong>Gospel Contact:</strong> '
                . e($contact->display_name)
                . ' '
                . '<span class="text-xs">'
                . '(not yet linked to People)'
                . '</span>'
                . '</li>';
        }

        foreach (
            $matches['campus']
            as $contact
        ) {
            $items[] =
                '<li>'
                . '<strong>Campus Contact:</strong> '
                . e($contact->display_name)
                . ' '
                . '<span class="text-xs">'
                . '(not yet linked to People)'
                . '</span>'
                . '</li>';
        }

        $guidance =
            $livewire instanceof CreatePerson
                ? '<p class="mt-2 text-sm">'
                    . 'If a matching Gospel or Campus Contact is the same person, '
                    . 'prefer adding or linking that existing contact to the People Database. '
                    . 'If these are genuinely different people, use '
                    . '<strong>Create this person anyway</strong> below.'
                    . '</p>'
                : '<p class="mt-2 text-sm">'
                    . 'Review the existing People, Gospel Contact, or Campus Contact record '
                    . 'before saving changes to this person\'s name.'
                    . '</p>';

        return new HtmlString(
            '<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">'
            . '<p class="font-bold">'
            . 'Possible matching record found.'
            . '</p>'
            . '<p class="mt-1 text-sm">'
            . 'The same first name and last name already exist:'
            . '</p>'
            . '<ul class="mt-2 list-disc space-y-1 pl-5 text-sm">'
            . implode('', $items)
            . '</ul>'
            . $guidance
            . '</div>'
        );
    }

    private static function hasDuplicateRecordFromForm(
        $get,
        $livewire
    ): bool {
        $matches =
            self::duplicateRecordsFromForm(
                $get,
                $livewire
            );

        return
            $matches['person'] !== null
            || $matches['gospel']->isNotEmpty()
            || $matches['campus']->isNotEmpty();
    }

    private static function duplicateRecordsFromForm(
        $get,
        $livewire
    ): array {
        $firstname = trim(
            (string) $get('firstname')
        );

        $lastname = trim(
            (string) $get('lastname')
        );

        if (
            $firstname === ''
            || $lastname === ''
        ) {
            return [
                'person' => null,
                'gospel' => collect(),
                'campus' => collect(),
            ];
        }

        $firstnameKey =
            strtolower($firstname);

        $lastnameKey =
            strtolower($lastname);

        $currentId =
            $livewire->record?->id
            ?? null;

        $person = Person::query()
            ->whereRaw(
                'LOWER(firstname) = ?',
                [$firstnameKey]
            )
            ->whereRaw(
                'LOWER(lastname) = ?',
                [$lastnameKey]
            )
            ->when(
                $currentId,
                fn ($query) =>
                    $query->where(
                        'id',
                        '!=',
                        $currentId
                    )
            )
            ->first();

        $gospel = GospelContact::query()
            ->whereNull('person_id')
            ->whereRaw(
                'LOWER(firstname) = ?',
                [$firstnameKey]
            )
            ->whereRaw(
                'LOWER(lastname) = ?',
                [$lastnameKey]
            )
            ->orderBy('id')
            ->limit(5)
            ->get();

        $campus = CampusContact::query()
            ->whereNull('person_id')
            ->whereRaw(
                'LOWER(firstname) = ?',
                [$firstnameKey]
            )
            ->whereRaw(
                'LOWER(lastname) = ?',
                [$lastnameKey]
            )
            ->orderBy('id')
            ->limit(5)
            ->get();

        return [
            'person' => $person,
            'gospel' => $gospel,
            'campus' => $campus,
        ];
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

    private static function schoolOptions(): array
    {
        return School::query()
            ->with('province:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (School $school): array {
                $location = collect([
                    $school->city_municipality,
                    $school->province?->name,
                ])
                    ->filter(fn ($value): bool => filled($value))
                    ->implode(', ');

                $label = $school->name;

                if (filled($location)) {
                    $label .= ' — ' . $location;
                }

                return [$school->id => $label];
            })
            ->toArray();
    }

    private static function gradeLevelOptions(): array
    {
        return \App\Support\MeetingFormDatabaseFieldRegistry::options(
            'grade_level'
        );
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
