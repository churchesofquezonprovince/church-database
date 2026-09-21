<?php

namespace App\Filament\Resources\People;

use App\Filament\Resources\People\Pages\CreatePerson;
use App\Filament\Resources\People\Pages\EditPerson;
use App\Filament\Resources\People\Pages\ListPeople;
use App\Filament\Resources\People\Pages\ViewPerson;
use App\Filament\Resources\People\Schemas\PersonForm;
use App\Filament\Resources\People\Schemas\PersonInfolist;
use App\Filament\Resources\People\Tables\PeopleTable;
use App\Models\Person;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PersonResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static ?string $recordTitleAttribute = 'lastname';

    protected static int $globalSearchResultsLimit = 15;

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'firstname',
            'middlename',
            'lastname',
            'suffix',
            'nickname',
            'contact_number',
            'localityRecord.name',
        ];
    }

    public static function getGlobalSearchResultTitle(
        Model $record
    ): string {
        return $record->display_name;
    }

    public static function getGlobalSearchResultDetails(
        Model $record
    ): array {
        return [
            'Locality' => $record->localityRecord?->name
                ?? $record->locality
                ?? 'No Locality',
            'Status' => $record->churchProfile?->status
                ?? 'No Status',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with([
                'localityRecord',
                'churchProfile',
            ]);
    }

//    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

//protected static string | \UnitEnum | null $navigationGroup = 'Church Database';

    protected static ?int $navigationSort = 20;

protected static string | \UnitEnum | null $navigationGroup = 'Church Database';

protected static ?string $navigationLabel = 'People';

protected static ?string $modelLabel = 'Person';

protected static ?string $pluralModelLabel = 'People';

//

    public static function form(Schema $schema): Schema
    {
        return PersonForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PersonInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PeopleTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeople::route('/'),
            'create' => CreatePerson::route('/create'),
            'view' => ViewPerson::route('/{record}'),
            'edit' => EditPerson::route('/{record}/edit'),
        ];
    }
}
