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
            // El campo booleano para controlar la tarea. Nace en false.
            $table->boolean('prl_revisado')->default(false)->after('activo');
            
            // El campo de fecha para medir los 14 días de la chapita naranja.
            $table->date('fecha_reactivacion')->nullable()->after('prl_revisado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->dropColumn(['prl_revisado', 'fecha_reactivacion']);
        });
    }
};