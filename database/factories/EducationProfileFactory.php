<?php

namespace Database\Factories;

use App\Models\EducationProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationProfile>
 */
class EducationProfileFactory extends Factory
{
    protected $model = EducationProfile::class;

    public function definition(): array
    {
        return [

            'person_id' => null,

            'grade_level' => $this->faker->randomElement([
                'Elementary',
                'Junior High',
                'Senior High',
                'College',
                'Graduate',
            ]),

            'course_strand' => $this->faker->optional()->randomElement([
                'BS Electronics Engineering',
                'BS Computer Engineering',
                'AB English',
                'STEM',
                'HUMSS',
            ]),

            'occupation' => $this->faker->jobTitle(),

            'school_workplace' => $this->faker->company(),
        ];
    }
}
