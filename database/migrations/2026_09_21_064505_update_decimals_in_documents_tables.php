<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->decimal('total', 14, 4)->change();
            // Añade aquí subtotal, impuestos, etc. si existen
        });

        Schema::table('albaranes_clientes', function (Blueprint $table) {
            $table->decimal('total', 14, 4)->change();
        });

        Schema::table('pedidos_clientes', function (Blueprint $table) {
            $table->decimal('total', 14, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->change();
        });

        Schema::table('albaranes_clientes', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->change();
        });

        Schema::table('pedidos_clientes', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->change();
        });
    }
};