<?php

namespace App\Filament\Resources\Households\Pages;

use App\Filament\Pages\FamilyTree;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Households\HouseholdResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHousehold extends EditRecord
{
    protected static string $resource = HouseholdResource::class;

protected function getHeaderActions(): array
{
    return [
        Action::make('viewHeadFamilyTree')
            ->label("View Head's Family Tree")
            ->icon('heroicon-o-user-group')
            ->visible(fn (): bool => filled($this->record->household_head_id))
            ->url(fn (): string => FamilyTree::getUrl([
                'personId' => $this->record->household_head_id,
            ])),

        DeleteAction::make(),
    ];
}

}
