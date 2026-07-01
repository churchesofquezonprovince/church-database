<?php

namespace App\Filament\Resources\People\Pages;

use App\Filament\Pages\FamilyTree;
use Filament\Actions\Action;
use App\Filament\Resources\People\PersonResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPerson extends EditRecord
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

        DeleteAction::make(),
    ];
}



}
