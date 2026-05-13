<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar tabla inventario_variantes para agregar tipos de atributos dinámicos
        Schema::table('inventario_variantes', function (Blueprint $table) {
            $table->json('tipos_atributos')->nullable()->after('nivel_critico')->comment('Tipos de variantes soportadas por este producto, ej: ["Talla", "Color"]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_variantes', function (Blueprint $table) {
            $table->dropColumn('tipos_atributos');
        });
    }
};
