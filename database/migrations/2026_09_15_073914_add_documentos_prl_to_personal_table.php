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
            // Añadimos los campos para almacenar la ruta de los documentos maestros
            // Usamos 'after' para posicionarlos ordenadamente en la base de datos tras la columna 'descripcion'
            $table->string('documento_riesgos')->nullable()->after('descripcion');
            $table->string('documento_epi')->nullable()->after('documento_riesgos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            // Es vital definir cómo revertir esta acción en caso de hacer un 'rollback' de la base de datos
            $table->dropColumn(['documento_riesgos', 'documento_epi']);
        });
    }
};