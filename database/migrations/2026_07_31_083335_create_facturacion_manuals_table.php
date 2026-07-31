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
        Schema::create('facturacion_manuals', function (Blueprint $table) {
            $table->id();
            
            // Relación con el pedido
            $table->foreignId('pedido_id')->constrained('pedidos_clientes')->onDelete('cascade');
            
            // Datos de la facturación
            $table->decimal('importe', 12, 2);
            $table->text('concepto');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturacion_manuals');
    }
};