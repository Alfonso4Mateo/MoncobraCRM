<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Aparatos Calibrables
        Schema::table('aparatos_calibrables', function (Blueprint $table) {
            $table->foreignId('departamento_id')->nullable()->after('ubicacion_id')->constrained('departamentos')->nullOnDelete();
        });

        // 2. Equipos Informáticos
        Schema::table('equipos_informaticos', function (Blueprint $table) {
            $table->foreignId('departamento_id')->nullable()->after('ubicacion_id')->constrained('departamentos')->nullOnDelete();
        });

        // 3. Herramientas Planta
        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->foreignId('departamento_id')->nullable()->after('ubicacion_id')->constrained('departamentos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aparatos_calibrables', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropColumn('departamento_id');
        });

        Schema::table('equipos_informaticos', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropColumn('departamento_id');
        });

        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropColumn('departamento_id');
        });
    }
};