<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aparatos_calibrables', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');
            $table->string('codigo')->nullable()->index();
            $table->string('numero_serie')->nullable()->index();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('categoria')->nullable();
            $table->string('estado', 40)->default('operativo')->index();
            $table->string('ubicacion')->nullable();
            $table->string('responsable')->nullable();
            $table->string('proveedor')->nullable();

            $table->string('instrumento')->nullable();
            $table->string('rango_medida')->nullable();
            $table->string('clase_exactitud')->nullable();
            $table->string('tolerancia')->nullable();
            $table->string('incertidumbre')->nullable();
            $table->string('norma_calibracion')->nullable();
            $table->string('laboratorio')->nullable();
            $table->string('certificado')->nullable();
            $table->date('fecha_certificado')->nullable();
            $table->boolean('laboratorio_externo')->default(false);
            $table->string('estado_certificado', 40)->nullable();
            $table->date('proxima_calibracion')->nullable()->index();
            $table->date('ultima_calibracion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aparatos_calibrables');
    }
};
