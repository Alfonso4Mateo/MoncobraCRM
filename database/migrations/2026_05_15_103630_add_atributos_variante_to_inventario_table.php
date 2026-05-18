<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('inventario', 'atributos_variante')) {
            Schema::table('inventario', function (Blueprint $table) {
                $table->json('atributos_variante')->nullable()->after('nivel_critico');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('inventario', 'atributos_variante')) {
            Schema::table('inventario', function (Blueprint $table) {
                $table->dropColumn('atributos_variante');
            });
        }
    }
};
