<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Le decimos a Laravel que ejecute tu seeder de Administrador
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}