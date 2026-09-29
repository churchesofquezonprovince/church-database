<?php

namespace App\Filament\Resources\People\Pages;

use App\Filament\Pages\ChildrenWorkDatabase;
use App\Filament\Resources\People\PersonResource;
use App\Models\Person;
use App\Support\ChurchProfileOptions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;

    #[Url]
    public ?string $source = null;

    protected bool $createAnyway = false;

    public function getTitle(): string
    {
        if ($this->isChildrenWorkCreation()) {
            return 'Add Child';
        }

        return 'Create Person';
    }

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $this->createAnyway =
            (bool) ($data['create_anyway'] ?? false);

        unset($data['create_anyway']);

        if ($this->isChildrenWorkCreation()) {
            $birthdate = $data['birthdate'] ?? null;

            $category =
                ChurchProfileOptions::categoryFromBirthdate(
                    $birthdate
                );

            if ($category !== 'Children') {
                throw ValidationException::withMessages([
                    'data.birthdate' =>
                        'Add Child requires a birthdate that '
                        . 'automatically belongs to the Children '
                        . 'category.',
                ]);
            }
        }

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

    protected function getRedirectUrl(): string
    {
        if ($this->isChildrenWorkCreation()) {
            return ChildrenWorkDatabase::getUrl();
        }

        return parent::getRedirectUrl();
    }

    private function isChildrenWorkCreation(): bool
    {
        return $this->source === 'children-work';
    }
}
