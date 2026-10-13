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
            $table->dropColumn('ubicacion'); // Borramos el texto plano
            $table->foreignId('ubicacion_id')->nullable()->after('estado')->constrained('ubicaciones')->nullOnDelete();
        });

        // 2. Equipos Informáticos
        Schema::table('equipos_informaticos', function (Blueprint $table) {
            $table->dropColumn('ubicacion'); 
            $table->foreignId('ubicacion_id')->nullable()->after('estado')->constrained('ubicaciones')->nullOnDelete();
        });

        // 3. Herramientas Planta
        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->dropColumn('ubicacion'); // Borramos el texto plano
            $table->dropColumn('ubicacion_2'); // ELIMINAMOS EL CAMPO FANTASMA
            $table->foreignId('ubicacion_id')->nullable()->after('estado')->constrained('ubicaciones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Rollback por si hay que echar marcha atrás
        Schema::table('aparatos_calibrables', function (Blueprint $table) {
            $table->dropForeign(['ubicacion_id']);
            $table->dropColumn('ubicacion_id');
            $table->string('ubicacion')->nullable();
        });

        Schema::table('equipos_informaticos', function (Blueprint $table) {
            $table->dropForeign(['ubicacion_id']);
            $table->dropColumn('ubicacion_id');
            $table->string('ubicacion')->nullable();
        });

        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->dropForeign(['ubicacion_id']);
            $table->dropColumn('ubicacion_id');
            $table->string('ubicacion')->nullable();
            $table->string('ubicacion_2')->nullable();
        });
    }
};