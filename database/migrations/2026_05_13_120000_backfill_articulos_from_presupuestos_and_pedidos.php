<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('articulos')) {
            return;
        }

        $now = now();

        $presupuestos = DB::table('presupuestos')
            ->select('proyecto_id', 'lista_articulos')
            ->whereNotNull('lista_articulos')
            ->get();

        foreach ($presupuestos as $presupuesto) {
            $this->syncLineas($presupuesto->proyecto_id, $presupuesto->lista_articulos, $now);
        }

        $pedidos = DB::table('pedidos_clientes')
            ->select('proyecto_id', 'lista_articulos')
            ->whereNotNull('lista_articulos')
            ->get();

        foreach ($pedidos as $pedido) {
            $this->syncLineas($pedido->proyecto_id, $pedido->lista_articulos, $now);
        }
    }

    public function down(): void
    {
        // No reversible: this migration fills a derived projection table from document lines.
    }

    private function syncLineas($proyectoId, $listaArticulos, $now): void
    {
        $decoded = json_decode((string) $listaArticulos, true);
        if (!is_array($decoded)) {
            return;
        }

        foreach ($decoded as $linea) {
            if (!is_array($linea)) {
                continue;
            }

            $numeroReferencia = trim((string) ($linea['articulo'] ?? ''));
            $descripcion = trim((string) ($linea['descripcion'] ?? ''));

            if ($numeroReferencia === '' || $descripcion === '') {
                continue;
            }

            DB::table('articulos')->updateOrInsert(
                [
                    'proyecto_id' => $proyectoId,
                    'numero_referencia' => $numeroReferencia,
                ],
                [
                    'descripcion' => $descripcion,
                    'cantidad' => round(max(0, (float) ($linea['cantidad'] ?? 0)), 2),
                    'medida' => trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? ''))) ?: null,
                    'precio_unitario' => round(max(0, (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0))), 2),
                    'margen' => round(max(0, (float) ($linea['margen'] ?? 0)), 2),
                    'total' => round(max(0, (float) ($linea['total'] ?? 0)), 2),
                    'facturado' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
};