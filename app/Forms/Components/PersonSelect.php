<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Select;

class PersonSelect
{
    public static function relationship(
        string $field,
        string $relationship,
        string $label = 'Person',
    ): Select {

        return Select::make($field)
            ->label($label)
            ->relationship(
                name: $relationship,
                titleAttribute: 'lastname',
            )
            ->searchable()
            ->preload()
            ->getOptionLabelFromRecordUsing(
                fn ($record) => $record->display_name
            );
    }
}
