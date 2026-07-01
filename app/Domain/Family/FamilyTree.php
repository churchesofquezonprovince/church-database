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
        public readonly Collection $parents,
        public readonly Collection $grandparents,
        public readonly Collection $children,
        public readonly Collection $grandchildren,
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

            'generations' => [
                'grandparents' => $this->mapPeople($this->grandparents),
                'parents' => $this->mapPeople($this->parents),
                'self' => [
                    [
                        'id' => $this->root->id,
                        'name' => $this->root->display_name,
                        'sex' => $this->root->sex,
                    ],
                ],
                'children' => $this->mapPeople($this->children),
                'grandchildren' => $this->mapPeople($this->grandchildren),
            ],

            'ancestors' => $this->mapPeople($this->ancestors),

            'descendants' => $this->mapPeople($this->descendants),
        ];
    }

    private function mapPeople(Collection $people): array
    {
        return $people
            ->map(fn (Person $person) => [
                'id' => $person->id,
                'name' => $person->display_name,
                'sex' => $person->sex,
            ])
            ->values()
            ->all();
    }
}
