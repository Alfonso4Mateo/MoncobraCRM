<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('curso_personal', function (Blueprint $table) {
            $table->string('archivo_diploma')->nullable()->after('descripcion_aptitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curso_personal', function (Blueprint $table) {
            // Eliminamos la columna si hacemos rollback
            $table->dropColumn('archivo_diploma');
        });
    }
};