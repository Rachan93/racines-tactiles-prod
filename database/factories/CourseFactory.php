<?php

namespace Database\Factories;

use App\Models\Type;
use App\Models\Instructor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+3 months');
        $endDate = clone $startDate;
        $endDate->modify('+' . rand(2, 7) . ' months');

        return [
            'type_id' => Type::query()->inRandomOrder()->first()->id,
            'default_instructor_id' => Instructor::query()->inRandomOrder()->first()->id,
            'name' => $this->faker->randomElement([
                'Remplacer par nom adéquat blabla',
                'Remplacer par nom adéquat khringo',
                'Remplacer par nom adéquat msemmen',
                'Remplacer par nom adéquat baghrir',
                'Remplacer par nom adéquat meloui',
                'Remplacer par nom adéquat ghrayef'
            ]) . ' ' . $this->faker->unique()->randomNumber(5),
            'first_lesson_date' => $startDate,
            'end_date' => $endDate,
            'default_start_time' => $this->faker->randomElement(['09:00', '14:00', '18:00']),
            'default_end_time' => $this->faker->randomElement(['12:00', '17:00', '21:00']),
            'frequency' => $this->faker->randomElement([7, 14, 28]), // hebdo, bihebdo, mensuel
            'default_spots_max_handbuilding' => $this->faker->numberBetween(4, 8),
            'default_spots_max_wheel' => $this->faker->numberBetween(2, 4),
            'default_price' => $this->faker->randomFloat(2, 35, 75),
        ];
    }
}
