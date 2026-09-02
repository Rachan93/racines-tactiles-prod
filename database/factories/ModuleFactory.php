<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Attendee;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
class ModuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            // paid_price n'est plus généré aléatoirement ici : il est calculé
            // dans ModuleSeeder::resolvePriceForType(), une fois le type de
            // cours et le nombre de séances connus (voir étape 4 du seeder).
            'paid_price' => null,
            'purchase_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'expiration_date' => $this->faker->optional()->dateTimeBetween('+9 months', '+12 months'),
            'is_active' => true,
        ];
    }

    public function forUser(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'participant_type' => User::class,
                'participant_id' => $user->id,
            ];
        });
    }

    public function forAttendee(Attendee $attendee)
    {
        return $this->state(function (array $attributes) use ($attendee) {
            return [
                'participant_type' => Attendee::class,
                'participant_id' => $attendee->id,
            ];
        });
    }
}
