<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => null,
            'module_id' => null,

            'status' => 'registered',
            'enrollment_type' => 'regular',
            'spot_type' => $this->faker->randomElement(['wheel', 'handbuilding']),
            'replaces_absence_id' => null,
            'cancellation_date' => null,
        ];


    }
    public function registered()
    {
        return $this->state(fn () => ['status' => 'registered', 'cancellation_date' => null]);
    }

    public function absent()
    {
        return $this->state(fn () => ['status' => 'absent', 'cancellation_date' => null]);
    }

    public function cancelled()
    {
        return $this->state(function () {
            return [
                'status' => 'cancelled',
                'cancellation_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            ];
        });
    }
}
