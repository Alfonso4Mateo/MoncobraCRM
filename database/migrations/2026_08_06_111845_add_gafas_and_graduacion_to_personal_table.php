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
        Schema::table('personal', function (Blueprint $table) {
            // Añadimos el campo gafas después del casco para mantener un orden lógico
            $table->string('gafas', 20)->nullable()->after('casco');
            
            // Añadimos las fechas de graduación
            $table->date('ultima_graduacion')->nullable()->after('proxima_revision_medica');
            $table->date('proxima_graduacion')->nullable()->after('ultima_graduacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            // El método down debe hacer exactamente lo contrario que el up
            $table->dropColumn([
                'gafas', 
                'ultima_graduacion', 
                'proxima_graduacion'
            ]);
        });
    }
};