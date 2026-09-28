<?php

namespace App\Filament\Resources\People\Pages;

use App\Filament\Resources\People\PersonResource;
use App\Models\Person;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;

    protected bool $createAnyway = false;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $this->createAnyway =
            (bool) ($data['create_anyway'] ?? false);

        unset($data['create_anyway']);

        return $data;
    }

    protected function handleRecordCreation(
        array $data
    ): Model {
        if ($this->createAnyway) {
            return Person::createAllowingExactDuplicate(
                $data
            );
        }

        return parent::handleRecordCreation($data);
    }
}
