<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Clase;
use App\Models\EntradaStock;
use App\Models\Inventario;
use App\Models\Personal;
use App\Models\Proyecto;
use App\Models\SalidaStock;
use App\Models\TrasladoStock;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            ->with(['claseRelacion', 'variante'])
            ->when($descripcion !== '', function ($query) use ($descripcion) {
                $query->where(function ($q) use ($descripcion) {
                    $q->where('codigo', 'like', '%' . $descripcion . '%')
                      ->orWhere('nombre', 'like', '%' . $descripcion . '%')
                      ->orWhere('descripcion', 'like', '%' . $descripcion . '%')
                      ->orWhere('referencia_proveedor', 'like', '%' . $descripcion . '%');
                });
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

        // Paginate by parent groups (not by child rows), so each page shows 8 parent items.
        $inventarioAgrupado = (clone $baseQuery)
            ->orderBy('codigo')
            ->orderBy('id')
            ->get()
            ->groupBy(function (Inventario $item) {
                $varianteId = (int) ($item->inventario_variante_id ?? 0);

                return $varianteId > 0 ? 'variante_' . $varianteId : 'item_' . (int) $item->id;
            })
            ->map(function ($grupo) {
                $grupo = collect($grupo)->sortBy('id')->values();
                $productoPadre = $grupo->first();

                if (is_array($productoPadre)) {
                    $productoPadre = (object) $productoPadre;
                }

                $variantePadre = data_get($productoPadre, 'variante');
                $stockGrupo = (int) $grupo->sum('stock_actual');

                $stockMinimoGrupo = (int) (
                    data_get($variantePadre, 'stock_minimo')
                    ?? data_get($productoPadre, 'stock_minimo')
                    ?? 0
                );

                $nivelCriticoGrupo = (int) (
                    data_get($variantePadre, 'nivel_critico')
                    ?? data_get($productoPadre, 'nivel_critico')
                    ?? 0
                );

                if ($stockGrupo <= $nivelCriticoGrupo) {
                    $estadoGrupo = 'critico';
                    $estadoTextoGrupo = 'Crítico';
                } elseif ($stockGrupo <= $stockMinimoGrupo) {
                    $estadoGrupo = 'bajo';
                    $estadoTextoGrupo = 'Reposición';
                } else {
                    $estadoGrupo = 'optimo';
                    $estadoTextoGrupo = 'Óptimo';
                }

                $hijos = $grupo->skip(1)->map(function ($producto) {
                    if (is_array($producto)) {
                        $producto = (object) $producto;
                    }

                    $stockActual = (int) data_get($producto, 'stock_actual', 0);
                    $stockMinimo = (int) data_get($producto, 'stock_minimo', 0);
                    $nivelCritico = (int) data_get($producto, 'nivel_critico', 0);

                    if ($stockActual <= $nivelCritico) {
                        $estado = 'critico';
                        $estadoTexto = 'Crítico';
                    } elseif ($stockActual <= $stockMinimo) {
                        $estado = 'bajo';
                        $estadoTexto = 'Reposición';
                    } else {
                        $estado = 'optimo';
                        $estadoTexto = 'Óptimo';
                    }

                    return (object) [
                        'codigo' => data_get($producto, 'codigo'),
                        'nombre' => data_get($producto, 'nombre'),
                        'descripcion' => data_get($producto, 'descripcion'),
                        'referencia_proveedor' => data_get($producto, 'referencia_proveedor'),
                        'almacen' => data_get($producto, 'almacen'),
                        'ubicacion' => data_get($producto, 'ubicacion'),
                        'id' => data_get($producto, 'id'),
                        'clase_relacion' => data_get($producto, 'claseRelacion') ?: data_get($productoPadre, 'claseRelacion'),
                        'stock_actual' => $stockActual,
                        'stock_minimo' => $stockMinimo,
                        'nivel_critico' => $nivelCritico,
                        'estado' => $estado,
                        'estado_texto' => $estadoTexto,
                    ];
                })->values();

                $hijosReposicion = $hijos->filter(fn ($hijo) => $hijo->stock_actual > 0 && $hijo->stock_actual <= $hijo->stock_minimo)->count();
                $hijosCriticos = $hijos->filter(fn ($hijo) => $hijo->stock_actual > 0 && $hijo->stock_actual <= $hijo->nivel_critico)->count();

                return (object) [
                    'padre' => $productoPadre,
                    'idPadre' => data_get($productoPadre, 'id'),
                    'variantePadre' => $variantePadre,
                    'variantePadreId' => data_get($variantePadre, 'id'),
                    'codigoPadre' => data_get($variantePadre, 'codigo') ?? data_get($productoPadre, 'codigo'),
                    'nombrePadre' => data_get($variantePadre, 'nombre') ?? data_get($productoPadre, 'nombre'),
                    'descripcionPadre' => data_get($variantePadre, 'descripcion') ?? data_get($productoPadre, 'descripcion'),
                    'referenciaPadre' => data_get($variantePadre, 'referencia_proveedor') ?? data_get($productoPadre, 'referencia_proveedor'),
                    'almacenPadre' => data_get($variantePadre, 'almacen') ?? data_get($productoPadre, 'almacen'),
                    'ubicacionPadre' => data_get($variantePadre, 'ubicacion') ?? data_get($productoPadre, 'ubicacion'),
                    'clasePadre' => is_object(data_get($productoPadre, 'claseRelacion'))
                        ? data_get($productoPadre, 'claseRelacion.nombre')
                        : data_get($productoPadre, 'clase', 'Sin clase'),
                    'stockGrupo' => $stockGrupo,
                    'estadoGrupo' => $estadoGrupo,
                    'estadoTextoGrupo' => $estadoTextoGrupo,
                    'hijosReposicion' => $hijosReposicion,
                    'hijosCriticos' => $hijosCriticos,
                    'tieneHijos' => $grupo->count() > 1,
                    'hijos' => $hijos,
                ];
            })
            ->values();

        $perPage = 8;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $totalGrupos = $inventarioAgrupado->count();
        $gruposPagina = $inventarioAgrupado
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $inventarios = new LengthAwarePaginator(
            $gruposPagina,
            $totalGrupos,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

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
            ->get(['id', 'codigo', 'descripcion', 'nombre', 'almacen', 'ubicacion', 'stock_actual']);

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
        $varianteId = request()->query('variante_id');

        $ultimaAccion = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->orderByDesc('updated_at')
            ->first();

        $varianteBase = null;
        $valoresIniciales = [];

        if (!empty($varianteId)) {
            $varianteBase = \App\Models\InventarioVariante::query()
                ->where('proyecto_id', $proyectoId)
                ->with('items')
                ->findOrFail($varianteId);

            foreach ($varianteBase->items as $item) {
                foreach (($item->atributos_variante ?? []) as $tipo => $valor) {
                    $valores = is_array($valor) ? $valor : [$valor];
                    foreach ($valores as $valorItem) {
                        $valorItem = trim((string) $valorItem);
                        if ($valorItem === '') {
                            continue;
                        }

                        $valoresIniciales[$tipo][] = $valorItem;
                    }
                }
            }

            foreach ($valoresIniciales as $tipo => $valores) {
                $valoresIniciales[$tipo] = collect($valores)->unique()->values()->all();
            }
        }

        $clases = \App\Models\Clase::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');

        return view('inventario.create-item', compact('ultimaAccion', 'clases', 'varianteBase', 'valoresIniciales'));
    }

    public function createSalida()
    {
        $proyectoId = $this->resolveActiveProyectoId(request());
        $delegacionPrefill = Proyecto::query()->where('id', $proyectoId)->value('nombre') ?? '';
        $solicitantePrefill = null;
        $personalId = (int) request()->query('personal_id', 0);

        if ($personalId > 0) {
            $persona = Personal::query()->find($personalId);
            if ($persona) {
                $solicitantePrefill = trim((string) $persona->name . ' ' . (string) $persona->apellido);
            }
        }

        if (!$solicitantePrefill) {
            $querySolicitante = trim((string) request()->query('solicitante', ''));
            $solicitantePrefill = $querySolicitante !== '' ? $querySolicitante : null;
        }

        $catalogo = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('descripcion')
            ->orderBy('codigo')
            ->get(['codigo', 'descripcion', 'almacen', 'ubicacion', 'stock_actual']);

        $salidasRecientes = Inventario::query()
            ->where('proyecto_id', $proyectoId)
            ->where('stock_actual', '>', 0)
            ->orderByDesc('updated_at')
            ->limit(2)
            ->get(['codigo', 'descripcion', 'updated_at', 'stock_actual']);

        $solicitantes = Personal::query()
            ->orderBy('name')
            ->orderBy('apellido')
            ->get(['name', 'apellido'])
            ->map(function (Personal $persona) {
                return trim((string) $persona->name . ' ' . (string) $persona->apellido);
            })
            ->filter()
            ->unique()
            ->values();

        return view('inventario.salida', compact('catalogo', 'salidasRecientes', 'solicitantes', 'solicitantePrefill', 'delegacionPrefill'));
    }

    public function storeSalida(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'producto_busqueda' => 'nullable|string|max:1000',
            'codigo' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.inventario_id' => 'nullable|integer',
            'items.*.producto_busqueda' => 'nullable|string|max:1000',
            'items.*.cantidad' => 'nullable|integer|min:1',
            'ot' => 'nullable|string|max:255',
            'solicitante' => 'nullable|string|max:255',
            'pdf_delegacion' => 'nullable|string|max:255',
            'pdf_fecha' => 'nullable|string|max:30',
            'pdf_trabajador' => 'nullable|string|max:255',
            'pdf_ficha' => 'nullable|string|max:255',
            'pdf_observaciones' => 'nullable|string|max:2000',
        ]);

        $itemsToProcess = [];

        if (!empty($validated['items']) && is_array($validated['items'])) {
            foreach ($validated['items'] as $it) {
                $busqueda = trim((string) ($it['producto_busqueda'] ?? ''));
                $cantidad = isset($it['cantidad']) ? (int) $it['cantidad'] : 0;
                if ($busqueda !== '' && $cantidad > 0) {
                    $itemsToProcess[] = ['busqueda' => $busqueda, 'cantidad' => $cantidad];
                }
            }
        }

        if (empty($itemsToProcess)) {
            return back()->withInput()->withErrors(['producto_busqueda' => 'No se especificaron items para la salida.']);
        }

        $numeroSalida = 'SL-' . now()->format('Ymd-His-u');
        $lineasDocumento = array_map(function ($it) {
            return ['articulo' => $it['busqueda'], 'cantidad' => $it['cantidad']];
        }, $itemsToProcess);

        $documentoMeta = [
            'nombre' => 'EPI_' . $numeroSalida . '.pdf',
            'delegacion' => trim((string) $request->input('pdf_delegacion', Proyecto::query()->where('id', $proyectoId)->value('nombre') ?? '')),
            'fecha' => trim((string) $request->input('pdf_fecha', now()->format('d/m/Y'))),
            'trabajador' => trim((string) $request->input('pdf_trabajador', $validated['solicitante'] ?? '')),
            'ficha' => trim((string) $request->input('pdf_ficha', '')),
            'observaciones' => trim((string) $request->input('pdf_observaciones', '')),
            'lineas' => $lineasDocumento,
        ];

        $documentoMeta = array_filter($documentoMeta, function ($value, $key) {
            if ($key === 'lineas') {
                return !empty($value);
            }

            return $value !== '' && $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        $guardarDocumento = $request->boolean('guardar_documento');
        $documentoMeta = $guardarDocumento ? $documentoMeta : null;

        // Resolve products existence (prefer inventario_id when provided) but defer stock validation to transactional lock
        $resolved = [];
        foreach ($itemsToProcess as $it) {
            $busqueda = $it['busqueda'];
            $inventarioId = isset($it['inventario_id']) ? (int) $it['inventario_id'] : null;
            $codigo = trim((string) ($it['codigo'] ?? ''));

            $producto = null;
            if ($inventarioId && $inventarioId > 0) {
                $producto = Inventario::query()->where('proyecto_id', $proyectoId)->where('id', $inventarioId)->first();
            }

            if (!$producto && $codigo !== '') {
                $producto = Inventario::query()
                    ->where('proyecto_id', $proyectoId)
                    ->where('codigo', $codigo)
                    ->first();
            }

            if (!$producto) {
                $producto = Inventario::query()
                    ->where('proyecto_id', $proyectoId)
                    ->where(function ($query) use ($busqueda) {
                        $query->where('descripcion', $busqueda)
                            ->orWhere('codigo', $busqueda);
                    })
                    ->first();
            }

            if (!$producto) {
                return back()->withInput()->withErrors(['producto_busqueda' => "No se encontró el item: {$busqueda}"]);
            }

            $resolved[] = ['producto' => $producto, 'cantidad' => (int) $it['cantidad']];
        }

        $seenIds = [];
        foreach ($resolved as $r) {
            $id = (int) $r['producto']->id;
            if (isset($seenIds[$id])) {
                return back()->withInput()->withErrors(['items' => 'No puedes añadir dos veces el mismo producto.']);
            }
            $seenIds[$id] = true;
        }

        $salida = null;

        try {
            DB::transaction(function () use ($proyectoId, $resolved, $validated, $numeroSalida, $documentoMeta, &$salida) {
                $itemsForSalida = [];

                foreach ($resolved as $entry) {
                    $id = (int) $entry['producto']->id;
                    // lock the row for update
                    $producto = Inventario::query()->where('proyecto_id', $proyectoId)->where('id', $id)->lockForUpdate()->first();
                    $cantidad = (int) $entry['cantidad'];

                    if (!$producto) {
                        throw new \RuntimeException("No se encontró el item con id {$id} durante la transacción.");
                    }

                    if ((int) $producto->stock_actual < $cantidad) {
                        throw new \RuntimeException("Stock insuficiente para {$producto->codigo} ({$producto->descripcion})");
                    }

                    $producto->stock_actual = (int) $producto->stock_actual - $cantidad;
                    $producto->save();

                    $itemsForSalida[] = [
                        'inventario_id' => (int) $producto->id,
                        'codigo' => (string) $producto->codigo,
                        'descripcion' => (string) $producto->descripcion,
                        'cantidad' => (int) $cantidad,
                        'almacen_origen' => (string) $producto->almacen,
                        'almacen_actual' => (string) ($producto->almacen ?? ''),
                    ];
                }

                $salida = SalidaStock::create([
                    'proyecto_id' => $proyectoId,
                    'numero_salida' => $numeroSalida,
                    'fecha' => now(),
                    'solicitante' => $validated['solicitante'] ?? null,
                    'ot' => $validated['ot'] ?? null,
                    'almacen_origen' => count($itemsForSalida) === 1 ? $itemsForSalida[0]['almacen_origen'] : 'Varios',
                    'items' => $itemsForSalida,
                    'documento_meta' => !empty($documentoMeta) ? $documentoMeta : null,
                    'estado' => 'aceptado',
                ]);
            });
        } catch (\RuntimeException $ex) {
            return back()->withInput()->withErrors(['items' => $ex->getMessage()]);
        }

        if ($guardarDocumento && $salida) {
            return redirect()->route('inventario.salida.documento', $salida)->with('success', 'Salida registrada y documento guardado correctamente.');
        }

        return redirect()->route('inventario.index')->with('success', 'Salida de stock registrada correctamente.');
    }

    public function showSalidaDocumento(SalidaStock $salida)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $salida->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $documento = (array) ($salida->documento_meta ?? []);
        $lineasDocumento = $documento['lineas'] ?? [];
        $delegacionPrefill = trim((string) ($documento['delegacion'] ?? (Proyecto::query()->where('id', $proyectoId)->value('nombre') ?? '')));

        if (empty($lineasDocumento) && is_array($salida->items)) {
            $lineasDocumento = collect($salida->items)
                ->filter(fn ($item) => is_array($item))
                ->map(function (array $item) {
                    return [
                        'articulo' => $item['descripcion'] ?? $item['codigo'] ?? 'Item',
                        'cantidad' => (string) ($item['cantidad'] ?? ''),
                    ];
                })
                ->values()
                ->all();
        }

        $lineasDocumento = array_slice($lineasDocumento, 0, 10);

        return view('inventario.salida-documento', [
            'salida' => $salida,
            'documento' => $documento,
            'lineasDocumento' => $lineasDocumento,
            'delegacionPrefill' => $delegacionPrefill,
        ]);
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
            'ot' => 'nullable|string|max:255',
            'solicitante' => 'nullable|string|max:255',
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

        $itemsMovimiento = [];
        $almacenesOrigen = [];

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

            $almacenOrigenItem = (string) ($producto->almacen ?? '');
            if (trim($almacenOrigenItem) !== '') {
                $almacenesOrigen[] = $almacenOrigenItem;
            }

            $itemsMovimiento[] = [
                'inventario_id' => (int) $producto->id,
                'codigo' => (string) $producto->codigo,
                'descripcion' => (string) $producto->descripcion,
                'cantidad' => (int) $cantidad,
                'almacen_origen' => $almacenOrigenItem,
                'almacen_actual' => (string) $validated['destino_global'],
            ];
        }

        $almacenesUnicos = collect($almacenesOrigen)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        DB::transaction(function () use ($proyectoId, $validated, $itemIds, $productos, $almacenesUnicos, $itemsMovimiento) {
            foreach ($itemIds as $itemId) {
                $producto = $productos->get($itemId);
                if (!$producto) {
                    continue;
                }

                // El modelo actual no separa stock por lote/ubicacion parcial; se actualiza la ubicacion global del item.
                $producto->almacen = $validated['destino_global'];
                $producto->save();
            }

            TrasladoStock::create([
                'proyecto_id' => $proyectoId,
                'numero_traslado' => 'TR-' . now()->format('Ymd-His-u'),
                'fecha' => now(),
                'solicitante' => $validated['solicitante'] ?? null,
                'ot' => $validated['ot'] ?? null,
                'almacen_origen' => $almacenesUnicos->count() === 1 ? $almacenesUnicos->first() : 'Varios',
                'almacen_actual' => $validated['destino_global'],
                'items' => $itemsMovimiento,
                'estado' => 'aceptado',
            ]);
        });

        return redirect()
            ->route('inventario.index')
            ->with('success', 'Traslado de lote registrado correctamente.');
    }

    public function storeEntrada(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'producto_busqueda' => 'required|string|max:1000',
            'codigo' => 'nullable|string|max:255',
            'referencia_proveedor' => 'nullable|string|max:255',
            'almacen' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'clase' => 'nullable|string|max:255',
            'stock_actual' => 'required|integer|min:1',
            'ot' => 'nullable|string|max:255',
            'solicitante' => 'nullable|string|max:255',
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

        DB::transaction(function () use ($proyectoId, $producto, $validated) {
            $cantidad = (int) $validated['stock_actual'];

            $producto->stock_actual = (int) $producto->stock_actual + $cantidad;

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

            EntradaStock::create([
                'proyecto_id' => $proyectoId,
                'numero_entrada' => 'EN-' . now()->format('Ymd-His-u'),
                'fecha' => now(),
                'solicitante' => $validated['solicitante'] ?? null,
                'ot' => $validated['ot'] ?? null,
                'almacen_origen' => $producto->almacen,
                'items' => [
                    [
                        'inventario_id' => (int) $producto->id,
                        'codigo' => (string) $producto->codigo,
                        'descripcion' => (string) $producto->descripcion,
                        'cantidad' => (int) $cantidad,
                    ],
                ],
                'estado' => 'aceptado',
            ]);
        });

        return redirect()
            ->route('inventario.index')
            ->with('success', 'Entrada de stock registrada correctamente.');
    }

    public function store(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'codigo' => 'required|string|max:255',
            'nombre' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'referencia_proveedor' => 'nullable|string|max:255',
            'clase_id' => 'nullable|integer|exists:clases,id',
            'ubicacion' => 'nullable|string|max:255',
            'almacen' => 'nullable|string|max:255',
            'stock_actual' => 'nullable|integer|min:0|required_without:variantes',
            'stock_minimo' => 'nullable|integer|min:0',
            'nivel_critico' => 'nullable|integer|min:0',
            'tipos_atributos' => 'nullable|string', // JSON string con tipos de variantes
            'atributos_variante' => 'nullable|array', // Valores de los atributos
            'atributos_variante.*' => 'nullable|array',
            'atributos_variante.*.*' => 'nullable|string|max:255',
            'variantes' => 'nullable|array',
            'variantes.*.activo' => 'nullable|boolean',
            'variantes.*.stock_actual' => 'nullable|integer|min:0',
            'variantes.*.atributos' => 'nullable|array',
            'variantes.*.atributos.*' => 'nullable|string|max:255',
            'inventario_variante_id' => 'nullable|integer|exists:inventario_variantes,id',
        ]);

        $validated['proyecto_id'] = $proyectoId;

        // Prefer 'nombre' as the main descripción if provided
        if (!empty($validated['nombre'])) {
            $validated['descripcion'] = $validated['nombre'];
        }

        // Parsear tipos de atributos si viene como string JSON
        $tiposAtributos = $this->normalizeVariantTypes($validated['tipos_atributos'] ?? null);

        // Limpiar y preparar atributos de variante
        $atributosVariante = $this->normalizeVariantAttributes($validated['atributos_variante'] ?? null);

        $variantes = $this->normalizeVariantRows($validated['variantes'] ?? null);

        $variante = null;

        if (!empty($validated['inventario_variante_id'])) {
            $variante = \App\Models\InventarioVariante::query()
                ->where('proyecto_id', $proyectoId)
                ->findOrFail((int) $validated['inventario_variante_id']);
        } else {
            $variante = \App\Models\InventarioVariante::firstOrCreate(
                [
                    'proyecto_id' => $proyectoId,
                    'codigo' => $validated['codigo'],
                ],
                [
                    'nombre' => $validated['nombre'] ?? $validated['descripcion'] ?? null,
                    'descripcion' => $validated['descripcion'] ?? $validated['nombre'] ?? null,
                    'referencia_proveedor' => $validated['referencia_proveedor'],
                    'clase_id' => $validated['clase_id'],
                    'ubicacion' => $validated['ubicacion'],
                    'almacen' => $validated['almacen'],
                    'stock_minimo' => $validated['stock_minimo'] ?? 0,
                    'nivel_critico' => $validated['nivel_critico'] ?? 0,
                    'tipos_atributos' => !empty($tiposAtributos) ? $tiposAtributos : null,
                ]
            );
        }

        $claseIdParaItems = !empty($validated['inventario_variante_id'])
            ? (int) ($variante->clase_id ?? 0)
            : ($validated['clase_id'] ?? null);

        $variante->update([
            'nombre' => $validated['nombre'] ?? $validated['descripcion'] ?? $variante->nombre,
            'descripcion' => $validated['descripcion'] ?? $validated['nombre'] ?? $variante->descripcion,
            'referencia_proveedor' => $validated['referencia_proveedor'],
            'clase_id' => !empty($validated['inventario_variante_id'])
                ? $variante->clase_id
                : $validated['clase_id'],
            'ubicacion' => $validated['ubicacion'],
            'almacen' => $validated['almacen'],
            'stock_minimo' => $validated['stock_minimo'] ?? 0,
            'nivel_critico' => $validated['nivel_critico'] ?? 0,
            'tipos_atributos' => !empty($tiposAtributos) ? $tiposAtributos : $variante->tipos_atributos,
        ]);

        if (!empty($variantes)) {
            DB::transaction(function () use ($proyectoId, $validated, $variante, $variantes, $tiposAtributos, $claseIdParaItems) {
                foreach ($variantes as $index => $varianteRow) {
                    $atributos = $varianteRow['atributos'];
                    $stockActual = (int) $varianteRow['stock_actual'];
                    $codigoItem = $this->buildVariantCode($validated['codigo'], $atributos, $index);

                    Inventario::create([
                        'proyecto_id' => $proyectoId,
                        'inventario_variante_id' => $variante->id,
                        'codigo' => $codigoItem,
                        'nombre' => $validated['nombre'] ?? $validated['descripcion'] ?? null,
                        'descripcion' => $this->buildVariantDescription($validated['descripcion'] ?? $validated['nombre'] ?? '', $atributos),
                        'referencia_proveedor' => $validated['referencia_proveedor'],
                        'clase_id' => $claseIdParaItems,
                        'ubicacion' => $validated['ubicacion'],
                        'almacen' => $validated['almacen'],
                        'stock_actual' => $stockActual,
                        'stock_minimo' => $validated['stock_minimo'] ?? 0,
                        'nivel_critico' => $validated['nivel_critico'] ?? 0,
                        'atributos_variante' => $atributos,
                    ]);
                }
            });

            return redirect()->route('inventario.index')->with('success', 'Variantes creadas correctamente');
        }

        $codigoItem = $validated['codigo'];

        Inventario::create([
            'proyecto_id' => $proyectoId,
            'inventario_variante_id' => $variante->id,
            'codigo' => $codigoItem,
            'nombre' => $validated['nombre'] ?? $validated['descripcion'] ?? null,
            'descripcion' => $validated['descripcion'],
            'referencia_proveedor' => $validated['referencia_proveedor'],
            'clase_id' => $claseIdParaItems,
            'ubicacion' => $validated['ubicacion'],
            'almacen' => $validated['almacen'],
            'stock_actual' => (int) ($validated['stock_actual'] ?? 0),
            'stock_minimo' => $validated['stock_minimo'] ?? 0,
            'nivel_critico' => $validated['nivel_critico'] ?? 0,
            'atributos_variante' => !empty($atributosVariante) ? $atributosVariante : null,
        ]);

        return redirect()->route('inventario.index')->with('success', 'Producto con variante creado correctamente');
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

        $inventario->loadMissing(['variante.items']);

        $clases = \App\Models\Clase::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');

        $valoresIniciales = $this->normalizeVariantAttributes($inventario->atributos_variante ?? []);
        $tiposAtributos = $inventario->variante?->tipos_atributos;

        $varianteBase = (object) [
            'codigo' => $inventario->codigo,
            'nombre' => $inventario->nombre,
            'descripcion' => $inventario->descripcion,
            'referencia_proveedor' => $inventario->referencia_proveedor,
            'clase_id' => $inventario->clase_id,
            'almacen' => $inventario->almacen,
            'ubicacion' => $inventario->ubicacion,
            'stock_actual' => $inventario->stock_actual,
            'stock_minimo' => $inventario->stock_minimo,
            'nivel_critico' => $inventario->nivel_critico,
            'tipos_atributos' => $tiposAtributos ?? array_keys($valoresIniciales),
        ];

        // FIX: Inicializamos $variantesIniciales. Si el producto pertenece a una familia con variantes, las cargamos.
        // Si es un producto sencillo importado del Excel, se quedará como un array vacío y el compact() no fallará.
        $variantesIniciales = $inventario->variante ? $inventario->variante->items : [];

        return view('inventario.edit', compact('inventario', 'clases', 'varianteBase', 'valoresIniciales', 'variantesIniciales'));
    }

    public function update(Request $request, Inventario $inventario)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        if ((int) $inventario->proyecto_id !== $proyectoId) {
            abort(404);
        }

        // 1. AQUI SOLO REGLAS DE VALIDACIÓN 
        $validated = $request->validate([
            'codigo' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'referencia_proveedor' => 'nullable|string|max:255',
            'clase_id' => 'nullable|integer|exists:clases,id',
            'ubicacion' => 'nullable|string|max:255',
            'almacen' => 'nullable|string|max:255',
            'stock_actual' => 'required|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'nivel_critico' => 'nullable|integer|min:0',
            'tipos_atributos' => 'nullable|string',
            'atributos_variante' => 'nullable|array',
            'atributos_variante.*' => 'nullable|array',
            'atributos_variante.*.*' => 'nullable|string|max:255',
            'variantes' => 'nullable|array',
            'variantes.*.id' => 'nullable|integer|exists:inventario,id',
            'variantes.*.activo' => 'nullable|boolean',
            'variantes.*.stock_actual' => 'nullable|integer|min:0',
            'variantes.*.atributos' => 'nullable|array',
            'variantes.*.atributos.*' => 'nullable|string|max:255',
        ]);

        // Parsear tipos de atributos si viene como string JSON
        $tiposAtributos = $this->normalizeVariantTypes($validated['tipos_atributos'] ?? null);

        // Limpiar y preparar atributos de variante
        $atributosVariante = $this->normalizeVariantAttributes($validated['atributos_variante'] ?? null);
        $variantesRows = $this->normalizeVariantRows($validated['variantes'] ?? null);

        $claseIdParaItems = !empty($inventario->inventario_variante_id)
            ? (int) ($inventario->variante?->clase_id ?? 0)
            : ($validated['clase_id'] ?? null);

        // Actualizar la variante si existe
        if ($inventario->variante) {
            $inventario->variante->update([
                'codigo' => $validated['codigo'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'referencia_proveedor' => $validated['referencia_proveedor'],
                'clase_id' => $validated['clase_id'],
                'ubicacion' => $validated['ubicacion'],
                'almacen' => $validated['almacen'],
                'stock_minimo' => $validated['stock_minimo'] ?? 0,
                'nivel_critico' => $validated['nivel_critico'] ?? 0,
                'tipos_atributos' => !empty($tiposAtributos) ? $tiposAtributos : null,
            ]);
        }

        // 2. AQUI ES DONDE SE GUARDAN LOS DATOS REALES EN LA BASE DE DATOS
        $inventario->update([
            'codigo' => $validated['codigo'],
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'],
            'referencia_proveedor' => $validated['referencia_proveedor'],
            'clase_id' => $validated['clase_id'],
            'ubicacion' => $validated['ubicacion'],
            'almacen' => $validated['almacen'],
            'stock_actual' => $validated['stock_actual'],
            'stock_minimo' => $validated['stock_minimo'] ?? 0,
            'nivel_critico' => $validated['nivel_critico'] ?? 0,
            'atributos_variante' => !empty($atributosVariante) ? $atributosVariante : null, 
        ]);

        if ($inventario->variante && !empty($variantesRows)) {
            $existingItems = $inventario->variante->items()->get()->keyBy('id');
            $keptIds = [];

            DB::transaction(function () use ($proyectoId, $validated, $variantesRows, $existingItems, $inventario, $claseIdParaItems, &$keptIds) {
                foreach ($variantesRows as $index => $varianteRow) {
                    $atributos = $varianteRow['atributos'];
                    $stockActual = (int) $varianteRow['stock_actual'];
                    $itemId = isset($varianteRow['id']) ? (int) $varianteRow['id'] : null;
                    $codigoItem = $this->buildVariantCode($validated['codigo'], $atributos, $index, $itemId);
                    $descripcionItem = $this->buildVariantDescription($validated['descripcion'] ?? $validated['nombre'] ?? '', $atributos);

                    if ($itemId && $existingItems->has($itemId)) {
                        $existingItem = $existingItems->get($itemId);
                        $existingItem->update([
                            'codigo' => $codigoItem,
                            'nombre' => $validated['nombre'] ?? $validated['descripcion'] ?? null,
                            'descripcion' => $descripcionItem,
                            'referencia_proveedor' => $validated['referencia_proveedor'],
                            'clase_id' => $claseIdParaItems,
                            'ubicacion' => $validated['ubicacion'],
                            'almacen' => $validated['almacen'],
                            'stock_actual' => $stockActual,
                            'stock_minimo' => $validated['stock_minimo'] ?? 0,
                            'nivel_critico' => $validated['nivel_critico'] ?? 0,
                            'atributos_variante' => $atributos,
                        ]);

                        $keptIds[] = $existingItem->id;
                        continue;
                    }

                    $createdItem = Inventario::create([
                        'proyecto_id' => $proyectoId,
                        'inventario_variante_id' => $inventario->variante->id,
                        'codigo' => $codigoItem,
                        'nombre' => $validated['nombre'] ?? $validated['descripcion'] ?? null,
                        'descripcion' => $descripcionItem,
                        'referencia_proveedor' => $validated['referencia_proveedor'],
                        'clase_id' => $claseIdParaItems,
                        'ubicacion' => $validated['ubicacion'],
                        'almacen' => $validated['almacen'],
                        'stock_actual' => $stockActual,
                        'stock_minimo' => $validated['stock_minimo'] ?? 0,
                        'nivel_critico' => $validated['nivel_critico'] ?? 0,
                        'atributos_variante' => $atributos,
                    ]);

                    $keptIds[] = $createdItem->id;
                }

                $inventario->variante->items()
                    ->whereNotIn('id', $keptIds)
                    ->delete();
            });

            return redirect()->route('inventario.index')->with('success', 'Producto actualizado');
        }

        return redirect()->route('inventario.index')->with('success', 'Producto actualizado');
    }

    protected function normalizeVariantTypes(mixed $value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = explode(',', $value);
            }
        }

        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizeVariantAttributes(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $atributos = [];

        foreach ($value as $tipo => $valores) {
            $tipo = trim((string) $tipo);

            if ($tipo === '') {
                continue;
            }

            $listaValores = is_array($valores) ? $valores : [$valores];

            $listaValores = collect($listaValores)
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($listaValores)) {
                $atributos[$tipo] = $listaValores;
            }
        }

        return $atributos;
    }

    protected function normalizeVariantRows(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $variantes = [];

        foreach ($value as $row) {
            if (!is_array($row)) {
                continue;
            }

            $activo = filter_var($row['activo'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($activo === false) {
                continue;
            }

            $atributos = $this->normalizeVariantAttributes($row['atributos'] ?? []);
            $stockActual = isset($row['stock_actual']) ? (int) $row['stock_actual'] : null;
            $rowId = isset($row['id']) ? (int) $row['id'] : null;

            if (empty($atributos) || $stockActual === null) {
                continue;
            }

            $variantes[] = [
                'id' => $rowId,
                'activo' => true,
                'atributos' => $atributos,
                'stock_actual' => max(0, $stockActual),
            ];
        }

        return $variantes;
    }

    protected function buildVariantCode(string $baseCode, array $atributos, int $index, ?int $ignoreId = null): string
    {
        $segmentos = [];

        foreach ($atributos as $valores) {
            $valor = is_array($valores) ? implode('-', $valores) : (string) $valores;
            $segmento = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $valor) ?? '');

            if ($segmento !== '') {
                $segmentos[] = $segmento;
            }
        }

        $codigoBase = trim($baseCode);
        $codigo = $codigoBase . (!empty($segmentos) ? '-' . implode('-', $segmentos) : '');
        $candidato = $codigo;
        $contador = 2;

        while (Inventario::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->where('codigo', $candidato)
            ->exists()) {
            $candidato = $codigo . '-' . $contador;
            $contador++;
        }

        return $candidato;
    }

    protected function buildVariantDescription(string $baseDescription, array $atributos): string
    {
        $partes = [];

        foreach ($atributos as $tipo => $valores) {
            $valoresTexto = is_array($valores) ? implode(', ', $valores) : (string) $valores;
            $partes[] = trim($tipo . ' ' . $valoresTexto);
        }

        $etiqueta = trim(implode(' / ', array_filter($partes)));

        return $etiqueta !== '' ? trim($baseDescription . ' - ' . $etiqueta) : $baseDescription;
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