<?php

namespace App\Http\Controllers;

use App\Models\AlbaranCliente;
use App\Models\Articulo;
use App\Models\Bolsa;
use App\Models\Cliente;
use App\Models\LineaBolsa;
use App\Models\PedidoCliente;
use App\Models\Presupuesto;
use App\Services\LineaBolsaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class PedidoController extends Controller
{
    private const MAX_LINEAS = 5000;
    private const MAX_DESCRIPCION = 500000;
    private const MAX_ARTICULO = 2000;
    private const MAX_MEDIDA = 200;
    private const MAX_CANTIDAD = 1000000;
    private const MAX_PRECIO = 10000000;
    private const MAX_MARGEN = 1000;
    private const MAX_TOTAL = 1000000000;
    private const PEDIDO_CLIENTE_ESTADOS = [
        'pendiente' => 'Pendiente',
        'facturado' => 'Facturado',
        'facturado_parcial' => 'Facturado parcial',
    ];  

    private const PRESUPUESTO_ESTADOS_PEDIDO = ['pendiente', 'pedido_pendiente'];
    
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Redirige al index real de clientes
        return $this->indexClientes($request);
    }

    public function indexClientes(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('pedidos.view');

        $proyectoId = $this->resolveActiveProyectoId($request);
        $search = trim((string) $request->input('search', ''));
        $estado = trim((string) $request->input('estado', ''));
        $desde = trim((string) $request->input('desde', ''));
        $hasta = trim((string) $request->input('hasta', ''));
        $bolsa = $request->boolean('bolsa');

        $pedidosQuery = PedidoCliente::query()
           ->with(['cliente', 'presupuesto', 'albaran', 'albaranesPivot'])
            ->withCount('albaranes as ui_albaranes_count')
            ->withCount('facturacionesManuales as facturaciones_manuales_count')
            ->withSum('facturacionesManuales as facturaciones_sum', 'importe')
            ->where('proyecto_id', $proyectoId);

        if ($search !== '') {
            $like = '%' . $search . '%';
            
            // CONTEXTO: Convertimos la coma española a punto decimal para que la Base de Datos lo entienda
            $searchNumeric = str_replace(',', '.', $search);

            $pedidosQuery->where(function ($query) use ($like, $searchNumeric) {
                $query->where('numero_pedido', 'like', $like)
                    ->orWhere('ot', 'like', $like) // Búsqueda directa por OT/CC
                    ->orWhereHas('cliente', function ($clienteQuery) use ($like) {
                        $clienteQuery->where('empresa_nombre', 'like', $like);
                    })
                    ->orWhereHas('presupuesto', function ($presupuestoQuery) use ($like) {
                        $presupuestoQuery->where('numero', 'like', $like);
                    });

                // Si lo que ha escrito el usuario es un número válido, buscamos también en el total
                if (is_numeric($searchNumeric)) {
                    $query->orWhere('total', 'like', '%' . $searchNumeric . '%')
                          ->orWhere('total', '=', (float) $searchNumeric);
                }
            });
        }

        if ($estado !== '' && array_key_exists($estado, self::PEDIDO_CLIENTE_ESTADOS)) {
            $pedidosQuery->where('estado', $estado);
        }

        if ($desde !== '') {
            try {
                $pedidosQuery->whereDate('fecha_pedido', '>=', Carbon::parse($desde)->toDateString());
            }
            catch (\Throwable $exception) {
                // Ignore invalid dates.
            }
        }

        if ($hasta !== '') {
            try {
                $pedidosQuery->whereDate('fecha_pedido', '<=', Carbon::parse($hasta)->toDateString());
            } catch (\Throwable $exception) {
                // Ignore invalid dates.
            }
        }

        if ($bolsa) {
            $pedidosQuery->where('bolsa', true);
        }

        if ($request->query('export') === 'csv') {
            abort_unless($request->user()->canAny(['clientes.export', 'pedidos.download']), 403);
            $rows = (clone $pedidosQuery)->orderByDesc('fecha_pedido')->orderByDesc('id')->get();

            return response()->streamDownload(function () use ($rows) {
                $h = fopen('php://output', 'w');
                fwrite($h, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($h, ['Nº pedido', 'Ref. cliente', 'Cliente', 'CC', 'Fecha', 'Estado', 'Total', 'Pendiente'], ';');
                foreach ($rows as $p) {
                    fputcsv($h, [
                        $p->numero_pedido,
                        $p->referencia_manual,
                        $p->cliente?->empresa_nombre ?? '',
                        $p->ot,
                        optional($p->fecha_pedido)->format('d/m/Y'),
                        $p->estado ?: 'pendiente',
                        number_format((float) $p->total, 2, ',', ''),
                        number_format($p->pendienteFacturar(), 2, ',', ''),
                    ], ';');
                }
                fclose($h);
            }, 'pedidos_' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $pedidos = $pedidosQuery
            ->orderByDesc('fecha_pedido')
            ->orderByDesc('id')
            ->paginate(8)
            ->withQueryString();

        $baseStatsQuery = PedidoCliente::query()->where('proyecto_id', $proyectoId);

        $pedidosActivos = (clone $baseStatsQuery)
            ->where(function ($query) {
                $query->whereNull('estado')
                    ->orWhereIn('estado', ['pendiente', 'facturado_parcial']);
            })
            ->count();

        $pendientesAlbaran = (clone $baseStatsQuery)
            ->where(function ($query) {
                $query->whereNull('albaran_id')->orWhere('albaran_id', 0);
            })
            ->where(function ($query) {
                $query->whereNull('estado')->orWhere('estado', 'pendiente');
            })
            ->count();

        $inicioMesActual = now()->copy()->startOfMonth();
        $inicioMesAnterior = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $finMesAnterior = now()->copy()->startOfMonth()->subDay();

        $facturacionMensual = (float) (clone $baseStatsQuery)
            ->whereBetween('fecha_pedido', [$inicioMesActual, now()])
            ->sum('total');

        $facturacionMesAnterior = (float) (clone $baseStatsQuery)
            ->whereBetween('fecha_pedido', [$inicioMesAnterior, $finMesAnterior])
            ->sum('total');

        $variacionPedidos = (clone $baseStatsQuery)
            ->whereBetween('fecha_pedido', [$inicioMesActual, now()])
            ->count();

        $variacionPedidosAnterior = (clone $baseStatsQuery)
            ->whereBetween('fecha_pedido', [$inicioMesAnterior, $finMesAnterior])
            ->count();
        $variacionPedidosPorcentaje = $variacionPedidosAnterior > 0
            ? round((($variacionPedidos - $variacionPedidosAnterior) / $variacionPedidosAnterior) * 100, 1)
            : ($variacionPedidos > 0 ? 100.0 : 0.0);

        $metaFacturacion = 500000;
        $porcentajeMeta = $metaFacturacion > 0
            ? min(100, round(($facturacionMensual / $metaFacturacion) * 100))
            : 0;

        $albaranesQuery = AlbaranCliente::query()->where('proyecto_id', $proyectoId);
        $albaranesPendientesRelacionados = (clone $albaranesQuery)
            ->whereNotNull('pedido_cliente')
            ->where('estado', 'pendiente')
            ->count();

        $presupuestos = Presupuesto::query()
            ->where('proyecto_id', $proyectoId)
            ->get(['id', 'numero'])
            ->keyBy('id');

        $numerosPedido = $pedidos->getCollection()
            ->pluck('numero_pedido')
            ->filter()
            ->map(fn ($item) => trim((string) $item))
            ->unique()
            ->values();

        $pedidos->getCollection()->transform(function (PedidoCliente $pedido) {
            $pedido->ui_estado = $pedido->estado ?: 'pendiente';
            $pedido->ui_estado_label = self::PEDIDO_CLIENTE_ESTADOS[$pedido->ui_estado] ?? ucfirst(str_replace('_', ' ', $pedido->ui_estado));
            $pedido->ui_estado_class = match ($pedido->ui_estado) {
                'facturado' => 'pedido-chip pedido-chip--paid',
                'facturado_parcial' => 'pedido-chip pedido-chip--partial',
                default => 'pedido-chip pedido-chip--pending',
            };
            $pedido->ui_total = (float) ($pedido->total ?? 0);
            $pedido->ui_presupuesto_numero = $pedido->presupuesto?->numero;

            $albaranes = $pedido->albaranesEfectivos();
            $pedido->ui_albaranes_count = $albaranes->count();
            $pedido->ui_albaran_numero = $albaranes->count() === 1 ? (string) $albaranes->first()->numero : null;
            $pedido->ui_albaran_id = $albaranes->count() === 1 ? (int) $albaranes->first()->id : null;
            $pedido->ui_total_albaranes = round((float) $albaranes->sum('total'), 2);
            $pedido->ui_total_facturaciones = $pedido->importeConsumido();
            $pedido->ui_pendiente = $pedido->pendienteFacturar();

            return $pedido;
        });

        return view('pedidos-clientes.index', [
            'pedidos' => $pedidos,
            'estadosFiltro' => self::PEDIDO_CLIENTE_ESTADOS,
            'searchActual' => $search,
            'estadoActual' => $estado,
            'desdeActual' => $desde,
            'hastaActual' => $hasta,
            'bolsaActual' => $bolsa,
            'pedidosActivos' => $pedidosActivos,
            'pendientesAlbaran' => $pendientesAlbaran,
            'albaranesPendientesRelacionados' => $albaranesPendientesRelacionados,
            'facturacionMensual' => $facturacionMensual,
            'facturacionMesAnterior' => $facturacionMesAnterior,
            'variacionPedidosPorcentaje' => $variacionPedidosPorcentaje,
            'metaFacturacion' => $metaFacturacion,
            'porcentajeMeta' => $porcentajeMeta,
            'titulo' => 'Pedidos de Clientes',
            'breadcrumb' => 'Gestión de pedidos de clientes',
        ]);
    }

    public function createCliente(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);

        $clientes = Cliente::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('empresa_nombre')
            ->get();

        $presupuestos = Presupuesto::query()
            ->where('proyecto_id', $proyectoId)
            ->whereIn('estado', self::PRESUPUESTO_ESTADOS_PEDIDO)
            ->whereDoesntHave('pedidoCliente')
            ->with('cliente')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $presupuestoSeleccionadoId = (int) $request->query('presupuesto_id', 0);
        $presupuestoSeleccionado = null;

        if ($presupuestoSeleccionadoId > 0) {
            $presupuestoSeleccionado = Presupuesto::with('cliente')
                ->where('proyecto_id', $proyectoId)
                ->where('id', $presupuestoSeleccionadoId)
                ->first();

            if ($presupuestoSeleccionado && !$presupuestos->contains('id', $presupuestoSeleccionado->id)) {
                $presupuestos->prepend($presupuestoSeleccionado);
            }
        }

        $clienteSeleccionadoId = (int) $request->query('cliente_id', 0);
        if ($clienteSeleccionadoId <= 0 && $presupuestoSeleccionado?->cliente_id) {
            $clienteSeleccionadoId = (int) $presupuestoSeleccionado->cliente_id;
        }

        if ($clienteSeleccionadoId > 0 && !$clientes->contains('id', $clienteSeleccionadoId)) {
            $clienteSeleccionadoId = 0;
        }

        $clienteSeleccionado = $clienteSeleccionadoId > 0
            ? $clientes->firstWhere('id', $clienteSeleccionadoId)
            : null;

        if (!$clienteSeleccionado && $presupuestoSeleccionado?->cliente) {
            $clienteSeleccionado = $presupuestoSeleccionado->cliente;
            $clienteSeleccionadoId = (int) $clienteSeleccionado->id;
        }

        $fechaPedido = (string) $request->query('fecha_pedido', now()->toDateString());

        $bolsaPreseleccionada = $request->boolean('bolsa', false);
        $bolsaIdPreseleccionada = (int) $request->query('bolsa_id', 0);

        $lineasInicialesRaw = is_array($presupuestoSeleccionado?->lista_articulos)
            ? $presupuestoSeleccionado->lista_articulos
            : [];

        $lineasIniciales = $this->normalizePedidoLineas($lineasInicialesRaw);

        $baseImponible = round((float) ($presupuestoSeleccionado?->total ?? 0), 4);
        $totalPedido = $baseImponible;
        
        $presupuestosParaPedido = $presupuestos->map(function (Presupuesto $presupuesto) {
            $lineas = is_array($presupuesto->lista_articulos) ? $presupuesto->lista_articulos : [];

            return [
                'id' => $presupuesto->id,
                'cliente_id' => $presupuesto->cliente_id,
                'numero' => $presupuesto->numero,
                'titulo' => $presupuesto->titulo,
                'ot' => $presupuesto->ot,
                'cliente_nombre' => $presupuesto->cliente?->empresa_nombre,
                'lineas' => $this->normalizePedidoLineas($lineas),
            ];
        })->values()->all();

        $pedidoCliente = null;
        $bolsasParaPedido = Bolsa::query()
            ->with('partidas')
            ->where('proyecto_id', $proyectoId)
            ->orderByDesc('fecha')
            ->get(['id', 'id_cliente', 'numero', 'tipo_bolsa', 'total'])
            ->map(function (Bolsa $bolsa) use ($pedidoCliente) {
                $esAbierta = (float) $bolsa->total <= 0;
                
                $dineroComprometido = $bolsa->partidas->sum('total');
                $saldoDisponible = max(0, $bolsa->total - $dineroComprometido);
                
                if (isset($pedidoCliente) && $bolsa->id === $pedidoCliente->bolsa_id) {
                    $saldoDisponible += (float) $pedidoCliente->total;
                }

                $saldoTexto = $esAbierta 
                    ? 'Bolsa Abierta' 
                    : number_format($saldoDisponible, 2, ',', '.') . ' € libres (Límite)';

                return [
                    'id' => $bolsa->id,
                    'id_cliente' => $bolsa->id_cliente,
                    'numero' => $bolsa->numero,
                    'tipo_bolsa' => $bolsa->tipo_bolsa,
                    'saldo_texto' => $saldoTexto,
                    'es_abierta' => $esAbierta,
                    'saldo_disponible' => round($saldoDisponible, 4), 
                ];
            })
            ->values()
            ->all();

        return view('pedidos-clientes.create', compact(
            'clientes',
            'clienteSeleccionado',
            'clienteSeleccionadoId',
            'presupuestos',
            'presupuestoSeleccionado',
            'presupuestoSeleccionadoId',
            'fechaPedido',
            'lineasIniciales',
            'presupuestosParaPedido',
            'bolsasParaPedido',
            'baseImponible',
            'totalPedido',
            'bolsaPreseleccionada',
            'bolsaIdPreseleccionada'
        ) + [
            'titulo' => 'Crear Nuevo Pedido',
            'breadcrumb' => 'Nuevo Pedido',
        ]);
    }

    public function storeCliente(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);

        $request->merge([
            'numero_pedido' => trim((string) $request->input('numero_pedido', '')),
            'bolsa' => $request->boolean('bolsa'),
            'bolsa_texto' => trim((string) $request->input('bolsa_texto', '')),
        ]);

        $validated = $request->validate([
            'numero_pedido' => [
                'required',
                'string',
                'max:80',
                Rule::unique('pedidos_clientes', 'numero_pedido')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)->whereNull('deleted_at')),
            ],
            'referencia_manual' => 'nullable|string|max:120',
            'bolsa_texto' => 'nullable|string|max:2000',
            'fecha_pedido' => 'required|date',
            'id_cliente' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],
            'ot' => 'nullable|string|max:100',
            'presupuesto_id' => [
                'nullable',
                'integer',
                // CAMBIO: Validación de estados permitidos según constante
                Rule::exists('presupuestos', 'id')->where(function ($query) use ($proyectoId) {
                    $query->where('proyecto_id', $proyectoId)
                        ->whereIn('estado', self::PRESUPUESTO_ESTADOS_PEDIDO);
                }),
                Rule::unique('pedidos_clientes', 'presupuesto_id')->where(function ($query) use ($proyectoId) {
                    $query->where('proyecto_id', $proyectoId);
                }),
            ],
            // CAMBIO: Validación estricta de estado del pedido
            'estado' => ['nullable', Rule::in(array_keys(self::PEDIDO_CLIENTE_ESTADOS))],
            'bolsa' => 'nullable|boolean',
            'bolsa_id' => [
                'nullable',
                'integer',
                Rule::exists('bolsas', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'total' => 'nullable|numeric|min:0|max:' . self::MAX_TOTAL,
            'lista_articulos' => 'nullable|json',
        ]);

        $bolsa = (bool) ($validated['bolsa'] ?? false);
        $bolsaGlobalId = $bolsa ? ($validated['bolsa_id'] ?? null) : null;

        $clienteValido = Cliente::query()
            ->where('proyecto_id', $proyectoId)
            ->where('id', $validated['id_cliente'])
            ->exists();

        $presupuestoId = null;
        if (!empty($validated['presupuesto_id'])) {
            $presupuesto = Presupuesto::query()
                ->where('proyecto_id', $proyectoId)
                ->where('id', $validated['presupuesto_id'])
                ->first();

            if ($presupuesto) {
                $presupuestoId = $presupuesto->id;
                $validated['id_cliente'] = (int) $presupuesto->cliente_id;
                $clienteValido = Cliente::query()
                    ->where('proyecto_id', $proyectoId)
                    ->where('id', $validated['id_cliente'])
                    ->exists();
            }
        }

        if (!$clienteValido) {
            abort(404);
        }

        $lineas = [];
        $total = 0.0;

        $lineasRaw = json_decode((string) ($validated['lista_articulos'] ?? '[]'), true);
        $lineasRaw = is_array($lineasRaw) ? $lineasRaw : [];
        
        $lineasFiltradas = collect($lineasRaw)
            ->filter(fn ($linea) => is_array($linea) && !empty(trim((string) ($linea['descripcion'] ?? ''))))
            ->values()
            ->all();

        if ($bolsa && empty($lineasFiltradas)) {
             $total = round((float) ($validated['total'] ?? 0), 4);
        } else {
             $this->validateLineasPayload($lineasFiltradas);

             // CAMBIO: Normalización limpia, sin ensuciar la base de datos de artículos
             $normalizer = new \App\Services\DocumentLineNormalizer();
             
             $lineas = array_map(function (array $l) {
                 $l['medida'] = $l['medida'] ?: 'und';
                 $l['unidad'] = $l['medida'];
                 return $l;
             }, $normalizer->normalize($lineasFiltradas));

             $total = (float) collect($lineas)->sum('total');
        }

        // --- ESCUDO DE SEGURIDAD LÍMITE DE BOLSA ---
        if ($bolsa && $bolsaGlobalId) {
            $bolsaGlobal = Bolsa::find($bolsaGlobalId);
            if ($bolsaGlobal && $bolsaGlobal->total > 0) {
                $dineroComprometido = $bolsaGlobal->partidas()->sum('total');
                $saldoLibreParaPartidas = $bolsaGlobal->total - $dineroComprometido;
                
                if ($total > $saldoLibreParaPartidas) {
                    throw ValidationException::withMessages([
                        'bolsa_id' => 'No hay capacidad suficiente. Límite restante para nuevas partidas: ' . number_format($saldoLibreParaPartidas, 2, ',', '.') . ' €'
                    ]);
                }
            }
        }

        // --- PROTECCIÓN BACKEND CONTRA DOBLE CLICK ---
        $dobleEnvio = PedidoCliente::where('proyecto_id', $proyectoId)
            ->where('id_cliente', $validated['id_cliente'])
            ->where('total', $total)
            ->where('created_at', '>=', Carbon::now()->subSeconds(10))
            ->first();

        if ($dobleEnvio) {
            return redirect()->route('pedidos-clientes.index')->with('success', 'Pedido de cliente creado correctamente');
        }

        DB::transaction(function () use ($validated, $proyectoId, $presupuestoId, $total, $lineas, $bolsa, $bolsaGlobalId, $lineasFiltradas) {
            if ($presupuestoId !== null) {
                $presupuestoBloqueado = Presupuesto::query()
                    ->where('proyecto_id', $proyectoId)
                    ->where('id', $presupuestoId)
                    ->lockForUpdate()
                    ->first();

                if (!$presupuestoBloqueado) {
                    throw ValidationException::withMessages([
                        'presupuesto_id' => 'El presupuesto seleccionado no está disponible.',
                    ]);
                }

                $pedidoExistente = PedidoCliente::query()
                    ->where('proyecto_id', $proyectoId)
                    ->where('presupuesto_id', $presupuestoId)
                    ->lockForUpdate()
                    ->first();

                if ($pedidoExistente) {
                    throw ValidationException::withMessages([
                        'presupuesto_id' => 'Este presupuesto ya tiene un pedido asignado.',
                    ]);
                }
            }

            $nuevoPedido = PedidoCliente::create([
                'id_cliente' => $validated['id_cliente'],
                'proyecto_id' => $proyectoId,
                'numero_pedido' => $validated['numero_pedido'],
                'referencia_manual' => $validated['referencia_manual'] ?? null,
                'fecha_pedido' => Carbon::parse($validated['fecha_pedido'])->toDateString(),
                'ot' => $validated['ot'] ?? null,
                'presupuesto_id' => $presupuestoId,
                'albaran_id' => null,
                'estado' => $validated['estado'] ?? 'pendiente',
                'bolsa' => $bolsa,
                'bolsa_texto' => $bolsa ? ($validated['bolsa_texto'] ?? null) : null,
                'bolsa_id' => $bolsaGlobalId,
                'total' => round($total, 4),
                'lista_articulos' => empty($lineas) ? null : $lineas,
            ]);

            if ($bolsa && !empty($lineasFiltradas)) {
                (new LineaBolsaService())->sincronizarLineasDePartida($nuevoPedido, $lineas);
            }

            if ($presupuestoId !== null) {
                Presupuesto::query()
                    ->where('proyecto_id', $proyectoId)
                    ->where('id', $presupuestoId)
                    ->update([
                        'estado' => 'aceptado',
                    ]);
            }

            if (!$bolsa && !empty($lineas)) {
                $this->syncArticulosFromLineas($proyectoId, $lineas);
            }
        });

        return redirect()->route('pedidos-clientes.index')->with('success', 'Pedido de cliente creado correctamente');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('pedidos.manage');

        return view('pedidos.create', [
            'titulo' => 'Crear Nuevo Pedido',
            'breadcrumb' => 'Nuevo Pedido'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('pedidos.manage');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('pedidos.view');

        return view('pedidos.show', [
            'id' => $id,
            'titulo' => 'Detalle del Pedido',
            'breadcrumb' => 'Ver Pedido'
        ]);
    }

    public function showCliente(PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.view');

        $proyectoId = $this->resolveActiveProyectoId(request());

        if ($pedidoCliente->proyecto_id && (int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        // En lugar de cargar la vista inútil, redirigimos al instante 
        // al panel de control real del pedido (el del progreso y totales)
        return redirect()->route('pedidos-clientes.albaranes', $pedidoCliente);
    }

    public function viewPdf(PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.download');

        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return $this->renderPdfResponse($pedidoCliente, false);
    }

    public function downloadPdf(PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.download');

        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return $this->renderPdfResponse($pedidoCliente, true);
    }

    public function preview(PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.download');

        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $pedidoCliente->loadMissing('cliente');

        return view('pedidos-clientes.preview', [
            'pedido' => $pedidoCliente,
            'pdfUrl' => route('pedidos-clientes.pdf', $pedidoCliente),
            'downloadUrl' => route('pedidos-clientes.pdf.download', $pedidoCliente),
        ]);
    }

    /**
     * Devuelve datos JSON útiles sobre un pedido para autocompletar formularios.
     */
    public function data(Request $request)
    {
        $this->authorize('pedidos.view');

        $proyectoId = $this->resolveActiveProyectoId($request);

        $pedido = null;
        $pedidoId = (int) $request->query('pedido_id', 0);
        $numero = trim((string) $request->query('numero', ''));

        if ($pedidoId > 0) {
            $pedido = PedidoCliente::query()->with('cliente')->where('proyecto_id', $proyectoId)->find($pedidoId);
        }

        if (!$pedido && $numero !== '') {
            $pedido = PedidoCliente::query()->with('cliente')->where('proyecto_id', $proyectoId)->where('numero_pedido', $numero)->first();
        }

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        $rawLineas = is_array($pedido->lista_articulos) ? $pedido->lista_articulos : [];

        $lineas = $this->normalizePedidoLineas($rawLineas);

        return response()->json([
            'id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'id_cliente' => $pedido->id_cliente,
            'ot' => $pedido->ot,
            'bolsa' => (bool) ($pedido->bolsa ?? false),
            'lineas' => $lineas,
            'lista_articulos' => $lineas,
        ]);
    }

    /**
     * Líneas de bolsa de una partida con su saldo REAL (agregando todas las
     * partidas que la nutren, no solo esta). Sustituye a data() como fuente
     * para el formulario de albarán cuando el pedido ya usa Bolsa V3.
     */
    public function lineasBolsaDisponibles(Request $request, PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.view');

        $proyectoId = $this->resolveActiveProyectoId($request);
        if ((int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $service = new LineaBolsaService();

        // Pedidos bolsa anteriores a Bolsa V3 (o sin líneas registradas): se migran al
        // vuelo desde su JSON, conservando los uid que ya tenían, para que el saldo por
        // línea funcione sin tener que borrar ni recrear nada.
        if ((bool) ($pedidoCliente->bolsa ?? false) && !$pedidoCliente->lineasBolsa()->exists()) {
            $lineasJson = is_array($pedidoCliente->lista_articulos) ? $pedidoCliente->lista_articulos : [];
            if ($lineasJson !== []) {
                $service->sincronizarLineasDePartida($pedidoCliente, $lineasJson);
            }
        }

        $lineas = $service->lineasDisponiblesPorPartida($pedidoCliente);

        return response()->json([
            'id' => $pedidoCliente->id,
            'numero_pedido' => $pedidoCliente->numero_pedido,
            'bolsa' => (bool) ($pedidoCliente->bolsa ?? false),
            'bolsa_id' => $pedidoCliente->bolsa_id,
            'lineas' => $lineas,
        ]);
    }

    /**
     * Vincula una línea de bolsa YA EXISTENTE (nacida en otra partida, incluso
     * de otra bolsa) a esta partida, asignándole un importe adicional.
     * Es el mecanismo de "reutilizar el sobrante de una bolsa en otra".
     */
    public function vincularLineaBolsa(Request $request, PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);
        if ((int) $pedidoCliente->proyecto_id !== $proyectoId || !(bool) ($pedidoCliente->bolsa ?? false)) {
            abort(404);
        }

        $validated = $request->validate([
            'linea_id' => [
                'required',
                'integer',
                Rule::exists('lineas_bolsa', 'id')->where(fn ($query) => $query->where('id_cliente', $pedidoCliente->id_cliente)),
            ],
            'importe' => 'required|numeric|min:0.01|max:' . self::MAX_TOTAL,
        ]);

        $linea = LineaBolsa::findOrFail($validated['linea_id']);

        // Si ambas partidas cuelgan de una bolsa global, exigimos que compartan
        // tipo_bolsa (grupo de fungibilidad) antes de dejar cruzar el importe.
        $bolsaOrigen = $linea->bolsaOrigen;
        $bolsaDestino = $pedidoCliente->bolsaGlobal;
        if ($bolsaOrigen && $bolsaDestino && $bolsaOrigen->tipo_bolsa !== $bolsaDestino->tipo_bolsa) {
            throw ValidationException::withMessages([
                'linea_id' => 'Esta línea pertenece a una bolsa de tipo "' . $bolsaOrigen->tipo_bolsa . '" y no puede vincularse a una partida de tipo "' . $bolsaDestino->tipo_bolsa . '".',
            ]);
        }

        $importeAVincular = round((float) $validated['importe'], 4);

        // --- ESCUDO DE SEGURIDAD LÍMITE DE BOLSA (vincular línea existente) ---
        // Esta es la única vía por la que se puede mover dinero entre partidas sin
        // pasar por storeCliente()/update(), así que necesita el mismo candado: no
        // dejar asignar a una línea más de lo que la bolsa destino tiene libre.
        if ($bolsaDestino && (float) $bolsaDestino->total > 0) {
            $saldoLibre = $bolsaDestino->saldoDisponible();
            if ($importeAVincular > $saldoLibre) {
                throw ValidationException::withMessages([
                    'importe' => 'No hay suficiente saldo en la bolsa global de destino. (Máximo disponible: '
                        . number_format($saldoLibre, 2, ',', '.') . ' €)',
                ]);
            }
        }
        // --- FIN ESCUDO DE SEGURIDAD ---

        (new LineaBolsaService())->asignarImporteAPartida($linea, $pedidoCliente, $importeAVincular);

        return response()->json(['success' => true]);
    }

    private function validateLineasPayload(array $lineas): void
    {
        Validator::make(['lineas' => $lineas], [
            'lineas' => ['array', 'max:' . self::MAX_LINEAS],
            'lineas.*.descripcion' => ['required', 'string', 'max:' . self::MAX_DESCRIPCION],
            'lineas.*.articulo' => ['nullable', 'string', 'max:' . self::MAX_ARTICULO],
            'lineas.*.medida' => ['nullable', 'string', 'max:' . self::MAX_MEDIDA],
            'lineas.*.unidad' => ['nullable', 'string', 'max:' . self::MAX_MEDIDA],
            'lineas.*.cantidad' => ['nullable', 'numeric', 'min:0', 'max:' . self::MAX_CANTIDAD],
            'lineas.*.precio_unitario' => ['nullable', 'numeric', 'min:0', 'max:' . self::MAX_PRECIO],
            'lineas.*.precio' => ['nullable', 'numeric', 'min:0', 'max:' . self::MAX_PRECIO],
            'lineas.*.margen' => ['nullable', 'numeric', 'min:0', 'max:' . self::MAX_MARGEN],
        ])->validate();
    }

    private function normalizePedidoLineas(array $lineas): array
    {
        return (new \App\Services\DocumentLineNormalizer())->normalize($lineas);
    }

    public function albaranesCliente(PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.view');

        $sessionProyectoId = (int) request()->session()->get('active_proyecto_id');
        $proyectoId = $sessionProyectoId > 0 ? $sessionProyectoId : (int) ($pedidoCliente->proyecto_id ?? 0);

        if ($pedidoCliente->proyecto_id && (int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        // 1. Refactorización aquí: Uso de albaranesEfectivos()
        $albaranes = $pedidoCliente->albaranesEfectivos()
            ->sortByDesc(fn (AlbaranCliente $a) => sprintf('%s-%06d', optional($a->fecha)->format('Y-m-d') ?? '0000-00-00', (int) $a->id))
            ->values();

        // 2. Totales calculados desde el modelo (sin bucles foreach)
        $totalPedido = round((float) ($pedidoCliente->total ?? 0), 2);
        $totalFacturado = $pedidoCliente->importeConsumido();
        $pendienteFacturar = $pedidoCliente->pendienteFacturar();

        $perPage = 10;
        $currentPage = max(1, (int) request()->query('page', 1));
        $pagedItems = $albaranes->forPage($currentPage, $perPage)->values();
        $albaranesPaginados = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedItems, $albaranes->count(), $perPage, $currentPage, ['path' => request()->url(), 'query' => request()->query()]
        );

        // 3. Widget global del cliente (Simplificado con map y filter)
        $resumenBolsasCliente = null;
        if ($pedidoCliente->bolsa) {
            $bolsasActivas = PedidoCliente::with(['albaranesPivot', 'albaran', 'albaranes'])
                ->where('proyecto_id', $proyectoId)
                ->where('id_cliente', $pedidoCliente->id_cliente)
                ->where('bolsa', true)
                ->where('id', '<>', $pedidoCliente->id) // Excluimos la bolsa que ya se está viendo
                ->where(function ($query) {
                    $query->whereNull('estado')
                          ->orWhereIn('estado', ['pendiente', 'facturado_parcial']);
                })
                ->orderByDesc('id')
                ->get();

            $bolsasResumen = $bolsasActivas->map(fn (PedidoCliente $b) => [
                'id' => $b->id,
                'numero_pedido' => $b->numero_pedido,
                'ot' => $b->ot,
                'total' => round((float) $b->total, 2),
                'facturado' => $b->importeConsumido(),
                'saldo_disponible' => $b->pendienteFacturar(),
            ])->filter(fn ($b) => $b['saldo_disponible'] > 0.001)->values();

            if ($bolsasResumen->isNotEmpty()) {
                $resumenBolsasCliente = [
                    'cantidad' => $bolsasResumen->count(),
                    'bolsas' => $bolsasResumen,
                ];
            }
        }

        // 4. Se pasa 'totalFacturado' a la vista en lugar de 'totalAlbaranes'
        return view('pedidos-clientes.albaranes', [
            'pedidoCliente' => $pedidoCliente,
            'albaranes' => $albaranesPaginados,
            'facturaciones' => collect(), // Se mantiene por seguridad si la vista aún lo pide
            'totalPedido' => $totalPedido,
            'totalFacturado' => $totalFacturado, // Cambio clave aquí
            'pendienteFacturar' => $pendienteFacturar,
            'resumenBolsasCliente' => $resumenBolsasCliente,
            'titulo' => 'Albaranes del pedido',
            'breadcrumb' => 'Albaranes del pedido',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function editCliente(Request $request, PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);

        if ($pedidoCliente->proyecto_id && (int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $presupuestoEstadosPermitidos = ['pendiente' , 'aceptado' , 'parcial' , 'pendiente pedido'];

        $clientes = Cliente::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('empresa_nombre')
            ->get();

        $presupuestos = Presupuesto::query()
            ->where('proyecto_id', $proyectoId)
            ->whereIn('estado', $presupuestoEstadosPermitidos)
            ->whereDoesntHave('pedidoCliente', function($q) use ($pedidoCliente) {
                // Permitimos que aparezca el presupuesto actual del pedido
                $q->where('id', '!=', $pedidoCliente->id);
            })
            ->with('cliente')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        // Asegurar que el presupuesto original está en la lista aunque su estado haya cambiado
        if ($pedidoCliente->presupuesto_id && !$presupuestos->contains('id', $pedidoCliente->presupuesto_id)) {
            $presupuestoOriginal = Presupuesto::with('cliente')
                ->where('proyecto_id', $proyectoId)
                ->find($pedidoCliente->presupuesto_id);
                
            if ($presupuestoOriginal) {
                $presupuestos->prepend($presupuestoOriginal);
            }
        }

        $presupuestosParaPedido = $presupuestos->map(function (Presupuesto $presupuesto) {
            $lineas = is_array($presupuesto->lista_articulos) ? $presupuesto->lista_articulos : [];

            return [
                'id' => $presupuesto->id,
                'cliente_id' => $presupuesto->cliente_id,
                'numero' => $presupuesto->numero,
                'titulo' => $presupuesto->titulo,
                'ot' => $presupuesto->ot,
                'cliente_nombre' => $presupuesto->cliente?->empresa_nombre,
                'lineas' => $this->normalizePedidoLineas($lineas),
            ];
        })->values()->all();

        // En PedidoController.php (Aplica esto en createCliente y editCliente)

        $bolsasParaPedido = Bolsa::query()
            ->with('partidas')
            ->where('proyecto_id', $proyectoId)
            ->orderByDesc('fecha')
            ->get(['id', 'id_cliente', 'numero', 'tipo_bolsa', 'total'])
            ->map(function (Bolsa $bolsa) use ($pedidoCliente) {
                $esAbierta = (float) $bolsa->total <= 0;
                
                // CAMBIO: Calculamos en base al dinero comprometido por las partidas
                $dineroComprometido = $bolsa->partidas->sum('total');
                $saldoDisponible = max(0, $bolsa->total - $dineroComprometido);
                
                // Ajuste inteligente para la edición: Le sumamos el total de este mismo pedido 
                // para calcular el límite real disponible para modificarlo.
                if (isset($pedidoCliente) && $bolsa->id === $pedidoCliente->bolsa_id) {
                    $saldoDisponible += (float) $pedidoCliente->total;
                }

                $saldoTexto = $esAbierta 
                    ? 'Bolsa Abierta' 
                    : number_format($saldoDisponible, 2, ',', '.') . ' € libres (Límite)';

                return [
                    'id' => $bolsa->id,
                    'id_cliente' => $bolsa->id_cliente,
                    'numero' => $bolsa->numero,
                    'tipo_bolsa' => $bolsa->tipo_bolsa,
                    'saldo_texto' => $saldoTexto,
                    'es_abierta' => $esAbierta,
                    'saldo_disponible' => round($saldoDisponible, 4), 
                ];
            })
            ->values()
            ->all();

        return view('pedidos-clientes.edit', compact(
            'pedidoCliente',
            'clientes',
            'presupuestos',
            'presupuestosParaPedido',
            'bolsasParaPedido'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);

        if ($pedidoCliente->proyecto_id && (int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $request->merge([
            'numero_pedido' => trim((string) $request->input('numero_pedido', '')),
            'bolsa' => $request->boolean('bolsa'),
            'bolsa_texto' => trim((string) $request->input('bolsa_texto', '')),
        ]);

        $validated = $request->validate([
            'numero_pedido' => [
                'required',
                'string',
                'max:80',
                Rule::unique('pedidos_clientes', 'numero_pedido')
                    ->where(fn ($query) => $query->where('proyecto_id', $proyectoId)->whereNull('deleted_at'))
                    ->ignore($pedidoCliente->id),
            ],
            'referencia_manual' => 'nullable|string|max:120',
            'bolsa_texto' => 'nullable|string|max:2000',
            'fecha_pedido' => 'required|date',
            'id_cliente' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],
            'ot' => 'nullable|string|max:100',
            'presupuesto_id' => [
                'nullable',
                'integer',
                Rule::exists('presupuestos', 'id')->where(function ($query) use ($proyectoId) {
                    $query->where('proyecto_id', $proyectoId);
                }),
            ],
            'estado' => 'nullable|string|max:30',
            'bolsa' => 'nullable|boolean',
            'bolsa_id' => [
                'nullable',
                'integer',
                Rule::exists('bolsas', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'total' => 'nullable|numeric|min:0|max:' . self::MAX_TOTAL,
            'lista_articulos' => 'nullable|json',
        ]);

        $bolsa = (bool) ($validated['bolsa'] ?? false);
        $bolsaGlobalId = $bolsa ? ($validated['bolsa_id'] ?? null) : null;

        $clienteValido = Cliente::query()
            ->where('proyecto_id', $proyectoId)
            ->where('id', $validated['id_cliente'])
            ->exists();

        $presupuestoId = $validated['presupuesto_id'] ?? null;
        if ($presupuestoId !== null) {
            $presupuesto = Presupuesto::query()
                ->where('proyecto_id', $proyectoId)
                ->where('id', $presupuestoId)
                ->first();

            if ($presupuesto) {
                $validated['id_cliente'] = (int) $presupuesto->cliente_id;
            }
        }

        if (!$clienteValido) {
            abort(404);
        }

        $lineas = [];
        $total = 0.0;

        $lineasRaw = json_decode((string) ($validated['lista_articulos'] ?? '[]'), true);
        $lineasRaw = is_array($lineasRaw) ? $lineasRaw : [];
        
        $lineasFiltradas = collect($lineasRaw)
            ->filter(fn ($linea) => is_array($linea) && !empty(trim((string) ($linea['descripcion'] ?? ''))))
            ->values()
            ->all();

        if ($bolsa && empty($lineasFiltradas)) {
             $total = round((float) ($validated['total'] ?? 0), 4);
        } else {
             $this->validateLineasPayload($lineasFiltradas);

             $lineas = collect($lineasFiltradas)
             ->map(function (array $linea, int $index) {
                 $modoConsumo = ($linea['modo_consumo'] ?? 'cantidad') === 'importe' ? 'importe' : 'cantidad';
                 $cantidad = $modoConsumo === 'importe' ? 1.0 : max(0, (float) ($linea['cantidad'] ?? 0));
                 $precioUnitario = max(0, (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0)));
                 $margen = max(0, (float) ($linea['margen'] ?? 0));
                 $totalLinea = $cantidad * $precioUnitario * (1 + ($margen / 100));
                 $medida = trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? '')));
                 $medida = $medida !== '' ? $medida : 'und';
                 $articuloOriginal = trim((string) ($linea['articulo'] ?? ''));
                 $articuloFinal = $articuloOriginal === '' ? (string) ($index + 1) : $articuloOriginal;
                 $uid = $linea['uid'] ?? (string) Str::uuid();

                 return [
                     'uid' => $uid,
                     'modo_consumo' => $modoConsumo,
                     'articulo' => $articuloFinal,
                     'descripcion' => trim((string) ($linea['descripcion'] ?? '')),
                     'cantidad' => round($cantidad, 2),
                     'medida' => $medida,
                     'precio_unitario' => round($precioUnitario, 4),
                     'margen' => round($margen, 2),
                     'total' => round($totalLinea, 4),
                 ];
             })
             ->values()
             ->all();

             $total = (float) collect($lineas)->sum('total');
        }

        // --- ESCUDO DE SEGURIDAD LÍMITE DE BOLSA (Paso 4 - Edición) ---
        if ($bolsa && $bolsaGlobalId) {
            $bolsaGlobal = Bolsa::find($bolsaGlobalId);
            if ($bolsaGlobal && $bolsaGlobal->total > 0) {
                // Calculamos el dinero ya comprometido
                $dineroComprometido = $bolsaGlobal->partidas()->sum('total');
                $saldoLibreParaPartidas = $bolsaGlobal->total - $dineroComprometido;
                
                // Si la partida ya pertenecía a esta misma bolsa, 
                // le sumamos su total anterior para calcular el límite real de edición.
                $limiteReal = ($pedidoCliente->bolsa_id == $bolsaGlobalId) 
                    ? $saldoLibreParaPartidas + $pedidoCliente->total 
                    : $saldoLibreParaPartidas;
                    
                if ($total > $limiteReal) {
                    throw ValidationException::withMessages([
                        'bolsa_id' => 'No hay capacidad suficiente. Límite restante para modificar esta partida: ' . number_format($limiteReal, 2, ',', '.') . ' €'
                    ]);
                }
            }
        }
        // --- FIN ESCUDO DE SEGURIDAD ---
        $antiguoPresupuestoId = $pedidoCliente->presupuesto_id;

        DB::transaction(function () use ($pedidoCliente, $validated, $presupuestoId, $antiguoPresupuestoId, $total, $lineas, $bolsa, $bolsaGlobalId, $lineasFiltradas) {
            
            // Liberar el presupuesto antiguo si se ha cambiado o quitado
            if ($antiguoPresupuestoId && $antiguoPresupuestoId !== $presupuestoId) {
                Presupuesto::where('id', $antiguoPresupuestoId)->update(['estado' => 'pendiente']);
            }

            // Bloquear el nuevo presupuesto si se ha seleccionado uno diferente
            if ($presupuestoId && $antiguoPresupuestoId !== $presupuestoId) {
                $pedidoExistente = PedidoCliente::query()
                    ->where('presupuesto_id', $presupuestoId)
                    ->where('id', '!=', $pedidoCliente->id)
                    ->first();

                if ($pedidoExistente) {
                    throw ValidationException::withMessages([
                        'presupuesto_id' => 'Este presupuesto ya está asignado a otro pedido.',
                    ]);
                }
                
                Presupuesto::where('id', $presupuestoId)->update(['estado' => 'aceptado']);
            }

            $pedidoCliente->update([
                'id_cliente' => $validated['id_cliente'],
                'numero_pedido' => $validated['numero_pedido'],
                'referencia_manual' => $validated['referencia_manual'] ?? null,
                'fecha_pedido' => Carbon::parse($validated['fecha_pedido'])->toDateString(),
                'ot' => $validated['ot'] ?? null,
                'presupuesto_id' => $presupuestoId,
                'estado' => $validated['estado'] ?? $pedidoCliente->estado,
                'bolsa' => $bolsa,
                'bolsa_texto' => $bolsa ? ($validated['bolsa_texto'] ?? null) : null,
                'bolsa_id' => $bolsaGlobalId,
                'total' => round($total, 4),
                'lista_articulos' => empty($lineas) ? null : $lineas,
            ]);

            if ($bolsa && !empty($lineasFiltradas)) {
                (new LineaBolsaService())->sincronizarLineasDePartida($pedidoCliente, $lineas);
            }
        });

        return redirect()->route('pedidos-clientes.index')->with('success', 'Pedido de cliente actualizado correctamente');
    }

    /**
     * Update estado of a pedido cliente
     */
    public function updateEstado(Request $request, PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);

        if ((int) $pedidoCliente->proyecto_id !== (int) $proyectoId) {
            abort(404);
        }

        $validated = $request->validate([
            'estado' => ['required', Rule::in(['pendiente', 'facturado', 'facturado_parcial'])],
        ]);

        $pedidoCliente->update([
            'estado' => $validated['estado'],
        ]);

        return redirect()->route('pedidos-clientes.index')->with('success', 'Estado del pedido actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('pedidos.delete');
    }

    public function destroyCliente(Request $request, PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.delete');

        $proyectoId = $this->resolveActiveProyectoId($request);

        if ((int) $pedidoCliente->proyecto_id !== (int) $proyectoId) {
            abort(404);
        }

        $pedidoCliente->loadMissing(['presupuesto', 'albaran', 'albaranes', 'albaranesPivot']);

        $albaranesVinculados = collect()
            ->merge($pedidoCliente->albaran?->id ? collect([$pedidoCliente->albaran]) : collect())
            ->merge($pedidoCliente->albaranes ?? collect())
            ->merge($pedidoCliente->albaranesPivot ?? collect())
            ->filter(fn (AlbaranCliente $albaran) => (int) ($albaran->proyecto_id ?? $pedidoCliente->proyecto_id) === (int) $pedidoCliente->proyecto_id)
            ->unique('id')
            ->values();

        // LÓGICA DE BLOQUEO O LIBERACIÓN
        if ($albaranesVinculados->isNotEmpty()) {
            if ($pedidoCliente->bolsa) {
                // Si es un pedido bolsa, simplemente liberamos los albaranes del pivote
                // Dejando el dinero flotante nuevamente para otro pedido.
            } else {
                // Si es un pedido normal, mantenemos el bloqueo estricto
                return back()->with('error', 'Este pedido tiene ' . $albaranesVinculados->count() . ' albarán/es asignado/s y no puede ser borrado.');
            }
        }

        DB::transaction(function () use ($pedidoCliente) {
            if ($pedidoCliente->bolsa) {
                $pedidoCliente->albaranesPivot()->detach();
            }

            $presupuestoId = $pedidoCliente->presupuesto_id;

            if ($presupuestoId) {
                Presupuesto::query()
                    ->where('id', $presupuestoId)
                    ->update([
                        'estado' => 'pendiente',
                    ]);
            }

            $pedidoCliente->update([
                'presupuesto_id' => null,
                'albaran_id' => null,
            ]);

            $pedidoCliente->delete();
        });

        return redirect()->route('pedidos-clientes.index')->with('success', 'Pedido eliminado correctamente.');
    }

    private function renderPdfResponse(PedidoCliente $pedido, bool $download)
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        if ($pedido->archivo_pdf && $disk->exists($pedido->archivo_pdf)) {
            $path = $disk->path($pedido->archivo_pdf);
            $fileName = basename((string) $pedido->archivo_pdf);

            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $fileName . '"',
            ]);
        }

        $pdfContent = null;
        $fileName = ($pedido->numero_pedido ?: 'pedido-' . $pedido->id) . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pedidos-clientes.pdf', compact('pedido'));
            $pdfContent = $pdf->output();
        } elseif (class_exists(\Dompdf\Dompdf::class)) {
            $html = view('pedidos-clientes.pdf', compact('pedido'))->render();
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            $pdfContent = $dompdf->output();
        }

        if ($pdfContent === null) {
            abort(404);
        }

        if ($pedido->archivo_pdf) {
            $disk->put($pedido->archivo_pdf, $pdfContent);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $fileName . '"',
        ]);
    }

    private function syncArticulosFromLineas(int $proyectoId, array $lineas): void
    {
        foreach ($lineas as $linea) {
            if (!is_array($linea)) {
                continue;
            }

            $numeroReferencia = trim((string) ($linea['articulo'] ?? ''));
            $descripcion = trim((string) ($linea['descripcion'] ?? ''));

            if ($numeroReferencia === '' || $descripcion === '') {
                continue;
            }

            // Usamos firstOrCreate en lugar de updateOrCreate.
            // Esto añadirá el artículo al catálogo general SOLO si no existe. 
            // Si ya existe (ej. ID 1 o ID 2), lo dejará intacto y no aplastará tus datos globales.
            Articulo::firstOrCreate(
                [
                    'proyecto_id' => $proyectoId,
                    'numero_referencia' => $numeroReferencia,
                ],
                [
                    'descripcion' => $descripcion,
                    'cantidad' => round(max(0, (float) ($linea['cantidad'] ?? 0)), 2),
                    'medida' => trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? ''))) ?: null,
                    'precio_unitario' => round(max(0, (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0))), 4),
                    'margen' => round(max(0, (float) ($linea['margen'] ?? 0)), 2),
                    'total' => round(max(0, (float) ($linea['total'] ?? 0)), 4),
                    'facturado' => false,
                ]
            );
        }
    }

    public function actualizarDescripcion(Request $request, PedidoCliente $pedidoCliente)
    {
        $this->authorize('pedidos.manage');

        // 1. Validamos que nos llegue la descripción y el índice (la posición de la línea)
        $request->validate([
            'linea_index' => 'required|integer|min:0',
            'descripcion' => 'required|string',
        ]);

        $lineas = $pedidoCliente->lista_articulos;
        $index = (int) $request->linea_index;

        // 2. Comprobamos que el array existe y que esa posición concreta es real
        if (is_array($lineas) && isset($lineas[$index])) {
            $lineas[$index]['descripcion'] = $request->descripcion; 
            $pedidoCliente->lista_articulos = $lineas;
            $pedidoCliente->save();
        }

        return redirect()->back()->with('success', 'Descripción actualizada correctamente.');
    }

    public function lineasMultiplesBolsas(Request $request)
    {
        $this->authorize('pedidos.view');
        $proyectoId = $this->resolveActiveProyectoId($request);
        
        $ids = $request->input('pedido_ids', []);
        if (empty($ids)) {
            return response()->json([]);
        }

        $pedidos = PedidoCliente::where('proyecto_id', $proyectoId)
            ->whereIn('id', $ids)
            ->get();

        $service = new \App\Services\LineaBolsaService();
        $resultado = [];

        foreach ($pedidos as $pedido) {
            // Migración al vuelo legacy -> V3 (igual que tienes en tu método original)
            if ($pedido->bolsa && !$pedido->lineasBolsa()->exists() && !empty($pedido->lista_articulos)) {
                $service->sincronizarLineasDePartida($pedido, $pedido->lista_articulos);
            }

            $resultado[] = [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'lineas' => $service->lineasDisponiblesPorPartida($pedido),
            ];
        }

        return response()->json($resultado);
    }
}