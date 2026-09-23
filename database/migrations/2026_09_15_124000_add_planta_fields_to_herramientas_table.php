<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('herramientas', function (Blueprint $table) {
            $table->string('familia_energetica')->nullable()->after('categoria');
            $table->string('potencia')->nullable()->after('familia_energetica');
            $table->string('tension')->nullable()->after('potencia');
            $table->string('combustible')->nullable()->after('tension');
            $table->string('criticidad', 30)->nullable()->after('estado');
            $table->unsignedInteger('horas_uso')->nullable()->after('fecha_mantenimiento');
            $table->boolean('bloqueado')->default(false)->after('activo');
            $table->string('motivo_bloqueo')->nullable()->after('bloqueado');
        });
    }

    public function down(): void
    {
        Schema::table('herramientas', function (Blueprint $table) {
            $table->dropColumn([
                'familia_energetica', 'potencia', 'tension', 'combustible',
                'criticidad', 'horas_uso', 'bloqueado', 'motivo_bloqueo',
            ]);
        });
    }
};
