<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        $sex = $this->faker->randomElement([
            'Male',
            'Female',
        ]);

        return [

            'firstname' => $sex === 'Male'
                ? $this->faker->firstNameMale()
                : $this->faker->firstNameFemale(),

            'middlename' => $this->faker->lastName(),

            'lastname' => $this->faker->lastName(),

            'suffix' => null,

            'sex' => $sex,

            'nickname' => null,

            'birthdate' => $this->faker->dateTimeBetween('-70 years', '-5 years'),

            'birthplace' => 'Lucena City',

            'household_id' => null,

            'spouse_id' => null,

            'locality' => null,

            'permanent_address' => null,

            'home_address' => null,

            'geocoordinates' => null,

            'email' => $this->faker->unique()->safeEmail(),

            'contact_number' => '09'.$this->faker->numerify('#########'),

            'emergency_contact_id' => null,

            'emergency_contact_relationship' => null,

            'emergency_contact_number' => null,
        ];
    }
}
