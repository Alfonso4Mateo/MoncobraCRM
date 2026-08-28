<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->foreignId('puesto_trabajo_id')
                ->nullable()
                ->after('puesto')
                ->constrained('puestos_trabajo')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->dropConstrainedForeignId('puesto_trabajo_id');
        });
    }
};