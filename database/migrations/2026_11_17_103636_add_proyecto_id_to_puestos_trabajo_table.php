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

        Schema::table('puestos_trabajo', function (Blueprint $table) use ($defaultProyectoId) {
            if (!Schema::hasColumn('puestos_trabajo', 'proyecto_id')) {
                $table->unsignedBigInteger('proyecto_id')->default($defaultProyectoId)->after('id');
                $table->foreign('proyecto_id')->references('id')->on('proyectos')->cascadeOnDelete();
                $table->index('proyecto_id');
            }
        });

        // Sustituir índice único global sobre 'nombre' por índice único compuesto ['proyecto_id', 'nombre']
        try {
            Schema::table('puestos_trabajo', function (Blueprint $table) {
                $table->dropUnique(['nombre']);
                $table->unique(['proyecto_id', 'nombre']);
            });
        } catch (\Throwable) {
            // Ignorar si el índice ya fue ajustado previamente
        }
    }

    public function down(): void
    {
        Schema::table('puestos_trabajo', function (Blueprint $table) {
            if (Schema::hasColumn('puestos_trabajo', 'proyecto_id')) {
                try {
                    $table->dropUnique(['proyecto_id', 'nombre']);
                } catch (\Throwable) {}

                $table->dropForeign(['proyecto_id']);
                $table->dropColumn('proyecto_id');
            }
        });
    }
};