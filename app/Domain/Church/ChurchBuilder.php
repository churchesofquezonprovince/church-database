<?php

namespace App\Domain\Church;

use App\Models\Household;
use App\Models\ParentRelationship;
use App\Models\Person;
use App\Models\ChurchProfile;
use App\Models\EducationProfile;

class ChurchBuilder
{
    public function __construct(
        protected Person $people,
        protected Household $households,
        protected ParentRelationship $parents,
    ) {
    }

    /**
     * Create a person.
     */
public function person(array $attributes = []): Person
{
    return Person::factory()->create($attributes);
}
    /**
     * Create a household.
     */
public function household(array $attributes = []): Household
{
    return Household::factory()->create($attributes);
}

    /**
     * Marry two people.
     */
    public function marry(Person $husband, Person $wife): void
    {
        $husband->update([
            'spouse_id' => $wife->id,
        ]);

        $wife->update([
            'spouse_id' => $husband->id,
        ]);
    }

    /**
     * Create a child for a husband and wife.
     */
    public function childOf(
        Person $father,
        Person $mother,
        array $attributes,
    ): Person {

        $attributes['household_id'] = $father->household_id;

$child = Person::factory()->create($attributes);

        $this->parents->create([
            'person_id' => $child->id,
            'parent_id' => $father->id,
            'relationship' => 'Father',
        ]);

        $this->parents->create([
            'person_id' => $child->id,
            'parent_id' => $mother->id,
            'relationship' => 'Mother',
        ]);

        return $child;
    }

public function householdHead(
    Household $household,
    Person $head,
): Household {
    $household->update([
        'household_head_id' => $head->id,
    ]);

    return $household->refresh();
}

public function churchProfile(
    Person $person,
    array $attributes = [],
): ChurchProfile {

    return ChurchProfile::factory()->create([
        'person_id' => $person->id,
        ...$attributes,
    ]);
}

public function educationProfile(
    Person $person,
    array $attributes = [],
): EducationProfile {

    return EducationProfile::factory()->create([
        'person_id' => $person->id,
        ...$attributes,
    ]);
}

public function emergencyContact(
    Person $person,
    Person $contact,
    string $relationship,
): Person {

    $person->update([

        'emergency_contact_id' => $contact->id,

        'emergency_contact_relationship' => $relationship,

        'emergency_contact_number' => $contact->contact_number,

    ]);

    return $person->refresh();
}

public function family(
    array $household,
    array $husband,
    array $wife,
): array {

    $house = $this->household($household);

    $husband['household_id'] = $house->id;
    $wife['household_id'] = $house->id;

    $man = $this->person($husband);

    $woman = $this->person($wife);

    $this->marry($man, $woman);

    $this->householdHead($house, $man);

    return [

        'household' => $house,

        'husband' => $man,

        'wife' => $woman,

    ];
}

public function setParents(
    Person $child,
    ?Person $father = null,
    ?Person $mother = null,
): void {
    if ($father) {
        ParentRelationship::updateOrCreate(
            [
                'person_id' => $child->id,
                'relationship' => 'Father',
            ],
            [
                'parent_id' => $father->id,
                'parent_name' => null,
            ],
        );
    }

    if ($mother) {
        ParentRelationship::updateOrCreate(
            [
                'person_id' => $child->id,
                'relationship' => 'Mother',
            ],
            [
                'parent_id' => $mother->id,
                'parent_name' => null,
            ],
        );
    }
}


}
