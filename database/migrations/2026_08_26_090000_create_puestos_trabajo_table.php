<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de "Puestos de Trabajo" usado para la vigilancia de la salud.
     * Es independiente del modelo Puesto (que en realidad es el "Perfil Formativo"
     * usado para la matriz de cursos obligatorios).
     */
    public function up(): void
    {
        Schema::create('puestos_trabajo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            // Periodicidad en meses para el reconocimiento médico de este puesto (ej: 12 = anual)
            $table->unsignedSmallInteger('periodicidad_meses')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puestos_trabajo');
    }
};
