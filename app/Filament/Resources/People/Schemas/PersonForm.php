<?php

namespace App\Filament\Resources\People\Schemas;

use App\Forms\Components\PersonSelect;
use App\Models\Person;
use App\Support\ChurchProfileOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                            ->maxLength(100)
                            ->placeholder('Juan'),

                        TextInput::make('middlename')
                            ->label('Middle Name')
                            ->maxLength(100)
                            ->placeholder('Reyes'),

                        TextInput::make('lastname')
                            ->label('Last Name')
                            ->required()
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
                            ->maxDate(now())
                            ->helperText('Used to automatically calculate the church category and detect duplicate records.'),

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
                            ->label('Shepherding Group')
                            ->options(ChurchProfileOptions::shepherdingServices())
                            ->searchable()
                            ->native(false)
                            ->placeholder('Select shepherding group'),

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
