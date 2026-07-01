<?php

namespace App\Filament\Resources\Households\Tables;

use App\Filament\Pages\FamilyTree;
use Filament\Actions\Action;
use App\Models\Household;
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
                    ->label('Household')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('head.display_name')
                    ->label('Head')
                    ->searchable(['firstname', 'lastname'])
                    ->sortable(),

                TextColumn::make('members_count')
                    ->label('Members')
                    ->getStateUsing(fn (Household $record): int => $record->members()->count())
                    ->sortable(),

                TextColumn::make('locality')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('address')
                    ->limit(40)
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
    Action::make('viewHeadFamilyTree')
        ->label("Head's Family Tree")
        ->icon('heroicon-o-user-group')
        ->visible(fn (Household $record): bool => filled($record->household_head_id))
        ->url(fn (Household $record): string => FamilyTree::getUrl([
            'personId' => $record->household_head_id,
        ])),

    ViewAction::make(),
    EditAction::make(),
])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
