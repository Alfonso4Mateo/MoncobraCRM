<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_carpetas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            // Relación recursiva (Adjacency List)
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('qr_carpetas')
                  ->restrictOnDelete(); 
            
            $table->timestamps();
            $table->softDeletes(); // Baja lógica
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_carpetas');
    }
};