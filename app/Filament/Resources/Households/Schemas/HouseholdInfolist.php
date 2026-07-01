<?php

namespace App\Filament\Resources\Households\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HouseholdInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Household Information')
                    ->schema([
                        TextEntry::make('household_name')
                            ->label('Household Name'),

                        TextEntry::make('head.display_name')
                            ->label('Household Head')
                            ->placeholder('None recorded'),

                        TextEntry::make('locality')
                            ->placeholder('None recorded'),

                        TextEntry::make('address')
                            ->placeholder('None recorded')
                            ->columnSpanFull(),

                        TextEntry::make('remarks')
                            ->placeholder('None recorded')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Members')
                    ->schema([
                        TextEntry::make('members_count')
                            ->label('Total Members')
                            ->state(fn ($record): int => $record->members()->count()),

                        TextEntry::make('members_list')
                            ->label('Members')
                            ->state(fn ($record): string => $record->members
                                ->map(fn ($person) => $person->display_name)
                                ->join(', '))
                            ->placeholder('No members recorded')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
