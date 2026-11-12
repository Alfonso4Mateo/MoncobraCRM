<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            if (!Schema::hasColumn('almacenes', 'codigo')) {
                $table->string('codigo', 50)->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('almacenes', 'activo')) {
                $table->boolean('activo')->default(true)->after('codigo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            if (Schema::hasColumn('almacenes', 'activo')) {
                $table->dropColumn('activo');
            }
            if (Schema::hasColumn('almacenes', 'codigo')) {
                $table->dropColumn('codigo');
            }
        });
    }
};