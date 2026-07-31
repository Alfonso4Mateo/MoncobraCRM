<?php

namespace App\Http\Controllers;

use App\Models\AlbaranCliente;
use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\PedidoCliente;
use App\Models\Presupuesto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    private const MAX_LINEAS = 500;
    private const MAX_DESCRIPCION = 500;
    private const MAX_ARTICULO = 100;
    private const MAX_MEDIDA = 20;
    private const MAX_CANTIDAD = 1000000;
    private const MAX_PRECIO = 10000000;
    private const MAX_MARGEN = 1000;
    private const MAX_TOTAL = 1000000000;
    private const PEDIDO_CLIENTE_ESTADOS = [

        'pendiente' => 'Pendiente',
        'facturado' => 'Facturado',
        'facturado_parcial' => 'Facturado parcial',
    ];
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->indexClientes($request);
    }

    public function indexClientes(Request $request)
    {
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

            $pedidosQuery->where(function ($query) use ($like) {
                $query->where('numero_pedido', 'like', $like)
                    ->orWhereHas('cliente', function ($clienteQuery) use ($like) {
                        $clienteQuery->where('empresa_nombre', 'like', $like);
                    });
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

        $pedidos->getCollection()->transform(function (PedidoCliente $pedido) use ($presupuestos) {
            $pedido->ui_estado = $pedido->estado ?: 'pendiente';
            $pedido->ui_estado_label = self::PEDIDO_CLIENTE_ESTADOS[$pedido->ui_estado] ?? ucfirst(str_replace('_', ' ', $pedido->ui_estado));
            $pedido->ui_estado_class = match ($pedido->ui_estado) {
                'pendiente' => 'pedido-chip pedido-chip--pending',
                'facturado' => 'pedido-chip pedido-chip--paid',
                'facturado_parcial' => 'pedido-chip pedido-chip--partial',
                default => 'pedido-chip pedido-chip--pending',
            };
            $pedido->ui_total = (float) ($pedido->total ?? 0);
            $pedido->ui_presupuesto_numero = $pedido->presupuesto?->numero
                ?: $presupuestos->get($pedido->presupuesto_id)?->numero;

            $albaranesPedido = collect($pedido->albaranesPivot ?? [])
                ->merge($pedido->albaran?->id ? collect([$pedido->albaran]) : collect())
                ->merge($pedido->albaranes ?? collect())
                ->filter(fn (AlbaranCliente $albaran) => (int) ($albaran->proyecto_id ?? $pedido->proyecto_id) === (int) $pedido->proyecto_id)
                ->unique('id')
                ->values();

            $pedido->ui_albaranes_count = $albaranesPedido->count();
            $pedido->ui_albaran_numero = $pedido->ui_albaranes_count === 1
                ? (string) ($albaranesPedido->first()->numero ?? '')
                : null;
            $pedido->ui_albaran_id = $pedido->ui_albaranes_count === 1
                ? (int) ($albaranesPedido->first()->id ?? 0)
                : null;
            $pedido->ui_total_albaranes = round((float) $albaranesPedido->sum('total'), 2);

            $pedido->ui_total_facturaciones = round((float) ($pedido->facturaciones_sum ?? 0), 2);
            $pedido->ui_pendiente = max(0, round($pedido->ui_total - $pedido->ui_total_albaranes - $pedido->ui_total_facturaciones, 2));

            return $pedido;
        });

        return view('pedidos-clientes.index', [
            'pedidos' => $pedidos,
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
        $proyectoId = $this->resolveActiveProyectoId($request);
        $presupuestoEstadosPermitidos = ['pendiente'];

        $clientes = Cliente::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('empresa_nombre')
            ->get();

        $presupuestos = Presupuesto::query()
            ->where('proyecto_id', $proyectoId)
            ->whereIn('estado', $presupuestoEstadosPermitidos)
            ->whereDoesntHave('pedidoCliente')
            ->with('cliente')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $presupuestoSeleccionadoId = (int) $request->query('presupuesto_id', 0);
        $presupuestoSeleccionado = $presupuestoSeleccionadoId > 0
            ? $presupuestos->firstWhere('id', $presupuestoSeleccionadoId)
            : null;

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

        $lineasInicialesRaw = is_array($presupuestoSeleccionado?->lista_articulos)
            ? $presupuestoSeleccionado->lista_articulos
            : [];

        $lineasIniciales = $this->normalizePedidoLineas($lineasInicialesRaw);

        $baseImponible = round((float) ($presupuestoSeleccionado?->total ?? 0), 2);
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
            'baseImponible',
            'totalPedido'
        ) + [
            'titulo' => 'Crear Nuevo Pedido',
            'breadcrumb' => 'Nuevo Pedido',
        ]);
    }

    public function storeCliente(Request $request)
    {
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
                Rule::exists('presupuestos', 'id')->where(function ($query) use ($proyectoId) {
                    $query->where('proyecto_id', $proyectoId)
                        ->where('estado', 'pendiente');
                }),
                Rule::unique('pedidos_clientes', 'presupuesto_id')->where(function ($query) use ($proyectoId) {
                    $query->where('proyecto_id', $proyectoId);
                }),
            ],
            'estado' => 'nullable|string|max:30',
            'bolsa' => 'nullable|boolean',
            'total' => 'required_if:bolsa,1|numeric|gt:0|max:' . self::MAX_TOTAL,
            'lista_articulos' => 'nullable|json',
        ]);

        $bolsa = (bool) ($validated['bolsa'] ?? false);

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

        if ($bolsa) {
            $total = round((float) ($validated['total'] ?? 0), 2);
        } else {
            $lineas = json_decode((string) ($validated['lista_articulos'] ?? '[]'), true);
            $lineas = is_array($lineas) ? $lineas : [];
            $lineasFiltradas = collect($lineas)
                ->filter(fn ($linea) => is_array($linea) && !empty(trim((string) ($linea['descripcion'] ?? ''))))
                ->values()
                ->all();

            $this->validateLineasPayload($lineasFiltradas);

            $lineas = collect($lineasFiltradas)
            // 1. Añadimos ", int $index" como segundo parámetro
            ->map(function (array $linea, int $index) {
                $cantidad = max(0, (float) ($linea['cantidad'] ?? 0));
                $precioUnitario = max(0, (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0)));
                $margen = max(0, (float) ($linea['margen'] ?? 0));

                $total = $cantidad * $precioUnitario * (1 + ($margen / 100));

                $medida = trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? '')));
                $medida = $medida !== '' ? $medida : null;

                // 2. Lógica del contador: 
                // Si el usuario no escribió un código de artículo, le asignamos la línea (1, 2, 3...)
                $articuloOriginal = trim((string) ($linea['articulo'] ?? ''));
                $articuloFinal = $articuloOriginal === '' ? (string) ($index + 1) : $articuloOriginal;

                return [
                    'articulo' => $articuloFinal, // 3. Guardamos el artículo generado
                    'descripcion' => trim((string) ($linea['descripcion'] ?? '')),
                    'cantidad' => round($cantidad, 2),
                    'medida' => $medida,
                    'precio_unitario' => round($precioUnitario, 2),
                    'margen' => round($margen, 2),
                    'total' => round($total, 2),
                ];
            })
            ->values()
            ->all();

            $total = (float) collect($lineas)->sum('total');
        }

        DB::transaction(function () use ($validated, $proyectoId, $presupuestoId, $total, $lineas, $bolsa) {
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

            PedidoCliente::create([
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
                'total' => round($total, 2),
                'lista_articulos' => $lineas ?: null,
            ]);

            if ($presupuestoId !== null) {
                Presupuesto::query()
                    ->where('proyecto_id', $proyectoId)
                    ->where('id', $presupuestoId)
                    ->update([
                        'estado' => 'aceptado',
                    ]);
            }

            if (!$bolsa) {
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return view('pedidos.show', [
            'id' => $id,
            'titulo' => 'Detalle del Pedido',
            'breadcrumb' => 'Ver Pedido'
        ]);
    }

    public function showCliente(PedidoCliente $pedidoCliente)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ($pedidoCliente->proyecto_id && (int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return view('pedidos.show-cliente', [
            'pedidoCliente' => $pedidoCliente,
            'titulo' => 'Detalle del Pedido de Cliente',
            'breadcrumb' => 'Pedido de Cliente',
        ]);
    }

    public function viewPdf(PedidoCliente $pedidoCliente)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return $this->renderPdfResponse($pedidoCliente, false);
    }

    public function downloadPdf(PedidoCliente $pedidoCliente)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return $this->renderPdfResponse($pedidoCliente, true);
    }

    public function preview(PedidoCliente $pedidoCliente)
    {
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
        // Try to get proyecto from session, fallback to pedido's proyecto
        $sessionProyectoId = (int) request()->session()->get('active_proyecto_id');
        $proyectoId = $sessionProyectoId > 0 ? $sessionProyectoId : (int) ($pedidoCliente->proyecto_id ?? 0);

        // Validate that user has access to this proyecto
        $user = request()->user();
        if (!$user) {
            abort(401);
        }

        $hasAccess = $user->role === 'superadmin'
            || $user->proyectos()->where('proyectos.id', $proyectoId)->exists();

        if (!$hasAccess) {
            abort(403);
        }

        // Verify pedido belongs to the proyectoId
        if ($pedidoCliente->proyecto_id && (int) $pedidoCliente->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $albaranes = collect($pedidoCliente->albaranesPivot)
            ->merge($pedidoCliente->albaran?->id ? collect([$pedidoCliente->albaran->loadMissing('cliente')]) : collect())
            ->merge($pedidoCliente->albaranes ?? collect())
            ->filter(fn (AlbaranCliente $albaran) => (int) ($albaran->proyecto_id ?? $pedidoCliente->proyecto_id) === (int) $proyectoId)
            ->unique('id')
            ->sortByDesc(fn (AlbaranCliente $albaran) => sprintf('%s-%06d', optional($albaran->fecha)->format('Y-m-d') ?? '0000-00-00', (int) $albaran->id))
            ->values();

        $totalAlbaranes = round((float) $albaranes->sum('total'), 2);
        $totalPedido = round((float) ($pedidoCliente->total ?? 0), 2);

        $perPage = 10;
        $currentPage = max(1, (int) request()->query('page', 1));
        $pagedItems = $albaranes->forPage($currentPage, $perPage)->values();
        $albaranes = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedItems,
            $albaranes->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // 1. Obtener facturaciones manuales (cuotas)
        $facturaciones = $pedidoCliente->facturacionesManuales()->orderBy('created_at', 'desc')->get();
        $totalFacturadoDirecto = round((float) $facturaciones->sum('importe'), 2);

        // 2. Calcular el pendiente final unificado (Pedido - Albaranes - Facturación Directa)
        $pendienteFacturar = round(max(0, $totalPedido - $totalAlbaranes - $totalFacturadoDirecto), 2);

        // 3. Único retorno con todas las variables empaquetadas correctamente
        return view('pedidos-clientes.albaranes', [
            'pedidoCliente' => $pedidoCliente,
            'albaranes' => $albaranes,
            'facturaciones' => $facturaciones,
            'totalPedido' => $totalPedido,
            'totalAlbaranes' => $totalAlbaranes,
            'pendienteFacturar' => $pendienteFacturar,
            'titulo' => 'Albaranes del pedido',
            'breadcrumb' => 'Albaranes del pedido',
        ]);
    }


    public function facturarCuota(Request $request, PedidoCliente $pedidoCliente)
    {
        // 1. Validamos que los datos lleguen correctamente
        $request->validate([
            'importe' => 'required|numeric|min:0.01',
            'concepto' => 'required|string|max:2000',
        ]);

        // 2. Guardamos en la tabla relacionada
        $pedidoCliente->facturacionesManuales()->create([
            'importe' => $request->importe,
            'concepto' => $request->concepto,
        ]);

        // 3. Redirigimos de vuelta a la misma pantalla con un mensaje de éxito
        return redirect()->route('pedidos-clientes.albaranes', $pedidoCliente)
                        ->with('success', 'Facturación añadida correctamente.');
    }

    public function destroyFacturacion(Request $request, $id)
    {
        // Buscamos la cuota por su ID y la eliminamos
        $facturacion = \App\Models\FacturacionManual::findOrFail($id);
        $facturacion->delete();

        return redirect()->back()->with('success', 'Cuota de facturación eliminada correctamente. Los totales se han recalculado.');
    }
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return view('pedidos.edit', [
            'id' => $id,
            'titulo' => 'Editar Pedido',
            'breadcrumb' => 'Editar Pedido'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Update estado of a pedido cliente
     */
    public function updateEstado(Request $request, PedidoCliente $pedidoCliente)
    {
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
        //
    }

    public function destroyCliente(Request $request, PedidoCliente $pedidoCliente)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

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

        if ($albaranesVinculados->isNotEmpty()) {
            return back()->with('error', 'Este pedido tiene ' . $albaranesVinculados->count() . ' albarán/es asignado/s y no puede ser borrado.');
        }

        DB::transaction(function () use ($pedidoCliente) {
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

        return redirect()->route('pedidos-clientes.index')->with('success', 'Pedido eliminado correctamente. El presupuesto volvió a estado pendiente.');
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

            Articulo::updateOrCreate(
                [
                    'proyecto_id' => $proyectoId,
                    'numero_referencia' => $numeroReferencia,
                ],
                [
                    'descripcion' => $descripcion,
                    'cantidad' => round(max(0, (float) ($linea['cantidad'] ?? 0)), 2),
                    'medida' => trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? ''))) ?: null,
                    'precio_unitario' => round(max(0, (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0))), 2),
                    'margen' => round(max(0, (float) ($linea['margen'] ?? 0)), 2),
                    'total' => round(max(0, (float) ($linea['total'] ?? 0)), 2),
                    'facturado' => false,
                ]
            );
        }
    }

    public function actualizarDescripcion(Request $request, PedidoCliente $pedidoCliente)
    {
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
}
