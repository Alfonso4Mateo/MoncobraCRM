<?php

namespace App\Services;

use App\Models\AlbaranCliente;
use App\Models\AlbaranLineaPartida;
use App\Models\AlbaranPedidoLinea;
use App\Models\LineaBolsa;
use App\Models\PedidoCliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LineaBolsaService
{
    /**
     * Crea o actualiza, para una partida (pedido bolsa) concreta, las
     * líneas que trae su formulario. Cada línea vive en `lineas_bolsa`
     * (identificada por su `uid`, el mismo que ya se generaba en el JSON)
     * y su aportación a ESTA partida se guarda en el pivote `linea_partida`.
     *
     * No toca aportaciones que la línea ya tuviera de OTRAS partidas.
     */
    public function sincronizarLineasDePartida(PedidoCliente $partida, array $lineasPayload): void
    {
        // uids que YA tenía esta partida antes de este guardado, para poder detectar
        // cuáles ha quitado el usuario del formulario (editar un pedido y borrar una
        // línea) y limpiar su aportación, en vez de dejarla huérfana consumiendo saldo
        // de la bolsa sin que se vea en ningún sitio.
        $uidsPrevios = $partida->lineasBolsa()->pluck('lineas_bolsa.uid', 'lineas_bolsa.id');

        $uidsEnviados = collect($lineasPayload)
            ->filter(fn ($l) => is_array($l) && trim((string) ($l['descripcion'] ?? '')) !== '')
            ->map(fn ($l) => trim((string) ($l['uid'] ?? '')))
            ->filter()
            ->values();

        foreach ($lineasPayload as $lineaPayload) {
            if (!is_array($lineaPayload)) {
                continue;
            }

            $descripcion = trim((string) ($lineaPayload['descripcion'] ?? ''));
            if ($descripcion === '') {
                continue;
            }

            $uid = trim((string) ($lineaPayload['uid'] ?? '')) ?: (string) Str::uuid();

            $modoConsumo = ($lineaPayload['modo_consumo'] ?? LineaBolsa::MODO_CANTIDAD) === LineaBolsa::MODO_IMPORTE
                ? LineaBolsa::MODO_IMPORTE
                : LineaBolsa::MODO_CANTIDAD;

            $cantidad = round(max(0, (float) ($lineaPayload['cantidad'] ?? 0)), 2);
            $precioUnitario = round(max(0, (float) ($lineaPayload['precio_unitario'] ?? ($lineaPayload['precio'] ?? 0))), 4);
            $margen = round(max(0, (float) ($lineaPayload['margen'] ?? 0)), 2);
            $total = round(
                (float) ($lineaPayload['total'] ?? ($cantidad * $precioUnitario * (1 + $margen / 100))),
                4
            );

            // En modo importe la unidad siempre es 1: lo que varía es el importe, no la cantidad.
            if ($modoConsumo === LineaBolsa::MODO_IMPORTE) {
                $cantidad = 1;
            }

            $lineaExistente = LineaBolsa::where('uid', $uid)->first();

            // ESCUDO DE SEGURIDAD: si se está reduciendo lo que ESTA partida le aporta a
            // una línea ya existente, no se puede bajar de lo que esta misma partida ya
            // tiene imputado en albaranes reales — igual que el escudo de bolsa, pero a
            // nivel de línea dentro de la edición del pedido.
            if ($lineaExistente) {
                $yaConsumidoDeEstaPartida = (float) AlbaranLineaPartida::where('linea_id', $lineaExistente->id)
                    ->where('pedido_id', $partida->id)
                    ->sum('importe');

                if ($total < $yaConsumidoDeEstaPartida - 0.0001) {
                    throw ValidationException::withMessages([
                        'lista_articulos' => sprintf(
                            'La línea "%s" ya tiene %s € facturados desde este pedido y no se puede reducir por debajo de esa cifra.',
                            \Illuminate\Support\Str::limit($descripcion, 60),
                            number_format($yaConsumidoDeEstaPartida, 4, ',', '.')
                        ),
                    ]);
                }
            }

            $linea = LineaBolsa::updateOrCreate(
                ['uid' => $uid],
                [
                    'proyecto_id' => $partida->proyecto_id,
                    'id_cliente' => $partida->id_cliente,
                    'bolsa_origen_id' => $partida->bolsa_id,
                    'articulo' => trim((string) ($lineaPayload['articulo'] ?? '')) ?: null,
                    'descripcion' => $descripcion,
                    'medida' => trim((string) ($lineaPayload['medida'] ?? ($lineaPayload['unidad'] ?? ''))) ?: null,
                    'modo_consumo' => $modoConsumo,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'margen' => $margen,
                    'importe_objetivo' => $total,
                    'estado' => LineaBolsa::ESTADO_ACTIVA,
                ]
            );

            // La aportación de ESTA partida concreta se fija al valor del formulario
            // (si el usuario edita la línea desde esta misma partida, es la fuente de verdad).
            DB::table('linea_partida')->updateOrInsert(
                ['linea_id' => $linea->id, 'pedido_id' => $partida->id],
                ['importe_asignado' => $total, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // Limpieza de huérfanas: líneas que esta partida alimentaba antes y que ya no
        // vienen en el envío (el usuario las ha borrado del formulario de edición).
        $idsAEliminar = $uidsPrevios->filter(fn ($uid) => !$uidsEnviados->contains($uid))->keys();

        foreach ($idsAEliminar as $lineaId) {
            $yaConsumidoDeEstaPartida = (float) AlbaranLineaPartida::where('linea_id', $lineaId)
                ->where('pedido_id', $partida->id)
                ->sum('importe');

            if ($yaConsumidoDeEstaPartida > 0.0001) {
                // Ya se ha facturado algo de esta partida contra esta línea: no se puede
                // desvincular en silencio porque se perdería a qué pedido correspondía ese
                // consumo. Se deja tal cual y se avisa, en vez de borrar historial real.
                throw ValidationException::withMessages([
                    'lista_articulos' => 'Hay una línea eliminada del formulario que ya tiene facturación real desde este pedido. No se puede quitar sin antes revisar sus albaranes.',
                ]);
            }

            DB::table('linea_partida')
                ->where('linea_id', $lineaId)
                ->where('pedido_id', $partida->id)
                ->delete();

            $linea = LineaBolsa::find($lineaId);
            if ($linea && $linea->partidas()->count() === 0 && $linea->consumos()->count() === 0) {
                // Sin ninguna otra partida que la nutra y sin consumo histórico: no sirve
                // para nada dejarla viva. Soft delete, no hard delete (conserva auditoría).
                $linea->delete();
            }
        }
    }

    /**
     * Vincula una línea que YA EXISTE (nacida en otra partida, incluso de
     * otra bolsa) a una partida nueva, sumando el importe indicado a lo
     * que esa partida ya le tuviera asignado. Esto es lo que permite
     * "reutilizar el sobrante" de una bolsa en la línea de otra.
     */
    public function asignarImporteAPartida(LineaBolsa $linea, PedidoCliente $partida, float $importeAdicional): void
    {
        if ($importeAdicional <= 0) {
            return;
        }

        $existente = DB::table('linea_partida')
            ->where('linea_id', $linea->id)
            ->where('pedido_id', $partida->id)
            ->first();

        if ($existente) {
            DB::table('linea_partida')
                ->where('id', $existente->id)
                ->update([
                    'importe_asignado' => (float) $existente->importe_asignado + $importeAdicional,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('linea_partida')->insert([
                'linea_id' => $linea->id,
                'pedido_id' => $partida->id,
                'importe_asignado' => $importeAdicional,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $linea->increment('importe_objetivo', $importeAdicional);

        if ($linea->estado === LineaBolsa::ESTADO_AGOTADA && $linea->fresh()->importeDisponible() > 0) {
            $linea->update(['estado' => LineaBolsa::ESTADO_ACTIVA]);
        }
    }

    /**
     * Líneas disponibles para una partida: las que ya le pertenecen, con
     * su saldo REAL (sumando todas las partidas que la nutren, no solo
     * esta), listas para que el formulario de albarán las consuma.
     */
    public function lineasDisponiblesPorPartida(PedidoCliente $partida): array
    {
        return $partida->lineasBolsa()
            ->get()
            ->map(function (LineaBolsa $linea) use ($partida) {
                return [
                    'uid' => $linea->uid,
                    'linea_id' => $linea->id,
                    'articulo' => $linea->articulo,
                    'descripcion' => $linea->descripcion,
                    'medida' => $linea->medida,
                    'modo_consumo' => $linea->modo_consumo,
                    'cantidad' => (float) $linea->cantidad,
                    'precio_unitario' => (float) $linea->precio_unitario,
                    'margen' => (float) $linea->margen,
                    'importe_asignado_partida' => (float) $partida->lineasBolsa()
                        ->where('linea_id', $linea->id)->first()?->pivot?->importe_asignado,
                    'importe_asignado_total' => $linea->importeAsignadoTotal(),
                    'importe_consumido_total' => $linea->importeConsumidoTotal(),
                    'num_partidas' => $linea->partidas()->count(),
                    'importe_disponible' => $linea->importeDisponible(),
                    'estado' => $linea->estado,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Desglose de una línea por cada partida que la nutre, con su saldo
     * individual. Es lo que alimenta la pantalla de "reparto manual" al
     * facturar: qué partidas hay y cuánto le queda a cada una.
     */
    public function desglosePorPartida(LineaBolsa $linea): array
    {
        return $linea->partidas()
            ->orderBy('linea_partida.created_at')
            ->get()
            ->map(function (PedidoCliente $partida) use ($linea) {
                $asignado = (float) $partida->pivot->importe_asignado;
                $consumido = (float) AlbaranLineaPartida::where('linea_id', $linea->id)
                    ->where('pedido_id', $partida->id)
                    ->sum('importe');

                return [
                    'pedido_id' => $partida->id,
                    'numero_pedido' => $partida->numero_pedido,
                    'importe_asignado' => round($asignado, 4),
                    'importe_disponible' => round(max(0, $asignado - $consumido), 4),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Registra el consumo de una línea en un albarán. Si no se pasa
     * reparto manual, reparte automáticamente entre las partidas con
     * saldo (la más antigua primero). Devuelve el importe que no cupo en
     * ninguna partida con saldo real (excedente), para que el llamador
     * lo pueda avisar igual que ya se hace con el excedente de bolsa.
     */
    public function registrarConsumo(AlbaranCliente $albaran, LineaBolsa $linea, float $importeTotal, array $repartoManual = []): float
    {
        // Traza técnica de "cuánto ha consumido este albarán de esta línea".
        $partidaPrincipal = $linea->partidas()->orderBy('linea_partida.created_at')->first();

        AlbaranPedidoLinea::updateOrCreate(
            ['albaran_id' => $albaran->id, 'linea_id' => $linea->id],
            [
                'pedido_id' => $partidaPrincipal?->id ?? 0,
                'linea_uid' => $linea->uid,
                'cantidad' => $linea->esPorImporte()
                    ? 1
                    : round($importeTotal / max(0.0001, (float) $linea->precio_unitario * (1 + $linea->margen / 100)), 2),
                'precio_unitario' => (float) $linea->precio_unitario,
                'total' => round($importeTotal, 4),
            ]
        );

        // Se recalcula desde cero cada vez, para que editar un albarán no acumule repartos viejos.
        AlbaranLineaPartida::where('albaran_id', $albaran->id)->where('linea_id', $linea->id)->delete();

        $tramos = $repartoManual !== []
            ? $this->normalizarRepartoManual($repartoManual)
            : $this->repartoAutomatico($linea);

        $restante = round($importeTotal, 4);

        foreach ($tramos as $tramo) {
            if ($restante <= 0.001) {
                break;
            }

            $importeTramo = min($restante, $tramo['importe']);
            if ($importeTramo <= 0) {
                continue;
            }

            AlbaranLineaPartida::create([
                'albaran_id' => $albaran->id,
                'linea_id' => $linea->id,
                'pedido_id' => $tramo['pedido_id'],
                'importe' => round($importeTramo, 4),
            ]);

            $restante = round($restante - $importeTramo, 4);
        }

        $excedente = 0.0;

        if ($restante > 0.001) {
            // No había saldo real suficiente: se deja imputado a la última partida
            // conocida para no perder el registro, y se avisa del excedente.
            $ultimaPartida = $linea->partidas()->orderByDesc('linea_partida.created_at')->first();

            if ($ultimaPartida) {
                AlbaranLineaPartida::create([
                    'albaran_id' => $albaran->id,
                    'linea_id' => $linea->id,
                    'pedido_id' => $ultimaPartida->id,
                    'importe' => $restante,
                ]);
            }

            $excedente = $restante;
        }

        if ($linea->fresh()->importeDisponible() <= 0.001) {
            $linea->update(['estado' => LineaBolsa::ESTADO_AGOTADA]);
        }

        return $excedente;
    }

    private function repartoAutomatico(LineaBolsa $linea): array
    {
        $tramos = [];

        $partidas = $linea->partidas()->orderBy('linea_partida.created_at')->get();

        foreach ($partidas as $partida) {
            $asignado = (float) $partida->pivot->importe_asignado;
            $consumidoPartida = (float) AlbaranLineaPartida::where('linea_id', $linea->id)
                ->where('pedido_id', $partida->id)
                ->sum('importe');

            $disponible = round(max(0, $asignado - $consumidoPartida), 4);

            if ($disponible > 0) {
                $tramos[] = ['pedido_id' => $partida->id, 'importe' => $disponible];
            }
        }

        return $tramos;
    }

    private function normalizarRepartoManual(array $reparto): array
    {
        return collect($reparto)
            ->map(fn ($item) => [
                'pedido_id' => (int) ($item['pedido_id'] ?? 0),
                'importe' => round(max(0, (float) ($item['importe'] ?? 0)), 4),
            ])
            ->filter(fn ($item) => $item['pedido_id'] > 0 && $item['importe'] > 0)
            ->values()
            ->all();
    }
}