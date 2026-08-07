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
            // Añadimos el campo sin_tallas como boolean (TINYINT en la base de datos)
            // Usamos ->default(false) para que por defecto sea falso, y ->after('gafas') para colocarlo después de la columna gafas.
            $table->boolean('sin_tallas')->default(false)->after('gafas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            // Si deshacemos la migración, borramos exactamente la columna que creamos
            $table->dropColumn('sin_tallas');
        });
    }
};
