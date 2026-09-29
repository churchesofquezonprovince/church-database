<?php

namespace App\Filament\Resources\HomeMeetingSchedules\Pages;

use App\Filament\Resources\HomeMeetingSchedules\HomeMeetingScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeMeetingSchedule extends EditRecord
{
    protected static string $resource =
        HomeMeetingScheduleResource::class;


    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(
                    fn (): bool =>
                        auth()->user()
                            ?->canDeleteRecords()
                        ?? false
                ),
        ];
    }
}
