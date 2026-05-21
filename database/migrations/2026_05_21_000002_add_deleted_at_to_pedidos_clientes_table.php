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
        Schema::table('pedidos_clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos_clientes', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_clientes', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos_clientes', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};