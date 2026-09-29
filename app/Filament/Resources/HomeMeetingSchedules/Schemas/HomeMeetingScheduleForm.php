<?php

namespace App\Filament\Resources\HomeMeetingSchedules\Schemas;

use App\Models\Household;
use App\Models\Locality;
use App\Models\Person;
use App\Models\ProvinceSetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomeMeetingScheduleForm
{
    public static function configure(
        Schema $schema
    ): Schema {
        return $schema
            ->components([
                Section::make(
                    'Home Meeting Information'
                )
                    ->description(
                        'Create or update a weekly Home Meeting schedule. Entries are automatically ordered by day and meeting time.'
                    )
                    ->schema([
                        Select::make('locality_id')
                            ->label('Locality')
                            ->options(
                                fn (): array =>
                                    self::localityOptions()
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->placeholder(
                                'Select locality'
                            )
                            ->helperText(
                                'Household and Contact Person choices are limited to this Locality.'
                            )
                            ->afterStateUpdated(
                                function (
                                    $set
                                ): void {
                                    /*
                                     * Avoid retaining a Household
                                     * or Person from the previous
                                     * Locality.
                                     */
                                    $set(
                                        'household_id',
                                        null
                                    );

                                    $set(
                                        'contact_person_id',
                                        null
                                    );
                                }
                            ),

                        Select::make('day_of_week')
                            ->label('Day')
                            ->options(
                                self::dayOptions()
                            )
                            ->required()
                            ->native(false),

                        TimePicker::make(
                            'meeting_time'
                        )
                            ->label('Meeting Time')
                            ->required()
                            ->seconds(false),

                        TextInput::make('area_name')
                            ->label(
                                'Area / Meeting Place'
                            )
                            ->maxLength(255)
                            ->placeholder(
                                'e.g. Ibabang Dupay'
                            ),

                        TextInput::make(
                            'display_name'
                        )
                            ->label('Display Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(
                                'e.g. Dela Cruz Family'
                            )
                            ->helperText(
                                'This is the name displayed on the Home Meeting Schedule.'
                            ),

                        Select::make('household_id')
                            ->label('Linked Household')
                            ->options(
                                fn ($get): array =>
                                    self::householdOptions(
                                        $get('locality_id')
                                    )
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable()
                            ->placeholder(
                                'No linked Household'
                            )
                            ->helperText(
                                'Optional. Link an existing Household from the selected Locality.'
                            ),

                        Select::make(
                            'contact_person_id'
                        )
                            ->label('Contact Person')
                            ->options(
                                fn ($get): array =>
                                    self::personOptions(
                                        $get('locality_id')
                                    )
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable()
                            ->placeholder(
                                'No Contact Person'
                            )
                            ->helperText(
                                'Optional. Choose a Person from the selected Locality.'
                            ),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText(
                                'Inactive entries remain in the database but do not appear on the Home Meeting Schedule.'
                            ),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder(
                                'Optional notes about this Home Meeting'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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


    private static function householdOptions(
        mixed $localityId
    ): array {
        $localityId =
            (int) ($localityId ?? 0);

        if ($localityId <= 0) {
            return [];
        }

        return Household::query()
            ->where(
                'locality_id',
                $localityId
            )
            ->with('head')
            ->orderBy('household_name')
            ->get()
            ->mapWithKeys(
                fn (
                    Household $household
                ): array => [
                    $household->id =>
                        $household
                            ->display_name,
                ]
            )
            ->all();
    }


    private static function personOptions(
        mixed $localityId
    ): array {
        $localityId =
            (int) ($localityId ?? 0);

        if ($localityId <= 0) {
            return [];
        }

        return Person::query()
            ->where(
                'locality_id',
                $localityId
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->mapWithKeys(
                fn (
                    Person $person
                ): array => [
                    $person->id =>
                        $person->display_name,
                ]
            )
            ->all();
    }
}
