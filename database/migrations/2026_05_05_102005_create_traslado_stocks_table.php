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
        Schema::create('traslado_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete();

            $table->string('numero_traslado');
            $table->timestamp('fecha')->useCurrent();
            $table->string('solicitante')->nullable();
            $table->string('ot')->nullable();
            $table->string('almacen_origen')->nullable();
            $table->string('almacen_actual')->nullable();
            $table->json('items');
            $table->string('estado')->default('aceptado');
            $table->timestamps();

            $table->index(['proyecto_id', 'fecha']);
            $table->index(['proyecto_id', 'estado']);
            $table->unique(['proyecto_id', 'numero_traslado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traslado_stocks');
    }
};
