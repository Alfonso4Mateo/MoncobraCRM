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
        Schema::create('albaran_pedido_lineas', function (Blueprint $table) {
            $table->id();
            
            // Relaciones con las tablas principales
            $table->unsignedBigInteger('albaran_id');
            $table->unsignedBigInteger('pedido_id');
            
            // Identificador lógico de la línea dentro del JSON (UUID)
            $table->string('linea_uid', 36)->index(); 
            
            // Datos del consumo exacto en este albarán
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->decimal('precio_unitario', 14, 4)->default(0); 
            $table->decimal('total', 14, 4)->default(0);
            
            $table->timestamps();

            // Claves foráneas (Si tus tablas se llaman distinto, ajusta 'albaranes_clientes' y 'pedidos_clientes')
            $table->foreign('albaran_id')->references('id')->on('albaranes_clientes')->onDelete('cascade');
            $table->foreign('pedido_id')->references('id')->on('pedidos_clientes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('albaran_pedido_lineas');
    }
};