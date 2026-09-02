<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Type::create(['name' => 'collectif', 'allows_makeup' => true, 'makeup_amount' => 2]);
        Type::create(['name' => 'stage']);
        Type::create(['name' => 'privé']);
    }
}
