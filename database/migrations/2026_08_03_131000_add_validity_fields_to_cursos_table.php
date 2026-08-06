<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->unsignedInteger('meses_validez')->nullable()->after('descripcion');
            $table->unsignedInteger('dias_aviso_previo')->default(30)->after('meses_validez');
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn(['meses_validez', 'dias_aviso_previo']);
        });
    }
};