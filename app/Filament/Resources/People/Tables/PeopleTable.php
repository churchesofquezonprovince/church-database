<?php

namespace App\Filament\Resources\People\Tables;

use App\Filament\Pages\FamilyTree;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PeopleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('firstname')
                    ->searchable()
                    ->sortable(),
TextColumn::make('middlename')
    ->label('Middle Name')
    ->searchable(),
                TextColumn::make('lastname')
                    ->searchable()
                    ->sortable(),
TextColumn::make('suffix')
    ->searchable(),
                TextColumn::make('nickname')
                    ->searchable(),
                TextColumn::make('birthdate')
                    ->date()
                    ->sortable(),
                TextColumn::make('locality')
                    ->searchable()
                    ->sortable(),
//                TextColumn::make('permanent_address')
//                    ->searchable(),
                TextColumn::make('home_address')
                    ->searchable(),

    TextColumn::make('church.status')
        ->label('Status')
        ->badge()
        ->sortable()
        ->colors([
            'success' => 'Active',
            'danger' => 'Dormant',
        ]),


                TextColumn::make('geocoordinates')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('contact_number')
                    ->searchable(),
                TextColumn::make('emergency_contact')
                    ->searchable(),
//                TextColumn::make('emergency_contact_number')
//                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])

->recordActions([
    Action::make('viewFamilyTree')
        ->label('View Family Tree')
        ->icon('heroicon-o-user-group')
        ->url(fn ($record): string => FamilyTree::getUrl([
            'personId' => $record->id,
        ])),

    EditAction::make(),
])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
