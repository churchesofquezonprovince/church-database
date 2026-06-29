<?php

namespace App\Filament\Resources\Households\Schemas;

use App\Models\Person;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HouseholdForm
{
    public static function configure(Schema $schema): Schema
    {
/*
        return $schema
            ->components([
                TextInput::make('household_name')
                    ->required(),
                TextInput::make('head_of_household_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('address')
                    ->default(null),
                TextInput::make('locality')
                    ->default(null),
                Textarea::make('remarks')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
*/

return $schema
    ->components([

        Section::make('Household Information')
            ->schema([

                Grid::make(2)
                    ->schema([

                        TextInput::make('household_name')
                            ->required()
                            ->maxLength(150),

                        Select::make('head_of_household_id')
                            ->label('Head of Household')
                            ->relationship('head', 'lastname')
                            ->getOptionLabelFromRecordUsing(
                                fn (Person $record) =>
                                    "{$record->lastname}, {$record->firstname}"
                            )
                            ->searchable()
                            ->preload(),

                        TextInput::make('locality'),

                        TextInput::make('address')
                            ->columnSpanFull(),

                        Textarea::make('remarks')
                            ->columnSpanFull(),

                    ]),

            ]),

    ]);


    }
}
