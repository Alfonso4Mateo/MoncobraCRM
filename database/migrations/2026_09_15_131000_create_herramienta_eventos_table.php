<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('herramienta_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('herramienta_id')->constrained('herramientas')->cascadeOnDelete();
            $table->string('tipo', 30)->index();
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha')->index();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('herramienta_eventos');
    }
};
