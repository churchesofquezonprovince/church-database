<?php

namespace App\Filament\Pages;

use App\Domain\Family\FamilyTreeBuilder;
use App\Models\Person;
use Filament\Pages\Page;

class FamilyTree extends Page
{
    protected string $view = 'filament.pages.family-tree';

    public ?int $personId = null;

    public array $people = [];

    public array $tree = [];

public function mount(): void
{
    $this->people = Person::query()
        ->orderBy('lastname')
        ->orderBy('firstname')
        ->get()
        ->mapWithKeys(fn (Person $person) => [
            $person->id => $person->display_name,
        ])
        ->all();

    $requestedPersonId = request()->integer('personId');

    if ($requestedPersonId && array_key_exists($requestedPersonId, $this->people)) {
        $this->personId = $requestedPersonId;
    } else {
        $this->personId = array_key_first($this->people);
    }

    $this->loadTree();
}

    public function getTitle(): string
    {
        return 'Family Tree';
    }

    public static function getNavigationLabel(): string
    {
        return 'Family Tree';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Church Database';
    }

    public function updatedPersonId(): void
    {
        $this->loadTree();
    }

    public function selectPerson(int $personId): void
    {
        $this->personId = $personId;

        $this->loadTree();
    }

    public function loadTree(): void
    {
        if (! $this->personId) {
            $this->tree = [];

            return;
        }

        $person = Person::query()->find($this->personId);

        if (! $person) {
            $this->tree = [];

            return;
        }

        $this->tree = app(FamilyTreeBuilder::class)
            ->build($person)
            ->toArray();
    }

public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }
}
