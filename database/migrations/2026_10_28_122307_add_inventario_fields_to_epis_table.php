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
        Schema::table('epis', function (Blueprint $table) {
            $table->foreignId('inventario_id')->nullable()->constrained('inventario')->nullOnDelete();
            $table->foreignId('inventario_variante_id')->nullable()->constrained('inventario_variantes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epis', function (Blueprint $table) {
            //
        });
    }
};
