<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->string('documento_referencia')->nullable()->after('numero_documento');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->dropColumn('documento_referencia');
        });
    }
};
