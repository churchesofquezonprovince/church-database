<?php

namespace App\Filament\Resources\People\Pages;

use App\Filament\Pages\FamilyTree;
use App\Filament\Resources\People\PersonResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPerson extends ViewRecord
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewFamilyTree')
                ->label('View Family Tree')
                ->icon('heroicon-o-user-group')
                ->url(fn (): string => FamilyTree::getUrl([
                    'personId' => $this->record->id,
                ])),

            EditAction::make(),
        ];
    }
}
