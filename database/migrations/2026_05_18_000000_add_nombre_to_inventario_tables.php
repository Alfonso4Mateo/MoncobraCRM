<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('inventario', 'nombre')) {
            Schema::table('inventario', function (Blueprint $table) {
                $table->string('nombre')->nullable()->after('codigo');
            });
        }

        if (Schema::hasTable('inventario_variantes') && !Schema::hasColumn('inventario_variantes', 'nombre')) {
            Schema::table('inventario_variantes', function (Blueprint $table) {
                $table->string('nombre')->nullable()->after('codigo');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('inventario', 'nombre')) {
            Schema::table('inventario', function (Blueprint $table) {
                $table->dropColumn('nombre');
            });
        }

        if (Schema::hasTable('inventario_variantes') && Schema::hasColumn('inventario_variantes', 'nombre')) {
            Schema::table('inventario_variantes', function (Blueprint $table) {
                $table->dropColumn('nombre');
            });
        }
    }
};
