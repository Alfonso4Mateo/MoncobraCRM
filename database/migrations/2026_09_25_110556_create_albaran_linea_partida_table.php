<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuando un albarán consume una línea que se nutre de varias partidas,
     * aquí se guarda la decisión manual del usuario: de este consumo,
     * cuánto se imputa a cada partida concreta.
     */
    public function up(): void
    {
        Schema::create('albaran_linea_partida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('albaran_id')->constrained('albaranes_clientes')->cascadeOnDelete();
            $table->foreignId('linea_id')->constrained('lineas_bolsa')->cascadeOnDelete();
            $table->foreignId('pedido_id')->constrained('pedidos_clientes')->cascadeOnDelete();
            $table->decimal('importe', 14, 4)->default(0);
            $table->timestamps();

            $table->index(['albaran_id', 'linea_id'], 'albaran_linea_idx');
            $table->index('pedido_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albaran_linea_partida');
    }
};