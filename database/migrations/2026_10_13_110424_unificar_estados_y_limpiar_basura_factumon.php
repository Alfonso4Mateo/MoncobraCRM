<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Normalizar histórico de Albaranes
        DB::table('albaranes_clientes')
            ->where('estado', 'entregado')
            ->update(['estado' => 'recibido']);
            
        DB::table('albaranes_clientes')
            ->where('estado', 'cancelado')
            ->update(['estado' => 'rechazado']);
            
        DB::table('albaranes_clientes')
            ->where('estado', 'facturado parcial')
            ->update(['estado' => 'facturado_parcial']);

        // 2. Normalizar histórico de Pedidos
        DB::table('pedidos_clientes')
            ->where('estado', 'facturado parcial')
            ->update(['estado' => 'facturado_parcial']);

        // 3. Normalizar histórico de Presupuestos
        DB::table('presupuestos')
            ->where('estado', 'pendiente pedido')
            ->update(['estado' => 'pedido_pendiente']);

        // 4. Limpiar los artículos basura del catálogo generados por el bug antiguo
        DB::table('articulos')
            ->whereRaw("numero_referencia REGEXP '^[0-9]{1,2}$'")
            ->delete();
    }

    public function down(): void
    {
        // Reversión de los estados por si necesitas dar marcha atrás
        DB::table('albaranes_clientes')
            ->where('estado', 'recibido')
            ->update(['estado' => 'entregado']);
            
        DB::table('albaranes_clientes')
            ->where('estado', 'rechazado')
            ->update(['estado' => 'cancelado']);
            
        DB::table('albaranes_clientes')
            ->where('estado', 'facturado_parcial')
            ->update(['estado' => 'facturado parcial']);

        DB::table('pedidos_clientes')
            ->where('estado', 'facturado_parcial')
            ->update(['estado' => 'facturado parcial']);

        DB::table('presupuestos')
            ->where('estado', 'pedido_pendiente')
            ->update(['estado' => 'pendiente pedido']);
    }
};