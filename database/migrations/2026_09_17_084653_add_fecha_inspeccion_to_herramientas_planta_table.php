<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('herramientas_planta', function (Blueprint $table) {
            // Añadimos el campo justo después del mantenimiento
            $table->date('fecha_inspeccion')->nullable()->after('fecha_mantenimiento');
        });
    }

    public function down(): void
    {
        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->dropColumn('fecha_inspeccion');
        });
    }
};