<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultProyectoId = (int) (DB::table('proyectos')->orderBy('id')->value('id') ?? 1);

        foreach (['herramientas_planta', 'equipos_informaticos', 'aparatos_calibrables'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $defaultProyectoId) {
                if (!Schema::hasColumn($tableName, 'proyecto_id')) {
                    $table->unsignedBigInteger('proyecto_id')->default($defaultProyectoId)->after('id');
                    $table->foreign('proyecto_id')->references('id')->on('proyectos')->cascadeOnDelete();
                    $table->index('proyecto_id');
                }
            });
        }

        // En herramientas_planta, cambiar unique global de id_interno por unique compuesto ['proyecto_id', 'id_interno']
        try {
            Schema::table('herramientas_planta', function (Blueprint $table) {
                $table->dropUnique(['id_interno']);
                $table->unique(['proyecto_id', 'id_interno']);
            });
        } catch (\Throwable) {
            // Ignorar si ya fue ajustado
        }
    }

    public function down(): void
    {
        try {
            Schema::table('herramientas_planta', function (Blueprint $table) {
                $table->dropUnique(['proyecto_id', 'id_interno']);
            });
        } catch (\Throwable) {}

        foreach (['herramientas_planta', 'equipos_informaticos', 'aparatos_calibrables'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'proyecto_id')) {
                    $table->dropForeign(['proyecto_id']);
                    $table->dropColumn('proyecto_id');
                }
            });
        }
    }
};