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
        // Apuntamos a la tabla pivote exacta que usan tus modelos
        Schema::table('pedido_cliente_albaran_cliente', function (Blueprint $table) {
            // Añadimos el importe imputado. 10 dígitos en total, 2 decimales.
            $table->decimal('importe_imputado', 10, 2)->default(0)->after('albaran_cliente_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_cliente_albaran_cliente', function (Blueprint $table) {
            $table->dropColumn('importe_imputado');
        });
    }
};