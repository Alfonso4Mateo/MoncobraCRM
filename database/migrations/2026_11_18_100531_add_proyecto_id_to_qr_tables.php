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

        foreach (['qr_carpetas', 'etiquetas_qr'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $defaultProyectoId) {
                if (!Schema::hasColumn($tableName, 'proyecto_id')) {
                    $table->unsignedBigInteger('proyecto_id')->default($defaultProyectoId)->after('id');
                    $table->foreign('proyecto_id')->references('id')->on('proyectos')->cascadeOnDelete();
                    $table->index('proyecto_id');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['qr_carpetas', 'etiquetas_qr'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'proyecto_id')) {
                    $table->dropForeign(['proyecto_id']);
                    $table->dropColumn('proyecto_id');
                }
            });
        }
    }
};