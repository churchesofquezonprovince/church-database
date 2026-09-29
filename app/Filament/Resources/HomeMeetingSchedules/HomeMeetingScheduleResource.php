<?php

namespace App\Filament\Resources\HomeMeetingSchedules;

use App\Filament\Resources\HomeMeetingSchedules\Pages\CreateHomeMeetingSchedule;
use App\Filament\Resources\HomeMeetingSchedules\Pages\EditHomeMeetingSchedule;
use App\Filament\Resources\HomeMeetingSchedules\Pages\ListHomeMeetingSchedules;
use App\Filament\Resources\HomeMeetingSchedules\Schemas\HomeMeetingScheduleForm;
use App\Filament\Resources\HomeMeetingSchedules\Tables\HomeMeetingSchedulesTable;
use App\Models\HomeMeetingScheduleEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HomeMeetingScheduleResource extends Resource
{
    protected static ?string $model =
        HomeMeetingScheduleEntry::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup =
        'Shepherding';

    protected static ?int $navigationSort = 41;

    protected static ?string $navigationLabel =
        'Manage Home Meetings';

    protected static ?string $modelLabel =
        'Home Meeting';

    protected static ?string $pluralModelLabel =
        'Home Meetings';


    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }


    public static function canViewAny(): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }


    public static function canCreate(): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }


    public static function canEdit($record): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }


    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteRecords()
            ?? false;
    }


    public static function form(
        Schema $schema
    ): Schema {
        return HomeMeetingScheduleForm::configure(
            $schema
        );
    }


    public static function table(
        Table $table
    ): Table {
        return HomeMeetingSchedulesTable::configure(
            $table
        );
    }


    public static function getPages(): array
    {
        return [
            'index' =>
                ListHomeMeetingSchedules::route('/'),

            'create' =>
                CreateHomeMeetingSchedule::route(
                    '/create'
                ),

            'edit' =>
                EditHomeMeetingSchedule::route(
                    '/{record}/edit'
                ),
        ];
    }
}
