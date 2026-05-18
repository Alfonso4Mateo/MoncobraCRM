<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('inventario', function (Blueprint $table) {
        // Añadimos la columna tipo json
        $table->json('atributos_variante')->nullable()->after('nivel_critico');
    });
}

public function down()
{
    Schema::table('inventario', function (Blueprint $table) {
        $table->dropColumn('atributos_variante');
    });
}
};
