<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos_informaticos', function (Blueprint $table) {
            $table->unsignedSmallInteger('dias_estimados_baja')->nullable()->after('estado');
            $table->date('fecha_baja')->nullable()->after('dias_estimados_baja');
        });
    }

    public function down(): void
    {
        Schema::table('equipos_informaticos', function (Blueprint $table) {
            $table->dropColumn(['dias_estimados_baja', 'fecha_baja']);
        });
    }
};