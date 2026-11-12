<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('epis', 'inventario_articulo_id')) {
            Schema::table('epis', function (Blueprint $table) {
                $table->foreignId('inventario_articulo_id')
                    ->nullable()
                    ->after('inventario_variante_id')
                    ->constrained('inventario_articulos')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('epis', 'inventario_articulo_id')) {
            Schema::table('epis', function (Blueprint $table) {
                $table->dropConstrainedForeignId('inventario_articulo_id');
            });
        }
    }
};
