<?php

namespace App\Filament\Resources\Households\Schemas;

use App\Filament\Resources\People\PersonResource;
use Illuminate\Support\HtmlString;
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
    ->state(function ($record): HtmlString {
        if ($record->members->isEmpty()) {
            return new HtmlString('No members recorded');
        }

        $links = $record->members
            ->sortBy('lastname')
            ->map(function ($person): string {
                $url = PersonResource::getUrl('view', [
                    'record' => $person->id,
                ]);

                return '<a href="' . e($url) . '" class="text-primary-600 hover:underline dark:text-primary-400">'
                    . e($person->display_name)
                    . '</a>';
            })
            ->join('<br>');

        return new HtmlString($links);
    })
    ->html()
    ->columnSpanFull(),


                    ]),
            ]);
    }
}
