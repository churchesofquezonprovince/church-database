<?php

namespace Database\Factories;

use App\Models\ChurchProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChurchProfile>
 */
class ChurchProfileFactory extends Factory
{
    protected $model = ChurchProfile::class;

    public function definition(): array
    {
        return [

            'person_id' => null,

            'category' => $this->faker->randomElement([
                'Children',
                'Young People',
                'College',
                'Working Saints',
                'Senior Saints',
                'New One',
            ]),

            'baptism_date' => $this->faker->optional()->date(),

            'shepherd_id' => null,

            'introduced_by_id' => null,

            'service' => $this->faker->optional()->randomElement([
                'Usher',
                'Children Service',
                'Music',
                'Sound',
                'Hospitality',
            ]),

            'status' => 'Active',
        ];
    }
}
