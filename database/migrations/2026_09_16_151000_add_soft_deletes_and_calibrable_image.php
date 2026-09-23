<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos_informaticos', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('aparatos_calibrables', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('proveedor');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('aparatos_calibrables', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('imagen');
        });

        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('equipos_informaticos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
