<?php

namespace App\Services;

use App\Models\Person;

class FamilyRelationshipService
{
    public function mother(Person $person): ?Person
    {
        return optional($person->mother)->parent;
    }

    public function father(Person $person): ?Person
    {
        return optional($person->father)->parent;
    }

public function children(Person $person)
{
    return Person::whereHas('parents', function ($query) use ($person) {
        $query->where('parent_id', $person->id);
    })->get();
}

public function fullSiblings(Person $person)
{
    $mother = $this->mother($person);
    $father = $this->father($person);

    if (!$mother || !$father) {
        return collect();
    }

    return Person::whereKeyNot($person->id)
        ->whereHas('parents', function ($query) use ($mother) {
            $query->where('parent_id', $mother->id)
                  ->where('relationship', 'Mother');
        })
        ->whereHas('parents', function ($query) use ($father) {
            $query->where('parent_id', $father->id)
                  ->where('relationship', 'Father');
        })
        ->get();
}

public function maternalHalfSiblings(Person $person)
{
    $mother = $this->mother($person);
    $father = $this->father($person);

    if (!$mother) {
        return collect();
    }

    return Person::whereKeyNot($person->id)

        ->whereHas('parents', function ($query) use ($mother) {
            $query->where('parent_id', $mother->id)
                  ->where('relationship', 'Mother');
        })

        ->whereDoesntHave('parents', function ($query) use ($father) {

            if ($father) {
                $query->where('parent_id', $father->id)
                      ->where('relationship', 'Father');
            }

        })

        ->get();
}

public function paternalHalfSiblings(Person $person)
{
    $mother = $this->mother($person);
    $father = $this->father($person);

    if (!$father) {
        return collect();
    }

    return Person::whereKeyNot($person->id)

        ->whereHas('parents', function ($query) use ($father) {
            $query->where('parent_id', $father->id)
                  ->where('relationship', 'Father');
        })

        ->whereDoesntHave('parents', function ($query) use ($mother) {

            if ($mother) {
                $query->where('parent_id', $mother->id)
                      ->where('relationship', 'Mother');
            }

        })

        ->get();
}

public function siblings(Person $person)
{
    return collect([
        'full' => $this->fullSiblings($person),
        'maternal_half' => $this->maternalHalfSiblings($person),
        'paternal_half' => $this->paternalHalfSiblings($person),
    ]);
}

public function grandparents(Person $person)
{
    return collect([
        $this->father($this->father($person)),
        $this->mother($this->father($person)),
        $this->father($this->mother($person)),
        $this->mother($this->mother($person)),
    ])
    ->filter()
    ->unique('id')
    ->values();
}

public function grandchildren(Person $person)
{
    return $this->children($person)
        ->flatMap(fn ($child) => $this->children($child))
        ->unique('id')
        ->values();
}








}
