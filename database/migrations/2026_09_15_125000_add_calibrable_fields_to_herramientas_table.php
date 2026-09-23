<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('herramientas', function (Blueprint $table) {
            $table->string('instrumento')->nullable()->after('categoria');
            $table->string('rango_medida')->nullable()->after('instrumento');
            $table->string('clase_exactitud')->nullable()->after('rango_medida');
            $table->string('tolerancia')->nullable()->after('clase_exactitud');
            $table->string('incertidumbre')->nullable()->after('tolerancia');
            $table->string('norma_calibracion')->nullable()->after('incertidumbre');
            $table->string('laboratorio')->nullable()->after('proveedor');
            $table->string('certificado')->nullable()->after('laboratorio');
            $table->date('fecha_certificado')->nullable()->after('certificado');
            $table->boolean('laboratorio_externo')->default(false)->after('fecha_certificado');
            $table->string('estado_certificado', 40)->nullable()->after('laboratorio_externo');
        });
    }

    public function down(): void
    {
        Schema::table('herramientas', function (Blueprint $table) {
            $table->dropColumn([
                'instrumento', 'rango_medida', 'clase_exactitud', 'tolerancia',
                'incertidumbre', 'norma_calibracion', 'laboratorio', 'certificado',
                'fecha_certificado', 'laboratorio_externo', 'estado_certificado',
            ]);
        });
    }
};
