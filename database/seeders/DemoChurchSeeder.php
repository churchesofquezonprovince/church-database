<?php

namespace Database\Seeders;

use App\Domain\Church\ChurchBuilder;
use Illuminate\Database\Seeder;

class DemoChurchSeeder extends Seeder
{
public function run(): void
{
    $builder = app(\App\Domain\Church\ChurchBuilder::class);

    /*
    |--------------------------------------------------------------------------
    | Santos Family
    |--------------------------------------------------------------------------
    */

    $santos = $builder->family(

        household: [

            'household_name' => 'Santos Family',

            'locality' => 'Lucena City',

            'address' => 'Maharlika Highway',

        ],

        husband: [

            'firstname' => 'Juan',

            'lastname' => 'Santos',

            'sex' => 'Male',

        ],

        wife: [

            'firstname' => 'Maria',

            'lastname' => 'Santos',

            'sex' => 'Female',

        ],

    );

    $builder->childOf(

        $santos['husband'],

        $santos['wife'],

        [

            'firstname' => 'Peter',

            'lastname' => 'Santos',

            'sex' => 'Male',

        ]

    );

    $builder->childOf(

        $santos['husband'],

        $santos['wife'],

        [

            'firstname' => 'Anna',

            'lastname' => 'Santos',

            'sex' => 'Female',

        ]

    );

    $builder->childOf(

        $santos['husband'],

        $santos['wife'],

        [

            'firstname' => 'Paul',

            'lastname' => 'Santos',

            'sex' => 'Male',

        ]

    );



$reyes = $builder->family(

    household: [

        'household_name' => 'Reyes Family',

    ],

    husband: [

        'firstname' => 'Mark',

        'lastname' => 'Reyes',

        'sex' => 'Male',

    ],

    wife: [

        'firstname' => 'Grace',

        'lastname' => 'Reyes',

        'sex' => 'Female',

    ],

);


}

}
