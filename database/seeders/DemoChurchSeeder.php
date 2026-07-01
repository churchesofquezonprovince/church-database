<?php

namespace Database\Seeders;

use App\Domain\Church\ChurchBuilder;
use Illuminate\Database\Seeder;

class DemoChurchSeeder extends Seeder
{
    public function run(): void
    {
        $builder = app(ChurchBuilder::class);

        $this->seedSantosFamily($builder);
        $this->seedReyesFamily($builder);
    }

    private function seedSantosFamily(ChurchBuilder $builder): void
    {
        // Generation 1: Santos grandparents
        $santosGrandparents = $builder->family(
            household: [
                'household_name' => 'Santos Ancestral Family',
                'locality' => 'Lucena City',
                'address' => 'Old Lucena Road',
            ],
            husband: [
                'firstname' => 'Gregorio',
                'middlename' => null,
                'lastname' => 'Santos',
                'sex' => 'Male',
                'birthdate' => '1948-03-12',
            ],
            wife: [
                'firstname' => 'Elena',
                'middlename' => null,
                'lastname' => 'Santos',
                'sex' => 'Female',
                'birthdate' => '1951-07-25',
            ],
        );

        // Generation 2: Juan and Maria
        $santos = $builder->family(
            household: [
                'household_name' => 'Santos Family',
                'locality' => 'Lucena City',
                'address' => 'Maharlika Highway',
            ],
            husband: [
                'firstname' => 'Juan',
                'middlename' => null,
                'lastname' => 'Santos',
                'sex' => 'Male',
                'birthdate' => '1978-06-15',
            ],
            wife: [
                'firstname' => 'Maria',
                'middlename' => null,
                'lastname' => 'Santos',
                'sex' => 'Female',
                'birthdate' => '1980-02-21',
            ],
        );

        $builder->setParents(
            child: $santos['husband'],
            father: $santosGrandparents['husband'],
            mother: $santosGrandparents['wife'],
        );

        // Maria's parents
        $cruzGrandparents = $builder->family(
            household: [
                'household_name' => 'Cruz Family',
                'locality' => 'Lucena City',
                'address' => 'Quezon Avenue',
            ],
            husband: [
                'firstname' => 'Roberto',
                'middlename' => null,
                'lastname' => 'Cruz',
                'sex' => 'Male',
                'birthdate' => '1950-01-10',
            ],
            wife: [
                'firstname' => 'Lourdes',
                'middlename' => null,
                'lastname' => 'Cruz',
                'sex' => 'Female',
                'birthdate' => '1953-09-18',
            ],
        );

        $builder->setParents(
            child: $santos['wife'],
            father: $cruzGrandparents['husband'],
            mother: $cruzGrandparents['wife'],
        );

        // Generation 3: Juan and Maria's children
        $peter = $builder->childOf($santos['husband'], $santos['wife'], [
            'firstname' => 'Peter',
            'middlename' => null,
            'lastname' => 'Santos',
            'sex' => 'Male',
            'birthdate' => '2002-04-05',
        ]);

        $builder->childOf($santos['husband'], $santos['wife'], [
            'firstname' => 'Anna',
            'middlename' => null,
            'lastname' => 'Santos',
            'sex' => 'Female',
            'birthdate' => '2005-08-17',
        ]);

        $builder->childOf($santos['husband'], $santos['wife'], [
            'firstname' => 'Paul',
            'middlename' => null,
            'lastname' => 'Santos',
            'sex' => 'Male',
            'birthdate' => '2008-11-03',
        ]);

        // Peter's spouse
        $ruth = $builder->person([
            'firstname' => 'Ruth',
            'middlename' => null,
            'lastname' => 'Garcia',
            'sex' => 'Female',
            'birthdate' => '2003-12-09',
            'household_id' => $santos['household']->id,
        ]);

        $builder->marry($peter, $ruth);

        // Generation 4: Juan and Maria's grandchild
/*
        $builder->childOf($peter, $ruth, [
            'firstname' => 'Daniel',
            'middlename' => null,
            'lastname' => 'Santos',
            'sex' => 'Male',
            'birthdate' => '2025-01-15',
        ]);
*/
$daniel = $builder->childOf($peter, $ruth, [
    'firstname' => 'Daniel',
    'middlename' => null,
    'lastname' => 'Santos',
    'sex' => 'Male',
    'birthdate' => '2025-01-15',
]);

// Demo church care assignments
$builder->churchProfile($santos['husband'], [
    'status' => 'Active',
    'service' => 'Middle Age (Age 46 - Age 59)',
]);

$builder->churchProfile($santos['wife'], [
    'status' => 'Full-Timer',
    'service' => 'Middle Age (Age 46 - Age 59)',
]);

$builder->churchProfile($peter, [
    'status' => 'Active',
    'service' => 'Young Adults (Graduates - Age 45)',
    'shepherd_id' => $santos['husband']->id,
    'introduced_by_id' => $santos['wife']->id,
]);

$builder->churchProfile($ruth, [
    'status' => 'New One',
    'service' => 'Young Adults (Graduates - Age 45)',
    'shepherd_id' => $santos['wife']->id,
    'introduced_by_id' => $peter->id,
]);

$anna = \App\Models\Person::where('firstname', 'Anna')
    ->where('lastname', 'Santos')
    ->first();

$paul = \App\Models\Person::where('firstname', 'Paul')
    ->where('lastname', 'Santos')
    ->first();

if ($anna) {
    $builder->churchProfile($anna, [
        'status' => 'New One',
        'service' => 'Collegian (G11-C1)',
        'shepherd_id' => $santos['wife']->id,
        'introduced_by_id' => $santos['husband']->id,
    ]);
}

if ($paul) {
    $builder->churchProfile($paul, [
        'status' => 'Gospel Friend',
        'service' => 'Young People (G8-G10)',
        'shepherd_id' => $santos['husband']->id,
        'introduced_by_id' => $peter->id,
    ]);
}

$builder->churchProfile($daniel, [
    'status' => 'Active',
    'service' => 'Children (Toddler-Kinder)',
    'shepherd_id' => $peter->id,
    'introduced_by_id' => $ruth->id,
]);

    }

    private function seedReyesFamily(ChurchBuilder $builder): void
    {
        $builder->family(
            household: [
                'household_name' => 'Reyes Family',
                'locality' => 'Lucena City',
                'address' => 'Dalahican Road',
            ],
            husband: [
                'firstname' => 'Mark',
                'middlename' => null,
                'lastname' => 'Reyes',
                'sex' => 'Male',
                'birthdate' => '1990-05-11',
            ],
            wife: [
                'firstname' => 'Grace',
                'middlename' => null,
                'lastname' => 'Reyes',
                'sex' => 'Female',
                'birthdate' => '1992-10-22',
            ],
        );
    }
}
