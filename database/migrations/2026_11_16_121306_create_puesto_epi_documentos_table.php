<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puesto_epi_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('puesto_id')->constrained('puestos')->cascadeOnDelete();
            $table->string('codigo_iso', 60)->default('SST-0840-002-1-2');
            $table->string('revision', 30)->default('Rev. 1');
            $table->date('fecha_emision');
            $table->string('marco_prl', 100)->default('RD 773/1997');
            $table->string('sello_prevencion', 120)->default('FIRMADO / HOMOLOGADO');
            $table->string('sello_delegacion', 120)->default('CONFORME / VALIDADO');
            $table->string('dpto_prevencion', 150)->default('Dpto. SST Moncobra, S.A.');
            $table->string('dpto_delegacion', 150)->default('Jefatura de Producción / Planta');
            $table->string('file_path');
            $table->json('epis_snapshot');
            $table->boolean('es_vigente')->default(true)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['puesto_id', 'es_vigente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puesto_epi_documentos');
    }
};