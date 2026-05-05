<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('apellido')->nullable();
            $table->string('dni_nie', 20)->nullable();
            $table->string('departamento')->nullable();
            $table->string('tipo_personal')->nullable();

            $table->string('telefono', 20)->nullable();
            $table->text('descripcion')->nullable();

            $table->string('camiseta')->nullable();
            $table->string('chaqueta')->nullable();
            $table->string('sudadera')->nullable();
            $table->string('pantalon')->nullable();
            $table->string('calzado')->nullable();
            $table->string('casco')->nullable();
            $table->string('guantes')->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal');
    }
};
