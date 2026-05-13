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
        Schema::table('inventario', function (Blueprint $table) {
            // Agregar columna de referencia a la variante
            $table->foreignId('inventario_variante_id')->nullable()->after('id')->constrained('inventario_variantes')->cascadeOnDelete();
            
            // Agregar campo para atributos dinámicos de la variante (JSON)
            $table->json('atributos_variante')->nullable()->after('almacen')->comment('Atributos dinámicos de la variante, ej: {"Talla": "M", "Color": "Rojo"}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario', function (Blueprint $table) {
            $table->dropForeignIdFor('inventario_variantes');
            $table->dropColumn(['inventario_variante_id', 'atributos_variante']);
        });
    }
};
