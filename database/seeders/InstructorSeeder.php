<?php

namespace Database\Seeders;

use App\Models\Instructor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Instructor::factory()->create([
            'first_name' => 'Thomas',
            'last_name' => 'Flamant',
        ]);
        Instructor::factory()->create([
            'first_name' => 'Anne',
            'last_name' => 'Hick',
            ]);
        Instructor::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'M. Heerde',
        ]);
        Instructor::factory()->create([
            'first_name' => 'Jill',
            'last_name' => 'Valentine',
        ]);

    }
}
