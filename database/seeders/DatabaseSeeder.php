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
        // Limpiar datos previos
        $this->call(ResetDemoDataSeeder::class);

        // Crear proyectos primero
        $this->call(ProyectoSeeder::class);

        // Crear clientes antes de los presupuestos
        $this->call(ClienteSeeder::class);

        // Crear presupuestos de ejemplo
        $this->call(PresupuestoSeeder::class);

        // Crear albaranes de ejemplo
        $this->call(AlbaranClienteSeeder::class);

        // Crear pedidos de cliente de ejemplo
        $this->call(PedidoClienteSeeder::class);

        // Crear almacenes de ejemplo
        $this->call(AlmacenSeeder::class);

        // Crear inventario de ejemplo
        $this->call(InventarioSeeder::class);

        // Crear articulos de ejemplo
        $this->call(ArticuloSeeder::class);

        // Crear usuarios con diferentes roles
        $this->call(UserSeeder::class);
    }
}
