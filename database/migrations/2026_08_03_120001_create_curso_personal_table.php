<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('personal_id')->constrained('personal')->cascadeOnDelete();
            $table->date('fecha_realizacion')->nullable();
            $table->boolean('apto')->default(false);
            $table->text('descripcion_aptitud')->nullable();
            $table->timestamps();

            $table->unique(['curso_id', 'personal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_personal');
    }
};