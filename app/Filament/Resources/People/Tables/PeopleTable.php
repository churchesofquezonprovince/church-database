<?php

namespace App\Filament\Resources\People\Tables;

use App\Filament\Pages\FamilyTree;
use App\Models\Locality;
use App\Models\Person;
use App\Models\Province;
use App\Models\ProvinceSetting;
use App\Support\ChurchProfileOptions;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class PeopleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(30)
            ->paginated([10, 30, 50, 100])
            ->columns([
                TextColumn::make('display_name')
                    ->label('Name')
                    ->formatStateUsing(fn (Person $record): HtmlString => self::nameColumn($record))
                    ->html()
                    ->searchable([
                        'firstname',
                        'middlename',
                        'lastname',
                        'suffix',
                        'nickname',
                    ])
                    ->sortable([
                        'lastname',
                        'firstname',
                    ]),

                TextColumn::make('contact_number')
                    ->label('Contact')
                    ->formatStateUsing(fn (?string $state): HtmlString => self::contactColumn($state))
                    ->html()
                    ->searchable()
                    ->copyable(),

                TextColumn::make('locality')
                    ->label('Locality')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('churchProfile.status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => ChurchProfileOptions::statusColor($state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('churchProfile.category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (?string $state): string => ChurchProfileOptions::categoryColor($state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('churchProfile.service')
                    ->label('Shepherding Groups')
                    ->formatStateUsing(fn (mixed $state): HtmlString => self::serviceColumn($state))
                    ->html()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('churchProfile.shepherd.display_name')
                    ->label('Shepherd')
                    ->placeholder('No shepherd')
                    ->limit(30)
                    ->searchable([
                        'firstname',
                        'middlename',
                        'lastname',
                        'nickname',
                    ])
                    ->toggleable(),

                TextColumn::make('home_address')
                    ->label('Home Address')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('nickname')
                    ->label('Nickname')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sex')
                    ->label('Sex')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Male' => 'info',
                        'Female' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('birthdate')
                    ->label('Birthdate')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('facebook_account')
                    ->label('Facebook Account')
                    ->searchable()
                    ->copyable()
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('churchProfile.introducedBy.display_name')
                    ->label('Introduced By')
                    ->placeholder('Not recorded')
                    ->limit(30)
                    ->searchable([
                        'firstname',
                        'middlename',
                        'lastname',
                        'nickname',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('educationProfile.school_workplace')
                    ->label('School')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('educationProfile.workplace')
                    ->label('Workplace')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('educationProfile.course_strand')
                    ->label('Course / Strand')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('educationProfile.grade_level')
                    ->label('Grade / Year Level')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('household.household_name')
                    ->label('Household')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('permanent_address')
                    ->label('Permanent Address')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('emergencyContact.display_name')
                    ->label('Emergency Contact')
                    ->placeholder('Not recorded')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('geocoordinates')
                    ->label('Geocoordinates')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
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

                SelectFilter::make('locality_id')
                    ->label('Locality')
                    ->options(function (): array {
                        $settings = ProvinceSetting::query()
                            ->with('primaryProvince')
                            ->first();

                        if (! $settings?->primary_province_id) {
                            return [];
                        }

                        $options = [];

                        $primaryLocalities = Locality::query()
                            ->where(
                                'province_id',
                                $settings->primary_province_id
                            )
                            ->orderByDesc('is_active')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(
                                fn (Locality $locality): array => [
                                    $locality->id =>
                                        $locality->name
                                        . ($locality->is_active ? '' : ' (Archived)'),
                                ]
                            )
                            ->all();

                        if ($primaryLocalities !== []) {
                            $options[
                                $settings->primaryProvince?->name
                                    ?? 'Primary Province'
                            ] = $primaryLocalities;
                        }

                        $outsideProvinces = Province::query()
                            ->with([
                                'localities' => fn ($query) =>
                                    $query
                                        ->orderByDesc('is_active')
                                        ->orderBy('name'),
                            ])
                            ->where(
                                'id',
                                '!=',
                                $settings->primary_province_id
                            )
                            ->whereHas('localities')
                            ->orderBy('name')
                            ->get();

                        foreach ($outsideProvinces as $province) {
                            $localities = $province->localities
                                ->mapWithKeys(
                                    fn (Locality $locality): array => [
                                        $locality->id =>
                                            $locality->name
                                            . ($locality->is_active ? '' : ' (Archived)'),
                                    ]
                                )
                                ->all();

                            if ($localities === []) {
                                continue;
                            }

                            $options[
                                'Outside — ' . $province->name
                            ] = $localities;
                        }

                        return $options;
                    })
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
                    ->label('Shepherding Groups')
                    ->options([
                        '__none' => 'No Shepherding Groups',
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
                    ->options(fn (): array => self::personOptions())
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
                    ->options(fn (): array => self::personOptions())
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

                SelectFilter::make('missing_data')
                    ->label('Missing Data')
                    ->options([
                        'no_contact' => 'No Contact Number',
                        'no_locality' => 'No Locality',
                        'no_household' => 'No Household',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        if ($value === 'no_contact') {
                            return $query->where(function (Builder $query): void {
                                $query->whereNull('contact_number')
                                    ->orWhere('contact_number', '');
                            });
                        }

                        if ($value === 'no_locality') {
                            return $query->whereNull('locality_id');
                        }

                        if ($value === 'no_household') {
                            return $query->whereNull('household_id');
                        }

                        return $query;
                    }),

            ])

            ->recordActions([
                Action::make('viewFamilyTree')
                    ->label('Family Tree')
                    ->icon('heroicon-o-user-group')
                    ->url(fn (Person $record): string => FamilyTree::getUrl([
                        'personId' => $record->id,
                    ])),

                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canManageRecords() ?? false),
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
                                ->options(fn (): array => self::personOptions())
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
                                ->options(fn (): array => self::personOptions())
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
                        ->label('Assign Shepherding Groups')
                        ->icon('heroicon-o-users')
                        ->schema([
                            Select::make('service')
                                ->label('Shepherding Groups')
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
                        ->label('Clear Shepherding Groups')
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

                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteRecords() ?? false),
                ])
                    ->visible(fn (): bool => auth()->user()?->canManageRecords() ?? false),
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


    private static function tableDisplayName(Person $record): string
    {
        $middleInitials = collect(preg_split('/\s+/', trim((string) $record->middlename)))
            ->filter()
            ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)) . '.')
            ->implode(' ');

        return collect([
            filled($record->lastname) ? trim((string) $record->lastname) . ',' : null,
            $record->firstname,
            $middleInitials,
            $record->suffix,
        ])
            ->filter(fn ($part): bool => filled($part))
            ->implode(' ');
    }

    private static function nameColumn(Person $person): HtmlString
    {
        $initials = collect([
            $person->firstname,
            $person->lastname,
        ])
            ->filter()
            ->map(fn (string $part): string => strtoupper(substr(trim($part), 0, 1)))
            ->join('');

        $nickname = filled($person->nickname)
            ? '<span class="mt-1 inline-flex rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-950 dark:text-primary-200">“' . e($person->nickname) . '”</span>'
            : '';

        $details = collect([
            $person->sex,
            $person->locality,
        ])
            ->filter()
            ->map(fn (string $value): string => e($value))
            ->join(' • ');

        return new HtmlString(
            '<div class="flex items-center gap-3">'
            . '<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white shadow-sm">'
            . e($initials ?: '?')
            . '</div>'
            . '<div class="min-w-0">'
            . '<div class="font-bold text-gray-900 dark:text-white">' . e($person->display_name) . '</div>'
            . ($details ? '<div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">' . $details . '</div>' : '')
            . $nickname
            . '</div>'
            . '</div>'
        );
    }

    private static function contactColumn(?string $number): HtmlString
    {
        if (blank($number)) {
            return new HtmlString(
                '<span class="rounded-lg border border-dashed border-gray-300 px-2.5 py-1 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">Not recorded</span>'
            );
        }

        return new HtmlString(
            '<a href="tel:' . e($number) . '" class="inline-flex rounded-lg border border-primary-200 bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700 hover:bg-primary-100 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">'
            . e($number)
            . '</a>'
        );
    }

    private static function serviceColumn(?string $service): HtmlString
    {
        if (blank($service)) {
            return new HtmlString(
                '<span class="inline-flex rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">No Shepherding Groups</span>'
            );
        }

        return new HtmlString(
            '<span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">'
            . e($service)
            . '</span>'
        );
    }
}
