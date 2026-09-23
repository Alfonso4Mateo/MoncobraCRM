<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('herramientas_planta', function (Blueprint $table) {
            $table->id();
            $table->string('id_interno')->unique();
            $table->string('nombre');
            $table->string('codigo')->nullable()->index();
            $table->string('numero_serie')->nullable()->index();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('categoria')->nullable();
            $table->string('estado', 40)->default('operativo')->index();
            $table->string('ubicacion')->nullable();
            $table->string('ubicacion_2')->nullable();
            $table->string('responsable')->nullable();
            $table->string('proveedor')->nullable();
            $table->string('id_proveedor')->nullable();
            $table->string('almacen')->nullable();
            $table->decimal('stock', 12, 3)->default(0);
            $table->string('unidad_medida', 30)->default('ud');
            $table->date('fecha_registro');
            $table->date('fecha_adquisicion')->nullable();
            $table->date('fecha_mantenimiento')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->foreignId('familia_id')->nullable()->constrained('familias_herramientas')->nullOnDelete();
            $table->string('familia_energetica')->nullable();
            $table->string('potencia')->nullable();
            $table->string('tension')->nullable();
            $table->string('combustible')->nullable();
            $table->string('criticidad', 30)->nullable();
            $table->unsignedInteger('horas_uso')->nullable();
            $table->boolean('bloqueado')->default(false);
            $table->string('motivo_bloqueo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('herramientas_planta');
    }
};
