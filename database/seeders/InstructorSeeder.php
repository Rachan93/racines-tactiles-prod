<?php

namespace Database\Seeders;

use App\Models\Instructor;
use Illuminate\Database\Seeder;

//version sans faker utilsable en prod
class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        Instructor::firstOrCreate(
            [
                'first_name' => 'Thomas',
                'last_name' => 'Flamant',
            ],
            [
                'bio' => 'Instructeur en céramique.',
            ]
        );

        Instructor::firstOrCreate(
            [
                'first_name' => 'Anne',
                'last_name' => 'Hick',
            ],
            [
                'bio' => 'Instructrice en céramique.',
            ]
        );

        Instructor::firstOrCreate(
            [
                'first_name' => 'Gordon',
                'last_name' => 'Freeman',
            ],
            [
                'bio' => 'Instructeur en céramique.',
            ]
        );

        Instructor::firstOrCreate(
            [
                'first_name' => 'Jill',
                'last_name' => 'Valentine',
            ],
            [
                'bio' => 'Instructrice en céramique.',
            ]
        );
    }
}
