<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La bolsa global: el compromiso grande del cliente (ej. 25.000€).
     * Las partidas (pedidos_clientes con bolsa=true) se cuelgan de ella.
     */
    public function up(): void
    {
        Schema::create('bolsas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete();
            $table->string('numero', 80)->nullable();

            // Grupo de fungibilidad: decide con qué otras bolsas puede
            // compartir sobrante (mismo cliente + mismo tipo_bolsa).
            // Por defecto el año en curso; editable a mano para casos
            // "excepcionales" o cualquier otro tipo futuro.
            $table->string('tipo_bolsa', 60)->default((string) date('Y'));

            $table->decimal('total', 14, 4)->default(0);
            $table->date('fecha')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tipo_bolsa');
            $table->index(['id_cliente', 'tipo_bolsa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bolsas');
    }
};