<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Clase;
use App\Models\Inventario;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $request = request();
        $proyectoId = $this->resolveActiveProyectoId(request());
        $descripcion = trim((string) $request->query('descripcion', ''));
        $claseId = $request->query('clase_id');
        $almacen = trim((string) $request->query('almacen', ''));

        $baseQuery = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->with('claseRelacion')
            ->when($descripcion !== '', function ($query) use ($descripcion) {
                $query->where('descripcion', 'like', '%' . $descripcion . '%');
            })
            ->when($claseId !== null && $claseId !== '', function ($query) use ($claseId) {
                $query->where('clase_id', $claseId);
            })
            ->when($almacen !== '', function ($query) use ($almacen) {
                $query->where('almacen', 'like', '%' . $almacen . '%');
            });

        $clases = Clase::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');

        $inventarios = (clone $baseQuery)
            ->orderBy('codigo')
            ->orderBy('id')
            ->paginate(7)
            ->withQueryString();

        $totalProductos = (clone $baseQuery)->count();
        $nivelCritico = (clone $baseQuery)->whereColumn('stock_actual', '<=', 'nivel_critico')->count();
        $stockBajo = (clone $baseQuery)->whereColumn('stock_actual', '<=', 'stock_minimo')->count();
        $stockTotal = (clone $baseQuery)->sum('stock_actual');
        $ubicaciones = (clone $baseQuery)
            ->whereNotNull('ubicacion')
            ->where('ubicacion', '<>', '')
            ->distinct()
            ->count('ubicacion');
        $almacenesRegistrados = Almacen::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $almacenes = $almacenesRegistrados->count();

        $movimientosRecientes = (clone $baseQuery)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get()
            ->values()
            ->map(function (Inventario $producto, int $index) {
                $tipos = [
                    ['etiqueta' => 'Entrada', 'icono' => 'fa-arrow-down', 'tono' => 'positive'],
                    ['etiqueta' => 'Salida', 'icono' => 'fa-arrow-up', 'tono' => 'negative'],
                    ['etiqueta' => 'Transferencia', 'icono' => 'fa-exchange-alt', 'tono' => 'neutral'],
                ];

                $tipo = $tipos[$index % count($tipos)];
                $unidades = max(1, (int) round(max(1, (int) $producto->stock_actual) * 0.08));

                return (object) [
                    'etiqueta' => $tipo['etiqueta'],
                    'icono' => $tipo['icono'],
                    'tono' => $tipo['tono'],
                    'titulo' => sprintf('%s: %s (%s uds.)', $tipo['etiqueta'], $producto->codigo, number_format($unidades, 0, ',', '.')),
                    'subtitulo' => trim(sprintf('%s - %s', $producto->descripcion, $producto->almacen ?: 'Sin almacén')),
                    'tiempo' => $producto->updated_at?->diffForHumans() ?? 'Reciente',
                ];
            });

        $inventarioPorAlmacen = (clone $baseQuery)
            ->selectRaw('almacen, COUNT(*) as total_productos, SUM(stock_actual) as stock_total')
            ->whereNotNull('almacen')
            ->where('almacen', '<>', '')
            ->groupBy('almacen')
            ->get()
            ->mapWithKeys(function ($item) {
                $nombre = trim((string) ($item->almacen ?? ''));

                return [
                    $nombre => (object) [
                        'total_productos' => (int) ($item->total_productos ?? 0),
                        'stock_total' => (int) ($item->stock_total ?? 0),
                    ],
                ];
            });

        $ocupacionAlmacenes = $almacenesRegistrados
            ->map(function (Almacen $almacenItem) use ($inventarioPorAlmacen, $totalProductos) {
                $nombre = trim((string) $almacenItem->nombre);
                $inventario = $inventarioPorAlmacen->get($nombre);
                $total = (int) ($inventario->total_productos ?? 0);

                return (object) [
                    'nombre' => $nombre,
                    'total_productos' => $total,
                    'stock_total' => (int) ($inventario->stock_total ?? 0),
                    'porcentaje' => $totalProductos > 0 && $total > 0
                        ? max(5, (int) round(($total * 100) / $totalProductos))
                        : 0,
                ];
            })
            ->sortByDesc('total_productos')
            ->values();

        // Include legacy warehouse names found in inventory that are not in almacenes table yet.
        $nombresRegistrados = $almacenesRegistrados
            ->map(fn (Almacen $almacenItem) => trim((string) $almacenItem->nombre))
            ->filter()
            ->values();

        $ocupacionLegacy = $inventarioPorAlmacen
            ->reject(fn ($item, $nombre) => $nombresRegistrados->contains($nombre))
            ->map(function ($inventario, $nombre) use ($totalProductos) {
                $total = (int) ($inventario->total_productos ?? 0);

                return (object) [
                    'nombre' => $nombre,
                    'total_productos' => $total,
                    'stock_total' => (int) ($inventario->stock_total ?? 0),
                    'porcentaje' => $totalProductos > 0 && $total > 0
                        ? max(5, (int) round(($total * 100) / $totalProductos))
                        : 0,
                ];
            })
            ->sortByDesc('total_productos')
            ->values();

        $ocupacionAlmacenes = $ocupacionAlmacenes->concat($ocupacionLegacy)->values();

        return view('inventario.index', compact(
            'inventarios',
            'clases',
            'totalProductos',
            'nivelCritico',
            'stockBajo',
            'stockTotal',
            'ubicaciones',
            'almacenes',
            'movimientosRecientes',
            'ocupacionAlmacenes',
            'descripcion',
            'claseId',
            'almacen'
        ));
    }

    public function create()
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        $catalogo = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('descripcion')
            ->orderBy('codigo')
            ->limit(30)
            ->get(['codigo', 'descripcion', 'almacen', 'ubicacion', 'stock_actual']);

        $proveedores = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->whereNotNull('referencia_proveedor')
            ->where('referencia_proveedor', '<>', '')
            ->orderBy('referencia_proveedor')
            ->distinct()
            ->pluck('referencia_proveedor')
            ->values();

        $clases = \App\Models\Clase::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');

        $stockBase = (int) ($catalogo->first()?->stock_actual ?? 0);

        return view('inventario.create', compact('catalogo', 'proveedores', 'clases', 'stockBase'));
    }

    public function createItem()
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        $ultimaAccion = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->orderByDesc('updated_at')
            ->first();

        $clases = \App\Models\Clase::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');

        return view('inventario.create-item', compact('ultimaAccion', 'clases'));
    }

    public function createSalida()
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        $catalogo = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('descripcion')
            ->orderBy('codigo')
            ->limit(30)
            ->get(['codigo', 'descripcion', 'almacen', 'ubicacion', 'stock_actual']);

        $salidasRecientes = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->where('stock_actual', '>', 0)
            ->orderByDesc('updated_at')
            ->limit(2)
            ->get(['codigo', 'descripcion', 'updated_at', 'stock_actual']);

        return view('inventario.salida', compact('catalogo', 'salidasRecientes'));
    }

    public function storeSalida(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'producto_busqueda' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:255',
            'cantidad_retirar' => 'required|integer|min:1',
            'ot' => 'nullable|string|max:255',
            'solicitante' => 'nullable|string|max:255',
        ]);

        $codigo = trim((string) ($validated['codigo'] ?? ''));
        $busqueda = trim((string) $validated['producto_busqueda']);
        $cantidad = (int) $validated['cantidad_retirar'];

        $producto = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->where(function ($query) use ($codigo, $busqueda) {
                if ($codigo !== '') {
                    $query->orWhere('codigo', $codigo);
                }

                $query->orWhere('descripcion', $busqueda)
                    ->orWhere('codigo', $busqueda);
            })
            ->first();

        if (!$producto) {
            return back()
                ->withInput()
                ->withErrors([
                    'producto_busqueda' => 'No se encontró el item en inventario para registrar la salida.',
                ]);
        }

        if ((int) $producto->stock_actual < $cantidad) {
            return back()
                ->withInput()
                ->withErrors([
                    'cantidad_retirar' => 'Stock insuficiente para completar la salida solicitada.',
                ]);
        }

        $producto->stock_actual = (int) $producto->stock_actual - $cantidad;
        $producto->save();

        return redirect()
            ->route('inventario.index')
            ->with('success', 'Salida de stock registrada correctamente.');
    }

    public function createTraslado()
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        $catalogo = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('descripcion')
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'descripcion', 'referencia_proveedor', 'almacen', 'stock_actual']);

        $destinos = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->whereNotNull('almacen')
            ->where('almacen', '<>', '')
            ->orderBy('almacen')
            ->distinct()
            ->pluck('almacen')
            ->values();

        $transaccionId = 'TRF-' . now()->format('Ymd-His');

        return view('inventario.traslado', compact('catalogo', 'destinos', 'transaccionId'));
    }

    public function storeTraslado(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'destino_global' => 'required|string|max:255',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'required|integer',
            'cantidades' => 'required|array|min:1',
            'cantidades.*' => 'required|integer|min:1',
        ]);

        $itemIds = collect($validated['item_ids'])->map(fn ($value) => (int) $value)->values();
        $cantidades = collect($validated['cantidades'])->map(fn ($value) => (int) $value)->values();

        $productos = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        if ($productos->count() !== $itemIds->count()) {
            return back()
                ->withInput()
                ->withErrors([
                    'item_ids' => 'Uno o mas productos del traslado ya no estan disponibles.',
                ]);
        }

        foreach ($itemIds as $index => $itemId) {
            $producto = $productos->get($itemId);
            $cantidad = (int) ($cantidades[$index] ?? 0);

            if (!$producto || $cantidad < 1) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'cantidades' => 'Se detectaron cantidades invalidas en el traslado.',
                    ]);
            }

            if ($cantidad > (int) $producto->stock_actual) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'cantidades' => 'La cantidad a trasladar no puede superar el stock disponible.',
                    ]);
            }

            // El modelo actual no separa stock por lote/ubicacion parcial; se actualiza la ubicacion global del item.
            $producto->almacen = $validated['destino_global'];
            $producto->save();
        }

        return redirect()
            ->route('inventario.index')
            ->with('success', 'Traslado de lote registrado correctamente.');
    }

    public function storeEntrada(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'producto_busqueda' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:255',
            'referencia_proveedor' => 'nullable|string|max:255',
            'almacen' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'clase' => 'nullable|string|max:255',
            'stock_actual' => 'required|integer|min:1',
        ]);

        $codigo = trim((string) ($validated['codigo'] ?? ''));
        $busqueda = trim((string) $validated['producto_busqueda']);

        $producto = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->where(function ($query) use ($codigo, $busqueda) {
                if ($codigo !== '') {
                    $query->orWhere('codigo', $codigo);
                }

                $query->orWhere('descripcion', $busqueda)
                    ->orWhere('codigo', $busqueda);
            })
            ->first();

        if (!$producto) {
            return back()
                ->withInput()
                ->withErrors([
                    'producto_busqueda' => 'No se encontró el item en inventario. Usa "Crear nuevo item" para darlo de alta.',
                ]);
        }

        $producto->stock_actual = (int) $producto->stock_actual + (int) $validated['stock_actual'];

        if (!empty($validated['almacen'])) {
            $producto->almacen = $validated['almacen'];
        }

        if (!empty($validated['ubicacion'])) {
            $producto->ubicacion = $validated['ubicacion'];
        }

        if (!empty($validated['clase'])) {
            $producto->clase = $validated['clase'];
        }

        if (!empty($validated['referencia_proveedor'])) {
            $producto->referencia_proveedor = $validated['referencia_proveedor'];
        }

        $producto->save();

        return redirect()
            ->route('inventario.index')
            ->with('success', 'Entrada de stock registrada correctamente.');
    }

    public function store(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'codigo' => 'required|unique:inventario,codigo',
            'descripcion' => 'required|string',
            'referencia_proveedor' => 'nullable|string|max:255',
            'clase_id' => 'nullable|integer|exists:clases,id',
            'ubicacion' => 'nullable|string|max:255',
            'almacen' => 'nullable|string|max:255',
            'stock_actual' => 'required|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'nivel_critico' => 'nullable|integer|min:0',
        ]);

        $validated['proyecto_id'] = $proyectoId;

        Inventario::create($validated);
        return redirect()->route('inventario.index')->with('success', 'Producto creado');
    }

    public function show(Inventario $inventario)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $inventario->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return view('inventario.show', compact('inventario'));
    }

    public function edit(Inventario $inventario)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $inventario->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $clases = \App\Models\Clase::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');

        return view('inventario.edit', compact('inventario', 'clases'));
    }

    public function update(Request $request, Inventario $inventario)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        if ((int) $inventario->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $validated = $request->validate([
            'codigo' => 'required|unique:inventario,codigo,' . $inventario->id,
            'descripcion' => 'required|string',
            'referencia_proveedor' => 'nullable|string|max:255',
            'clase_id' => 'nullable|integer|exists:clases,id',
            'ubicacion' => 'nullable|string|max:255',
            'almacen' => 'nullable|string|max:255',
            'stock_actual' => 'required|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'nivel_critico' => 'nullable|integer|min:0',
        ]);

        $inventario->update($validated);
        return redirect()->route('inventario.index')->with('success', 'Producto actualizado');
    }

    public function destroy(Inventario $inventario)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $inventario->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $inventario->delete();
        return redirect()->route('inventario.index')->with('success', 'Producto eliminado');
    }
}
