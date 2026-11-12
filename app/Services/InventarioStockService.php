<?php

namespace App\Services;

use App\Models\InventarioMovimiento;
use App\Models\InventarioMovimientoLinea;
use App\Models\InventarioStockAlmacen;
use App\Models\InventarioVariante;
use App\Models\Almacen;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventarioStockService
{
    /**
     * @param array{proyecto_id:int, almacen_destino_id:int, lineas:array<int, array{variante_id:int, cantidad:int}>, personal_id?:int|null, ot?:string|null, motivo?:string|null, observaciones?:string|null, created_by?:int|null} $data
     */
    public function entrada(array $data): InventarioMovimiento
    {
        return $this->registrar('ENTRADA', $data);
    }

    /**
     * @param array{proyecto_id:int, almacen_origen_id:int, lineas:array<int, array{variante_id:int, cantidad:int}>, personal_id?:int|null, ot?:string|null, motivo?:string|null, observaciones?:string|null, created_by?:int|null} $data
     */
    public function salida(array $data): InventarioMovimiento
    {
        return $this->registrar('SALIDA', $data);
    }

    /**
     * @param array{proyecto_id:int, almacen_origen_id:int, almacen_destino_id:int, lineas:array<int, array{variante_id:int, cantidad:int}>, personal_id?:int|null, ot?:string|null, motivo?:string|null, observaciones?:string|null, created_by?:int|null} $data
     */
    public function traslado(array $data): InventarioMovimiento
    {
        if ((int) $data['almacen_origen_id'] === (int) $data['almacen_destino_id']) {
            throw new RuntimeException('El almacén de origen y destino deben ser distintos.');
        }

        return $this->registrar('TRASLADO', $data);
    }

    public function revertir(InventarioMovimiento $movimiento): void
    {
        DB::transaction(function () use ($movimiento): void {
            $movimiento = InventarioMovimiento::query()
                ->with('lineas')
                ->lockForUpdate()
                ->findOrFail($movimiento->id);

            if ($movimiento->estado === 'CANCELADO') {
                throw new RuntimeException('El movimiento ya está cancelado.');
            }

            foreach ($movimiento->lineas as $linea) {
                if ($movimiento->tipo === 'ENTRADA') {
                    $this->decrementar($linea->variante_id, $movimiento->almacen_destino_id, $linea->cantidad);
                } elseif ($movimiento->tipo === 'SALIDA') {
                    $this->incrementar($linea->variante_id, $movimiento->almacen_origen_id, $linea->cantidad);
                } else {
                    $this->decrementar($linea->variante_id, $movimiento->almacen_destino_id, $linea->cantidad);
                    $this->incrementar($linea->variante_id, $movimiento->almacen_origen_id, $linea->cantidad);
                }
            }

            $movimiento->update(['estado' => 'CANCELADO']);
        });
    }

    private function registrar(string $tipo, array $data): InventarioMovimiento
    {
        return DB::transaction(function () use ($tipo, $data): InventarioMovimiento {
            $lineas = $this->normalizarLineas($data['lineas'] ?? []);
            if ($lineas === []) {
                throw new RuntimeException('El movimiento debe contener al menos una línea.');
            }

            $variantCount = InventarioVariante::query()
                ->where('proyecto_id', $data['proyecto_id'])
                ->whereIn('id', array_column($lineas, 'variante_id'))
                ->count();
            if ($variantCount !== count($lineas)) {
                throw new RuntimeException('Una o más variantes no pertenecen al proyecto activo.');
            }

            $warehouseIds = array_filter([
                $data['almacen_origen_id'] ?? null,
                $data['almacen_destino_id'] ?? null,
            ]);
            if (Almacen::query()->where('proyecto_id', $data['proyecto_id'])->whereIn('id', $warehouseIds)->count() !== count(array_unique($warehouseIds))) {
                throw new RuntimeException('El almacén seleccionado no pertenece al proyecto activo.');
            }

            $movimiento = InventarioMovimiento::create([
                'proyecto_id' => $data['proyecto_id'],
                'numero_documento' => $this->siguienteNumero($tipo, $data['proyecto_id']),
                'documento_referencia' => $data['documento_referencia'] ?? ($data['motivo'] ?? null),
                'tipo' => $tipo,
                'almacen_origen_id' => $data['almacen_origen_id'] ?? null,
                'almacen_destino_id' => $data['almacen_destino_id'] ?? null,
                'personal_id' => $data['personal_id'] ?? null,
                'ot' => $data['ot'] ?? null,
                'motivo' => $data['motivo'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($lineas as $linea) {
                $origenAntes = null;
                $origenDespues = null;
                $destinoAntes = null;
                $destinoDespues = null;

                if ($tipo === 'SALIDA' || $tipo === 'TRASLADO') {
                    $origenAntes = $this->saldo($linea['variante_id'], $data['almacen_origen_id']);
                    if ($origenAntes < $linea['cantidad']) {
                        throw new RuntimeException(sprintf(
                            'Stock insuficiente para la variante %d: disponibles %d, solicitadas %d.',
                            $linea['variante_id'],
                            $origenAntes,
                            $linea['cantidad']
                        ));
                    }
                    $origenDespues = $origenAntes - $linea['cantidad'];
                    $this->establecerSaldo($linea['variante_id'], $data['almacen_origen_id'], $origenDespues);
                }

                if ($tipo === 'ENTRADA' || $tipo === 'TRASLADO') {
                    $destinoAntes = $this->saldo($linea['variante_id'], $data['almacen_destino_id']);
                    $destinoDespues = $destinoAntes + $linea['cantidad'];
                    $this->establecerSaldo($linea['variante_id'], $data['almacen_destino_id'], $destinoDespues);
                }

                InventarioMovimientoLinea::create([
                    'movimiento_id' => $movimiento->id,
                    'variante_id' => $linea['variante_id'],
                    'cantidad' => $linea['cantidad'],
                    'saldo_anterior_origen' => $origenAntes,
                    'saldo_posterior_origen' => $origenDespues,
                    'saldo_anterior_destino' => $destinoAntes,
                    'saldo_posterior_destino' => $destinoDespues,
                ]);
            }

            return $movimiento->load('lineas');
        });
    }

    private function saldo(int $varianteId, int $almacenId): int
    {
        return (int) InventarioStockAlmacen::query()
            ->where('variante_id', $varianteId)
            ->where('almacen_id', $almacenId)
            ->lockForUpdate()
            ->value('cantidad');
    }

    private function incrementar(int $varianteId, int $almacenId, int $cantidad): void
    {
        $saldo = $this->saldo($varianteId, $almacenId);
        $this->establecerSaldo($varianteId, $almacenId, $saldo + $cantidad);
    }

    private function decrementar(int $varianteId, int $almacenId, int $cantidad): void
    {
        $saldo = $this->saldo($varianteId, $almacenId);
        if ($saldo < $cantidad) {
            throw new RuntimeException('No hay stock suficiente para revertir el movimiento.');
        }
        $this->establecerSaldo($varianteId, $almacenId, $saldo - $cantidad);
    }

    private function establecerSaldo(int $varianteId, int $almacenId, int $cantidad): void
    {
        InventarioStockAlmacen::query()->updateOrCreate(
            ['variante_id' => $varianteId, 'almacen_id' => $almacenId],
            ['cantidad' => $cantidad]
        );
    }

    /**
     * @return array<int, array{variante_id:int, cantidad:int}>
     */
    private function normalizarLineas(array $lineas): array
    {
        $resultado = [];
        foreach ($lineas as $linea) {
            $varianteId = (int) ($linea['variante_id'] ?? 0);
            $cantidad = (int) ($linea['cantidad'] ?? 0);
            if ($varianteId < 1 || $cantidad < 1) {
                throw new RuntimeException('Cada línea debe indicar una variante válida y una cantidad mayor que cero.');
            }
            $resultado[$varianteId] = ($resultado[$varianteId] ?? 0) + $cantidad;
        }

        return array_map(
            fn (int $cantidad, int $varianteId): array => ['variante_id' => $varianteId, 'cantidad' => $cantidad],
            $resultado,
            array_keys($resultado)
        );
    }

    private function siguienteNumero(string $tipo, int $proyectoId): string
    {
        $prefijo = match ($tipo) {
            'ENTRADA' => 'EN',
            'SALIDA' => 'SA',
            'TRASLADO' => 'TR',
            default => 'AJ',
        };

        do {
            $numero = sprintf('%s-%s-%04d', $prefijo, now()->format('Ymd'), random_int(1, 9999));
        } while (InventarioMovimiento::where('proyecto_id', $proyectoId)->where('numero_documento', $numero)->exists());

        return $numero;
    }
}
