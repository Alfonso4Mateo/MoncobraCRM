<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('herramientas', function (Blueprint $table) {
            $table->string('tipo_equipo')->nullable()->after('categoria');
            $table->string('sistema_operativo')->nullable()->after('modelo');
            $table->string('procesador')->nullable()->after('sistema_operativo');
            $table->string('memoria_ram')->nullable()->after('procesador');
            $table->string('almacenamiento')->nullable()->after('memoria_ram');
            $table->string('mac_address', 50)->nullable()->after('numero_serie');
            $table->string('ip_address', 50)->nullable()->after('mac_address');
            $table->date('garantia_hasta')->nullable()->after('fecha_adquisicion');
            $table->date('soporte_hasta')->nullable()->after('garantia_hasta');
            $table->string('condicion_fisica')->nullable()->after('estado');
            $table->string('etiqueta_color', 30)->nullable()->after('condicion_fisica');
        });
    }

    public function down(): void
    {
        Schema::table('herramientas', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_equipo', 'sistema_operativo', 'procesador', 'memoria_ram',
                'almacenamiento', 'mac_address', 'ip_address', 'garantia_hasta',
                'soporte_hasta', 'condicion_fisica', 'etiqueta_color',
            ]);
        });
    }
};
