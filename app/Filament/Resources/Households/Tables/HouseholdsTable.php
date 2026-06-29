<?php

namespace App\Filament\Resources\Households\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HouseholdsTable
{
    public static function configure(Table $table): Table
    {
        return $table

->columns([

    TextColumn::make('household_name')
        ->searchable()
        ->sortable(),

    TextColumn::make('head.lastname')
        ->label('Head')
        ->formatStateUsing(fn ($record) =>
            $record->head
                ? "{$record->head->lastname}, {$record->head->firstname}"
                : '-'
        )
        ->searchable(),

    TextColumn::make('locality')
        ->searchable(),

    TextColumn::make('members_count')
        ->counts('members')
        ->label('Members')
        ->sortable(),

]);


/*
            ->columns([
                TextColumn::make('household_name')
                    ->searchable(),
                TextColumn::make('head_of_household_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('locality')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
*/



    }
}
