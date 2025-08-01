<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Anang Egga',
            'email' => 'anangegga@gmail.com',
            'password' => '1122',
            'role' => 'admin',
        ]);
        \App\Models\AlamatSambung::create([
            'kelompok' => 'LAINNYA',
            'desa' => 'LAINNYA',
            'daerah' => 'LAINNYA',
        ]);
    }
}
