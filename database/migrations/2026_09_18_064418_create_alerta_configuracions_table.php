<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas_configuraciones', function (Blueprint $table) {
            $table->id();
            
            // Identificador del módulo (Ej: 'mantenimiento', 'prl', 'rrhh')
            $table->string('modulo')->unique(); 
            
            // Aquí guardaremos la lista de correos en formato JSON: ["jefe@empresa.com", "taller@empresa.com"]
            $table->json('destinatarios')->nullable(); 
            
            // Configuración del Cron Job (Día y Hora)
            $table->string('dia_semana')->nullable(); // Ej: 'Lunes', 'Miércoles', 'Viernes', 'Diario'
            $table->time('hora_ejecucion')->nullable(); // Ej: '15:16:00'
            
            // Los checks de lo que quieren recibir: ["caducidades", "averias", "preventivos"]
            $table->json('tipos_reporte')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_configuraciones');
    }
};