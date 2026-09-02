<?php

namespace Database\Seeders;

use App\Models\StudioClosure;
use Illuminate\Database\Seeder;

class StudioClosureSeeder extends Seeder
{
    public function run(): void
    {
        $closures = [
            [
                'name' => "Vacances d'automne (Toussaint)",
                'type' => 'school_holiday',
                'start_date' => '2026-10-19',
                'end_date' => '2026-10-30',
            ],
            [
                'name' => "Vacances d'hiver (Noël)",
                'type' => 'school_holiday',
                'start_date' => '2026-12-21',
                'end_date' => '2027-01-01',
            ],
            [
                'name' => "Congé de détente (Carnaval)",
                'type' => 'school_holiday',
                'start_date' => '2027-02-15',
                'end_date' => '2027-02-26',
            ],
            [
                'name' => "Vacances de printemps (Pâques)",
                'type' => 'school_holiday',
                'start_date' => '2027-04-05',
                'end_date' => '2027-04-16',
            ],
            [
                'name' => "Maintenance annuelle des fours",
                'type' => 'studio_closure',
                'start_date' => '2026-11-11',
                'end_date' => '2026-11-13',
                'notes' => 'Fermeture exceptionnelle pour réfection des briques réfractaires.',
            ],
        ];

        foreach ($closures as $closure) {
            StudioClosure::firstOrCreate(
                ['name' => $closure['name'], 'start_date' => $closure['start_date']],
                $closure
            );
        }
    }
}
