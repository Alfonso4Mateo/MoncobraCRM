<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Recorre los pedidos bolsa existentes y, por cada línea de su JSON
     * lista_articulos, crea (si no existe ya por su uid) la fila
     * equivalente en lineas_bolsa, y una fila en linea_partida que la
     * vincula a esa partida con el importe que ya tenía.
     *
     * Idempotente: se puede ejecutar más de una vez sin duplicar, porque
     * busca primero por uid y por el par (linea_id, pedido_id).
     *
     * modo_consumo se deja siempre en 'cantidad' (el comportamiento de
     * hoy). Pasar líneas concretas a 'importe' es una decisión manual
     * posterior, no algo que esta migración deba adivinar.
     */
    public function up(): void
    {
        if (!Schema::hasTable('lineas_bolsa') || !Schema::hasTable('pedidos_clientes')) {
            return;
        }

        $now = now();

        $pedidosBolsa = DB::table('pedidos_clientes')
            ->where('bolsa', true)
            ->whereNotNull('lista_articulos')
            ->get(['id', 'proyecto_id', 'id_cliente', 'bolsa_id', 'lista_articulos']);

        foreach ($pedidosBolsa as $pedido) {
            $lineas = json_decode((string) $pedido->lista_articulos, true);
            if (!is_array($lineas)) {
                continue;
            }

            foreach ($lineas as $linea) {
                if (!is_array($linea)) {
                    continue;
                }

                $descripcion = trim((string) ($linea['descripcion'] ?? ''));
                if ($descripcion === '') {
                    continue;
                }

                $uid = trim((string) ($linea['uid'] ?? '')) ?: (string) Str::uuid();

                $existente = DB::table('lineas_bolsa')->where('uid', $uid)->first();

                if ($existente) {
                    $lineaId = $existente->id;
                } else {
                    $cantidad = round(max(0, (float) ($linea['cantidad'] ?? 0)), 2);
                    $precioUnitario = round(max(0, (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0))), 4);
                    $margen = round(max(0, (float) ($linea['margen'] ?? 0)), 2);
                    $total = round(
                        (float) ($linea['total'] ?? ($cantidad * $precioUnitario * (1 + $margen / 100))),
                        4
                    );

                    $lineaId = DB::table('lineas_bolsa')->insertGetId([
                        'uid' => $uid,
                        'proyecto_id' => $pedido->proyecto_id,
                        'id_cliente' => $pedido->id_cliente,
                        'bolsa_origen_id' => $pedido->bolsa_id,
                        'articulo' => trim((string) ($linea['articulo'] ?? '')) ?: null,
                        'descripcion' => $descripcion,
                        'medida' => trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? ''))) ?: null,
                        'modo_consumo' => 'cantidad',
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'margen' => $margen,
                        'importe_objetivo' => $total,
                        'estado' => 'activa',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('linea_partida')->updateOrInsert(
                    ['linea_id' => $lineaId, 'pedido_id' => $pedido->id],
                    [
                        'importe_asignado' => round((float) ($linea['total'] ?? 0), 4),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    /**
     * No reversible: es un volcado derivado del JSON, no una estructura
     * que se pueda deshacer sin perder información generada después.
     */
    public function down(): void
    {
        //
    }
};