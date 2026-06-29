<?php

namespace App\Filament\Resources\People\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PersonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('firstname'),
                TextEntry::make('lastname'),
                TextEntry::make('nickname')
                    ->placeholder('-'),
                TextEntry::make('birthdate')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('locality')
                    ->placeholder('-'),
                TextEntry::make('permanent_address')
                    ->placeholder('-'),
                TextEntry::make('home_address')
                    ->placeholder('-'),
                TextEntry::make('geocoordinates')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('contact_number')
                    ->placeholder('-'),
                TextEntry::make('emergency_contact')
                    ->placeholder('-'),
                TextEntry::make('emergency_contact_number')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
