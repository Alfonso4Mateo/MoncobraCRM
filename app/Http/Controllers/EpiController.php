<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEpiRequest;
use App\Http\Requests\UpdateEpiRequest;
use App\Models\Epi;
use App\Models\Inventario;
use App\Models\InventarioVariante;
use App\Models\Puesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EpiController extends Controller
{
    private const CATEGORIAS_SUGERIDAS = [
        'Protección Craneal',
        'Protección Ocular y Facial',
        'Protección Auditiva',
        'Protección Respiratoria',
        'Protección de Manos y Brazos',
        'Protección de Pies y Piernas',
        'Ropa de Trabajo / Vestuario',
        'Trabajos en Altura',
        'Otros',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        $epis = Epi::withCount('puestos')
            ->with(['puestos', 'inventario', 'inventarioVariante.items'])
            ->orderBy('nombre')
            ->get();

        $episPorCategoria = $epis
            ->groupBy(fn ($epi) => $epi->categoria ?: 'Sin categoría')
            ->sortKeys();

        $puestos = Puesto::where('activo', true)->orderBy('nombre')->get();

        $categoriasDisponibles = $epis->pluck('categoria')
            ->filter()
            ->unique()
            ->merge(self::CATEGORIAS_SUGERIDAS)
            ->unique()
            ->sort()
            ->values();

        // Opciones de inventario (familias con variantes e ítems individuales) para el selector de vinculación
        $variantesInventario = InventarioVariante::query()
            ->where('proyecto_id', $proyectoId)
            ->with('items')
            ->orderBy('nombre')
            ->get();

        $itemsSueltosInventario = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->whereNull('inventario_variante_id')
            ->orderBy('descripcion')
            ->get(['id', 'codigo', 'nombre', 'descripcion', 'stock_actual']);

        return view('epis.index', compact(
            'episPorCategoria',
            'puestos',
            'categoriasDisponibles',
            'variantesInventario',
            'itemsSueltosInventario'
        ));
    }

    public function store(StoreEpiRequest $request)
    {
        $validated = $request->validated();

        $epi = DB::transaction(function () use ($validated) {
            $epi = Epi::create([
                'nombre' => mb_strtoupper($validated['nombre']),
                'descripcion' => $validated['descripcion'] ?? null,
                'categoria' => $validated['categoria'] ?? null,
                'inventario_id' => !empty($validated['inventario_id']) ? (int) $validated['inventario_id'] : null,
                'inventario_variante_id' => !empty($validated['inventario_variante_id']) ? (int) $validated['inventario_variante_id'] : null,
                'activo' => true,
            ]);

            if (!empty($validated['puestos'])) {
                $syncData = [];
                foreach ($validated['puestos'] as $puestoId) {
                    $cantidad = $validated['cantidades'][$puestoId] ?? 1;
                    $syncData[$puestoId] = ['cantidad' => max(1, (int) $cantidad)];
                }
                $epi->puestos()->sync($syncData);
            }

            return $epi;
        });

        $mensaje = 'EPI creado y vinculado correctamente.';

        if ($request->wantsJson()) {
            return $this->jsonCardResponse($epi, $mensaje);
        }

        return redirect()->route('epis.index')->with('success', $mensaje);
    }

    public function update(UpdateEpiRequest $request, Epi $epi)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $epi) {
            $epi->update([
                'nombre' => mb_strtoupper($validated['nombre']),
                'descripcion' => $validated['descripcion'] ?? null,
                'categoria' => $validated['categoria'] ?? null,
                'inventario_id' => !empty($validated['inventario_id']) ? (int) $validated['inventario_id'] : null,
                'inventario_variante_id' => !empty($validated['inventario_variante_id']) ? (int) $validated['inventario_variante_id'] : null,
            ]);

            $syncData = [];
            if (!empty($validated['puestos'])) {
                foreach ($validated['puestos'] as $puestoId) {
                    $cantidad = $validated['cantidades'][$puestoId] ?? 1;
                    $syncData[$puestoId] = ['cantidad' => max(1, (int) $cantidad)];
                }
            }
            $epi->puestos()->sync($syncData);
        });

        $mensaje = 'EPI actualizado correctamente.';

        if ($request->wantsJson()) {
            return $this->jsonCardResponse($epi, $mensaje);
        }

        return redirect()->route('epis.index')->with('success', $mensaje);
    }

    public function toggleActivo(Request $request, Epi $epi)
    {
        $epi->update(['activo' => !$epi->activo]);

        $mensaje = $epi->activo
            ? 'EPI reactivado correctamente.'
            : 'EPI dado de baja. Ya no aparecerá en los puestos ni en las prescripciones oficiales.';

        if ($request->wantsJson()) {
            return $this->jsonCardResponse($epi, $mensaje);
        }

        return redirect()->route('epis.index')->with('success', $mensaje);
    }

    public function destroy(Request $request, Epi $epi)
    {
        if ($epi->puestos()->exists()) {
            $mensaje = 'No se puede eliminar: este EPI está vinculado a uno o más puestos. Quítalo de esos puestos o dalo de baja en su lugar.';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $mensaje], 422);
            }

            return redirect()->route('epis.index')->with('error', $mensaje);
        }

        $epi->delete();
        $mensaje = 'EPI eliminado correctamente.';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $mensaje]);
        }

        return redirect()->route('epis.index')->with('success', $mensaje);
    }

    private function jsonCardResponse(Epi $epi, string $mensaje)
    {
        $epi->load(['puestos', 'inventario', 'inventarioVariante.items']);
        $epi->loadCount('puestos');

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'categoria' => $epi->categoria ?: 'Sin categoría',
            'html' => view('epis.card', ['epi' => $epi])->render(),
        ]);
    }
}