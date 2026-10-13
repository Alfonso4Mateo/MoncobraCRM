<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solo añade softDeletes a clientes si NO tiene la columna deleted_at
        if (!Schema::hasColumn('clientes', 'deleted_at')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Solo añade softDeletes a proyectos si NO tiene la columna deleted_at
        if (!Schema::hasColumn('proyectos', 'deleted_at')) {
            Schema::table('proyectos', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clientes', 'deleted_at')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('proyectos', 'deleted_at')) {
            Schema::table('proyectos', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};