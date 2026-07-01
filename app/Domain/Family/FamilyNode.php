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
            'id' => $this->person->id,
            'name' => $this->person->display_name,
            'sex' => $this->person->sex,
            'generation' => $this->generation,
            'relationship_to_root' => $this->relationshipToRoot,

            'spouse' => $this->spouse ? [
                'id' => $this->spouse->id,
                'name' => $this->spouse->display_name,
                'sex' => $this->spouse->sex,
            ] : null,

            'father' => $this->father ? [
                'id' => $this->father->id,
                'name' => $this->father->display_name,
            ] : null,

            'mother' => $this->mother ? [
                'id' => $this->mother->id,
                'name' => $this->mother->display_name,
            ] : null,

            'siblings' => $this->siblings
                ->map(fn (Person $person) => [
                    'id' => $person->id,
                    'name' => $person->display_name,
                    'sex' => $person->sex,
                ])
                ->values()
                ->all(),

            'children' => $this->children
                ->map(fn (FamilyNode $child) => $child->toArray())
                ->values()
                ->all(),
        ];
    }
}
