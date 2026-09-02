<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendee>
 */
class AttendeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $birthday = $this->faker->boolean();
        return [
            'user_id' => User::inRandomOrder()->first()->id, // Utilise un utilisateur existant aléatoire
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'birthday' => $birthday ? $this->faker->dateTimeBetween('-75 years', '-10 years')->format('Y-m-d') : null,
        ];
    }

    /**
     * Configure l'attendee pour un utilisateur spécifique.
     */
    public function forUser(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }
}
