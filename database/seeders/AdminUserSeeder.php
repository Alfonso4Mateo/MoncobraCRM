<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
// Ya no hace falta importar Hash si tu modelo User tiene el cast 'password' => 'hashed'

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'admin@moncobra.local'; // Correo para iniciar sesión
        
        // No crear si ya existe
        if (User::where('email', $email)->exists()) {
            return;
        }

        User::create([
            'name'          => 'Administrador',
            'apellido'      => 'Principal', // Añadido para que no dé error
            'email'         => $email,
            'password'      => 'Admin2026!', // Contraseña para entrar
            'role'          => 'superadmin',
            'activo'        => true,
            'tipo_personal' => 'indefinido', // Campo obligatorio que añadimos antes
        ]);
    }
}