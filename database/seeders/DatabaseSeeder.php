<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@misterplan.com',
            'password' => bcrypt('password'),
        ]);

        // Segundo usuario para probar autorización (no tiene reservas propias)
        \App\Models\User::factory()->create([
            'name' => 'Otro',
            'email' => 'otro@misterplan.com',
            'password' => bcrypt('password'),
        ]);

        $this->call([
            ReservationSeeder::class,
        ]);
    }
}
