<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reparto de presupuesto: cuánto aporta cada partida (pedido bolsa) al
     * importe_objetivo de una línea. Una línea puede tener varias filas
     * aquí si se nutre de más de una partida (incluso de otra bolsa).
     */
    public function up(): void
    {
        Schema::create('linea_partida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')->constrained('lineas_bolsa')->cascadeOnDelete();
            $table->foreignId('pedido_id')->constrained('pedidos_clientes')->cascadeOnDelete();
            $table->decimal('importe_asignado', 14, 4)->default(0);
            $table->timestamps();

            $table->unique(['linea_id', 'pedido_id'], 'linea_partida_unique');
            $table->index('pedido_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linea_partida');
    }
};