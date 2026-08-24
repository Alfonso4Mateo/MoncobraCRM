<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('puestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('curso_puesto', function (Blueprint $table) {
        $table->id();
        $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
        $table->foreignId('puesto_id')->constrained('puestos')->onDelete('cascade');
        $table->boolean('es_obligatorio')->default(true);
        $table->timestamps();
        
        // Evitamos duplicidades: el puesto X no puede tener asignado el curso Y dos veces
        $table->unique(['curso_id', 'puesto_id']);
        });

        // 2. Asignación de Puestos a Trabajadores (Polivalencia)
        Schema::create('personal_puesto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personal')->onDelete('cascade');
            $table->foreignId('puesto_id')->constrained('puestos')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['personal_id', 'puesto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Primero borramos las tablas dependientes (pivotes)
        Schema::dropIfExists('personal_puesto');
        Schema::dropIfExists('curso_puesto');
        
        // 2. Finalmente borramos la tabla principal
        Schema::dropIfExists('puestos');
    }
};
