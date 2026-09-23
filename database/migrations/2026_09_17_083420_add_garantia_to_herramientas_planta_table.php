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
        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->date('garantia_hasta')->nullable()->after('fecha_adquisicion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('herramientas_planta', function (Blueprint $table) {
            $table->dropColumn('garantia_hasta');
        });
    }
};