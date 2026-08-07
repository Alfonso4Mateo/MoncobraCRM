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
        Schema::table('personal', function (Blueprint $table) {
            // Añadimos los campos como string (VARCHAR en la base de datos)
            // Usamos ->nullable() porque habrá trabajadores que no tengan estos datos rellenados al principio
            // Usamos ->after('proxima_graduacion') para colocar las columnas de forma ordenada en la base de datos, en lugar de al final del todo.
            $table->string('reconocido_en')->nullable()->after('proxima_graduacion');
            $table->string('graduado_en')->nullable()->after('reconocido_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            // Si deshacemos la migración, borramos exactamente las columnas que creamos
            $table->dropColumn(['reconocido_en', 'graduado_en']);
        });
    }
};