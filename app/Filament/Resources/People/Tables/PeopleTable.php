<?php

namespace App\Filament\Resources\People\Tables;

use App\Support\ChurchProfileOptions;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
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
->toggleable(isToggledHiddenByDefault: true)
    ->searchable(),

                TextColumn::make('lastname')
                    ->searchable()
                    ->sortable(),
TextColumn::make('suffix')
->toggleable(isToggledHiddenByDefault: true)
    ->searchable(),
                TextColumn::make('nickname')
                    ->searchable(),
                TextColumn::make('birthdate')
                    ->date()
->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('locality')
                    ->searchable()
                    ->sortable(),
//                TextColumn::make('permanent_address')
//                    ->searchable(),
                TextColumn::make('home_address')
                    ->searchable(),

                TextColumn::make('geocoordinates')
->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('contact_number')
                    ->searchable(),

TextColumn::make('emergencyContact.display_name')
    ->label('Emergency Contact')
    ->toggleable(isToggledHiddenByDefault: true),

//                TextColumn::make('emergency_contact')
//                    ->searchable(),
//                TextColumn::make('emergency_contact_number')
//                    ->searchable(),

TextColumn::make('churchProfile.category')
    ->label('Category')
    ->badge()
    ->sortable(),

TextColumn::make('churchProfile.status')
    ->label('Status')
    ->badge()
    ->sortable(),

TextColumn::make('churchProfile.service')
    ->label('Shepherding Service')
    ->limit(30)
    ->toggleable(),

TextColumn::make('churchProfile.shepherd.display_name')
    ->label('Shepherd')
    ->toggleable(),

TextColumn::make('churchProfile.introducedBy.display_name')
    ->label('Introduced By')
    ->toggleable(isToggledHiddenByDefault: true),


                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),


            ])

->filters([
    SelectFilter::make('church_status')
        ->label('Status')
        ->options(ChurchProfileOptions::statuses())
        ->query(function (Builder $query, array $data): Builder {
            $value = $data['value'] ?? null;

            if (blank($value)) {
                return $query;
            }

            return $query->whereHas('churchProfile', function (Builder $query) use ($value): void {
                $query->where('status', $value);
            });
        }),

    SelectFilter::make('church_category')
        ->label('Category')
        ->options(ChurchProfileOptions::categories())
        ->query(function (Builder $query, array $data): Builder {
            $value = $data['value'] ?? null;

            if (blank($value)) {
                return $query;
            }

            return $query->whereHas('churchProfile', function (Builder $query) use ($value): void {
                $query->where('category', $value);
            });
        }),

    SelectFilter::make('shepherding_service')
        ->label('Shepherding Service')
        ->options(ChurchProfileOptions::shepherdingServices())
        ->query(function (Builder $query, array $data): Builder {
            $value = $data['value'] ?? null;

            if (blank($value)) {
                return $query;
            }

            return $query->whereHas('churchProfile', function (Builder $query) use ($value): void {
                $query->where('service', $value);
            });
        }),
])

->recordActions([
    Action::make('viewFamilyTree')
        ->label('View Family Tree')
        ->icon('heroicon-o-user-group')
        ->url(fn ($record): string => FamilyTree::getUrl([
            'personId' => $record->id,
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
