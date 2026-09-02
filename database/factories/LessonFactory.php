<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'override_instructor_id' => null,
            'override_start_time' => null,
            'override_end_time' => null,
            'override_spots_max_handbuilding' => null,
            'override_spots_max_wheel' => null,
            'override_price' => null,
            'is_cancelled' => false,
            'cancellation_reason' => null,
            'is_overridden' => false,
        ];
    }

    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_cancelled' => true,
                'cancellation_reason' => $this->faker->sentence(),
            ];
        });
    }

    // Nouvelle méthode pour marquer comme overridden sans changer les autres champs
    public function markAsOverridden($isOverridden = true)
    {
        return $this->state(function (array $attributes) use ($isOverridden) {
            return [
                'is_overridden' => $isOverridden,
            ];
        });
    }

    // Méthode pour appliquer des overrides aléatoirement avec une probabilité corrélée
    public function randomOverrides($overrideProbability = 0.6)
    {
        return $this->state(function (array $attributes) use ($overrideProbability) {
            $isOverridden = $this->faker->boolean($overrideProbability * 100);

            // Si marqué comme overridden, plus forte probabilité d'avoir des valeurs modifiées
            $fieldOverrideProbability = $isOverridden ? 0.2 : 0.1;

            $overrides = [
                'is_overridden' => $isOverridden,
            ];

            if ($this->faker->boolean($fieldOverrideProbability * 100)) {
                $overrides['override_instructor_id'] = Instructor::query()->inRandomOrder()->first()->id;
            }

            if ($this->faker->boolean($fieldOverrideProbability * 100)) {
                $overrides['override_start_time'] = $this->faker->randomElement(['10:00', '15:00', '19:00']);
            }

            if ($this->faker->boolean($fieldOverrideProbability * 100)) {
                $overrides['override_end_time'] = $this->faker->randomElement(['13:00', '18:00', '22:00']);
            }

            if ($this->faker->boolean($fieldOverrideProbability * 100)) {
                $overrides['override_price'] = $this->faker->randomFloat(2, 40, 80);
            }

            if ($this->faker->boolean($fieldOverrideProbability * 100)) {
                $overrides['override_spots_max_wheel'] = $this->faker->numberBetween(1, 3);
            }

            if ($this->faker->boolean($fieldOverrideProbability * 100)) {
                $overrides['override_spots_max_handbuilding'] = $this->faker->numberBetween(3, 6);
            }

            return $overrides;
        });
    }
}
