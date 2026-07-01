<?php

namespace App\Filament\Resources\People\Tables;

use App\Models\Person;
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
    ->label('Shepherding Group')
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

SelectFilter::make('locality')
    ->label('Locality')
    ->options(fn (): array => Person::query()
        ->whereNotNull('locality')
        ->where('locality', '!=', '')
        ->distinct()
        ->orderBy('locality')
        ->pluck('locality', 'locality')
        ->toArray())
    ->searchable(),

SelectFilter::make('shepherd_status')
    ->label('Shepherd Status')
    ->options([
        'with_shepherd' => 'With Shepherd',
        'without_shepherd' => 'No Shepherd',
    ])
    ->query(function (Builder $query, array $data): Builder {
        $value = $data['value'] ?? null;

        if (blank($value)) {
            return $query;
        }

        if ($value === 'with_shepherd') {
            return $query->whereHas('churchProfile', function (Builder $query): void {
                $query->whereNotNull('shepherd_id');
            });
        }

        if ($value === 'without_shepherd') {
            return $query->whereHas('churchProfile', function (Builder $query): void {
                $query->whereNull('shepherd_id');
            });
        }

        return $query;
    }),


SelectFilter::make('shepherding_group')
    ->label('Shepherding Group')
    ->options([
        '__none' => 'No Shepherding Group',
        ...ChurchProfileOptions::shepherdingServices(),
    ])
    ->query(function (Builder $query, array $data): Builder {
        $value = $data['value'] ?? null;

        if (blank($value)) {
            return $query;
        }

        if ($value === '__none') {
            return $query->whereHas('churchProfile', function (Builder $query): void {
                $query->whereNull('service')
                    ->orWhere('service', '');
            });
        }

        return $query->whereHas('churchProfile', function (Builder $query) use ($value): void {
            $query->where('service', $value);
        });
    }),

SelectFilter::make('shepherd_id')
    ->label('Shepherd')
    ->options(fn (): array => Person::query()
        ->orderBy('lastname')
        ->orderBy('firstname')
        ->get()
        ->mapWithKeys(fn (Person $person) => [
            $person->id => $person->display_name,
        ])
        ->toArray())
    ->searchable()
    ->query(function (Builder $query, array $data): Builder {
        $value = $data['value'] ?? null;

        if (blank($value)) {
            return $query;
        }

        return $query->whereHas('churchProfile', function (Builder $query) use ($value): void {
            $query->where('shepherd_id', $value);
        });
    }),

SelectFilter::make('introduced_by_id')
    ->label('Introduced By')
    ->options(fn (): array => Person::query()
        ->orderBy('lastname')
        ->orderBy('firstname')
        ->get()
        ->mapWithKeys(fn (Person $person) => [
            $person->id => $person->display_name,
        ])
        ->toArray())
    ->searchable()
    ->query(function (Builder $query, array $data): Builder {
        $value = $data['value'] ?? null;

        if (blank($value)) {
            return $query;
        }

        return $query->whereHas('churchProfile', function (Builder $query) use ($value): void {
            $query->where('introduced_by_id', $value);
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
