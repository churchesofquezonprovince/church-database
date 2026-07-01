<?php

namespace App\Filament\Resources\People\Tables;

use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ChurchProfile;
use App\Models\Person;
use App\Filament\Pages\FamilyTree;
use App\Support\ChurchProfileOptions;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
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
        BulkAction::make('assignStatus')
            ->label('Assign Status')
            ->icon('heroicon-o-check-circle')
            ->schema([
                Select::make('status')
                    ->label('Status')
                    ->options(ChurchProfileOptions::statuses())
                    ->required()
                    ->native(false),
            ])
            ->action(function (Collection $records, array $data): void {
                $records->each(function (Person $person) use ($data): void {
                    $profile = $person->churchProfile()->firstOrNew([]);

                    $profile->status = $data['status'];
                    $profile->save();
                });
            })
            ->deselectRecordsAfterCompletion(),

        BulkAction::make('assignShepherd')
            ->label('Assign Shepherd')
            ->icon('heroicon-o-user-plus')
            ->schema([
                Select::make('shepherd_id')
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
                    ->required()
                    ->native(false),
            ])
            ->action(function (Collection $records, array $data): void {
                $records->each(function (Person $person) use ($data): void {
                    $profile = $person->churchProfile()->firstOrNew([]);

                    $profile->shepherd_id = $data['shepherd_id'];
                    $profile->save();
                });
            })
            ->deselectRecordsAfterCompletion(),

        BulkAction::make('assignIntroducedBy')
            ->label('Assign Introduced By')
            ->icon('heroicon-o-user')
            ->schema([
                Select::make('introduced_by_id')
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
                    ->required()
                    ->native(false),
            ])
            ->action(function (Collection $records, array $data): void {
                $records->each(function (Person $person) use ($data): void {
                    $profile = $person->churchProfile()->firstOrNew([]);

                    $profile->introduced_by_id = $data['introduced_by_id'];
                    $profile->save();
                });
            })
            ->deselectRecordsAfterCompletion(),

        BulkAction::make('assignShepherdingGroup')
            ->label('Assign Shepherding Group')
            ->icon('heroicon-o-users')
            ->schema([
                Select::make('service')
                    ->label('Shepherding Group')
                    ->options(ChurchProfileOptions::shepherdingServices())
                    ->searchable()
                    ->required()
                    ->native(false),
            ])
            ->action(function (Collection $records, array $data): void {
                $records->each(function (Person $person) use ($data): void {
                    $profile = $person->churchProfile()->firstOrNew([]);

                    $profile->service = $data['service'];
                    $profile->save();
                });
            })
            ->deselectRecordsAfterCompletion(),

BulkAction::make('clearShepherd')
    ->label('Clear Shepherd')
    ->icon('heroicon-o-x-circle')
    ->color('warning')
    ->requiresConfirmation()
    ->action(function (Collection $records): void {
        $records->each(function (Person $person): void {
            $profile = $person->churchProfile()->firstOrNew([]);

            $profile->shepherd_id = null;
            $profile->save();
        });
    })
    ->deselectRecordsAfterCompletion(),

BulkAction::make('clearIntroducedBy')
    ->label('Clear Introduced By')
    ->icon('heroicon-o-x-circle')
    ->color('warning')
    ->requiresConfirmation()
    ->action(function (Collection $records): void {
        $records->each(function (Person $person): void {
            $profile = $person->churchProfile()->firstOrNew([]);

            $profile->introduced_by_id = null;
            $profile->save();
        });
    })
    ->deselectRecordsAfterCompletion(),

BulkAction::make('clearShepherdingGroup')
    ->label('Clear Shepherding Group')
    ->icon('heroicon-o-x-circle')
    ->color('warning')
    ->requiresConfirmation()
    ->action(function (Collection $records): void {
        $records->each(function (Person $person): void {
            $profile = $person->churchProfile()->firstOrNew([]);

            $profile->service = null;
            $profile->save();
        });
    })
    ->deselectRecordsAfterCompletion(),

BulkAction::make('resetStatusUnknown')
    ->label('Set Status to Unknown')
    ->icon('heroicon-o-question-mark-circle')
    ->color('gray')
    ->requiresConfirmation()
    ->action(function (Collection $records): void {
        $records->each(function (Person $person): void {
            $profile = $person->churchProfile()->firstOrNew([]);

            $profile->status = 'Unknown';
            $profile->save();
        });
    })
    ->deselectRecordsAfterCompletion(),

        DeleteBulkAction::make(),
    ]),
]);

    }
}
