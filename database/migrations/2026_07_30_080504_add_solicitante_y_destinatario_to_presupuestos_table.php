<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            // Añadimos los campos como string (VARCHAR).
            // Le ponemos nullable() por si en algún presupuesto no quieren rellenarlo.
            $table->string('solicitante')->nullable()->after('ot');
            $table->string('destinatario')->nullable()->after('solicitante');
        });
    }

    public function down()
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn(['solicitante', 'destinatario']);
        });
    }
};