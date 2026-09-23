<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entregas_epis', function (Blueprint $table) {
            $table->id();
            
            // Relación con el trabajador. Cascade asegura la limpieza de datos.
            $table->foreignId('personal_id')
                  ->constrained('personal')
                  ->onDelete('cascade');
                  
            $table->date('fecha_entrega');
            $table->string('tipo_documento'); // Ej: Entrega de EPIs, Info Riesgos
            $table->string('observaciones', 500)->nullable();
            $table->string('archivo_path'); // Ruta física en el disco (storage)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entregas_epis');
    }
};