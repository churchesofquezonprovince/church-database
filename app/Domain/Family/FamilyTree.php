<?php

namespace App\Domain\Family;

use App\Models\Person;
use Illuminate\Support\Collection;

class FamilyTree
{
    public function __construct(
        public readonly Person $root,
        public readonly FamilyNode $rootNode,
        public readonly Collection $ancestors,
        public readonly Collection $descendants,
    ) {}

    public function toArray(): array
    {
        return [
            'root' => [
                'id' => $this->root->id,
                'name' => $this->root->display_name,
                'sex' => $this->root->sex,
            ],

            'tree' => $this->rootNode->toArray(),

            'ancestors' => $this->ancestors
                ->map(fn (Person $person) => [
                    'id' => $person->id,
                    'name' => $person->display_name,
                    'sex' => $person->sex,
                ])
                ->values()
                ->all(),

            'descendants' => $this->descendants
                ->map(fn (Person $person) => [
                    'id' => $person->id,
                    'name' => $person->display_name,
                    'sex' => $person->sex,
                ])
                ->values()
                ->all(),
        ];
    }
}
