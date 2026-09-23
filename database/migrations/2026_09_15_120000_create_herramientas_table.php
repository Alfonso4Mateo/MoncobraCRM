<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('herramientas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['informatica', 'herramienta', 'calibrable'])->index();
            $table->string('nombre');
            $table->string('codigo')->nullable()->index();
            $table->string('numero_serie')->nullable()->index();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('categoria')->nullable();
            $table->string('estado')->default('operativo')->index();
            $table->string('ubicacion')->nullable();
            $table->string('responsable')->nullable();
            $table->string('proveedor')->nullable();
            $table->date('fecha_adquisicion')->nullable();
            $table->date('proxima_calibracion')->nullable();
            $table->date('ultima_calibracion')->nullable();
            $table->date('fecha_mantenimiento')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('herramientas');
    }
};
