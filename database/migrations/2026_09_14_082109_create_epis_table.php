<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla principal: Catálogo base de ítems EPI
        Schema::create('epis', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 2. Tabla Pivot: Relación Muchos a Muchos con el atributo de 'cantidad'
        Schema::create('epi_puesto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epi_id')->constrained('epis')->onDelete('cascade');
            
            // Se vincula a la tabla de perfiles formativos
            $table->foreignId('puesto_id')->constrained('puestos')->onDelete('cascade');
            
            // El atributo extra que pertenece a la relación
            $table->unsignedInteger('cantidad')->default(1);
            
            $table->timestamps();
            
            // Medida de seguridad: Evita duplicidades exactas de EPI+Puesto en la base de datos
            $table->unique(['epi_id', 'puesto_id']);
        });
    }

    public function down(): void
    {
        // Por seguridad referencial, siempre se elimina primero la tabla pivot
        Schema::dropIfExists('epi_puesto');
        Schema::dropIfExists('epis');
    }
};