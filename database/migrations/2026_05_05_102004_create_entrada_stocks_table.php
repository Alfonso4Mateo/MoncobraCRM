<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entrada_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete();

            $table->string('numero_entrada');
            $table->timestamp('fecha')->useCurrent();
            $table->string('solicitante')->nullable();
            $table->string('ot')->nullable();
            $table->string('almacen_origen')->nullable();
            $table->json('items');
            $table->string('estado')->default('aceptado');
            $table->timestamps();

            $table->index(['proyecto_id', 'fecha']);
            $table->index(['proyecto_id', 'estado']);
            $table->unique(['proyecto_id', 'numero_entrada']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrada_stocks');
    }
};
