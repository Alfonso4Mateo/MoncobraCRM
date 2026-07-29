<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('personal', function (Blueprint $table) {
        // string(nombre, longitud). Le ponemos nullable por si algún trabajador antiguo no lo tiene.
        // unique() es muy recomendable para que no haya dos personas con el mismo ID de RRHH.
        $table->string('id_rrhh', 10)->nullable()->unique()->after('dni_nie');
    });
}

public function down()
{
    Schema::table('personal', function (Blueprint $table) {
        $table->dropColumn('id_rrhh');
    });
}
};  
