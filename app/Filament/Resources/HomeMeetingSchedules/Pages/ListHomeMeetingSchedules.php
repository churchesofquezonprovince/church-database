<?php

namespace App\Filament\Resources\HomeMeetingSchedules\Pages;

use App\Filament\Resources\HomeMeetingSchedules\HomeMeetingScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeMeetingSchedules extends ListRecords
{
    protected static string $resource =
        HomeMeetingScheduleResource::class;


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
