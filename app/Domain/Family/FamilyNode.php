<?php

namespace App\Domain\Family;

use App\Models\Person;
use Illuminate\Support\Collection;

class FamilyNode
{
    public readonly Collection $siblings;

    public readonly Collection $children;

    public function __construct(
        public readonly Person $person,
        public readonly ?Person $spouse = null,
        public readonly ?Person $father = null,
        public readonly ?Person $mother = null,
        ?Collection $siblings = null,
        ?Collection $children = null,
        public readonly int $generation = 0,
        public readonly string $relationshipToRoot = 'self',
    ) {
        $this->siblings = $siblings ?? collect();
        $this->children = $children ?? collect();
    }

    public function displayName(): string
    {
        return $this->person->display_name;
    }

    public function toArray(): array
    {
        return [
            ...$this->personData($this->person),

            'generation' => $this->generation,
            'relationship_to_root' => $this->relationshipToRoot,

            'spouse' => $this->spouse
                ? $this->personData($this->spouse)
                : null,

            'father' => $this->father
                ? $this->personData($this->father)
                : null,

            'mother' => $this->mother
                ? $this->personData($this->mother)
                : null,

            'siblings' => $this->siblings
                ->map(fn (Person $person) => $this->personData($person))
                ->values()
                ->all(),

            'children' => $this->children
                ->map(fn (FamilyNode $child) => $child->toArray())
                ->values()
                ->all(),
        ];
    }

    private function personData(Person $person): array
    {
        return [
            'id' => $person->id,
            'name' => $person->display_name,
            'sex' => $person->sex,
            'locality' => $person->locality,
'household_id' => $person->household?->id,
'household' => $person->household?->display_name,
            'contact_number' => $person->contact_number,
        ];
    }
}
