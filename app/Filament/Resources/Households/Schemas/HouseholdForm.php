<?php

namespace App\Filament\Resources\Households\Schemas;

use App\Models\Person;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HouseholdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Household Information')
                    ->schema([
                        TextInput::make('household_name')
                            ->label('Household Name')
                            ->required()
                            ->maxLength(150),

                        Select::make('household_head_id')
                            ->label('Household Head')
                            ->relationship(
                                name: 'head',
                                titleAttribute: 'lastname',
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Person $record): string => $record->display_name
                            )
                            ->searchable()
                            ->preload(),

                        TextInput::make('locality')
                            ->maxLength(150),

                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('remarks')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
