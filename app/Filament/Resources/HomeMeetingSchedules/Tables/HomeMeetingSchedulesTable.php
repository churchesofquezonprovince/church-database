<?php

namespace App\Filament\Resources\HomeMeetingSchedules\Tables;

use App\Models\HomeMeetingScheduleEntry;
use App\Models\Locality;
use App\Models\ProvinceSetting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HomeMeetingSchedulesTable
{
    public static function configure(
        Table $table
    ): Table {
        return $table
            /*
             * Monday through Saturday, followed by
             * Lord's Day, then earlier meeting times.
             *
             * The legacy sort_order column remains in
             * the database but no longer controls the
             * Home Meeting schedule.
             */
            ->modifyQueryUsing(
                fn ($query) =>
                    $query
                        ->orderByRaw(
                            'CASE WHEN day_of_week = 0 THEN 7 ELSE day_of_week END'
                        )
                        ->orderBy(
                            'meeting_time'
                        )
                        ->orderBy(
                            'display_name'
                        )
            )
            ->columns([
                TextColumn::make(
                    'locality.name'
                )
                    ->label('Locality')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make(
                    'day_of_week'
                )
                    ->label('Day')
                    ->formatStateUsing(
                        fn (
                            mixed $state
                        ): string =>
                            self::dayLabel(
                                (int) $state
                            )
                    ),

                TextColumn::make('area_name')
                    ->label('Area')
                    ->placeholder(
                        'Not specified'
                    )
                    ->searchable(),

                TextColumn::make(
                    'display_name'
                )
                    ->label('Home Meeting')
                    ->searchable(),

                TextColumn::make(
                    'meeting_time'
                )
                    ->label('Time')
                    ->formatStateUsing(
                        fn (
                            ?string $state
                        ): string =>
                            self::formatTime(
                                $state
                            )
                    ),

                TextColumn::make(
                    'household.display_name'
                )
                    ->label('Household')
                    ->placeholder(
                        'Not linked'
                    ),

                TextColumn::make(
                    'contactPerson.display_name'
                )
                    ->label('Contact Person')
                    ->placeholder(
                        'Not linked'
                    )
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(40)
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),
            ])
            ->filters([
                SelectFilter::make(
                    'locality_id'
                )
                    ->label('Locality')
                    ->options(
                        fn (): array =>
                            self::localityOptions()
                    )
                    ->searchable(),

                SelectFilter::make(
                    'day_of_week'
                )
                    ->label('Day')
                    ->options(
                        self::dayOptions()
                    ),

                SelectFilter::make(
                    'is_active'
                )
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(
                            fn (): bool =>
                                auth()->user()
                                    ?->canDeleteRecords()
                                ?? false
                        ),
                ])
                    ->visible(
                        fn (): bool =>
                            auth()->user()
                                ?->canDeleteRecords()
                            ?? false
                    ),
            ]);
    }


    private static function dayOptions(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            0 => "Lord's Day",
        ];
    }


    private static function dayLabel(
        int $day
    ): string {
        return self::dayOptions()[$day]
            ?? 'Unknown Day';
    }


    private static function formatTime(
        ?string $time
    ): string {
        if (blank($time)) {
            return 'Not set';
        }

        return \Carbon\CarbonImmutable::
            createFromFormat(
                'H:i:s',
                $time
            )
            ->format('g:i A');
    }


    private static function localityOptions(): array
    {
        $primaryProvinceId =
            ProvinceSetting::query()
                ->value(
                    'primary_province_id'
                );

        if (! $primaryProvinceId) {
            return [];
        }

        return Locality::query()
            ->where(
                'province_id',
                $primaryProvinceId
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
