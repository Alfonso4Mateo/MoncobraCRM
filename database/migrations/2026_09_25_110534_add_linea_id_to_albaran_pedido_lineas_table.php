<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * albaran_pedido_lineas ya registraba el consumo por uid dentro de un
     * pedido concreto. Añadimos linea_id para que apunte también a la
     * entidad línea real. pedido_id se mantiene tal cual para no romper
     * los registros históricos ya guardados.
     */
    public function up(): void
    {
        Schema::table('albaran_pedido_lineas', function (Blueprint $table) {
            if (!Schema::hasColumn('albaran_pedido_lineas', 'linea_id')) {
                $table->foreignId('linea_id')
                    ->nullable()
                    ->after('linea_uid')
                    ->constrained('lineas_bolsa')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('albaran_pedido_lineas', function (Blueprint $table) {
            if (Schema::hasColumn('albaran_pedido_lineas', 'linea_id')) {
                $table->dropForeign(['linea_id']);
                $table->dropColumn('linea_id');
            }
        });
    }
};