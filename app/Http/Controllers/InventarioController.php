<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Clase;
use App\Models\Inventario;
use App\Models\InventarioArticulo;
use App\Models\InventarioMovimiento;
use App\Models\InventarioStockAlmacen;
use App\Models\InventarioVariante;
use App\Models\Personal;
use App\Models\Proyecto;
use App\Models\SalidaStock;
use App\Services\InventarioStockService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    public function __construct(private readonly ?InventarioStockService $stockService = null)
    {
        $this->middleware('auth');
        $this->middleware('permission:inventario.view')->only(['index', 'show']);
        $this->middleware('permission:inventario.manage')->only([
            'create',
            'store',
            'createItem',
            'edit',
            'update',
            'destroy',
            'createSalida',
            'storeSalida',
            'createTraslado',
            'storeTraslado',
            'storeEntrada',
        ]);
    }

    public function index()
    {
        $request = request();
        $proyectoId = $this->resolveActiveProyectoId($request);
        $descripcion = trim((string) $request->query('descripcion', ''));
        $claseId = $request->query('clase_id');
        $almacenId = $request->query('almacen_id');

        $baseArticulosQuery = InventarioArticulo::query()
            ->where('proyecto_id', $proyectoId)
            ->where('activo', true)
            ->with(['clase', 'variantes.stockAlmacenes.almacen'])
            ->when($descripcion !== '', function ($query) use ($descripcion) {
                $query->where(function ($q) use ($descripcion) {
                    $q->where('codigo', 'like', '%' . $descripcion . '%')
                      ->orWhere('nombre', 'like', '%' . $descripcion . '%')
                      ->orWhere('descripcion', 'like', '%' . $descripcion . '%')
                      ->orWhere('referencia_proveedor', 'like', '%' . $descripcion . '%')
                      ->orWhere('nombre_proveedor', 'like', '%' . $descripcion . '%')
                      ->orWhereHas('variantes', function ($qv) use ($descripcion) {
                          $qv->where('codigo', 'like', '%' . $descripcion . '%')
                             ->orWhere('nombre', 'like', '%' . $descripcion . '%')
                             ->orWhere('descripcion', 'like', '%' . $descripcion . '%');
                      });
                });
            })
            ->when($claseId !== null && $claseId !== '', function ($query) use ($claseId) {
                $query->where('clase_id', $claseId);
            })
            ->when($almacenId !== null && $almacenId !== '', function ($query) use ($almacenId) {
                $query->whereHas('variantes.stockAlmacenes', function ($q) use ($almacenId) {
                    $q->where('almacen_id', $almacenId)->where('cantidad', '>', 0);
                });
            });

        $clases = Clase::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');

        $almacenesRegistrados = Almacen::query()
            ->where('proyecto_id', $proyectoId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        $articulosCollection = (clone $baseArticulosQuery)
            ->orderBy('codigo')
            ->get()
            ->map(function (InventarioArticulo $articulo) use ($almacenId) {
                $variantes = $articulo->variantes->where('activo', true);

                $stockTotalArticulo = 0;
                $hijos = collect();
                $hijosCriticos = 0;
                $hijosReposicion = 0;

                foreach ($variantes as $variante) {
                    $stocksFiltrados = ($variante->stockAlmacenes ?? collect())->when($almacenId, function ($c) use ($almacenId) {
                        return $c->where('almacen_id', (int) $almacenId);
                    });

                    $stockVariante = (int) $stocksFiltrados->sum('cantidad');
                    $stockTotalArticulo += $stockVariante;

                    $stockMin = (int) ($variante->stock_minimo ?? $articulo->stock_minimo_global ?? 0);
                    $criticoMin = (int) ($variante->nivel_critico ?? $articulo->nivel_critico_global ?? 0);

                    if ($stockVariante <= $criticoMin) {
                        $estadoVar = 'critico';
                        $estadoVarTexto = 'Crítico';
                        if ($stockVariante > 0) $hijosCriticos++;
                    } elseif ($stockVariante <= $stockMin) {
                        $estadoVar = 'bajo';
                        $estadoVarTexto = 'Reposición';
                        if ($stockVariante > 0) $hijosReposicion++;
                    } else {
                        $estadoVar = 'optimo';
                        $estadoVarTexto = 'Óptimo';
                    }

                    $nombresAlmacenes = $stocksFiltrados->where('cantidad', '>', 0)->map(function ($sa) {
                        return ($sa->almacen?->nombre ?? 'Almacén') . " ({$sa->cantidad})";
                    })->join(', ');

                    $atributosChips = collect($variante->atributos ?? [])
                        ->map(function ($valores,$tipo) {
                            $valStr = is_array($valores) ? implode(', ', $valores) : (string)$valores;
                            return trim($tipo . ': ' .$valStr);
                        })
                        ->filter()
                        ->values()
                        ->all();

                    $hijos->push((object) [
                        'id' => $variante->id,
                        'codigo' => $variante->sku ?? $variante->codigo,
                        'nombre' => $variante->nombre,
                        'descripcion' => $variante->descripcion ?: ($variante->nombre ?: $articulo->descripcion),
                        'atributos_chips' => $atributosChips,
                        'referencia_proveedor' => $variante->referencia_proveedor ?: $articulo->referencia_proveedor,
                        'clase_relacion' => (object) ['nombre' => $articulo->clase?->nombre ?? 'Sin clase'],
                        'almacen' => $nombresAlmacenes ?: 'Sin existencias',
                        'stock_actual' => $stockVariante,
                        'stock_minimo' => $stockMin,
                        'nivel_critico' => $criticoMin,
                        'estado' => $estadoVar,
                        'estado_texto' => $estadoVarTexto,
                    ]);
                }

                $stockMinPadre = (int) ($articulo->stock_minimo_global ?? 0);
                $criticoPadre = (int) ($articulo->nivel_critico_global ?? 0);

                if ($stockTotalArticulo <= $criticoPadre) {
                    $estadoGrupo = 'critico';
                    $estadoTextoGrupo = 'Crítico';
                } elseif ($stockTotalArticulo <= $stockMinPadre) {
                    $estadoGrupo = 'bajo';
                    $estadoTextoGrupo = 'Reposición';
                } else {
                    $estadoGrupo = 'optimo';
                    $estadoTextoGrupo = 'Óptimo';
                }

                $almacenesPadre = $articulo->variantes->flatMap(fn ($v) => $v->stockAlmacenes ?? collect())
                    ->where('cantidad', '>', 0)
                    ->map(fn ($sa) => $sa->almacen?->nombre)
                    ->filter()
                    ->unique()
                    ->join(', ');

                $primeraVarianteId = $variantes->first()?->id ?? $articulo->id;

                return (object) [
                    'padre' => $articulo,
                    'idPadre' => $primeraVarianteId,
                    'variantePadreId' => $articulo->id,
                    'codigoPadre' => $articulo->codigo,
                    'nombrePadre' => $articulo->nombre,
                    'descripcionPadre' => $articulo->descripcion,
                    'referenciaPadre' => $articulo->referencia_proveedor,
                    'clasePadre' => $articulo->clase?->nombre ?? 'Sin clase',
                    'almacenPadre' => $almacenesPadre ?: 'Sin existencias',
                    'stockGrupo' => $stockTotalArticulo,
                    'estadoGrupo' => $estadoGrupo,
                    'estadoTextoGrupo' => $estadoTextoGrupo,
                    'hijosReposicion' => $hijosReposicion,
                    'hijosCriticos' => $hijosCriticos,
                    'tieneHijos' => $hijos->count() > 1,
                    'hijos' => $hijos,
                ];
            });

        $perPage = 8;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $totalGrupos = $articulosCollection->count();
        $gruposPagina = $articulosCollection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $inventarios = new LengthAwarePaginator(
            $gruposPagina,
            $totalGrupos,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $totalProductos = InventarioArticulo::where('proyecto_id', $proyectoId)->where('activo', true)->count();
        $stockTotal = (int) InventarioStockAlmacen::query()
            ->whereHas('almacen', fn ($q) => $q->where('proyecto_id', $proyectoId))
            ->whereHas('variante', fn ($q) => $q->where('activo', true))
            ->sum('cantidad');

        $nivelCritico = InventarioVariante::query()
            ->whereHas('articulo', fn ($q) => $q->where('proyecto_id', $proyectoId))
            ->where('activo', true)
            ->withSum('stockAlmacenes as stock_calculado', 'cantidad')
            ->get()
            ->filter(fn ($v) => (int) ($v->stock_calculado ?? 0) <= (int) ($v->nivel_critico ?? 0))
            ->count();

        $stockBajo = InventarioVariante::query()
            ->whereHas('articulo', fn ($q) => $q->where('proyecto_id', $proyectoId))
            ->where('activo', true)
            ->withSum('stockAlmacenes as stock_calculado', 'cantidad')
            ->get()
            ->filter(fn ($v) => (int) ($v->stock_calculado ?? 0) <= (int) ($v->stock_minimo ?? 0))
            ->count();

        $movimientosRecientes = InventarioMovimiento::query()
            ->where('proyecto_id', $proyectoId)
            ->with(['lineas', 'almacenOrigen', 'almacenDestino'])
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->map(function (InventarioMovimiento $mov) {
                $uds = $mov->lineas->sum('cantidad');
                $tipoNormalizado = strtolower((string) $mov->tipo);
                $tipoEtiqueta = match ($tipoNormalizado) {
                    'entrada' => 'Entrada',
                    'salida' => 'Salida',
                    'traslado' => 'Transferencia',
                    default => ucfirst($tipoNormalizado),
                };
                $icono = match ($tipoNormalizado) {
                    'entrada' => 'fa-arrow-down',
                    'salida' => 'fa-arrow-up',
                    'traslado' => 'fa-exchange-alt',
                    default => 'fa-box',
                };
                $tono = match ($tipoNormalizado) {
                    'entrada' => 'positive',
                    'salida' => 'negative',
                    default => 'neutral',
                };

                return (object) [
                    'etiqueta' => $tipoEtiqueta,
                    'icono' => $icono,
                    'tono' => $tono,
                    'titulo' => sprintf('%s: %s (%s uds.)', $tipoEtiqueta, $mov->numero_documento ?: '#' . $mov->id, number_format($uds, 0, ',', '.')),
                    'subtitulo' => $mov->motivo ?: ($mov->observaciones ?: 'Sin referencia'),
                    'tiempo' => $mov->created_at ? $mov->created_at->diffForHumans() : 'Reciente',
                ];
            });

        $ocupacionAlmacenes = $almacenesRegistrados->map(function (Almacen $almacenItem) use ($stockTotal) {
            $baseStockAlm = InventarioStockAlmacen::query()
                ->where('almacen_id', $almacenItem->id)
                ->whereHas('variante', fn ($q) => $q->where('activo', true));

            $totalProds = (clone $baseStockAlm)->where('cantidad', '>', 0)->count();
            $stockSum = (int) (clone $baseStockAlm)->sum('cantidad');

            return (object) [
                'id' => $almacenItem->id,
                'nombre' => $almacenItem->nombre,
                'total_productos' => $totalProds,
                'stock_total' => $stockSum,
                'porcentaje' => $stockTotal > 0 && $stockSum > 0
                    ? max(5, (int) round(($stockSum * 100) / $stockTotal))
                    : 0,
            ];
        })->sortByDesc('stock_total')->values();

        $almacenes = $almacenesRegistrados->count();
        $almacen = $almacenId;

        return view('inventario.index', compact(
            'inventarios',
            'clases',
            'totalProductos',
            'nivelCritico',
            'stockBajo',
            'stockTotal',
            'almacenes',
            'almacenesRegistrados',
            'movimientosRecientes',
            'ocupacionAlmacenes',
            'descripcion',
            'claseId',
            'almacenId',
            'almacen'
        ));
    }

    public function create()
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        $almacenes = Almacen::query()
            ->where('proyecto_id', $proyectoId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        $variantes = InventarioVariante::query()
            ->where('proyecto_id', $proyectoId)
            ->where('activo', true)
            ->with(['articulo.clase'])
            ->orderBy('codigo')
            ->get();

        return view('inventario.create', compact('almacenes', 'variantes'));
    }

    public function createItem()
    {
        $proyectoId = $this->resolveActiveProyectoId(request());
        $varianteId = request()->query('variante_id');

        $ultimaAccion = InventarioVariante::query()
            ->where('proyecto_id', $proyectoId)
            ->orderByDesc('updated_at')
            ->first();

        $varianteBase = null;
        $valoresIniciales = [];
        $variantesIniciales = [];

        if (!empty($varianteId)) {
            $articulo = InventarioArticulo::query()
                ->where('proyecto_id', $proyectoId)
                ->with(['variantes' => fn ($q) => $q->where('activo', true)->with('stockAlmacenes.almacen')])
                ->find($varianteId);

            if (!$articulo) {
                $varEncontrada = InventarioVariante::query()
                    ->where('proyecto_id', $proyectoId)
                    ->with(['articulo.variantes' => fn ($q) => $q->where('activo', true)->with('stockAlmacenes.almacen')])
                    ->find($varianteId);
                $articulo = $varEncontrada?->articulo;
            }

            if ($articulo) {
                $primeraVar = $articulo->variantes->first();
                $primerAlmacen = $primeraVar?->stockAlmacenes?->first()?->almacen?->nombre
                    ?? $primeraVar?->almacen;

                $varianteBase = (object) [
                    'id' => $articulo->id,
                    'codigo' => $articulo->codigo,
                    'nombre' => $articulo->nombre,
                    'descripcion' => $articulo->descripcion,
                    'referencia_proveedor' => $articulo->referencia_proveedor,
                    'nombre_proveedor' => $articulo->nombre_proveedor,
                    'clase_id' => $articulo->clase_id,
                    'almacen' => $primerAlmacen,
                    'stock_actual' => (int) ($primeraVar?->stockAlmacenes?->sum('cantidad') ?? 0),
                    'stock_minimo' => $articulo->stock_minimo_global,
                    'nivel_critico' => $articulo->nivel_critico_global,
                    'tipos_atributos' => $articulo->tipos_atributos,
                ];

                foreach ($articulo->variantes as $varItem) {
                    foreach (($varItem->atributos ?? []) as $tipo => $valor) {
                        $valores = is_array($valor) ? $valor : [$valor];
                        foreach ($valores as $valorItem) {
                            $valorItem = trim((string) $valorItem);
                            if ($valorItem === '') continue;
                            $valoresIniciales[$tipo][] = $valorItem;
                        }
                    }
                }

                $variantesIniciales = $articulo->variantes->map(function ($v) {
                    $primerStockAlm = $v->stockAlmacenes->first();
                    return [
                        'id' => $v->id,
                        'sku' => $v->codigo,
                        'almacen_id' => $primerStockAlm?->almacen?->nombre ?? $v->almacen,
                        'atributos' => $v->atributos,
                        'stock_actual' => (int) $v->stockAlmacenes->sum('cantidad'),
                        'stock_minimo' => (int) ($v->stock_minimo ?? 10),
                        'nivel_critico' => (int) ($v->nivel_critico ?? 5),
                        'activo' => (bool) $v->activo,
                    ];
                })->values()->toArray();
            }

            foreach ($valoresIniciales as $tipo => $valores) {
                $valoresIniciales[$tipo] = collect($valores)->unique()->values()->all();
            }
        }

        $clases = Clase::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');

        $almacenes = Almacen::query()
            ->where('proyecto_id', $proyectoId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        return view('inventario.create-item', compact(
            'ultimaAccion',
            'clases',
            'varianteBase',
            'valoresIniciales',
            'variantesIniciales',
            'almacenes'
        ));
    }

    public function createSalida()
    {
        $proyectoId = $this->resolveActiveProyectoId(request());
        $personalId = (int) request()->query('personal_id', 0);

        $almacenes = Almacen::query()
            ->where('proyecto_id', $proyectoId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

    $variantesConStock = InventarioStockAlmacen::query()
            ->whereHas('almacen', fn ($q) => $q->where('proyecto_id', $proyectoId)->where('activo', true))
            ->whereHas('variante', fn ($q) => $q->where('activo', true))
            ->where('cantidad', '>', 0)
            ->with(['variante.articulo.clase', 'almacen'])
            ->get();

        $trabajadoresQuery = Personal::query()
            ->where('activo', true)
            ->orderBy('name')
            ->orderBy('apellido');

        if (auth()->user()?->role !== 'superadmin') {
            $proyectoIds = auth()->user()?->proyectos()->pluck('proyectos.id') ?? collect();
            $trabajadoresQuery->whereHas('proyectos', fn ($query) => $query->whereIn('proyectos.id', $proyectoIds));
        }

        $trabajadores = $trabajadoresQuery->get([
            'id', 'name', 'apellido', 'dni_nie', 'id_rrhh', 'puesto', 'camiseta', 'sudadera',
            'chaqueta', 'pantalon', 'calzado', 'casco', 'guantes', 'gafas', 'bolsa_fod', 'sin_tallas',
        ]);

        $solicitantePrefill = null;
        if ($personalId > 0) {
            $persona = Personal::find($personalId);
            if ($persona) {
                $solicitantePrefill = trim("{$persona->name} {$persona->apellido}");
            }
        }

        return view('inventario.salida', compact(
            'almacenes',
            'variantesConStock',
            'trabajadores',
            'solicitantePrefill',
            'personalId'
        ));
    }

    public function createTraslado()
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        $almacenes = Almacen::query()
            ->where('proyecto_id', $proyectoId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        $stocks = InventarioStockAlmacen::query()
            ->whereHas('almacen', fn ($q) => $q->where('proyecto_id', $proyectoId)->where('activo', true))
            ->where('cantidad', '>', 0)
            ->with(['variante.articulo', 'almacen'])
            ->get();

        $transaccionId = 'TRF-' . now()->format('Ymd-His');

        return view('inventario.traslado', compact('almacenes', 'stocks', 'transaccionId'));
    }

    public function storeSalida(Request $request)
    {
        return app(InventarioMovimientoController::class)->storeSalida($request);
    }

    public function storeEntrada(Request $request)
    {
        return app(InventarioMovimientoController::class)->storeEntrada($request);
    }

    public function storeTraslado(Request $request)
    {
        return app(InventarioMovimientoController::class)->storeTraslado($request);
    }

    /**
     * Soporta tanto un ID de InventarioMovimiento (Kardex nuevo) como un SalidaStock (Legacy)
     */
    public function showSalidaDocumento(Request $request, $id)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        // 1. Buscar primero en el nuevo motor de Kardex (InventarioMovimiento)
        $movimiento = \App\Models\InventarioMovimiento::with(['lineas.variante.articulo', 'personal', 'almacenOrigen'])
            ->where('proyecto_id', $proyectoId)
            ->where('tipo', 'SALIDA')
            ->find($id);

        if ($movimiento) {
            $nombreTrabajador = $movimiento->personal
                ? trim($movimiento->personal->name . ' ' . $movimiento->personal->apellido)
                : ($movimiento->observaciones ?: 'Sin asignar');

            $salida = (object) [
                'id' => $movimiento->id,
                'numero_salida' => $movimiento->numero_documento,
                'fecha' => $movimiento->created_at,
                'solicitante' => $nombreTrabajador,
            ];

            $documento = [
                'delegacion' => $movimiento->almacenOrigen?->nombre ?? 'Cádiz',
                'fecha' => $movimiento->created_at?->format('d/m/Y') ?? now()->format('d/m/Y'),
                'trabajador' => $nombreTrabajador,
                'ficha' => $movimiento->personal?->id_rrhh ?? ($movimiento->personal?->dni_nie ?? ''),
                'observaciones' => $movimiento->observaciones ?? '',
            ];

            $lineasDocumento = $movimiento->lineas->map(function ($linea) {
                $variante = $linea->variante;
                $articulo = $variante?->articulo;
                $sku = $variante?->sku ?? $variante?->codigo ?? '';

                $atributos = collect($variante?->atributos ?? [])
                    ->map(fn ($v, $k) => ucfirst($k) . ': ' . (is_array($v) ? implode(', ', $v) : $v))
                    ->implode(' · ');

                // Usamos el nombre del artículo padre para no duplicar los atributos que ya vienen en $variante->nombre
                $nombreBase = $articulo?->nombre ?: ($variante?->nombre ?: ($articulo?->descripcion ?: 'Artículo'));
                $textoArticulo = $atributos ? "{$nombreBase} ({$atributos})" : $nombreBase;

                if ($sku !== '') {
                    $textoArticulo = "[{$sku}] " . $textoArticulo;
                }

                return [
                    'articulo' => $textoArticulo,
                    'cantidad' => (string) $linea->cantidad,
                ];
            })->values()->all();    

            return view('inventario.salida-documento', [
                'salida' => $salida,
                'documento' => $documento,
                'lineasDocumento' => $lineasDocumento,
                'delegacionPrefill' => 'Cádiz',
            ]);
        }

        // 2. Fallback a SalidaStock legacy si era un registro antiguo
        $salida = SalidaStock::query()
            ->where('proyecto_id', $proyectoId)
            ->findOrFail($id);

        $documento = (array) ($salida->documento_meta ?? []);
        $lineasDocumento = $documento['lineas'] ?? [];
        $delegacionPrefill = trim((string) ($documento['delegacion'] ?? (Proyecto::query()->where('id', $proyectoId)->value('nombre') ?? '')));

        if (empty($lineasDocumento) && is_array($salida->items)) {
            $lineasDocumento = collect($salida->items)
                ->filter(fn ($item) => is_array($item))
                ->map(fn (array $item) => [
                    'articulo' => $item['descripcion'] ?? $item['codigo'] ?? 'Item',
                    'cantidad' => (string) ($item['cantidad'] ?? ''),
                ])
                ->values()
                ->all();
        }

        $lineasDocumento = array_slice($lineasDocumento, 0, 10);

        return view('inventario.salida-documento', compact('salida', 'documento', 'lineasDocumento', 'delegacionPrefill'));
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
            'almacen' => 'nullable|string|max:255',
            'stock_actual' => 'nullable|integer|min:0|max:999999|required_without:variantes',
            'stock_minimo' => 'nullable|integer|min:0',
            'nivel_critico' => 'nullable|integer|min:0',
            'tipos_atributos' => 'nullable|string',
            'atributos_variante' => 'nullable|array',
            'atributos_variante.*' => 'nullable|array',
            'atributos_variante.*.*' => 'nullable|string|max:255',
            'variantes' => 'nullable|array',
            'variantes.*.activo' => 'nullable|boolean',
            'variantes.*.stock_actual' => 'nullable|integer|min:0|max:999999',
            'variantes.*.atributos' => 'nullable|array',
            'variantes.*.atributos.*' => 'nullable|string|max:255',
            'inventario_variante_id' => 'nullable|integer',
            'variantes.*.sku' => 'nullable|string|max:255',
            'variantes.*.almacen_id' => 'nullable|string|max:255',
            'variantes.*.stock_minimo' => 'nullable|integer|min:0',
            'variantes.*.nivel_critico' => 'nullable|integer|min:0',
            'nombre_proveedor' => 'nullable|string|max:255'
        ]);

        $nombreBase = trim((string) ($validated['nombre'] ?? ''));
        $descripcionBase = trim((string) ($validated['descripcion'] ?? ''));

        if ($descripcionBase === '' && $nombreBase !== '') $descripcionBase = $nombreBase;
        if ($nombreBase === '' && $descripcionBase !== '') $nombreBase = $descripcionBase;

        $validated['nombre'] = $nombreBase !== '' ? $nombreBase : $validated['codigo'];
        $validated['descripcion'] = $descripcionBase !== '' ? $descripcionBase : $validated['nombre'];

        $tiposAtributos = $this->normalizeVariantTypes($validated['tipos_atributos'] ?? null);
        $atributosVariante = $this->normalizeVariantAttributes($validated['atributos_variante'] ?? null);
        $variantesRows = $this->normalizeVariantRows($validated['variantes'] ?? null, true);

        // Resolver almacén físico donde depositar el stock inicial
        $almacenModel = null;
        if (!empty($validated['almacen'])) {
            $almacenModel = Almacen::query()
                ->where('proyecto_id', $proyectoId)
                ->where(function ($q) use ($validated) {
                    $q->where('nombre', $validated['almacen'])
                      ->orWhere('id', is_numeric($validated['almacen']) ? (int) $validated['almacen'] : 0);
                })
                ->first();
        }
        if (!$almacenModel) {
            $almacenModel = Almacen::query()->where('proyecto_id', $proyectoId)->orderBy('id')->first();
        }

        DB::transaction(function () use ($proyectoId, $validated, $tiposAtributos, $atributosVariante, $variantesRows, $almacenModel) {
            // 1. Crear o actualizar el Artículo Maestro
            $articulo = InventarioArticulo::updateOrCreate(
                ['proyecto_id' => $proyectoId, 'codigo' => $validated['codigo']],
                [
                    'nombre' => $validated['nombre'],
                    'descripcion' => $validated['descripcion'],
                    'referencia_proveedor' => $validated['referencia_proveedor'] ?? null,
                    'nombre_proveedor' => $validated['nombre_proveedor'] ?? null,
                    'clase_id' => $validated['clase_id'] ?? null,
                    'tiene_variantes' => !empty($variantesRows),
                    'tipos_atributos' => !empty($tiposAtributos) ? $tiposAtributos : null,
                    'stock_minimo_global' => (int) ($validated['stock_minimo'] ?? 0),
                    'nivel_critico_global' => (int) ($validated['nivel_critico'] ?? 0),
                    'activo' => true,
                ]
            );

            // 2. Si tiene múltiples variantes en la rejilla
            if (!empty($variantesRows)) {
                $existingVariantes = $articulo->variantes()->with('stockAlmacenes')->get()->keyBy('id');
                $processedVariantIds = [];
                $activeNewVariants = collect();

                foreach ($variantesRows as $index => $varianteRow) {
                    $atributos = $varianteRow['atributos'];
                    $activo = (bool) ($varianteRow['activo'] ?? true);
                    $rowId = isset($varianteRow['id']) ? (int) $varianteRow['id'] : null;

                    // Reutilizar ID solo si la variante conserva exactamente los mismos atributos dimensionales
                    $existingVar = null;
                    if ($rowId && $existingVariantes->has($rowId)) {
                        $candidate = $existingVariantes->get($rowId);
                        if ($this->sameVariantAttributes($candidate->atributos ?? [], $atributos)) {
                            $existingVar = $candidate;
                        }
                    }
                    if (!$existingVar) {
                        $existingVar = $existingVariantes->first(
                            fn ($v) => $this->sameVariantAttributes($v->atributos ?? [], $atributos)
                        );
                    }

                    if (!$activo) {
                        if ($existingVar) {
                            $existingVar->update(['activo' => false]);
                            $processedVariantIds[] = $existingVar->id;
                        }
                        continue;
                    }

                    $stockActual = isset($varianteRow['stock_actual']) ? (int) $varianteRow['stock_actual'] : 0;
                    $minFila = isset($varianteRow['stock_minimo']) ? (int) $varianteRow['stock_minimo'] : (int) ($validated['stock_minimo'] ?? 10);
                    $critFila = isset($varianteRow['nivel_critico']) ? (int) $varianteRow['nivel_critico'] : (int) ($validated['nivel_critico'] ?? 5);

                    $codigoItem = !empty($varianteRow['sku'])
                        ? $varianteRow['sku']
                        : $this->buildVariantCode($validated['codigo'], $atributos, $index, $existingVar?->id);

                    $descripcionItem = $this->buildVariantDescription($validated['descripcion'], $atributos);

                    $almacenFila = $almacenModel;
                    if (!empty($varianteRow['almacen_id'])) {
                        $encontradoAlm = Almacen::where('proyecto_id', $proyectoId)
                            ->where(fn ($q) => $q->where('nombre', $varianteRow['almacen_id'])->orWhere('id', (int) $varianteRow['almacen_id']))
                            ->first();
                        if ($encontradoAlm) {
                            $almacenFila = $encontradoAlm;
                        }
                    }

                    if ($existingVar) {
                        $existingVar->update([
                            'codigo' => $codigoItem,
                            'nombre' => $descripcionItem,
                            'descripcion' => $descripcionItem,
                            'referencia_proveedor' => $validated['referencia_proveedor'] ?? null,
                            'nombre_proveedor' => $validated['nombre_proveedor'] ?? null,
                            'clase_id' => $validated['clase_id'] ?? $articulo->clase_id,
                            'almacen' => $almacenFila?->nombre ?? $existingVar->almacen,
                            'stock_minimo' => $minFila,
                            'nivel_critico' => $critFila,
                            'tipos_atributos' => !empty($tiposAtributos) ? $tiposAtributos : null,
                            'atributos' => $atributos,
                            'activo' => true,
                        ]);
                        $targetVar = $existingVar;
                    } else {
                        $targetVar = InventarioVariante::create([
                            'articulo_id' => $articulo->id,
                            'proyecto_id' => $proyectoId,
                            'codigo' => $codigoItem,
                            'nombre' => $descripcionItem,
                            'descripcion' => $descripcionItem,
                            'referencia_proveedor' => $validated['referencia_proveedor'] ?? null,
                            'nombre_proveedor' => $validated['nombre_proveedor'] ?? null,
                            'clase_id' => $validated['clase_id'] ?? $articulo->clase_id,
                            'almacen' => $almacenFila?->nombre ?? null,
                            'stock_minimo' => $minFila,
                            'nivel_critico' => $critFila,
                            'tipos_atributos' => !empty($tiposAtributos) ? $tiposAtributos : null,
                            'atributos' => $atributos,
                            'activo' => true,
                        ]);
                    }

                    if ($almacenFila) {
                        InventarioStockAlmacen::updateOrCreate(
                            ['variante_id' => $targetVar->id, 'almacen_id' => $almacenFila->id],
                            ['cantidad' => $stockActual]
                        );
                    }

                    $processedVariantIds[] = $targetVar->id;
                    $activeNewVariants->push($targetVar);
                }

                // Sincronizar y depurar variantes antiguas/desfasadas que ya no pertenecen a la matriz
                $this->depurarVariantesDesfasadas($existingVariantes, $processedVariantIds, $activeNewVariants);
            } else {
                // 3. Producto simple sin matriz de variantes (1 Artículo = 1 Variante base)
                $stockInicial = (int) ($validated['stock_actual'] ?? 0);

                $varModel = InventarioVariante::updateOrCreate(
                    [
                        'proyecto_id' => $proyectoId,
                        'codigo' => $validated['codigo'],
                    ],
                    [
                        'articulo_id' => $articulo->id,
                        'nombre' => $validated['nombre'],
                        'descripcion' => $validated['descripcion'],
                        'referencia_proveedor' => $validated['referencia_proveedor'] ?? null,
                        'nombre_proveedor' => $validated['nombre_proveedor'] ?? null,
                        'clase_id' => $validated['clase_id'] ?? null,
                        'almacen' => $almacenModel?->nombre ?? ($validated['almacen'] ?? null),
                        'stock_minimo' => (int) ($validated['stock_minimo'] ?? 0),
                        'nivel_critico' => (int) ($validated['nivel_critico'] ?? 0),
                        'tipos_atributos' => !empty($tiposAtributos) ? $tiposAtributos : null,
                        'atributos' => !empty($atributosVariante) ? $atributosVariante : null,
                        'activo' => true,
                    ]
                );

                if ($almacenModel) {
                    InventarioStockAlmacen::updateOrCreate(
                        ['variante_id' => $varModel->id, 'almacen_id' => $almacenModel->id],
                        ['cantidad' => $stockInicial]
                    );
                }
            }
        });

        return redirect()->route('inventario.index')->with('success', 'Producto y existencias guardados correctamente en el catálogo.');
    }

    /**
     * Resuelve el ítem tanto si $id es de InventarioVariante, InventarioArticulo o Inventario legacy
     */
    public function show($id)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        $variante = InventarioVariante::with(['articulo.clase', 'stockAlmacenes.almacen'])
            ->where('proyecto_id', $proyectoId)
            ->find($id);

        if (!$variante) {
            $articulo = InventarioArticulo::with(['clase', 'variantes.stockAlmacenes.almacen'])
                ->where('proyecto_id', $proyectoId)
                ->find($id);
            $variante = $articulo?->variantes->first();
        }

        if ($variante) {
            $stockTotal = (int) $variante->stockAlmacenes->sum('cantidad');
            $almacenesStr = $variante->stockAlmacenes
                ->where('cantidad', '>', 0)
                ->map(fn ($sa) => $sa->almacen?->nombre . ' (' . $sa->cantidad . ')')
                ->join(', ');

            $inventario = (object) [
                'id' => $variante->id,
                'proyecto_id' => $variante->proyecto_id,
                'codigo' => $variante->codigo,
                'nombre' => $variante->nombre ?: $variante->articulo?->nombre,
                'descripcion' => $variante->descripcion ?: $variante->articulo?->descripcion,
                'referencia_proveedor' => $variante->referencia_proveedor ?: $variante->articulo?->referencia_proveedor,
                'nombre_proveedor' => $variante->nombre_proveedor ?: $variante->articulo?->nombre_proveedor,
                'clase_id' => $variante->clase_id ?: $variante->articulo?->clase_id,
                'claseRelacion' => $variante->articulo?->clase ?? $variante->claseRelacion,
                'almacen' => $almacenesStr ?: ($variante->almacen ?: 'Sin existencias'),
                'stock_actual' => $stockTotal,
                'stock_minimo' => $variante->stock_minimo ?? $variante->articulo?->stock_minimo_global ?? 0,
                'nivel_critico' => $variante->nivel_critico ?? $variante->articulo?->nivel_critico_global ?? 0,
                'atributos_variante' => $variante->atributos,
                'created_at' => $variante->created_at,
                'updated_at' => $variante->updated_at,
            ];

            return view('inventario.show', compact('inventario'));
        }

        $inventario = Inventario::where('proyecto_id', $proyectoId)->findOrFail($id);
        return view('inventario.show', compact('inventario'));
    }

    public function edit($id)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        $variante = InventarioVariante::with([
            'articulo.variantes' => fn ($q) => $q->where('activo', true)->with('stockAlmacenes.almacen')
        ])->where('proyecto_id', $proyectoId)->find($id);

        $articulo = $variante?->articulo;

        if (!$articulo) {
            $articulo = InventarioArticulo::with([
                'variantes' => fn ($q) => $q->where('activo', true)->with('stockAlmacenes.almacen')
            ])->where('proyecto_id', $proyectoId)->find($id);
            $variante = $articulo?->variantes->first();
        }

        $clases = Clase::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->pluck('nombre', 'id');

        $almacenes = Almacen::query()
            ->where('proyecto_id', $proyectoId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        if ($articulo && $variante) {
            $valoresIniciales = [];
            foreach ($articulo->variantes as $varItem) {
                foreach (($varItem->atributos ?? []) as $tipo => $valor) {
                    $valores = is_array($valor) ? $valor : [$valor];
                    foreach ($valores as $valorItem) {
                        $valorItem = trim((string) $valorItem);
                        if ($valorItem !== '') {
                            $valoresIniciales[$tipo][] = $valorItem;
                        }
                    }
                }
            }
            foreach ($valoresIniciales as $tipo => $valores) {
                $valoresIniciales[$tipo] = collect($valores)->unique()->values()->all();
            }

            $primerAlmacen = $variante->stockAlmacenes->first()?->almacen?->nombre
                ?? $variante->almacen
                ?? $almacenes->first()?->nombre;

            $stockVariante = (int) $variante->stockAlmacenes->sum('cantidad');

            $varianteBase = (object) [
                'id' => $articulo->id,
                'codigo' => $articulo->codigo,
                'nombre' => $articulo->nombre,
                'descripcion' => $articulo->descripcion,
                'referencia_proveedor' => $articulo->referencia_proveedor,
                'nombre_proveedor' => $articulo->nombre_proveedor,
                'clase_id' => $articulo->clase_id,
                'almacen' => $primerAlmacen,
                'stock_actual' => $stockVariante,
                'stock_minimo' => $articulo->stock_minimo_global ?? $variante->stock_minimo ?? 0,
                'nivel_critico' => $articulo->nivel_critico_global ?? $variante->nivel_critico ?? 0,
                'tipos_atributos' => $articulo->tipos_atributos ?? array_keys($valoresIniciales),
            ];

            $variantesIniciales = $articulo->variantes->map(function ($v) {
                $primerStockAlm = $v->stockAlmacenes->first();
                return [
                    'id' => $v->id,
                    'sku' => $v->codigo,
                    'almacen_id' => $primerStockAlm?->almacen?->nombre ?? $v->almacen,
                    'atributos' => $v->atributos,
                    'stock_actual' => (int) $v->stockAlmacenes->sum('cantidad'),
                    'stock_minimo' => (int) ($v->stock_minimo ?? 10),
                    'nivel_critico' => (int) ($v->nivel_critico ?? 5),
                    'activo' => (bool) $v->activo,
                ];
            })->values()->toArray();

            $inventario = $variante;
            $inventario->stock_actual = $stockVariante;

            return view('inventario.edit', compact('inventario', 'clases', 'varianteBase', 'valoresIniciales', 'variantesIniciales', 'almacenes'));
        }

        // Fallback a tabla legacy si no estaba en catálogo
        $inventario = Inventario::where('proyecto_id', $proyectoId)->findOrFail($id);
        $valoresIniciales = $this->normalizeVariantAttributes($inventario->atributos_variante ?? []);
        $varianteBase = (object) [
            'codigo' => $inventario->codigo,
            'nombre' => $inventario->nombre,
            'descripcion' => $inventario->descripcion,
            'referencia_proveedor' => $inventario->referencia_proveedor,
            'clase_id' => $inventario->clase_id,
            'almacen' => $inventario->almacen,
            'stock_actual' => $inventario->stock_actual,
            'stock_minimo' => $inventario->stock_minimo,
            'nivel_critico' => $inventario->nivel_critico,
            'tipos_atributos' => array_keys($valoresIniciales),
        ];
        $variantesIniciales = [];

        return view('inventario.edit', compact('inventario', 'clases', 'varianteBase', 'valoresIniciales', 'variantesIniciales', 'almacenes'));
    }

    public function update(Request $request, $id)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $variante = InventarioVariante::with('articulo')->where('proyecto_id', $proyectoId)->find($id);
        $articulo = $variante?->articulo ?? InventarioArticulo::where('proyecto_id', $proyectoId)->find($id);

        if (!$articulo) {
            abort(404);
        }

        $validated = $request->validate([
            'codigo' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'referencia_proveedor' => 'nullable|string|max:255',
            'clase_id' => 'nullable|integer|exists:clases,id',
            'almacen' => 'nullable|string|max:255',
            'stock_actual' => 'nullable|integer|min:0|required_without:variantes',
            'stock_minimo' => 'nullable|integer|min:0',
            'nivel_critico' => 'nullable|integer|min:0',
            'tipos_atributos' => 'nullable|string',
            'atributos_variante' => 'nullable|array',
            'atributos_variante.*' => 'nullable|array',
            'atributos_variante.*.*' => 'nullable|string|max:255',
            'variantes' => 'nullable|array',
            'variantes.*.id' => 'nullable|integer',
            'variantes.*.activo' => 'nullable|boolean',
            'variantes.*.stock_actual' => 'nullable|integer|min:0',
            'variantes.*.atributos' => 'nullable|array',
            'variantes.*.atributos.*' => 'nullable|string|max:255',
            'variantes.*.sku' => 'nullable|string|max:255',
            'variantes.*.almacen_id' => 'nullable|string|max:255',
            'variantes.*.stock_minimo' => 'nullable|integer|min:0',
            'variantes.*.nivel_critico' => 'nullable|integer|min:0',
            'nombre_proveedor' => 'nullable|string|max:255'
        ]);

        $validated['descripcion'] = trim((string) ($validated['descripcion'] ?? '')) !== ''
            ? $validated['descripcion']
            : $validated['nombre'];

        $tiposAtributos = $this->normalizeVariantTypes($validated['tipos_atributos'] ?? null);
        $atributosVariante = $this->normalizeVariantAttributes($validated['atributos_variante'] ?? null);
        $variantesRows = $this->normalizeVariantRows($validated['variantes'] ?? null, false);

        $almacenModel = null;
        if (!empty($validated['almacen'])) {
            $almacenModel = Almacen::query()
                ->where('proyecto_id', $proyectoId)
                ->where(function ($q) use ($validated) {
                    $q->where('nombre', $validated['almacen'])
                      ->orWhere('id', is_numeric($validated['almacen']) ? (int) $validated['almacen'] : 0);
                })
                ->first();
        }
        if (!$almacenModel) {
            $almacenModel = Almacen::query()->where('proyecto_id', $proyectoId)->orderBy('id')->first();
        }

        DB::transaction(function () use ($proyectoId, $articulo, $variante, $validated, $tiposAtributos, $atributosVariante, $variantesRows, $almacenModel) {
            $articulo->update([
                'codigo' => $validated['codigo'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'referencia_proveedor' => $validated['referencia_proveedor'] ?? null,
                'nombre_proveedor' => $validated['nombre_proveedor'] ?? null,
                'clase_id' => $validated['clase_id'] ?? $articulo->clase_id,
                'tiene_variantes' => !empty($variantesRows),
                'tipos_atributos' => !empty($tiposAtributos) ? $tiposAtributos : null,
                'stock_minimo_global' => (int) ($validated['stock_minimo'] ?? 0),
                'nivel_critico_global' => (int) ($validated['nivel_critico'] ?? 0),
            ]);

            if (!empty($variantesRows)) {
                $existingVariantes = $articulo->variantes()->with('stockAlmacenes')->get()->keyBy('id');
                $processedVariantIds = [];
                $activeNewVariants = collect();

                foreach ($variantesRows as $index => $varianteRow) {
                    $atributos = $varianteRow['atributos'];
                    $activo = (bool) ($varianteRow['activo'] ?? true);
                    $rowId = isset($varianteRow['id']) ? (int) $varianteRow['id'] : null;

                    // Reutilizar ID solo si la variante conserva exactamente los mismos atributos dimensionales
                    $existingVar = null;
                    if ($rowId && $existingVariantes->has($rowId)) {
                        $candidate = $existingVariantes->get($rowId);
                        if ($this->sameVariantAttributes($candidate->atributos ?? [], $atributos)) {
                            $existingVar = $candidate;
                        }
                    }
                    if (!$existingVar) {
                        $existingVar = $existingVariantes->first(
                            fn ($v) => $this->sameVariantAttributes($v->atributos ?? [], $atributos)
                        );
                    }

                    if (!$activo) {
                        if ($existingVar) {
                            $existingVar->update(['activo' => false]);
                            $processedVariantIds[] = $existingVar->id;
                        }
                        continue;
                    }

                    $stockActual = isset($varianteRow['stock_actual']) ? (int) $varianteRow['stock_actual'] : 0;
                    $minFila = isset($varianteRow['stock_minimo']) ? (int) $varianteRow['stock_minimo'] : (int) ($validated['stock_minimo'] ?? 10);
                    $critFila = isset($varianteRow['nivel_critico']) ? (int) $varianteRow['nivel_critico'] : (int) ($validated['nivel_critico'] ?? 5);

                    $codigoItem = !empty($varianteRow['sku'])
                        ? $varianteRow['sku']
                        : $this->buildVariantCode($validated['codigo'], $atributos, $index, $existingVar?->id);

                    $descripcionItem = $this->buildVariantDescription($validated['descripcion'], $atributos);

                    $almacenFila = $almacenModel;
                    if (!empty($varianteRow['almacen_id'])) {
                        $encontradoAlm = Almacen::where('proyecto_id', $proyectoId)
                            ->where(fn ($q) => $q->where('nombre', $varianteRow['almacen_id'])->orWhere('id', (int) $varianteRow['almacen_id']))
                            ->first();
                        if ($encontradoAlm) {
                            $almacenFila = $encontradoAlm;
                        }
                    }

                    if ($existingVar) {
                        $existingVar->update([
                            'codigo' => $codigoItem,
                            'nombre' => $descripcionItem,
                            'descripcion' => $descripcionItem,
                            'referencia_proveedor' => $validated['referencia_proveedor'] ?? null,
                            'nombre_proveedor' => $validated['nombre_proveedor'] ?? null,
                            'clase_id' => $validated['clase_id'] ?? $articulo->clase_id,
                            'almacen' => $almacenFila?->nombre ?? $existingVar->almacen,
                            'stock_minimo' => $minFila,
                            'nivel_critico' => $critFila,
                            'tipos_atributos' => !empty($tiposAtributos) ? $tiposAtributos : null,
                            'atributos' => $atributos,
                            'activo' => true,
                        ]);
                        $targetVar = $existingVar;
                    } else {
                        $targetVar = InventarioVariante::create([
                            'articulo_id' => $articulo->id,
                            'proyecto_id' => $proyectoId,
                            'codigo' => $codigoItem,
                            'nombre' => $descripcionItem,
                            'descripcion' => $descripcionItem,
                            'referencia_proveedor' => $validated['referencia_proveedor'] ?? null,
                            'nombre_proveedor' => $validated['nombre_proveedor'] ?? null,
                            'clase_id' => $validated['clase_id'] ?? $articulo->clase_id,
                            'almacen' => $almacenFila?->nombre ?? null,
                            'stock_minimo' => $minFila,
                            'nivel_critico' => $critFila,
                            'tipos_atributos' => !empty($tiposAtributos) ? $tiposAtributos : null,
                            'atributos' => $atributos,
                            'activo' => true,
                        ]);
                    }

                    if ($almacenFila) {
                        InventarioStockAlmacen::updateOrCreate(
                            ['variante_id' => $targetVar->id, 'almacen_id' => $almacenFila->id],
                            ['cantidad' => $stockActual]
                        );
                    }

                    $processedVariantIds[] = $targetVar->id;
                    $activeNewVariants->push($targetVar);
                }

                // Depuración y archivo de variantes de la estructura anterior
                $this->depurarVariantesDesfasadas($existingVariantes, $processedVariantIds, $activeNewVariants);
            }
        });

        return redirect()->route('inventario.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Request $request, $id)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);
        $scope = $request->input('scope', 'auto'); // 'padre', 'variante' o 'auto'

        // 1. Si se pide borrar una variante hija específica (desde el modal)
        if ($scope === 'variante') {
            $variante = InventarioVariante::where('proyecto_id', $proyectoId)->findOrFail($id);
            $stockVariante = (int) InventarioStockAlmacen::where('variante_id', $variante->id)->sum('cantidad');

            if ($stockVariante > 0) {
                return back()->with('error', "No se puede eliminar la variante '{$variante->codigo}' porque aún tiene {$stockVariante} uds. en stock. Registra antes una salida.");
            }

            DB::transaction(function () use ($variante) {
                $variante->update(['activo' => false]);

                // Si era la última variante activa del artículo padre, damos de baja también al padre
                if ($variante->articulo_id) {
                    $quedanActivas = InventarioVariante::where('articulo_id', $variante->articulo_id)
                        ->where('activo', true)
                        ->exists();

                    if (!$quedanActivas) {
                        InventarioArticulo::where('id', $variante->articulo_id)->update(['activo' => false]);
                    }
                }
            });

            return redirect()->route('inventario.index')->with('success', "Variante '{$variante->codigo}' eliminada correctamente.");
        }

        // 2. Si se pide borrar el Artículo Padre completo (con todas sus variantes)
        $articulo = InventarioArticulo::where('proyecto_id', $proyectoId)->find($id);
        if (!$articulo) {
            $variante = InventarioVariante::where('proyecto_id', $proyectoId)->find($id);
            $articulo = $variante?->articulo;
        }

        if ($articulo) {
            $stockTotal = (int) InventarioStockAlmacen::whereHas('variante', fn ($q) => $q->where('articulo_id', $articulo->id)->where('activo', true))
                ->sum('cantidad');

            if ($stockTotal > 0) {
                return back()->with('error', "No se puede eliminar el producto '{$articulo->nombre}' porque sus variantes suman {$stockTotal} uds. en almacén.");
            }

            DB::transaction(function () use ($articulo) {
                $articulo->variantes()->update(['activo' => false]);
                $articulo->update(['activo' => false]);
            });

            return redirect()->route('inventario.index')->with('success', "Producto '{$articulo->nombre}' y todas sus variantes han sido eliminados.");
        }

        $inventario = Inventario::where('proyecto_id', $proyectoId)->findOrFail($id);
        $inventario->delete();

        return redirect()->route('inventario.index')->with('success', 'Producto eliminado.');
    }

    protected function normalizeVariantTypes(mixed $value): array
    {
        if (empty($value)) return [];

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }

        if (!is_array($value)) return [];

        return collect($value)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizeVariantAttributes(mixed $value): array
    {
        if (!is_array($value)) return [];

        $atributos = [];
        foreach ($value as $tipo => $valores) {
            $tipo = trim((string) $tipo);
            if ($tipo === '') continue;

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

    protected function normalizeVariantRows(mixed $value, bool $onlyActive = true): array
    {
        if (!is_array($value)) return [];

        $variantes = [];
        foreach ($value as $row) {
            if (!is_array($row)) continue;

            $activo = filter_var($row['activo'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $activo = $activo !== false;

            if ($onlyActive && !$activo) continue;

            $atributos = $this->normalizeVariantAttributes($row['atributos'] ?? []);
            if (empty($atributos)) continue;

            $variantes[] = [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'sku' => isset($row['sku']) && trim((string) $row['sku']) !== '' ? trim((string) $row['sku']) : null,
                'almacen_id' => isset($row['almacen_id']) && trim((string) $row['almacen_id']) !== '' ? trim((string) $row['almacen_id']) : null,
                'activo' => $activo,
                'atributos' => $atributos,
                'stock_actual' => max(0, isset($row['stock_actual']) ? (int) $row['stock_actual'] : 0),
                'stock_minimo' => max(0, isset($row['stock_minimo']) ? (int) $row['stock_minimo'] : 0),
                'nivel_critico' => max(0, isset($row['nivel_critico']) ? (int) $row['nivel_critico'] : 0),
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
            if ($segmento !== '') $segmentos[] = $segmento;
        }

        $codigoBase = trim($baseCode);
        $codigo = $codigoBase . (!empty($segmentos) ? '-' . implode('-', $segmentos) : '');
        $candidato = $codigo;
        $contador = 2;

        while (InventarioVariante::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->where('proyecto_id', $this->resolveActiveProyectoId(request()))
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

    /**
     * Normaliza un valor de atributo (sea string o array) a texto plano sin acentos en minúsculas.
     */
    protected function cleanAttrString(mixed $val): string
    {
        $str = is_array($val) ? implode(',', array_map('trim', $val)) : trim((string) $val);
        $str = mb_strtolower($str, 'UTF-8');
        return strtr($str, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        ]);
    }

    /**
     * Compara dos arrays de atributos de variante sin importar el orden de las claves, tildes o formato array/string.
     */
    protected function sameVariantAttributes(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }

        $norm = function (array $attrs): array {
            $res = [];
            foreach ($attrs as $k => $v) {
                $key = $this->cleanAttrString($k);
                $res[$key] = $this->cleanAttrString($v);
            }
            ksort($res);
            return $res;
        };

        return $norm($a) === $norm($b);
    }

    /**
     * Busca si una variante desfasada (ej. Talla: 11) tiene una heredera directa
     * en la nueva matriz (ej. Talla: 11 + Ignífugo: No, o Talla: 11 + Color: Verde).
     */
    protected function encontrarVarianteHeredera(InventarioVariante $oldVar, \Illuminate\Support\Collection $newVariants): ?InventarioVariante
    {
        $oldAttrs = $oldVar->atributos ?? [];
        $valoresNeutros = ['no', '00', '0', 'false', 'estandar', 'standard', 'std', 'unica', 'unico', 'uni', 'n/a', 'na', 'normal', 'sin especificar'];

        // Si la variante antigua era el producto base sin atributos (0 dimensiones),
        // su heredera es la variante con valores neutros o la primera de la nueva matriz
        if (empty($oldAttrs)) {
            return $newVariants->first(function (InventarioVariante $cand) use ($valoresNeutros) {
                foreach (($cand->atributos ?? []) as $v) {
                    if (in_array($this->cleanAttrString($v), $valoresNeutros, true)) {
                        return true;
                    }
                }
                return false;
            }) ?? $newVariants->first();
        }

        // 1. Filtrar las nuevas variantes que contengan TODOS los atributos que ya tenía la variante antigua
        $candidatas = $newVariants->filter(function (InventarioVariante $newVar) use ($oldAttrs) {
            $newAttrs = $newVar->atributos ?? [];
            foreach ($oldAttrs as $tipoOld => $valOld) {
                $cleanKeyOld = $this->cleanAttrString($tipoOld);
                $cleanValOld = $this->cleanAttrString($valOld);

                $matched = false;
                foreach ($newAttrs as $tipoNew => $valNew) {
                    if ($cleanKeyOld === $this->cleanAttrString($tipoNew)) {
                        if ($cleanValOld === $this->cleanAttrString($valNew)) {
                            $matched = true;
                        }
                        break;
                    }
                }
                if (!$matched) {
                    return false;
                }
            }
            return true;
        });

        if ($candidatas->isEmpty()) {
            return null;
        }

        // 2. Priorizar la candidata cuyos atributos NUEVOS tengan un valor neutro ("No", "00", "Estándar", etc.)
        $oldKeysClean = array_map(fn ($k) => $this->cleanAttrString($k), array_keys($oldAttrs));

        $herederaPorDefecto = $candidatas->first(function (InventarioVariante $cand) use ($oldKeysClean, $valoresNeutros) {
            foreach (($cand->atributos ?? []) as $k => $v) {
                if (!in_array($this->cleanAttrString($k), $oldKeysClean, true)) {
                    if (in_array($this->cleanAttrString($v), $valoresNeutros, true)) {
                        return true;
                    }
                }
            }
            return false;
        });

        // 3. Si no hay valor neutro (ej. se añadió Color: Verde, Rojo, Azul), devuelve la primera candidata de esa misma Talla
        return $herederaPorDefecto ?? $candidatas->first();
    }

    /**
     * Procesa las variantes que quedaron fuera de la nueva matriz dimensional:
     * - Si ninguna de las nuevas variantes hijas de su rama recibió stock en el formulario,
     *   traspasa el stock viejo a su heredera natural para evitar pérdidas accidentales.
     * - Si el usuario ya asignó el stock en el formulario (ej. a "rojo" o repartido entre "verde" y "rojo"),
     *   respeta exactamente el reparto del formulario sin duplicar.
     * - Si la variante antigua tiene historial en Kardex, la archiva como inactiva ([OBSOLETO]) con stock 0.
     * - Si no tiene historial en Kardex, la elimina físicamente de la base de datos.
     */
    protected function depurarVariantesDesfasadas(
        \Illuminate\Support\Collection $existingVariantes,
        array $processedVariantIds,
        \Illuminate\Support\Collection $activeNewVariants
    ): void {
        $obsoletas = $existingVariantes->except($processedVariantIds);

        foreach ($obsoletas as $oldVar) {
            $stocksViejos = $oldVar->stockAlmacenes ?? collect();
            $totalViejo = (int) $stocksViejos->sum('cantidad');

            if ($totalViejo > 0 && $activeNewVariants->isNotEmpty()) {
                $oldAttrs = $oldVar->atributos ?? [];

                // Buscar todas las variantes nuevas que son hijas de esta variante antigua (ej. todas las de Talla 11)
                $hijasDeRama = $activeNewVariants->filter(function (InventarioVariante $newVar) use ($oldAttrs) {
                    if (empty($oldAttrs)) return true;
                    $newAttrs = $newVar->atributos ?? [];
                    foreach ($oldAttrs as $tipoOld => $valOld) {
                        $cleanKeyOld = $this->cleanAttrString($tipoOld);
                        $cleanValOld = $this->cleanAttrString($valOld);
                        $matched = false;
                        foreach ($newAttrs as $tipoNew => $valNew) {
                            if ($cleanKeyOld === $this->cleanAttrString($tipoNew)) {
                                if ($cleanValOld === $this->cleanAttrString($valNew)) {
                                    $matched = true;
                                }
                                break;
                            }
                        }
                        if (!$matched) return false;
                    }
                    return true;
                });

                // Comprobar cuánto stock total tienen ya asignado las hijas de esta rama tras guardar el formulario
                $idsHijas = $hijasDeRama->pluck('id')->all();
                $stockYaAsignadoEnRama = !empty($idsHijas)
                    ? (int) InventarioStockAlmacen::whereIn('variante_id', $idsHijas)->sum('cantidad')
                    : 0;

                // Solo auto-migramos en backend si la rama entera llegó con 0 stock desde el formulario
                if ($stockYaAsignadoEnRama === 0) {
                    $heredera = $this->encontrarVarianteHeredera($oldVar, $activeNewVariants);
                    if ($heredera) {
                        foreach ($stocksViejos as $stockAlm) {
                            if ((int) $stockAlm->cantidad <= 0) continue;

                            InventarioStockAlmacen::updateOrCreate(
                                ['variante_id' => $heredera->id, 'almacen_id' => $stockAlm->almacen_id],
                                ['cantidad' => (int) $stockAlm->cantidad]
                            );
                        }
                    }
                }
            }

            // Comprobar si tiene historial en el Kardex (movimientos registrados)
            $tieneHistorialKardex = \App\Models\InventarioMovimientoLinea::where('variante_id', $oldVar->id)->exists();

            if ($tieneHistorialKardex) {
                InventarioStockAlmacen::where('variante_id', $oldVar->id)->update(['cantidad' => 0]);
                $oldVar->update([
                    'activo' => false,
                    'descripcion' => str_contains((string) $oldVar->descripcion, '[OBSOLETO]')
                        ? $oldVar->descripcion
                        : trim($oldVar->descripcion . ' [OBSOLETO]'),
                ]);
            } else {
                InventarioStockAlmacen::where('variante_id', $oldVar->id)->delete();
                $oldVar->delete();
            }
        }
    }
}