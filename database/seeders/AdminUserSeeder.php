<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'admin@example.com';
        // Do not create if already exists
        if (User::where('email', $email)->exists()) {
            return;
        }

        User::create([
            'name' => 'Administrador',
            'apellido' => '',
            'email' => $email,
            'password' => 'Admin2026!', // will be hashed by the model cast
            'role' => 'superadmin',
            'activo' => true,
        ]);
    }
}
