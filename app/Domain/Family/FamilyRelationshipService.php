<?php

namespace App\Domain\Family;

use App\Models\Person;
use App\Models\ParentRelationship;
use Illuminate\Support\Collection;

class FamilyRelationshipService
{
    /*
    |--------------------------------------------------------------------------
    | Parents
    |--------------------------------------------------------------------------
    */

    public function parents(Person $person): Collection
    {
        return ParentRelationship::query()
            ->where('person_id', $person->id)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->get()
            ->pluck('parent')
            ->filter();
    }

    public function father(Person $person): ?Person
    {
        return ParentRelationship::query()
            ->where('person_id', $person->id)
            ->where('relationship', 'Father')
            ->first()?->parent;
    }

    public function mother(Person $person): ?Person
    {
        return ParentRelationship::query()
            ->where('person_id', $person->id)
            ->where('relationship', 'Mother')
            ->first()?->parent;
    }

    /*
    |--------------------------------------------------------------------------
    | Children
    |--------------------------------------------------------------------------
    */

    public function children(Person $person): Collection
    {
        return ParentRelationship::query()
            ->where('parent_id', $person->id)
            ->with('person')
            ->get()
            ->pluck('person')
            ->filter()
            ->unique('id')
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Siblings
    |--------------------------------------------------------------------------
    */

    public function siblings(Person $person): Collection
    {
        $parentIds = ParentRelationship::query()
            ->where('person_id', $person->id)
            ->pluck('parent_id')
            ->filter();

        if ($parentIds->isEmpty()) {
            return collect();
        }

        $siblingIds = ParentRelationship::query()
            ->whereIn('parent_id', $parentIds)
            ->pluck('person_id')
            ->unique()
            ->reject(fn ($id) => $id == $person->id);

        return Person::whereIn('id', $siblingIds)->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Recursive Graph Traversal
    |--------------------------------------------------------------------------
    */

    private function collectAncestors(
        Person $person,
        Collection &$ancestors,
    ): void {

        $parents = $this->parents($person);

        foreach ($parents as $parent) {

            if ($ancestors->contains('id', $parent->id)) {
                continue;
            }

            $ancestors->push($parent);

            $this->collectAncestors(
                $parent,
                $ancestors
            );
        }
    }

    private function collectDescendants(
        Person $person,
        Collection &$descendants,
    ): void {

        $children = $this->children($person);

        foreach ($children as $child) {

            if ($descendants->contains('id', $child->id)) {
                continue;
            }

            $descendants->push($child);

            $this->collectDescendants(
                $child,
                $descendants
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Ancestors & Descendants
    |--------------------------------------------------------------------------
    */

    public function ancestors(Person $person): Collection
    {
        $ancestors = collect();

        $this->collectAncestors(
            $person,
            $ancestors
        );

        return $ancestors;
    }

    public function descendants(Person $person): Collection
    {
        $descendants = collect();

        $this->collectDescendants(
            $person,
            $descendants
        );

        return $descendants;
    }

// Grandparents

    public function grandparents(Person $person): Collection
    {
        return $this->parents($person)
            ->flatMap(
                fn (Person $parent) => $this->parents($parent)
            )
            ->unique('id')
            ->values();
    }

// Grand Children

    public function grandchildren(Person $person): Collection
    {
        return $this->children($person)
            ->flatMap(
                fn (Person $child) => $this->children($child)
            )
            ->unique('id')
            ->values();
    }

/*
|--------------------------------------------------------------------------
| Relationship Trees
|--------------------------------------------------------------------------
*/

public function ancestorTree(Person $person): array
{
    return [
        'person' => $person,
        'parents' => $this->buildAncestorTree($person),
    ];
}

private function buildAncestorTree(Person $person): array
{
    return $this->parents($person)

        ->map(function (Person $parent) {

            return [

                'person' => $parent,

                'parents' => $this->buildAncestorTree($parent),

            ];

        })

        ->values()

        ->all();
}

// Descendant Tree

public function descendantTree(Person $person): array
{
    return [

        'person' => $person,

        'children' => $this->buildDescendantTree($person),

    ];
}

private function buildDescendantTree(Person $person): array
{
    return $this->children($person)

        ->map(function (Person $child) {

            return [

                'person' => $child,

                'children' => $this->buildDescendantTree($child),

            ];

        })

        ->values()

        ->all();
}






}
