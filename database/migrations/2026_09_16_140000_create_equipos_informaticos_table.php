<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos_informaticos', function (Blueprint $table) {
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
            $table->date('fecha_adquisicion')->nullable();
            $table->date('fecha_mantenimiento')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true)->index();
            $table->string('tipo_equipo')->nullable();
            $table->string('sistema_operativo')->nullable();
            $table->string('procesador')->nullable();
            $table->string('memoria_ram')->nullable();
            $table->string('almacenamiento')->nullable();
            $table->string('mac_address', 50)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->date('garantia_hasta')->nullable();
            $table->date('soporte_hasta')->nullable();
            $table->string('condicion_fisica')->nullable();
            $table->string('etiqueta_color', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos_informaticos');
    }
};
