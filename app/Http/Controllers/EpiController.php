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

    private function getMisProyectosIds(): ?array
    {
        $user = auth()->user();
        if (!$user || $user->role === 'superadmin') {
            return null;
        }

        return $user->proyectos->pluck('id')->all();
    }

    private function authorizeEpiProyecto(Epi $epi): void
    {
        $misProyectosIds = $this->getMisProyectosIds();
        if ($misProyectosIds === null) {
            return;
        }

        abort_unless(
            in_array((int) $epi->proyecto_id, array_map('intval', $misProyectosIds), true),
            403,
            'No tienes acceso a un EPI de otra sede.'
        );
    }

    public function index(): View
    {
        $this->authorize('epis.gestion');

        $proyectoId = $this->resolveProyectoForCorrelativo(request());

        $epis = Epi::query()
            ->where('proyecto_id', $proyectoId)
            ->withCount(['puestos' => fn ($q) => $q->where('puestos.proyecto_id', $proyectoId)])
            ->with(['puestos', 'inventario', 'inventarioVariante.items', 'inventarioArticulo'])
            ->orderBy('nombre')
            ->get();

        $episPorCategoria = $epis
            ->groupBy(fn ($epi) => $epi->categoria ?: 'Sin categoría')
            ->sortKeys();

        $puestos = Puesto::where('proyecto_id', $proyectoId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $categoriasDisponibles = $epis->pluck('categoria')
            ->filter()
            ->unique()
            ->merge(self::CATEGORIAS_SUGERIDAS)
            ->unique()
            ->sort()
            ->values();

        // Opciones de inventario (familias con variantes e ítems individuales) filtradas por sede activa
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
        $this->authorize('epis.gestion');

        $proyectoId = $this->resolveActiveProyectoId($request);
        $validated = $request->validated();

        $epi = DB::transaction(function () use ($validated, $proyectoId) {
            $epi = Epi::create([
                'proyecto_id' => $proyectoId,
                'nombre' => mb_strtoupper($validated['nombre']),
                'descripcion' => $validated['descripcion'] ?? null,
                'categoria' => $validated['categoria'] ?? null,
                'inventario_id' => !empty($validated['inventario_id']) ? (int) $validated['inventario_id'] : null,
                'inventario_variante_id' => !empty($validated['inventario_variante_id']) ? (int) $validated['inventario_variante_id'] : null,
                'inventario_articulo_id' => !empty($validated['inventario_articulo_id']) ? (int) $validated['inventario_articulo_id'] : null,
                'activo' => true,
            ]);

            if (!empty($validated['puestos'])) {
                $puestosValidosIds = Puesto::where('proyecto_id', $proyectoId)
                    ->whereIn('id', $validated['puestos'])
                    ->pluck('id')
                    ->all();

                $syncData = [];
                foreach ($puestosValidosIds as $puestoId) {
                    $cantidad = $validated['cantidades'][$puestoId] ?? 1;
                    $syncData[$puestoId] = ['cantidad' => max(1, (int) $cantidad)];
                }
                $epi->puestos()->sync($syncData);
            }

            return $epi;
        });

        $mensaje = 'EPI creado correctamente.';

        if ($request->wantsJson()) {
            return $this->jsonCardResponse($epi, $mensaje);
        }

        return redirect()->route('epis.index')->with('success', $mensaje);
    }

    public function update(UpdateEpiRequest $request, Epi $epi)
    {
        $this->authorize('epis.gestion');
        $this->authorizeEpiProyecto($epi);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $epi, $request) {
            $invId = !empty($validated['inventario_id']) ? (int) $validated['inventario_id'] : null;
            $varId = !empty($validated['inventario_variante_id']) ? (int) $validated['inventario_variante_id'] : null;
            $artId = !empty($validated['inventario_articulo_id']) ? (int) $validated['inventario_articulo_id'] : null;

            if ($invId !== null || $varId !== null) {
                $artId = null;
            }

            $epi->update([
                'nombre' => mb_strtoupper($validated['nombre']),
                'descripcion' => $validated['descripcion'] ?? null,
                'categoria' => $validated['categoria'] ?? null,
                'inventario_id' => $invId,
                'inventario_variante_id' => $varId,
                'inventario_articulo_id' => $artId,
            ]);

            if ($request->has('puestos') || $request->has('sync_puestos')) {
                $syncData = [];
                if (!empty($validated['puestos'])) {
                    $puestosValidosIds = Puesto::where('proyecto_id', $epi->proyecto_id)
                        ->whereIn('id', $validated['puestos'])
                        ->pluck('id')
                        ->all();

                    foreach ($puestosValidosIds as $puestoId) {
                        $cantidad = $validated['cantidades'][$puestoId] ?? 1;
                        $syncData[$puestoId] = ['cantidad' => max(1, (int) $cantidad)];
                    }
                }
                $epi->puestos()->sync($syncData);
            }
        });

        $mensaje = 'EPI actualizado correctamente.';

        if ($request->wantsJson()) {
            return $this->jsonCardResponse($epi, $mensaje);
        }

        return redirect()->route('epis.index')->with('success', $mensaje);
    }

    public function toggleActivo(Request $request, Epi $epi)
    {
        $this->authorize('epis.gestion');
        $this->authorizeEpiProyecto($epi);

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
        $this->authorize('epis.gestion');
        $this->authorizeEpiProyecto($epi);

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
        $epi->load(['puestos', 'inventario', 'inventarioVariante.items', 'inventarioArticulo']);
        $epi->loadCount(['puestos' => fn ($q) => $q->where('puestos.proyecto_id', $epi->proyecto_id)]);

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'categoria' => $epi->categoria ?: 'Sin categoría',
            'html' => view('epis.card', ['epi' => $epi])->render(),
        ]);
    }
}