<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendee;
use Illuminate\Database\Seeder;

class AttendeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $users = User::get();

        foreach ($users as $user) {
            // 30% des utilisateurs auront des attendees
            if (fake()->boolean(30)) {
                // Créer entre 1 et 3 attendees par utilisateur
                $attendeeCount = fake()->numberBetween(1, 3);

                for ($i = 0; $i < $attendeeCount; $i++) {
                    Attendee::factory()->forUser($user)->create();
                }
            }
        }
    }
}
