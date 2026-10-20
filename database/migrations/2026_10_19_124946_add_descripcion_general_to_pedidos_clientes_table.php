<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up()
    {
        Schema::table('pedidos_clientes', function (Blueprint $table) {
            // Añadimos el campo justo después de la referencia manual para mantener orden
            $table->text('descripcion_general')->nullable()->after('referencia_manual');
        });
    }

    public function down()
    {
        Schema::table('pedidos_clientes', function (Blueprint $table) {
            $table->dropColumn('descripcion_general');
        });
    }
};
