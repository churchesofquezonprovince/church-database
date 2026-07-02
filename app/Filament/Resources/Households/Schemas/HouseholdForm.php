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
                    ->description('Create or update a household record. Members are linked from each person profile.')
                    ->schema([
                        TextInput::make('household_name')
                            ->label('Household Name')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Santos Family'),

                        Select::make('household_head_id')
                            ->label('Household Head')
                            ->options(fn (): array => self::personOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select household head')
                            ->helperText('This person will be used for the household family tree shortcut.'),

                        TextInput::make('locality')
                            ->label('Locality')
                            ->required()
                            ->maxLength(150)
                            ->helperText('Used together with household name to detect duplicate households.')
                            ->placeholder('Lucena, Pagbilao, Tayabas'),

                        Textarea::make('address')
                            ->label('Address')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('remarks')
                            ->label('Remarks')
                            ->rows(3)
                            ->placeholder('Optional notes about this household')
                            ->columnSpanFull(),
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
}
