<?php

namespace App\Filament\Resources\People\Schemas;

use App\Forms\Components\PersonSelect;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;

class PersonForm
{
    public static function configure(Schema $schema): Schema
    {

return $schema
    ->components([

        Section::make('Personal Information')
            ->schema([

                Grid::make(2)
                    ->schema([


TextInput::make('firstname')
    ->label('First Name')
    ->required()
    ->maxLength(100),

TextInput::make('middlename')
    ->label('Middle Name')
    ->maxLength(100),

TextInput::make('lastname')
    ->label('Last Name')
    ->required()
    ->maxLength(100),

TextInput::make('suffix')
    ->label('Suffix')
    ->placeholder('Jr., Sr., III'),

TextInput::make('nickname')
->label('Nickname')
->columnSpan(1),

Select::make('sex')
    ->options([
        'Male' => 'Male',
        'Female' => 'Female',
    ])
    ->required(),

DatePicker::make('birthdate')
->label('Birthdate'),

TextInput::make('birthplace')
->placeholder('Lucena, Pagbilao, Tayabas')
->columnSpan(1),


                        TextInput::make('contact_number')
                            ->label('Contact Number')
                            ->tel(),

                        TextInput::make('email')
                            ->email(),

                    ]),


Select::make('spouse_id')
    ->label('Spouse')
    ->relationship('spouse', 'lastname')
    ->getOptionLabelFromRecordUsing(
        fn ($record) => $record->full_name
    )
    ->searchable()
    ->preload()
    ->nullable()

->options(function ($livewire) {

    $id = $livewire->record?->id;

    return \App\Models\Person::query()
        ->when($id, fn ($q) => $q->where('id', '!=', $id))
        ->orderBy('lastname')
        ->orderBy('firstname')
        ->get()
        ->mapWithKeys(fn ($person) => [
            $person->id => "{$person->lastname}, {$person->firstname}"
        ]);

}),


                TextInput::make('locality')
    		->placeholder('Lucana, Pagbilao, Tayabas'),
//		->columnSpan(1),

                TextInput::make('home_address')
                    ->columnSpanFull(),

                TextInput::make('permanent_address')
                    ->columnSpanFull(),

                TextInput::make('geocoordinates')
                    ->label('GPS Coordinates'),

Select::make('emergency_contact_id')
    ->label('Emergency Contact')
    ->relationship(
        name: 'emergencyContact',
        titleAttribute: 'lastname'
    )
    ->getOptionLabelFromRecordUsing(
        fn ($record) => $record->display_name
    )
    ->searchable()
    ->preload(),

                TextInput::make('emergency_contact_number')
                    ->tel(),

            ]),
	


Section::make('Church Information')
->relationship('churchProfile')
    ->schema([

        Grid::make(2)
            ->schema([

Select::make('category')
    ->options([
        'Children' => 'Children',
        'Young People' => 'Young People',
        'Collegian' => 'Collegian',
        'Young Adult' => 'Young Adult',
        'Middle Age' => 'Middle Age',
        'Elderly' => 'Elderly',
    ])
    ->searchable()
    ->required(),


                Select::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Dormant' => 'Dormant',
                    ])
                    ->default('Active')
                    ->required(),

                DatePicker::make('baptism_date'),


Select::make('service')
    ->options([
        'Children (Toddler-Kinder)' => 'Children (Toddler-Kinder)',
        'Children (G1-G4)' => 'Children (G1-G4)',
        'Junior Young People (G5-G7)' => 'Junior Young People (G5-G7)',
        'Young People (G8-G10)' => 'Young People (G8-G10)',
        'Collegian (G11-C1)' => 'Collegian (G11-C1)',
        'Collegian (C2-Graduating)' => 'Collegian (C2-Graduating)',
    ])
    ->searchable(),


                Select::make('shepherd_id')
                    ->label('Shepherd')
                    ->relationship(
                        name: 'shepherd',
                        titleAttribute: 'firstname'
                    )
                    ->searchable()
                    ->preload(),

                Select::make('introduced_by_id')
                    ->label('Introduced By')
                    ->relationship(
                        name: 'introducedBy',
                        titleAttribute: 'firstname'
                    )
                    ->searchable()
                    ->preload(),

            ]),
    ]),

Section::make('Education / Work')
->relationship('educationProfile')
    ->schema([

        Grid::make(2)
            ->schema([


Select::make('grade_level')
    ->options(
        collect([
            'Pre-School',
            'Kinder I',
            'Kinder II',
        ])
        ->merge(
            collect(range(1, 12))
                ->mapWithKeys(fn ($i) => ["Grade $i" => "Grade $i"])
        )
        ->merge([
            'College - Year 1' => 'College - Year 1',
            'College - Year 2' => 'College - Year 2',
            'College - Year 3' => 'College - Year 3',
            'College - Year 4' => 'College - Year 4',
        ])
        ->toArray()
    )
    ->searchable(),


                TextInput::make('course_strand')
                    ->label('Course / Strand')
                    ->maxLength(100),

                TextInput::make('occupation')
                    ->maxLength(100),

                TextInput::make('school_workplace')
                    ->label('School / Workplace')
                    ->maxLength(255),

            ]),
    ]),

Section::make('Parents / Guardian')
    ->schema([

Repeater::make('parentRelationships')
    ->relationship('parentRelationships')
            ->schema([


Grid::make(2)
    ->schema([
/*
        PersonSelect::make('parent_id')
            ->label('Existing Person')
            ->relationship('parent')
            ->getOptionLabelFromRecordUsing(
                fn ($record) => $record->full_name
            )
            ->searchable()
            ->preload(),
 */
PersonSelect::relationship(
    field: 'parent_id',
    relationship: 'parent',
    label: 'Existing Person',
),

        TextInput::make('parent_name')
            ->label('Or Enter Parent Name')
            ->maxLength(150),

        Select::make('relationship')
            ->options([
                'Father' => 'Father',
                'Mother' => 'Mother',
                'Guardian' => 'Guardian',
            ])
            ->required(),

    ])

            ])
            ->defaultItems(0)
            ->addActionLabel('Add Parent'),

    ])
//
]);

    }
}
