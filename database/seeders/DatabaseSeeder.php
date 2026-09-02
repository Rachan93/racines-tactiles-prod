<?php

namespace Database\Seeders;

use Faker;
use App\Models\Course;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        // il faut faire attention à l'ordre dans lequel on appelle les seeders en fonction des clés étrangères

        $this->call([
            TypeSeeder::class,
            RoleSeeder::class,
            InstructorSeeder::class,
            CourseSeeder::class,
            UserSeeder::class,
            AttendeeSeeder::class,
            ModuleSeeder::class,
        ]);
    }
}
