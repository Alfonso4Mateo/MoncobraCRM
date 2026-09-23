<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('eventos', function (Blueprint $table) {
        // Añadimos el campo después de la descripción para mantener un orden lógico
        $table->string('archivo_adjunto')->nullable()->after('descripcion');
    });
}

public function down()
{
    Schema::table('eventos', function (Blueprint $table) {
        $table->dropColumn('archivo_adjunto');
    });
}
};
