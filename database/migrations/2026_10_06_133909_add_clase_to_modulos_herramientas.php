<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aparatos_calibrables', function (Blueprint $table) {
            $table->string('clase')->nullable()->after('categoria');
        });

        Schema::table('equipos_informaticos', function (Blueprint $table) {
            $table->string('clase')->nullable()->after('categoria');
        });

        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->string('clase')->nullable()->after('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('aparatos_calibrables', function (Blueprint $table) {
            $table->dropColumn('clase');
        });

        Schema::table('equipos_informaticos', function (Blueprint $table) {
            $table->dropColumn('clase');
        });

        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->dropColumn('clase');
        });
    }
};
