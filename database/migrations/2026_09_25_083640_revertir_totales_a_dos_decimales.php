<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones (Aplica el cambio a 2 decimales).
     */
    public function up(): void
    {
        // Contrastando con tu archivo anterior, aquí bajamos de (14, 4) a (14, 2)
        // Mantenemos la longitud de 14 dígitos totales para soportar importes millonarios sin problema.
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->decimal('total', 14, 2)->change();
        });

        Schema::table('albaranes_clientes', function (Blueprint $table) {
            $table->decimal('total', 14, 2)->change();
        });

        Schema::table('pedidos_clientes', function (Blueprint $table) {
            $table->decimal('total', 14, 2)->change();
        });
    }

    /**
     * Revierte las migraciones (En caso de hacer rollback, volvería a 4 decimales).
     */
    public function down(): void
    {
        // Esta es nuestra red de seguridad. Si el comando up() falla o necesitas deshacerlo,
        // Laravel usará esta función para devolver todo a como estaba esta mañana.
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->decimal('total', 14, 4)->change();
        });

        Schema::table('albaranes_clientes', function (Blueprint $table) {
            $table->decimal('total', 14, 4)->change();
        });

        Schema::table('pedidos_clientes', function (Blueprint $table) {
            $table->decimal('total', 14, 4)->change();
        });
    }
};