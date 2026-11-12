<?php

namespace App\Http\Controllers;

use App\Models\InventarioMovimiento;
use App\Models\Personal;
use App\Services\InventarioStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class InventarioMovimientoController extends Controller
{
    public function __construct(private readonly InventarioStockService $stockService)
    {
        $this->middleware('auth');
        $this->middleware('permission:inventario.manage');
    }

    private function registrarDetalleLineasEnAuditoria(InventarioMovimiento $movimiento): void
    {
        if (!class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            return;
        }

        $resumenArticulos = $movimiento->obtenerResumenLineasAuditoria();
        $clases = $movimiento->obtenerClasesInvolucradasAuditoria();
        $totalUnidades = (int) $movimiento->lineas()->sum('cantidad');

        // Buscamos el log recién creado por el trait LogsActivity para este movimiento
        $ultimoLog = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', InventarioMovimiento::class)
            ->where('subject_id', $movimiento->id)
            ->latest('id')
            ->first();

        if ($ultimoLog) {
            $props = $ultimoLog->properties->toArray();
            $props['attributes']['clases'] = $clases;
            $props['attributes']['total_unidades'] = $totalUnidades . ' uds.';
            $props['attributes']['articulos'] = $resumenArticulos;

            $ultimoLog->properties = collect($props);
            $ultimoLog->save();
        }
    }

    public function storeEntrada(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'almacen_destino_id' => 'required|integer|exists:almacenes,id',
            'documento_referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
            'lineas' => 'required|array|min:1',
            'lineas.*.variante_id' => 'required|integer|exists:inventario_variantes,id',
            'lineas.*.cantidad' => 'required|integer|min:1',
        ]);

        return $this->execute(function () use ($request, $validated) {
            return $this->stockService->entrada([
                'proyecto_id' => $this->resolveActiveProyectoId($request),
                'almacen_destino_id' => (int) $validated['almacen_destino_id'],
                'lineas' => $validated['lineas'],
                'documento_referencia' => $validated['documento_referencia'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'created_by' => auth()->id(),
            ]);
        }, 'Entrada registrada correctamente en el Kardex.');
    }

    public function storeSalida(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'almacen_origen_id' => 'required|integer|exists:almacenes,id',
            'personal_id' => 'nullable|integer|exists:' . Personal::class . ',id',
            'ot' => 'nullable|string|max:100',
            'documento_referencia' => 'nullable|string|max:150',
            'observaciones' => 'nullable|string|max:1000',
            'lineas' => 'required|array|min:1',
            'lineas.*.variante_id' => 'required|integer|exists:inventario_variantes,id',
            'lineas.*.cantidad' => 'required|integer|min:1',
        ]);

        try {
            $proyectoId = $this->resolveActiveProyectoId($request);

            $payload = [
                'proyecto_id' => $proyectoId,
                'almacen_origen_id' => (int) $validated['almacen_origen_id'],
                'personal_id' => !empty($validated['personal_id']) ? (int) $validated['personal_id'] : null,
                'ot' => $validated['ot'] ?? null,
                'documento_referencia' => $validated['documento_referencia'] ?? null,
                'motivo' => $validated['documento_referencia'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'lineas' => $validated['lineas'],
                'created_by' => auth()->id(),
            ];

            $movimiento = method_exists($this->stockService, 'salida')
                ? $this->stockService->salida($payload)
                : $this->stockService->registrarSalida($proyectoId, $validated);

            if ($movimiento instanceof InventarioMovimiento) {
                $this->registrarDetalleLineasEnAuditoria($movimiento);
            }

            if (!empty($validated['personal_id']) && $movimiento) {
                return redirect()
                    ->action([\App\Http\Controllers\InventarioController::class, 'showSalidaDocumento'], $movimiento->id)
                    ->with('success', "Salida {$movimiento->numero_documento} registrada. Ya puedes imprimir el documento de entrega.");
            }

            return redirect()
                ->route('inventario.index')
                ->with('success', 'Salida de stock registrada correctamente en el Kardex.');
        } catch (Throwable $e) {
            report($e);
            return back()->withInput()->with('error', $e->getMessage() ?: 'No se pudo registrar la salida.');
        }
    }

    public function storeTraslado(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'almacen_origen_id' => 'required|integer|exists:almacenes,id',
            'almacen_destino_id' => 'required|integer|exists:almacenes,id|different:almacen_origen_id',
            'documento_referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
            'lineas' => 'required|array|min:1',
            'lineas.*.variante_id' => 'required|integer|exists:inventario_variantes,id',
            'lineas.*.cantidad' => 'required|integer|min:1',
        ]);

        return $this->execute(function () use ($request, $validated) {
            return $this->stockService->traslado([
                'proyecto_id' => $this->resolveActiveProyectoId($request),
                'almacen_origen_id' => (int) $validated['almacen_origen_id'],
                'almacen_destino_id' => (int) $validated['almacen_destino_id'],
                'lineas' => $validated['lineas'],
                'documento_referencia' => $validated['documento_referencia'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'created_by' => auth()->id(),
            ]);
        }, 'Traslado completado con éxito.');
    }

    public function revertir(Request $request, InventarioMovimiento $movimiento): RedirectResponse
    {
        return $this->execute(function () use ($movimiento) {
            $this->stockService->revertir($movimiento);
        }, 'Movimiento revertido correctamente y stock actualizado.');
    }

    private function execute(callable $operation, string $successMessage): RedirectResponse
    {
        try {
            $result = $operation();
            if ($result instanceof InventarioMovimiento) {
                $this->registrarDetalleLineasEnAuditoria($result);
            }
            return redirect()->route('inventario.index')->with('success', $successMessage);
        } catch (Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', $exception->getMessage() ?: 'No se pudo procesar la operación.');
        }
    }
}