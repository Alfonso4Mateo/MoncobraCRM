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

        Schema::table('puestos', function (Blueprint $table) use ($defaultProyectoId) {
            if (!Schema::hasColumn('puestos', 'proyecto_id')) {
                $table->unsignedBigInteger('proyecto_id')->default($defaultProyectoId)->after('id');
                $table->foreign('proyecto_id')->references('id')->on('proyectos')->cascadeOnDelete();
                $table->index('proyecto_id');
            }
        });

        Schema::table('epis', function (Blueprint $table) use ($defaultProyectoId) {
            if (!Schema::hasColumn('epis', 'proyecto_id')) {
                $table->unsignedBigInteger('proyecto_id')->default($defaultProyectoId)->after('id');
                $table->foreign('proyecto_id')->references('id')->on('proyectos')->cascadeOnDelete();
                $table->index('proyecto_id');
            }
        });

        try {
            Schema::table('puestos', function (Blueprint $table) {
                $table->dropUnique(['nombre']);
                $table->unique(['proyecto_id', 'nombre']);
            });
        } catch (\Throwable) {}

        try {
            Schema::table('epis', function (Blueprint $table) {
                $table->dropUnique(['nombre']);
                $table->unique(['proyecto_id', 'nombre']);
            });
        } catch (\Throwable) {}
    }

    public function down(): void
    {
        Schema::table('puestos', function (Blueprint $table) {
            if (Schema::hasColumn('puestos', 'proyecto_id')) {
                $table->dropForeign(['proyecto_id']);
                $table->dropColumn('proyecto_id');
            }
        });

        Schema::table('epis', function (Blueprint $table) {
            if (Schema::hasColumn('epis', 'proyecto_id')) {
                $table->dropForeign(['proyecto_id']);
                $table->dropColumn('proyecto_id');
            }
        });
    }
};