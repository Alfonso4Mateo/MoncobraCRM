<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pedidos_clientes') && !Schema::hasColumn('pedidos_clientes', 'archivo_pdf')) {
            Schema::table('pedidos_clientes', function (Blueprint $table) {
                $table->string('archivo_pdf')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pedidos_clientes') && Schema::hasColumn('pedidos_clientes', 'archivo_pdf')) {
            Schema::table('pedidos_clientes', function (Blueprint $table) {
                $table->dropColumn('archivo_pdf');
            });
        }
    }
};
