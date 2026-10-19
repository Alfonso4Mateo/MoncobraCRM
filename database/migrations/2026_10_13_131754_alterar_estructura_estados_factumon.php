<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Transformamos los ENUM restrictivos en VARCHAR(50) para que 
        // la base de datos acepte los estados con guiones bajos sin quejarse.
        
        DB::statement("ALTER TABLE presupuestos MODIFY COLUMN estado VARCHAR(50) DEFAULT 'pendiente'");
        DB::statement("ALTER TABLE pedidos_clientes MODIFY COLUMN estado VARCHAR(50) DEFAULT 'pendiente'");
        DB::statement("ALTER TABLE albaranes_clientes MODIFY COLUMN estado VARCHAR(50) DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        // No es estrictamente necesario definir el down para un paso a VARCHAR,
        // ya que el VARCHAR es totalmente compatible hacia atrás.
    }
};