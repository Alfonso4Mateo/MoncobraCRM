<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula la partida (pedido bolsa) con su bolsa global.
     * Nullable: no rompe pedidos existentes ni pedidos no-bolsa.
     */
    public function up(): void
    {
        Schema::table('pedidos_clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos_clientes', 'bolsa_id')) {
                $table->foreignId('bolsa_id')
                    ->nullable()
                    ->after('bolsa_texto')
                    ->constrained('bolsas')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_clientes', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos_clientes', 'bolsa_id')) {
                $table->dropForeign(['bolsa_id']);
                $table->dropColumn('bolsa_id');
            }
        });
    }
};