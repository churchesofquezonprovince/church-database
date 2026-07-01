<?php

namespace Database\Factories;

use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Household>
 */
class HouseholdFactory extends Factory
{
    protected $model = Household::class;

    public function definition(): array
    {
        return [
            'household_name' => $this->faker->lastName() . ' Family',
            'address' => $this->faker->streetAddress(),
            'locality' => $this->faker->randomElement([
                'Lucena City',
                'Mayao',
                'Ibabang Dupay',
                'Gulang-Gulang',
                'Dalahican',
            ]),
            'remarks' => null,
            'household_head_id' => null,
        ];
    }
}
