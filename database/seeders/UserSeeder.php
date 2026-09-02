<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Generator as Faker;



class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Faker $faker): void
    {
        User::factory()->create([
            'first_name' => 'User',
            'last_name' => 'Test',
            'email' => 'test@example.com',
            'role_id' => 1,
        ]);
        User::factory(100)->create();
    }
}
