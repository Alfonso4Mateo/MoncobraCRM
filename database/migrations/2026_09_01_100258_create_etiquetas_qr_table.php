<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etiquetas_qr', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('contenido_datos'); // La URL de Drive o el link de Forms
            
            $table->foreignId('carpeta_id')
                  ->constrained('qr_carpetas')
                  ->restrictOnDelete(); // Impide borrar la carpeta si tiene QRs dentro
                  
            $table->string('ruta_archivo')->nullable(); // Ruta local del SVG/PNG
            $table->boolean('activo')->default(true);
            
            $table->timestamps();
            $table->softDeletes(); // Baja lógica
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etiquetas_qr');
    }
};