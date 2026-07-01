<?php

namespace App\Domain\Family;

use App\Models\Person;

class FamilyTreeBuilder
{
    public function __construct(
        protected FamilyRelationshipService $relationships,
    ) {}

    public function build(Person $person, int $depth = 2): FamilyTree
    {
        $rootNode = $this->buildNode(
            person: $person,
            generation: 0,
            relationshipToRoot: 'self',
            depth: $depth,
            visited: [],
        );

        return new FamilyTree(
            root: $person,
            rootNode: $rootNode,
            ancestors: $this->relationships->ancestors($person),
            descendants: $this->relationships->descendants($person),
            parents: $this->relationships->parents($person),
            grandparents: $this->relationships->grandparents($person),
            children: $this->relationships->children($person),
            grandchildren: $this->relationships->grandchildren($person),
        );
    }

    protected function buildNode(
        Person $person,
        int $generation,
        string $relationshipToRoot,
        int $depth,
        array $visited,
    ): FamilyNode {
        if (in_array($person->id, $visited, true)) {
            return new FamilyNode(
                person: $person,
                spouse: $person->spouse,
                father: $this->relationships->father($person),
                mother: $this->relationships->mother($person),
                siblings: collect(),
                children: collect(),
                generation: $generation,
                relationshipToRoot: $relationshipToRoot,
            );
        }

        $visited[] = $person->id;

        $children = collect();

        if ($depth > 0) {
            $children = $this->relationships
                ->children($person)
                ->map(fn (Person $child) => $this->buildNode(
                    person: $child,
                    generation: $generation + 1,
                    relationshipToRoot: 'child',
                    depth: $depth - 1,
                    visited: $visited,
                ));
        }

        return new FamilyNode(
            person: $person,
            spouse: $person->spouse,
            father: $this->relationships->father($person),
            mother: $this->relationships->mother($person),
            siblings: $this->relationships->siblings($person),
            children: $children,
            generation: $generation,
            relationshipToRoot: $relationshipToRoot,
        );
    }
}
