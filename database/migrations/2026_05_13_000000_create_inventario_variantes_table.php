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
        Schema::create('inventario_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            
            // Datos del producto base
            $table->string('codigo')->unique();
            $table->string('descripcion');
            $table->string('referencia_proveedor')->nullable();
            $table->foreignId('clase_id')->nullable()->constrained('clases')->cascadeOnDelete();
            
            // Campos comunes a todas las variantes de este producto
            $table->string('ubicacion')->nullable();
            $table->string('almacen')->nullable();
            $table->integer('stock_minimo')->default(0);
            $table->integer('nivel_critico')->default(0);
            
            $table->timestamps();
            
            // Índices para búsquedas comunes
            $table->index('codigo');
            $table->index('proyecto_id');
            $table->index('almacen');
            $table->index('clase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_variantes');
    }
};
