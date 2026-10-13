<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La línea deja de vivir dentro del JSON de un único pedido: es su
     * propia entidad, y puede sobrevivir a la partida donde nació y
     * recibir aportaciones de varias partidas (ver tabla linea_partida).
     */
    public function up(): void
    {
        Schema::create('lineas_bolsa', function (Blueprint $table) {
            $table->id();

            // Se conserva el mismo uid que ya se genera hoy en el JSON de
            // lista_articulos, para poder mapear el histórico sin perderlo.
            $table->string('uid', 36)->unique();

            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete();
            $table->foreignId('id_cliente')->constrained('clientes')->restrictOnDelete();

            // Informativo: en qué bolsa global nació esta línea originalmente.
            $table->foreignId('bolsa_origen_id')->nullable()->constrained('bolsas')->nullOnDelete();

            $table->string('articulo', 2000)->nullable();
            $table->text('descripcion');
            $table->string('medida', 200)->nullable();

            // 'cantidad': el consumo mueve la cantidad (comportamiento legacy,
            //             pensado para artículos con unidad real medible).
            // 'importe':  la cantidad se queda fija (normalmente 1) y lo que
            //             varía es el importe consumido (líneas de servicio/trabajo).
            $table->string('modo_consumo', 20)->default('cantidad');

            $table->decimal('cantidad', 12, 2)->default(1);
            $table->decimal('precio_unitario', 14, 4)->default(0);
            $table->decimal('margen', 8, 2)->default(0);

            // El límite económico real de la línea (lo que hoy sería su "total").
            $table->decimal('importe_objetivo', 14, 4)->default(0);

            $table->string('estado', 20)->default('activa'); // activa | agotada

            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
            $table->index(['id_cliente', 'modo_consumo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineas_bolsa');
    }
};