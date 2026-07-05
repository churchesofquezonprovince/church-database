<?php

namespace App\Filament\Resources\People\Schemas;

use App\Forms\Components\PersonSelect;
use App\Models\Person;
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

                                PersonSelect::relationship(
                                    field: 'parent_id',
                                    relationship: 'parent',
                                    label: 'Existing Person',
                                )
                                    ->helperText('Use this if the parent or guardian is already encoded.'),

                                TextInput::make('parent_name')
                                    ->label('Parent / Guardian Name')
                                    ->maxLength(255)
                                    ->helperText('Use this if the parent or guardian is not yet encoded.'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Parent / Guardian')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['relationship'] ?? 'Parent / Guardian')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Address')
                    ->description('Locality, home address, permanent address, and optional map coordinates.')
                    ->schema([
                        TextInput::make('locality')
                            ->label('Locality')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Lucena, Pagbilao, Tayabas'),

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
                    ->description('Church status, shepherding group, shepherd, and gospel contact information.')
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

                        DatePicker::make('baptism_date')
                            ->label('Baptism Date'),

                        Select::make('service')
                            ->label('Shepherding Groups')
                            ->options(ChurchProfileOptions::shepherdingServices())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select one or more shepherding groups')
                            ->afterStateHydrated(function ($component, $state): void {
                                if (blank($state)) {
                                    $component->state([]);

                                    return;
                                }

                                if (is_array($state)) {
                                    $component->state($state);

                                    return;
                                }

                                $component->state(
                                    collect(explode(',', (string) $state))
                                        ->map(fn (string $group): string => trim($group))
                                        ->filter()
                                        ->values()
                                        ->all()
                                );
                            })
                            ->dehydrateStateUsing(fn ($state): ?string => collect($state ?? [])
                                ->map(fn ($group): string => trim((string) $group))
                                ->filter()
                                ->unique()
                                ->implode(', ') ?: null),

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
                            ->label('School / Workplace')
                            ->maxLength(255)
                            ->placeholder('School or workplace name'),
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
}
