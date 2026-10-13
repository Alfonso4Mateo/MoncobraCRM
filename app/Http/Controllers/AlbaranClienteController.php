<?php

namespace App\Http\Controllers;

use App\Models\AlbaranCliente;
use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\LineaBolsa;
use App\Models\PedidoCliente;
use App\Models\Presupuesto;
use App\Services\DocumentLineNormalizer;
use App\Services\LineaBolsaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AlbaranClienteController extends Controller
{
    private const MAX_LINEAS = 5000;
    private const MAX_DESCRIPCION = 500000;
    private const MAX_ARTICULO = 2000;
    private const MAX_MEDIDA = 200;
    private const MAX_CANTIDAD = 1000000;
    private const MAX_PRECIO = 10000000;
    private const MAX_MARGEN = 1000;
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.view');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);

        $buscar = trim((string) $request->query('buscar', ''));
        $desde = trim((string) $request->query('desde', ''));
        $hasta = trim((string) $request->query('hasta', ''));
        $estado = trim((string) $request->query('estado', '')); 
        $bolsaActual = $request->boolean('bolsa');

        $albaranesQuery = AlbaranCliente::query()
            ->with(['cliente', 'pedidosClientes'])
            ->where('proyecto_id', $proyectoId)
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        if ($buscar !== '') {
            $buscarTerms = collect(preg_split('/[\s,;]+/', $buscar))
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->values();

            if ($buscarTerms->isNotEmpty()) {
                $albaranesQuery->where(function ($query) use ($buscarTerms) {
                    foreach ($buscarTerms as $term) {
                        $normalizedTerm = trim((string) $term);

                        $query->orWhere(function ($subQuery) use ($normalizedTerm) {
                            $subQuery->where('numero', 'like', '%' . $normalizedTerm . '%')
                                ->orWhere('documento', 'like', '%' . $normalizedTerm . '%')
                                ->orWhere('ot', 'like', '%' . $normalizedTerm . '%')
                                ->orWhere('pedido_cliente', 'like', '%' . $normalizedTerm . '%')
                                ->orWhere('titulo', 'like', '%' . $normalizedTerm . '%')
                                ->orWhere('estado', 'like', '%' . $normalizedTerm . '%')
                                ->orWhereHas('cliente', function ($clienteQuery) use ($normalizedTerm) {
                                    $clienteQuery->where('empresa_nombre', 'like', '%' . $normalizedTerm . '%');
                                });

                            if (is_numeric($normalizedTerm)) {
                                $subQuery->orWhere('total', '=', (float) $normalizedTerm)
                                    ->orWhere('total', 'like', '%' . $normalizedTerm . '%');
                            }

                            try {
                                $fecha = Carbon::parse($normalizedTerm)->toDateString();
                                $subQuery->orWhereDate('fecha', $fecha);
                            } catch (\Throwable $exception) {
                                // Ignore non-date search terms.
                            }
                        });
                    }
                });
            }
        }

        if ($desde !== '') {
            try {
                $desdeDate = Carbon::parse($desde)->toDateString();
                $albaranesQuery->whereDate('fecha', '>=', $desdeDate);
            } catch (\Throwable $exception) {
                // Ignore invalid dates and keep the query usable.
            }
        }

        if ($hasta !== '') {
            try {
                $hastaDate = Carbon::parse($hasta)->toDateString();
                $albaranesQuery->whereDate('fecha', '<=', $hastaDate);
            } catch (\Throwable $exception) {
                // Ignore invalid dates
            }
        }

        if ($estado !== '') {
            $albaranesQuery->where('estado', $estado);
        }

        if ($bolsaActual) {
            $albaranesQuery->whereHas('pedidosClientes', function ($query) {
                $query->where('bolsa', true);
            });
        }

        $albaranes = $albaranesQuery
            ->paginate(8)
            ->withQueryString();

        $presupuestos = Presupuesto::query()
            ->where('proyecto_id', $proyectoId)
            ->whereIn('numero', $albaranes->getCollection()->pluck('documento')->filter()->map(fn ($item) => trim((string) $item))->unique())
            ->get(['id', 'numero', 'total'])
            ->keyBy(fn (Presupuesto $presupuesto) => trim((string) $presupuesto->numero));

        $pedidos = PedidoCliente::query()
            ->where('proyecto_id', $proyectoId)
            ->whereIn('numero_pedido', $albaranes->getCollection()->pluck('pedido_cliente')->filter()->map(fn ($item) => trim((string) $item))->unique())
            ->with(['presupuesto:id,numero'])
            ->withCount('albaranes')
            ->get(['id', 'numero_pedido', 'presupuesto_id'])
            ->keyBy(fn (PedidoCliente $pedido) => trim((string) $pedido->numero_pedido));

        $albaranes->getCollection()->transform(function (AlbaranCliente $albaran) use ($presupuestos, $pedidos) {
            $presupuestoNumero = trim((string) $albaran->documento);
            $pedidoNumero = trim((string) $albaran->pedido_cliente);

            $presupuestoRelacionado = $presupuestoNumero !== '' ? $presupuestos->get($presupuestoNumero) : null;
            $pedidoRelacionado = $pedidoNumero !== '' ? $pedidos->get($pedidoNumero) : null;
            $totalAlbaran = round((float) ($albaran->total ?? 0), 4);

            $pedidoPresupuesto = $pedidoRelacionado?->presupuesto;

            $albaran->ui_presupuesto_id = $pedidoPresupuesto?->id
                ?: $presupuestoRelacionado?->id;
            $albaran->ui_presupuesto_numero = $pedidoPresupuesto?->numero
                ?: ($presupuestoRelacionado?->numero ?? null);
            $albaran->ui_total = $totalAlbaran > 0
                ? $totalAlbaran
                : ($presupuestoRelacionado ? (float) $presupuestoRelacionado->total : 0);
            $albaran->ui_pedido_id = $pedidoRelacionado?->id;
            $albaran->ui_pedido_numero = $pedidoNumero;
            $albaran->ui_pedido_albaranes_count = (int) ($pedidoRelacionado?->albaranes_count ?? 0);
            $albaran->estado = $albaran->estado ?: 'pendiente';
            $albaran->ui_pedidos_vinculados = $albaran->pedidosClientes->map(function ($pedido) use ($totalAlbaran) {
                $imputado = isset($pedido->pivot) ? (float) $pedido->pivot->importe_imputado : 0.0;
                // ESCUDO LEGACY: Si es antiguo y marca 0, asume el total
                if ($imputado <= 0.001) {
                    $imputado = $totalAlbaran;
                }
                return [
                    'id' => $pedido->id,
                    'numero' => $pedido->numero_pedido,
                    'imputado' => $imputado
                ];
            });

            $sumaImputada = 0.0;
            if ($albaran->pedidosClientes->isNotEmpty()) {
                $sumaImputada = (float) $albaran->pedidosClientes->sum('pivot.importe_imputado');
                // ESCUDO LEGACY
                if ($sumaImputada <= 0.001) {
                    $sumaImputada = $totalAlbaran;
                }
            } elseif (trim((string) $pedidoNumero) !== '') {
                $sumaImputada = $totalAlbaran;
            }
            $albaran->ui_excedente = max(0, round($totalAlbaran - $sumaImputada, 4));

            return $albaran;
        });

        $baseStatsQuery = AlbaranCliente::query()->where('proyecto_id', $proyectoId);
        $totalAlbaranes = (clone $baseStatsQuery)->count();
        $pendientesEntrega = (clone $baseStatsQuery)->where('estado', 'pendiente')->count();
        $entregadosHoy = (clone $baseStatsQuery)->where('estado', 'entregado')->whereDate('updated_at', now()->toDateString())->count();

        $inicioMesActual = now()->copy()->startOfMonth();
        $inicioMesAnterior = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $finMesAnterior = now()->copy()->startOfMonth()->subDay();

        $albaranesMesActual = (clone $baseStatsQuery)
            ->whereBetween('created_at', [$inicioMesActual, now()])
            ->count();

        $albaranesMesAnterior = (clone $baseStatsQuery)
            ->whereBetween('created_at', [$inicioMesAnterior, $finMesAnterior])
            ->count();

        $variacionMensual = $albaranesMesAnterior > 0
            ? round((($albaranesMesActual - $albaranesMesAnterior) / $albaranesMesAnterior) * 100, 1)
            : ($albaranesMesActual > 0 ? 100.0 : 0.0);

        $entregadosAyer = (clone $baseStatsQuery)
            ->where('estado', 'entregado')
            ->whereDate('updated_at', now()->copy()->subDay()->toDateString())
            ->count();

        $variacionEntregadosHoy = $entregadosAyer > 0
            ? round((($entregadosHoy - $entregadosAyer) / $entregadosAyer) * 100, 1)
            : ($entregadosHoy > 0 ? 100.0 : 0.0);

        return view('albaranes.index', compact(
            'albaranes',
            'buscar',
            'desde',
            'hasta',
            'estado',
            'totalAlbaranes',
            'pendientesEntrega',
            'entregadosHoy',
            'variacionMensual',
            'variacionEntregadosHoy',
            'bolsaActual'
        ));
    }

    public function create(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.manage');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        $clientes = Cliente::where('proyecto_id', $proyectoId)->orderBy('empresa_nombre')->get();
        $numeroAlbaranAuto = $this->resolveNextAlbaranClienteNumber($proyectoId)['numero'];
        $pedidoContext = $this->resolvePedidoContext($request, $proyectoId);
        $pedidosClientes = PedidoCliente::query()
            ->with('cliente' , 'bolsaGlobal')
            ->where('proyecto_id', $proyectoId)
            ->where(function ($query) use ($pedidoContext) {
                $query->whereNull('estado')
                    ->orWhere('estado', '<>', 'facturado');

                if ($pedidoContext) {
                    $query->orWhere('id', $pedidoContext->id);
                }
            })
            ->orderByDesc('id')
            ->get(['id', 'numero_pedido', 'id_cliente', 'ot', 'bolsa' , 'bolsa_id']);
            
        $pedidoBolsa = (bool) ($pedidoContext?->bolsa ?? false);
        $pedidoModoRestringido = false; 
        $lineasIniciales = []; 
        $pedidoPendienteFacturar = $pedidoBolsa && $pedidoContext
            ? $this->calculatePedidoPendienteFacturar($pedidoContext, $proyectoId)
            : null;
        $pedidoDefaults = [
            'cliente_id' => $pedidoContext?->id_cliente,
            'pedido_cliente' => $pedidoContext?->numero_pedido,
            'ot' => $pedidoContext?->ot,
            'pedido_id' => $pedidoContext?->id,
        ];

        return view('albaranes.create', compact(
            'clientes',
            'pedidosClientes',
            'pedidoContext',
            'pedidoBolsa',
            'pedidoModoRestringido',
            'pedidoPendienteFacturar',
            'lineasIniciales',
            'pedidoDefaults',
            'numeroAlbaranAuto'
        ));
    }

    public function store(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.manage');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);

        $request->merge([
            'pedidos_cliente' => array_values(array_filter(
                (array) $request->input('pedidos_cliente', []),
                fn ($v) => trim((string) $v) !== ''
            )),
        ]);

        $validated = $request->validate([
            'numero' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('albaranes_clientes', 'numero')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)->whereNull('deleted_at')),
            ],
            'fecha' => 'required|date',
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)->whereNull('deleted_at')),
            ],
            'ot' => 'nullable|string|max:255',
            'pedidos_cliente' => 'nullable|array',
            'pedidos_cliente.*' => [
                'string', 'max:255',
                Rule::exists('pedidos_clientes', 'numero_pedido')
                    ->where(fn ($q) => $q->where('proyecto_id', $proyectoId)->whereNull('deleted_at'))
            ],
            'titulo' => 'nullable|string|max:255',
            'lineas_json' => 'nullable|json',
            'estado' => ['nullable', Rule::in(['pendiente', 'recibido', 'facturado', 'facturado_parcial', 'cancelado'])],
            'archivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $pedidoContext = $this->resolvePedidoContext($request, $proyectoId);
        $pedidoBolsa = (bool) ($pedidoContext?->bolsa ?? false);

        $lineasRaw = $this->decodeLineasJson($validated['lineas_json'] ?? '[]');
        $this->validateLineasPayload($lineasRaw);
        $lineas = $this->normalizeLineas($validated['lineas_json'] ?? '[]', $lineasRaw);
        $this->assertLimitesLineaBolsa($lineas); 
        $totalAlbaran = round((float) collect($lineas)->sum(fn (array $linea) => (float) ($linea['total'] ?? 0)), 4);

        // --- PROTECCIÓN BACKEND CONTRA DOBLE CLICK (Double Submit) ---
        $dobleEnvio = AlbaranCliente::where('proyecto_id', $proyectoId)
            ->where('cliente_id', $validated['cliente_id'])
            ->where('total', $totalAlbaran)
            ->where('created_at', '>=', Carbon::now()->subSeconds(10))
            ->first();

        if ($dobleEnvio) {
            return redirect()->route('albaranes.index')->with('success', 'Albarán procesado correctamente');
        }

        if ($request->hasFile('archivo_pdf')) {
            $validated['archivo_pdf'] = $request->file('archivo_pdf')->store('albaranes', 'public');
        }

        $pedidosSeleccionados = $validated['pedidos_cliente'] ?? [];
        $validated['pedido_cliente'] = !empty($pedidosSeleccionados) ? $pedidosSeleccionados[0] : null;
        unset($validated['pedidos_cliente']);

        // --- INICIO CUARTO CANDADO: Bloqueo de bolsas incompatibles ---
        if (!empty($pedidosSeleccionados)) {
            $pedidosAVincular = PedidoCliente::with('bolsaGlobal')->where('proyecto_id', $proyectoId)->whereIn('numero_pedido', $pedidosSeleccionados)->get();
            $tiposBolsa = $pedidosAVincular->filter(fn($p) => $p->bolsa)->map(fn($p) => $p->bolsaGlobal->tipo_bolsa ?? 'bolsa_suelta')->unique();
            
            if ($tiposBolsa->count() > 1) {
                throw ValidationException::withMessages([
                    'pedidos_cliente' => 'Bloqueo de Seguridad: No puedes mezclar en un mismo albarán partidas de bolsas incompatibles. Estás intentando cruzar bolsas de tipo: ' . $tiposBolsa->implode(', ')
                ]);
            }

            // --- QUINTO CANDADO: el tope de la bolsa global también se respeta al facturar ---
            $this->assertLimitesBolsaGlobal($pedidosAVincular, $lineas);
            // --- FIN QUINTO CANDADO ---
        }
        // --- FIN CUARTO CANDADO ---

        $excedenteFlotante = 0.0;

        DB::transaction(function () use (&$excedenteFlotante, $validated, $proyectoId, $lineas, $totalAlbaran, $pedidosSeleccionados) {
            
            $correlativoActual = $this->resolveNextAlbaranClienteNumber($proyectoId);
            $numeroManual = trim((string) ($validated['numero'] ?? ''));
            $numeroFinal = $numeroManual !== '' ? $numeroManual : $correlativoActual['numero'];
            $manualCorrelativo = $this->extractCorrelativoFromNumero($correlativoActual['formato'], $numeroFinal);
            
            $validated['numero'] = $numeroFinal;
            $validated['proyecto_id'] = $proyectoId;
            $validated['documento'] = 'Albarán';
            $validated['estado'] = $validated['estado'] ?? 'pendiente';
            $validated['lista_articulos'] = $lineas === [] ? null : $lineas;
            $validated['total'] = $totalAlbaran;
            unset($validated['lineas_json']);

            $albaran = AlbaranCliente::create($validated);

            if ($manualCorrelativo !== null) {
                $nextValue = max($correlativoActual['correlativo'], $manualCorrelativo + 1);
            } else {
                $nextValue = $correlativoActual['correlativo'];
            }
            
            $this->setContadorValue($proyectoId, 'albaranes_next_correlativo', $nextValue);
            $this->setCorrelativoFormato($proyectoId, $correlativoActual['formato'], 'albaranes_formato_correlativo');
            
            // --- MOTOR UNIFICADO MULTI-BOLSA ---
            $excedenteFlotante = $this->syncPedidoClienteLink($albaran, $proyectoId, $pedidosSeleccionados, $lineas);
            $excedenteFlotante += $this->registrarConsumosBolsa($albaran, $lineas, $proyectoId); 

            // --- BLOQUEO DURO DE SALDO: Romper la transacción si falta dinero ---
            if ($excedenteFlotante > 0.001) {
                throw ValidationException::withMessages([
                    'lineas_json' => 'Bloqueo de Seguridad: El importe a facturar supera el saldo disponible. Faltan ' . number_format($excedenteFlotante, 2, ',', '.') . ' € para cubrir este albarán. Reduce las cantidades o vincula más partidas con saldo.'
                ]);
            }
            // --- FIN BLOQUEO DURO ---

            $this->syncPedidoEstadoFromAlbaran($albaran, $proyectoId, $lineas);

            $pedido = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);
            if ($pedido && ! (bool) ($pedido->bolsa ?? false)) {
                $this->adjustPedidoLineasFromAlbaran($pedido, $lineas, -1);
            }

            // Restar stock de artículos
            $consumos = collect($lineas)
                ->filter(fn ($linea) => is_array($linea) && (int) ($linea['articulo_id'] ?? 0) > 0)
                ->groupBy(fn ($linea) => (int) ($linea['articulo_id'] ?? 0))
                ->map(fn ($items) => round($items->sum(fn ($linea) => (float) ($linea['cantidad'] ?? 0)), 2));

            if ($consumos->isNotEmpty()) {
                $articulos = Articulo::query()
                    ->where('proyecto_id', $proyectoId)
                    ->whereIn('id', $consumos->keys())
                    ->lockForUpdate() 
                    ->get();

                foreach ($articulos as $articulo) {
                    $consumo = (float) ($consumos[$articulo->id] ?? 0);
                    if ($consumo <= 0) {
                        continue;
                    }

                    $cantidadActual = (float) ($articulo->cantidad ?? 0);
                    $restante = round(max(0, $cantidadActual - $consumo), 2);
                    $precioUnitario = (float) ($articulo->precio_unitario ?? 0);
                    $margen = (float) ($articulo->margen ?? 0);

                    $articulo->cantidad = $restante;
                    $articulo->facturado = $restante <= 0;
                    $articulo->total = round($restante * $precioUnitario * (1 + ($margen / 100)), 4);
                    $articulo->save();
                }
            }
        });

        return redirect()->route('albaranes.index')->with('success', 'Albarán procesado correctamente');
    }

    public function edit(AlbaranCliente $albaran)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.manage');

        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $albaran->load('pedidosClientes');

        $clientes = Cliente::where('proyecto_id', $proyectoId)->orderBy('empresa_nombre')->get();

        $pedidosVinculadosIds = $albaran->pedidosClientes->pluck('id');

        $pedidosClientes = PedidoCliente::query()
            ->with('cliente' , 'bolsaGlobal')
            ->where('proyecto_id', $proyectoId)
            ->where(function ($query) use ($pedidosVinculadosIds) {
                $query->whereNull('estado')
                    ->orWhere('estado', '<>', 'facturado')
                    ->orWhereIn('id', $pedidosVinculadosIds);
            })
            ->orderByDesc('id')
            ->get(['id', 'numero_pedido', 'id_cliente', 'ot', 'bolsa' , 'bolsa_id']);

        if ($albaran->cliente && !$clientes->contains('id', $albaran->cliente_id)) {
            $clientes->prepend($albaran->cliente);
        }

        return view('albaranes.edit', compact('albaran', 'clientes', 'pedidosClientes'));
    }

    public function update(Request $request, AlbaranCliente $albaran)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.manage');
        abort_if($albaran->estado === 'facturado', 403, 'No se pueden modificar albaranes facturados.');

        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        // --- PROTECCIÓN BACKEND CONTRA DOBLE CLICK EN ACTUALIZAR ---
        if ($albaran->updated_at && $albaran->updated_at->diffInSeconds(now()) < 5) {
            $returnTo = trim((string) ($request->input('return_to', '')));
            
            // CORRECCIÓN DE SEGURIDAD AQUÍ (1)
            $hostRetorno = parse_url($returnTo, PHP_URL_HOST);
            $hostApp = parse_url(config('app.url'), PHP_URL_HOST);

            if ($returnTo !== '' && $hostRetorno === $hostApp) {
                return redirect($returnTo)->with('success', 'Albarán actualizado correctamente.');
            }
            return redirect()->route('albaranes.index')->with('success', 'Albarán actualizado correctamente.');
        }

        $pedidoAnterior = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);
        $lineasAnteriores = is_array($albaran->lista_articulos) ? $albaran->lista_articulos : [];

        $request->merge([
            'pedidos_cliente' => array_values(array_filter(
                (array) $request->input('pedidos_cliente', []),
                fn ($v) => trim((string) $v) !== ''
            )),
        ]);

        $validated = $request->validate([
            'numero' => [
                'required', 'string', 'max:80',
                Rule::unique('albaranes_clientes', 'numero')
                    ->where(fn ($q) => $q->where('proyecto_id', $proyectoId)->whereNull('deleted_at'))
                    ->ignore($albaran->id),
            ],
            // ... (resto de tus validaciones)
            'fecha' => 'required|date',
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'ot' => 'nullable|string|max:255',
            'pedidos_cliente' => 'nullable|array',
            'pedidos_cliente.*' => [
                'string', 'max:255',
                Rule::exists('pedidos_clientes', 'numero_pedido')
                    ->where(fn ($q) => $q->where('proyecto_id', $proyectoId)->whereNull('deleted_at'))
            ],
            'titulo' => 'nullable|string|max:255',
            'estado' => ['nullable', Rule::in(['pendiente', 'recibido', 'facturado', 'facturado_parcial', 'cancelado', 'entregado'])],
            'lineas_json' => 'nullable|json',
            'return_to' => 'nullable|string|max:2048',
        ]);

        // ... (todo el bloque de lógica central y base de datos se mantiene igual)
        
        $lineasRaw = $this->decodeLineasJson($validated['lineas_json'] ?? '[]');
        $this->validateLineasPayload($lineasRaw);
        $lineas = $this->normalizeLineas($validated['lineas_json'] ?? '[]', $lineasRaw);
        $this->assertLimitesLineaBolsa($lineas, (int) $albaran->id);
        $total = collect($lineas)->sum(fn (array $linea) => (float) ($linea['total'] ?? 0));

        $pedidosSeleccionados = $validated['pedidos_cliente'] ?? [];
        $validated['pedido_cliente'] = !empty($pedidosSeleccionados) ? $pedidosSeleccionados[0] : null;
        unset($validated['pedidos_cliente']);

        // --- INICIO CUARTO CANDADO: Bloqueo de bolsas incompatibles ---
        if (!empty($pedidosSeleccionados)) {
            $pedidosAVincular = PedidoCliente::with('bolsaGlobal')->where('proyecto_id', $proyectoId)->whereIn('numero_pedido', $pedidosSeleccionados)->get();
            $tiposBolsa = $pedidosAVincular->filter(fn($p) => $p->bolsa)->map(fn($p) => $p->bolsaGlobal->tipo_bolsa ?? 'bolsa_suelta')->unique();
            
            if ($tiposBolsa->count() > 1) {
                throw ValidationException::withMessages([
                    'pedidos_cliente' => 'Bloqueo de Seguridad: No puedes mezclar en un mismo albarán partidas de bolsas incompatibles. Estás intentando cruzar bolsas de tipo: ' . $tiposBolsa->implode(', ')
                ]);
            }

            // --- QUINTO CANDADO: el tope de la bolsa global también se respeta al facturar ---
            $this->assertLimitesBolsaGlobal($pedidosAVincular, $lineas, (int) $albaran->id);
            // --- FIN QUINTO CANDADO ---
        }
        // --- FIN CUARTO CANDADO ---

        $excedenteFlotante = 0.0;

        DB::transaction(function () use (&$excedenteFlotante, $albaran, $validated, $lineas, $total, $proyectoId, $lineasAnteriores, $pedidosSeleccionados) {
            
            $pedidosAnteriores = $albaran->pedidosClientes()->get();
            $pedidoPrimarioAnterior = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);
            if ($pedidoPrimarioAnterior && !$pedidosAnteriores->contains('id', $pedidoPrimarioAnterior->id)) {
                $pedidosAnteriores->push($pedidoPrimarioAnterior);
            }

            // Restaurar stock
            $this->adjustArticulosStock($lineasAnteriores, $proyectoId, 1);
            
            // ¡NUEVO! Limpiamos el rastro anterior PERFECTAMENTE antes de volver a registrar
            $this->limpiarRastrosBolsa($albaran);
            
            $albaran->update([
                'documento' => $validated['documento'],
                'numero' => $validated['numero'],
                'fecha' => $validated['fecha'],
                'cliente_id' => $validated['cliente_id'],
                'ot' => $validated['ot'] ?? null,
                'pedido_cliente' => $validated['pedido_cliente'] ?? null,
                'titulo' => $validated['titulo'] ?? null,
                'estado' => $validated['estado'],
                'lista_articulos' => $lineas === [] ? null : $lineas,
                'total' => round($total, 4),
            ]);

            // Consumir stock
            $this->adjustArticulosStock($lineas, $proyectoId, -1);

            // --- MOTOR UNIFICADO MULTI-BOLSA ---
            $excedenteFlotante = $this->syncPedidoClienteLink($albaran, $proyectoId, $pedidosSeleccionados, $lineas);
            $excedenteFlotante += $this->registrarConsumosBolsa($albaran, $lineas, $proyectoId);

            // --- BLOQUEO DURO DE SALDO: Romper la transacción si falta dinero ---
            if ($excedenteFlotante > 0.001) {
                throw ValidationException::withMessages([
                    'lineas_json' => 'Bloqueo de Seguridad: El importe a facturar supera el saldo disponible. Faltan ' . number_format($excedenteFlotante, 2, ',', '.') . ' € para cubrir este albarán. Reduce las cantidades o vincula más partidas con saldo.'
                ]);
            }
            // --- FIN BLOQUEO DURO ---

            $pedidoActual = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);

            foreach ($pedidosAnteriores as $pAnt) {
                if (! (bool) ($pAnt->bolsa ?? false)) {
                    $this->adjustPedidoLineasFromAlbaran($pAnt, $lineasAnteriores, 1);
                }
            }

            if ($pedidoActual && ! (bool) ($pedidoActual->bolsa ?? false)) {
                $this->adjustPedidoLineasFromAlbaran($pedidoActual, $lineas, -1);
            }

            $pedidosARecalcular = $pedidosAnteriores->push($pedidoActual)->filter()->unique('id')->all();
            $this->recalculatePedidosEstadoFromAlbaran($proyectoId, $pedidosARecalcular, $albaran);
        });

        try {
            $albaran->refresh();
            $identificador = $albaran->numero ?: $albaran->id;
            $filePathWith = "albaranes/{$identificador}.pdf";
            $filePathWithout = "albaranes/{$identificador}-sin-presupuesto.pdf";

            $presupuesto = Presupuesto::query()
                ->where('proyecto_id', $albaran->proyecto_id)
                ->where('numero', trim((string) $albaran->documento))
                ->first();

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdfWith = \Barryvdh\DomPDF\Facade\Pdf::loadView('albaranes.pdf', ['albaran' => $albaran, 'with_presupuesto' => true, 'presupuesto' => $presupuesto]);
                $pdfWith->save(Storage::disk('public')->path($filePathWith));

                $pdfWithout = \Barryvdh\DomPDF\Facade\Pdf::loadView('albaranes.pdf', ['albaran' => $albaran, 'with_presupuesto' => false, 'presupuesto' => null]);
                $pdfWithout->save(Storage::disk('public')->path($filePathWithout));

                $albaran->forceFill(['archivo_pdf' => $filePathWith])->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $returnTo = trim((string) ($validated['return_to'] ?? ''));

        // CORRECCIÓN DE SEGURIDAD AQUÍ (2)
        if ($returnTo !== '') {
            $hostRetorno = parse_url($returnTo, PHP_URL_HOST);
            $hostApp = parse_url(config('app.url'), PHP_URL_HOST);
            
            if ($hostRetorno === $hostApp) {
                return redirect($returnTo)->with('success', 'Albarán actualizado correctamente.');
            }
        }

        return redirect()->route('albaranes.index')->with('success', 'Albarán actualizado correctamente.');
    }

    public function updateEstado(Request $request, AlbaranCliente $albaran)
    {
        $this->authorize('albaranes.manage');

        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        $validated = $request->validate([
            'estado' => ['required', Rule::in(['pendiente', 'recibido', 'facturado', 'facturado_parcial', 'rechazado'])],
        ]);

        DB::transaction(function () use ($albaran, $validated, $proyectoId) {
            
            $estadoAntes = $albaran->estado;
            
            $albaran->update(['estado' => $validated['estado']]);

            // Ajuste de stock según si se cancela o se revierte la cancelación
            if ($estadoAntes !== 'rechazado' && $validated['estado'] === 'rechazado') {
                $this->adjustArticulosStock((array) $albaran->lista_articulos, $proyectoId, 1);
            } elseif ($estadoAntes === 'rechazado' && $validated['estado'] !== 'rechazado') {
                $this->adjustArticulosStock((array) $albaran->lista_articulos, $proyectoId, -1);
            }

            $pedidos = $albaran->pedidosClientes()->get()
                ->push($this->resolvePedidoFromAlbaran($albaran, $proyectoId))
                ->filter()->unique('id');

            foreach ($pedidos as $p) {
                $this->recalculatePedidoEstado($p, $proyectoId);
            }
        });

        return redirect()->back()->with('success', 'Estado del albarán actualizado.');
    }

    public function desvincular(Request $request, AlbaranCliente $albaran)
    {
        $this->authorize('albaranes.manage');
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        $pedidoIdEspecifico = (int) $request->input('pedido_id');
        $pedidoAfectado = null;

        // --- VALIDACIÓN PREVIA (FUERA DE LA TRANSACCIÓN) ---
        if ($pedidoIdEspecifico > 0) {
            $pedidoAfectado = PedidoCliente::where('proyecto_id', $proyectoId)->find($pedidoIdEspecifico);

            // Se aceptan los tres tipos de vínculo para no romper albaranes antiguos
            $vinculado = $pedidoAfectado && (
                $albaran->pedidosClientes()->whereKey($pedidoAfectado->id)->exists()
                || trim((string) $albaran->pedido_cliente) === trim((string) $pedidoAfectado->numero_pedido)
                || (int) $pedidoAfectado->albaran_id === (int) $albaran->id
            );

            abort_unless($vinculado, 404);
        }
        // --- FIN VALIDACIÓN PREVIA ---

        $lineasAnteriores = is_array($albaran->lista_articulos) ? $albaran->lista_articulos : [];

        // --- INICIO DE TRANSACCIÓN SEGURA ---
        DB::transaction(function () use ($albaran, $proyectoId, $pedidoIdEspecifico, $pedidoAfectado, $lineasAnteriores) {
            
            if ($pedidoIdEspecifico > 0 && $pedidoAfectado) {
                // MODO QUIRÚRGICO (Usamos $pedidoAfectado directamente)
                
                $albaran->pedidosClientes()->detach($pedidoAfectado->id);
                
                // Limpieza general de bolsa 
                $this->limpiarRastrosBolsa($albaran, $pedidoAfectado->id);

                if (trim((string) $albaran->pedido_cliente) === trim((string) $pedidoAfectado->numero_pedido)) {
                    $albaran->update(['pedido_cliente' => null]);
                }

                if (! (bool) ($pedidoAfectado->bolsa ?? false)) {
                    $this->adjustPedidoLineasFromAlbaran($pedidoAfectado, $lineasAnteriores, 1);
                }

                if ($pedidoAfectado->albaran_id === $albaran->id) {
                    $remaining = $pedidoAfectado->albaranesPivot()->first();
                    $pedidoAfectado->forceFill(['albaran_id' => $remaining ? $remaining->id : null])->save();
                }

                $this->recalculatePedidosEstadoFromAlbaran($proyectoId, [$pedidoAfectado], $albaran);
                
            } else {
                // MODO DESTRUCCIÓN GLOBAL
                $pedidosAfectados = $albaran->pedidosClientes()->get();
                $pedidoPrimario = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);

                $albaran->update(['pedido_cliente' => null]);
                $albaran->pedidosClientes()->detach();

                // Limpieza general de bolsa 
                $this->limpiarRastrosBolsa($albaran);

                if ($pedidoPrimario && ! (bool) ($pedidoPrimario->bolsa ?? false)) {
                    $this->adjustPedidoLineasFromAlbaran($pedidoPrimario, $lineasAnteriores, 1);
                }

                if ($pedidoPrimario && $pedidoPrimario->albaran_id === $albaran->id) {
                    $remaining = $pedidoPrimario->albaranesPivot()->first();
                    $pedidoPrimario->forceFill(['albaran_id' => $remaining ? $remaining->id : null])->save();
                }

                $pedidosARecalcular = $pedidosAfectados->push($pedidoPrimario)->filter()->unique('id')->all();
                $this->recalculatePedidosEstadoFromAlbaran($proyectoId, $pedidosARecalcular, $albaran);
            }
        });

        return redirect()->back()->with('success', 'Albarán desvinculado correctamente.');
    }

    public function destroy(AlbaranCliente $albaran)
    {
        $this->authorize('albaranes.delete');
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        $pedidosAfectados = $albaran->pedidosClientes()->get();
        $pedidoPrimario = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);
        $lineasAlbaran = is_array($albaran->lista_articulos) ? $albaran->lista_articulos : [];
        $numeroBorrado = (string) $albaran->numero;

        DB::transaction(function () use ($albaran, $proyectoId, $lineasAlbaran, $pedidoPrimario) {
            if ($pedidoPrimario) {
                if (! (bool) ($pedidoPrimario->bolsa ?? false)) {
                    $this->adjustPedidoLineasFromAlbaran($pedidoPrimario, $lineasAlbaran, 1);
                }

                $remainingAlbaranes = $pedidoPrimario->albaranes()
                    ->where('albaranes_clientes.id', '!=', $albaran->id)
                    ->orderByDesc('fecha')
                    ->orderByDesc('id')
                    ->get();

                $pedidoPrimario->forceFill([
                    'albaran_id' => $remainingAlbaranes->first()?->id,
                ])->save();
            }
            
            // Limpieza general perfecta antes de borrar
            $this->limpiarRastrosBolsa($albaran);

            $albaran->pedidosClientes()->detach();
            $this->adjustArticulosStock($lineasAlbaran, $proyectoId, 1);
            $albaran->delete();
        });

        $pedidosARecalcular = $pedidosAfectados->push($pedidoPrimario)->filter()->unique('id')->all();
        $this->recalculatePedidosEstadoFromAlbaran($proyectoId, $pedidosARecalcular, $albaran);

        $formato = $this->getCorrelativoFormato($proyectoId, 'albaranes_formato_correlativo', function () {
            return 'A0000-' . now()->format('y');
        });
        
        $correlativoBorrado = $this->extractCorrelativoFromNumero($formato, $numeroBorrado);

        if ($correlativoBorrado !== null) {
            $override = DB::table('contadores')->where('proyecto_id', $proyectoId)->where('clave', 'albaranes_next_correlativo')->value('valor');
            if ($override !== null && (int) $override === ($correlativoBorrado + 1)) {
                $this->setContadorValue($proyectoId, 'albaranes_next_correlativo', $correlativoBorrado);
            }
        }

        return redirect()->route('albaranes.index')->with('success', 'Albarán eliminado correctamente');
    }

    public function show(AlbaranCliente $albaran)
    {
        $this->authorize('albaranes.view');
        $proyectoId = $this->resolveVisibleAlbaranProyectoId($albaran);

        if ($proyectoId === null) {
            return redirect()->route('albaranes.index')->with('error', 'No se pudo ver el albarán seleccionado.');
        }

        return view('albaranes.preview', [
            'albaran' => $albaran,
            'pdfUrlWithPresupuesto' => route('albaranes.pdf.file', $albaran) . '?with_presupuesto=1',
            'pdfUrlWithoutPresupuesto' => route('albaranes.pdf.file', $albaran) . '?with_presupuesto=0',
            'downloadUrlWithPresupuesto' => route('albaranes.pdf.download', $albaran) . '?with_presupuesto=1',
            'downloadUrlWithoutPresupuesto' => route('albaranes.pdf.download', $albaran) . '?with_presupuesto=0',
        ]);
    }

    public function pdfViewer(AlbaranCliente $albaran)
    {
        $this->authorize('albaranes.download');
        $proyectoId = $this->resolveVisibleAlbaranProyectoId($albaran);

        if ($proyectoId === null) {
            abort(404);
        }

        $baseFile = route('albaranes.pdf.file', $albaran);
        $baseDownload = route('albaranes.pdf.download', $albaran);

        return view('albaranes.preview', [
            'albaran' => $albaran,
            'pdfUrlWithPresupuesto' => $baseFile . '?with_presupuesto=1',
            'pdfUrlWithoutPresupuesto' => $baseFile . '?with_presupuesto=0',
            'downloadUrlWithPresupuesto' => $baseDownload . '?with_presupuesto=1',
            'downloadUrlWithoutPresupuesto' => $baseDownload . '?with_presupuesto=0',
        ]);
    }

    public function preview(AlbaranCliente $albaran)
    {
        $this->authorize('albaranes.download');
        return $this->pdfViewer($albaran);
    }

    public function streamPdf(AlbaranCliente $albaran)
    {
        $this->authorize('albaranes.download');
        $proyectoId = $this->resolveVisibleAlbaranProyectoId($albaran);

        if ($proyectoId === null) abort(404);

        $withPresupuesto = (bool) request()->boolean('with_presupuesto', false);
        [$pdfPath, $pdfContent, $fileName] = $this->buildOrResolvePdf($albaran, $withPresupuesto);

        if ($pdfPath && $pdfContent === null) {
            $disk = Storage::disk('public');
            $path = $disk->path($pdfPath);

            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($pdfPath) . '"',
            ]);
        }

        if ($pdfContent === null) abort(404);

        if ($pdfPath) {
            Storage::disk('public')->put($pdfPath, $pdfContent);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function downloadPdf(AlbaranCliente $albaran)
    {
        $this->authorize('albaranes.download');
        $proyectoId = $this->resolveVisibleAlbaranProyectoId($albaran);

        if ($proyectoId === null) abort(404);

        $withPresupuesto = (bool) request()->boolean('with_presupuesto', false);
        [$pdfPath, $pdfContent, $fileName] = $this->buildOrResolvePdf($albaran, $withPresupuesto);

        if ($pdfPath && $pdfContent === null) {
            $disk = Storage::disk('public');
            return response()->file($disk->path($pdfPath), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . basename($pdfPath) . '"',
            ]);
        }

        if ($pdfContent === null) abort(404);

        if ($pdfPath) {
            Storage::disk('public')->put($pdfPath, $pdfContent);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function editCorrelativo(Request $request)
    {
        $this->authorize('albaranes.manage');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        $formatoActual = $this->getCorrelativoFormato($proyectoId, 'albaranes_formato_correlativo', function () {
            return 'A0000-' . now()->format('y');
        });
        $statsFormato = $this->correlativoStatsForPrefix($proyectoId, $formatoActual);
        $max = $statsFormato['max'];
        $generadosConFormatoActual = $statsFormato['count'];
        $ultimosConFormato = $statsFormato['ultimos'];
        $override = DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', 'albaranes_next_correlativo')
            ->value('valor');

        $suggested = max(($max ?? 0) + 1, (int) ($override ?? 0));
        $ejemplo = $this->formatCorrelativoNumero($formatoActual, $suggested);

        return view('albaranes.correlativo', compact(
            'max', 'override', 'suggested', 'formatoActual', 'ejemplo', 'generadosConFormatoActual', 'ultimosConFormato'
        ));
    }

    public function updateCorrelativo(Request $request)
    {
        $this->authorize('albaranes.manage');
        $proyectoId = $this->resolveProyectoForCorrelativo($request);

        $validated = $request->validate([
            'formato' => ['required', 'string', 'max:100', 'regex:/0000/'],
            'next' => ['required', 'integer', 'min:1'],
        ]);

        $formato = trim((string) $validated['formato']);
        $next = (int) $validated['next'];
        $max = $this->maxCorrelativoForPrefix($proyectoId, $formato);

        if ($next <= ($max ?? 0)) {
            return back()->withErrors(['next' => 'El número debe ser mayor que el máximo correlativo existente (' . ($max ?? 0) . ').']);
        }

        $this->setCorrelativoFormato($proyectoId, $formato, 'albaranes_formato_correlativo');
        $this->setContadorValue($proyectoId, 'albaranes_next_correlativo', $next);

        return redirect()->route('albaranes.index')->with('success', 'Correlativo de albaranes actualizado. Siguiente número: ' . $this->formatCorrelativoNumero($formato, $next));
    }

    // ========================================================================
    // MÉTODOS PRIVADOS Y HELPER FUNCTIONS
    // ========================================================================

    private function resolveNextAlbaranClienteNumber(int $proyectoId): array
    {
        $formato = $this->getCorrelativoFormato($proyectoId, 'albaranes_formato_correlativo', function () {
            return 'A0000-' . now()->format('y');
        });

        $nextIndex = $this->getContadorValue($proyectoId, 'albaranes_next_correlativo');

        if ($nextIndex <= 0) {
            $nextIndex = $this->maxCorrelativoForPrefix($proyectoId, $formato) + 1;
            $this->setContadorValue($proyectoId, 'albaranes_next_correlativo', $nextIndex);
            $this->setCorrelativoFormato($proyectoId, $formato, 'albaranes_formato_correlativo');
        }

        return [
            'formato' => $formato,
            'correlativo' => $nextIndex,
            'numero' => $this->formatCorrelativoNumero($formato, $nextIndex),
        ];
    }

    private function getCorrelativoFormato(int $proyectoId, string $claveBase, callable $fallback): string
    {
        $storedKey = DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', 'like', $claveBase . ':%')
            ->orderByDesc('id')
            ->value('clave');

        $formato = '';
        if (is_string($storedKey) && str_starts_with($storedKey, $claveBase . ':')) {
            $formato = substr($storedKey, strlen($claveBase) + 1);
        }

        if ($formato === '' || !preg_match('/^.+-0000$/', $formato)) {
            $formato = (string) $fallback();
            $this->setCorrelativoFormato($proyectoId, $formato, $claveBase);
        }

        return $formato;
    }

    private function setCorrelativoFormato(int $proyectoId, string $formato, string $claveBase): void
    {
        $clave = $claveBase . ':' . $formato;

        DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', 'like', $claveBase . ':%')
            ->where('clave', '!=', $clave)
            ->delete();

        if (!DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', $clave)
            ->exists()) {
            DB::table('contadores')->insert(['proyecto_id' => $proyectoId, 'clave' => $clave, 'valor' => 1]);
        }
    }

    private function getContadorValue(int $proyectoId, string $clave): int
    {
        return (int) DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', $clave)
            ->lockForUpdate() 
            ->value('valor');
    }

    private function setContadorValue(int $proyectoId, string $clave, int $valor): void
    {
        $exists = DB::table('contadores')->where('proyecto_id', $proyectoId)->where('clave', $clave)->exists();

        if ($exists) {
            DB::table('contadores')->where('proyecto_id', $proyectoId)->where('clave', $clave)->update(['valor' => $valor]);
            return;
        }

        DB::table('contadores')->insert(['proyecto_id' => $proyectoId, 'clave' => $clave, 'valor' => $valor]);
    }

    private function maxCorrelativoForPrefix(int $proyectoId, string $formato): int
    {
        return $this->correlativoStatsForPrefix($proyectoId, $formato)['max'];
    }

    private function correlativoStatsForPrefix(int $proyectoId, string $formato): array
    {
        $parts = explode('0000', $formato);
        $regex = '/^' . preg_quote($parts[0], '/') . '(\d{4})' . preg_quote($parts[1] ?? '', '/') . '$/';
        $likePattern = str_replace('0000', '%', $formato); 

        $max = 0;
        $count = 0;

        $numeros = AlbaranCliente::query()->where('proyecto_id', $proyectoId)->where('numero', 'like', $likePattern)->pluck('numero');

        foreach ($numeros as $numero) {
            if (is_string($numero) && preg_match($regex, $numero, $match) === 1) {
                $count++;
                $max = max($max, (int) $match[1]);
            }
        }

        $ultimos = AlbaranCliente::query()
            ->where('proyecto_id', $proyectoId)
            ->where('numero', 'like', $likePattern)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get(['id', 'numero', 'fecha'])
            ->filter(fn (AlbaranCliente $albaran) => is_string($albaran->numero) && preg_match($regex, $albaran->numero) === 1) 
            ->take(5);

        return ['max' => $max, 'count' => $count, 'ultimos' => $ultimos];
    }

    private function formatCorrelativoNumero(string $formato, int $correlativo): string
    {
        $padded = str_pad((string) max(0, $correlativo), 4, '0', STR_PAD_LEFT);
        return str_replace('0000', $padded, $formato);
    }

    private function extractCorrelativoFromNumero(string $formato, string $numero): ?int
    {
        $parts = explode('0000', $formato); 
        $pattern = '/^' . preg_quote($parts[0], '/') . '(\d{4})' . preg_quote($parts[1] ?? '', '/') . '$/';

        if (preg_match($pattern, $numero, $match) !== 1) return null;
        return (int) $match[1];
    }

    private function normalizeLineas(?string $lineasJson, ?array $decodedLineas = null): array
    {
        $decoded = $decodedLineas ?? json_decode((string) ($lineasJson ?? '[]'), true);
        $normalizer = new DocumentLineNormalizer();

        $lineas = $normalizer->normalize($normalizer->filter(is_array($decoded) ? $decoded : []));

        return array_map(function (array $l) {
            $medida = $l['medida'] ?? 'und';
            return array_merge($l, ['medida' => $medida, 'unidad' => $medida, 'precio' => $l['precio_unitario']]);
        }, $lineas);
    }

    private function decodeLineasJson(?string $lineasJson): array
    {
        $decoded = json_decode((string) ($lineasJson ?? '[]'), true);
        if (!is_array($decoded)) return [];

        return collect($decoded)
            ->filter(fn ($linea) => is_array($linea) && trim((string) ($linea['descripcion'] ?? '')) !== '')
            ->values()
            ->all();
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

    private function resolvePdfPath(AlbaranCliente $albaran): ?string
    {
        $candidates = [];

        $archivoPdf = trim((string) ($albaran->archivo_pdf ?? ''));
        if ($archivoPdf !== '') $candidates[] = $archivoPdf;

        $documento = trim((string) ($albaran->documento ?? ''));
        if ($documento !== '' && str_ends_with(strtolower($documento), '.pdf')) $candidates[] = $documento;

        $disk = Storage::disk('public');
        foreach (array_unique($candidates) as $path) {
            if ($disk->exists($path)) return $path;
        }

        return null;
    }

    private function buildOrResolvePdf(AlbaranCliente $albaran, bool $withPresupuesto = false): array
    {
        $disk = Storage::disk('public');
        $fileName = ($albaran->numero ?: 'albaran-' . $albaran->id) . '.pdf';

        $basePdfPath = $this->resolvePdfPath($albaran);
        $pdfPath = $this->resolvePdfVariantPath($albaran, $withPresupuesto, $basePdfPath);

        if (!$withPresupuesto && $pdfPath && $disk->exists($pdfPath)) {
            return [$pdfPath, null, basename($pdfPath)];
        }

        $pdfContent = null;
        $presupuesto = null;
        if ($withPresupuesto) {
            $presupuesto = Presupuesto::query()
                ->where('proyecto_id', $albaran->proyecto_id)
                ->where('numero', trim((string) $albaran->documento))
                ->first();
        }

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('albaranes.pdf', ['albaran' => $albaran, 'with_presupuesto' => $withPresupuesto, 'presupuesto' => $presupuesto]);
            $pdfContent = $pdf->output();
        } elseif (class_exists(\Dompdf\Dompdf::class)) {
            $html = view('albaranes.pdf', ['albaran' => $albaran, 'with_presupuesto' => $withPresupuesto, 'presupuesto' => $presupuesto])->render();
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            $pdfContent = $dompdf->output();
        }

        if ($pdfContent !== null) {
            if ($withPresupuesto) {
                $pdfPath = $basePdfPath ?: 'albaranes/albaran-' . $albaran->id . '.pdf';
                $albaran->forceFill(['archivo_pdf' => $pdfPath])->save();
            } elseif (!$pdfPath) {
                $pdfPath = 'albaranes/albaran-' . $albaran->id . '-sin-presupuesto.pdf';
            }
        }

        return [$pdfPath, $pdfContent, $fileName];
    }

    private function resolvePdfVariantPath(AlbaranCliente $albaran, bool $withPresupuesto, ?string $basePdfPath = null): ?string
    {
        if ($withPresupuesto) return $basePdfPath;
        if (!$basePdfPath) return 'albaranes/albaran-' . $albaran->id . '-sin-presupuesto.pdf';

        $directory = dirname($basePdfPath);
        $filename = pathinfo($basePdfPath, PATHINFO_FILENAME);

        return $directory . '/' . $filename . '-sin-presupuesto.pdf';
    }

    private function resolveProyectoIdWithFallback(?int $fallbackProyectoId = null): int
    {
        $sessionProyectoId = (int) request()->session()->get('active_proyecto_id');
        if ($sessionProyectoId > 0) return $sessionProyectoId;

        if ($fallbackProyectoId && $fallbackProyectoId > 0) return $fallbackProyectoId;

        return $this->resolveActiveProyectoId(request());
    }

    private function validateProyectoAccess(int $proyectoId): void
    {
        $user = request()->user();
        if (!$user) abort(401);

        $hasAccess = $user->role === 'superadmin' || $user->proyectos()->where('proyectos.id', $proyectoId)->exists();
        if (!$hasAccess) abort(403);
    }

    // ========================================================================
    // MOTOR UNIFICADO BOLSA (Sustituye los múltiples motores antiguos)
    // ========================================================================

    private function syncPedidoClienteLink(AlbaranCliente $albaran, int $proyectoId, array $pedidosNumeros = [], array $lineas = []): float
    {
        $pedidosAnteriores = $albaran->pedidosClientes()->get();
        $albaran->pedidosClientes()->detach();

        foreach ($pedidosAnteriores as $pedidoAnterior) {
            if ($pedidoAnterior->albaran_id === $albaran->id) {
                $remaining = $pedidoAnterior->albaranesPivot()->first(); 
                $pedidoAnterior->forceFill(['albaran_id' => $remaining ? $remaining->id : null])->save();
            }
        }

        if (empty($pedidosNumeros)) {
            return (float) ($albaran->total ?? 0);
        }

        $totalesPorBolsa = [];
        $totalSinBolsaEspecifica = 0.0;

        if (!empty($lineas)) {
            foreach ($lineas as $linea) {
                $num = trim((string) ($linea['numero_pedido'] ?? ''));
                if ($num !== '') {
                    $totalesPorBolsa[$num] = ($totalesPorBolsa[$num] ?? 0.0) + (float) ($linea['total'] ?? 0);
                } else {
                    $totalSinBolsaEspecifica += (float) ($linea['total'] ?? 0);
                }
            }
        } else {
            $totalSinBolsaEspecifica = (float) ($albaran->total ?? 0);
        }

        $pedidos = PedidoCliente::query()
            ->where('proyecto_id', $proyectoId)
            ->whereIn('numero_pedido', $pedidosNumeros)
            ->lockForUpdate()
            ->get();

        foreach ($pedidos as $pedido) {
            $importeAImputar = 0.0;

            if (isset($totalesPorBolsa[$pedido->numero_pedido])) {
                $importeAImputar = $totalesPorBolsa[$pedido->numero_pedido];
            } elseif ($totalSinBolsaEspecifica > 0.001) {
                $pendiente = $this->calculatePedidoPendienteFacturar($pedido, $proyectoId, $albaran->id);
                $asignar = max(0, min($totalSinBolsaEspecifica, $pendiente));
                
                if ($asignar > 0.001) {
                    $importeAImputar = $asignar;
                    $totalSinBolsaEspecifica -= $asignar;
                }
            }

            if ($importeAImputar > 0.001) {
                $albaran->pedidosClientes()->attach($pedido->id, ['importe_imputado' => round($importeAImputar, 4)]);
                
                if (empty($pedido->albaran_id)) {
                    $pedido->forceFill(['albaran_id' => $albaran->id])->save();
                }
            }
        }
        $primero = $albaran->pedidosClientes()
            ->orderBy('pedidos_clientes.fecha_pedido')->orderBy('pedidos_clientes.id')->first();
        $albaran->forceFill(['pedido_cliente' => $primero?->numero_pedido])->save();

        return max(0, round($totalSinBolsaEspecifica, 4));
    }

    /**
     * UNIFICA LA INSERCIÓN DE CONSUMOS:
     * Si la línea es V3 (tiene linea_id), se delega en el LineaBolsaService.
     * Si la línea es V2 (Legacy, solo json uid), se hace insert directo en albaran_pedido_lineas.
     */
    private function registrarConsumosBolsa(AlbaranCliente $albaran, array $lineas, int $proyectoId): float
    {
        $excedenteTotal = 0.0;
        $pedidos = $albaran->pedidosClientes()->get(); // Refrescamos relaciones por si acaban de cambiar

        // Capturamos las líneas que tienen `linea_id` válida para tratarlas por V3
        $uidsV3 = collect($lineas)->filter(fn($l) => !empty($l['linea_id']))->pluck('uid')->filter()->values();$lineasBolsaPorUid = LineaBolsa::whereIn('uid', $uidsV3)->get()->keyBy('uid');$service = new LineaBolsaService();

        $insertsV2 = [];$now = Carbon::now();

        foreach ($lineas as$linea) {
            $uid = trim((string) ($linea['uid'] ?? ''));
            if ($uid === '') continue;

            $importeConsumido = round((float) ($linea['total'] ?? 0), 4);
            if ($importeConsumido <= 0) continue;

            if (!empty($linea['linea_id']) && $lineasBolsaPorUid->has($uid)) {
                // -- LÓGICA V3 (Moderna y estricta) --
                $repartoManual = is_array($linea['repartos'] ?? null) ? $linea['repartos'] : [];$excedenteTotal += $service->registrarConsumo($albaran, $lineasBolsaPorUid->get($uid), $importeConsumido,$repartoManual);
            } else {
                // -- LÓGICA V2 (Legacy Fallback basada en JSON) --
                foreach ($pedidos as$pedido) {
                    if (!(bool)($pedido->bolsa ?? false) || empty($pedido->lista_articulos)) continue;
                    
                    $listaArticulos = is_array($pedido->lista_articulos) ? $pedido->lista_articulos : [];$encontrado = collect($listaArticulos)->firstWhere('uid',$uid);
                    
                    if ($encontrado) {$insertsV2[] = [
                            'albaran_id' => $albaran->id,
                            'pedido_id' => $pedido->id,
                            'linea_uid' => $uid,
                            'cantidad' => round((float) ($linea['cantidad'] ?? 0), 2),
                            'precio_unitario' => round((float) ($linea['precio_unitario'] ?? 0), 4),
                            'total' =>$importeConsumido,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        break; 
                    }
                }
            }
        }

        // Insertamos en bloque todo lo que era Legacy
        if (!empty($insertsV2)) {
            DB::table('albaran_pedido_lineas')->insert($insertsV2);
        }

        return round($excedenteTotal, 4);
    }

    /**
     * GOMA DE BORRAR DEFINITIVA:
     * Limpia cualquier rastro (V2 o V3) de este albarán antes de actualizarlo.
     */
    private function limpiarRastrosBolsa(AlbaranCliente $albaran, ?int $pedidoId = null): void
    {
        // 1. Limpiar rastro de la Bolsa V3 estricta
        $queryPartidas = \App\Models\AlbaranLineaPartida::where('albaran_id',$albaran->id);
        if ($pedidoId !== null) {
            $queryPartidas->where('pedido_id',$pedidoId);
        }
        $lineaIds = $queryPartidas->pluck('linea_id')->unique();$queryPartidas->delete();

        // 2. Limpiar rastro de la Bolsa V2/Mixta (El causante de la duplicación)
        $queryPedidoLineas = \App\Models\AlbaranPedidoLinea::where('albaran_id',$albaran->id);
        if ($pedidoId !== null) {
            $queryPedidoLineas->where('pedido_id',$pedidoId);
        }
        $queryPedidoLineas->delete();

        // 3. Reactivar líneas agotadas si han recuperado saldo tras la limpieza
        if ($lineaIds->isNotEmpty()) {
            LineaBolsa::whereIn('id', $lineaIds)
                ->where('estado', LineaBolsa::ESTADO_AGOTADA)
                ->get()
                ->each(function (LineaBolsa $linea) {
                    if ($linea->importeDisponible() > 0) {$linea->update(['estado' => LineaBolsa::ESTADO_ACTIVA]);
                    }
                });
        }
    }

    /**
     * Bolsa V3: Límite DURO en backend antes de procesar nada.
     */
    private function assertLimitesLineaBolsa(array $lineas, ?int $albaranId = null): void
    {
        $uids = collect($lineas)->map(fn ($l) => trim((string) ($l['uid'] ?? '')))->filter()->unique()->values();
        if ($uids->isEmpty()) return;

        $lineasBolsa = LineaBolsa::whereIn('uid', $uids)->get()->keyBy('uid');
        if ($lineasBolsa->isEmpty()) return;

        // BUGFIX: si el mismo uid aparece más de una vez en el envío (por ejemplo,
        // porque el usuario seleccionó dos partidas que alimentan la misma línea),
        // hay que sumar lo pedido ANTES de comparar contra el disponible. Comparar
        // entrada por entrada permitía colar, p.ej., 2.000€ + 2.000€ contra una
        // línea con 2.500€ reales, porque cada entrada por separado pasaba el check.
        $importePedidoPorUid = collect($lineas)
            ->groupBy(fn ($l) => trim((string) ($l['uid'] ?? '')))
            ->map(fn ($grupo) => round($grupo->sum(fn ($l) => (float) ($l['total'] ?? 0)), 4));

        $mensajes = [];

        foreach ($uids as $uid) {
            $lineaBolsa = $lineasBolsa->get($uid);
            if (!$lineaBolsa) continue;

            $previo = $albaranId
                ? (float) \App\Models\AlbaranLineaPartida::where('albaran_id', $albaranId)
                    ->where('linea_id', $lineaBolsa->id)->sum('importe')
                : 0.0;

            $disponible = round($lineaBolsa->importeDisponible() + $previo, 4);
            $importe = (float) $importePedidoPorUid->get($uid, 0.0);

            if ($importe > $disponible + 0.0001) {
                $mensajes[] = sprintf(
                    'La línea "%s" solo tiene %s € disponibles en total (sumando todas las partidas que la nutren) y se intenta facturar %s €.',
                    \Illuminate\Support\Str::limit((string) ($lineaBolsa->descripcion ?? ''), 60),
                    number_format($disponible, 4, ',', '.'),
                    number_format($importe, 4, ',', '.')
                );
            }
        }

        if ($mensajes !== []) throw ValidationException::withMessages(['lineas_json' => $mensajes]);
    }

    /**
     * QUINTO CANDADO: el tope de la bolsa global (`bolsas.total`) también se
     * respeta al facturar, no solo al crear/editar una partida.
     *
     * Antes de este método, nada en el flujo de facturación comprobaba el
     * tope de la bolsa: `assertLimitesLineaBolsa` solo mira el disponible de
     * cada línea, y el escudo de `PedidoController` solo actúa al crear o
     * editar una partida (comparando contra lo YA facturado, que al crear
     * una partida nueva suele ser 0 — así que apenas bloqueaba nada). Esto
     * cierra el hueco: suma, por cada bolsa implicada en las partidas
     * seleccionadas, lo que este albarán le añadiría a lo que esa bolsa ya
     * tiene facturado, y lo compara contra su tope.
     *
     * $pedidosAVincular: partidas seleccionadas en el albarán, con
     * `bolsaGlobal` precargada (se reutiliza la consulta del cuarto candado).
     */
    private function assertLimitesBolsaGlobal($pedidosAVincular, array $lineas, ?int $albaranId = null): void
    {
        $bolsas = $pedidosAVincular
            ->filter(fn (PedidoCliente $p) => (bool) $p->bolsa && $p->bolsaGlobal !== null)
            ->pluck('bolsaGlobal')
            ->unique('id');

        if ($bolsas->isEmpty()) {
            return;
        }

        $uids = collect($lineas)
            ->filter(fn ($l) => !empty($l['linea_id']))
            ->map(fn ($l) => trim((string) ($l['uid'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($uids->isEmpty()) {
            return;
        }

        $lineasBolsa = LineaBolsa::whereIn('uid', $uids)->get()->keyBy('uid');

        $importePorUid = collect($lineas)
            ->groupBy(fn ($l) => trim((string) ($l['uid'] ?? '')))
            ->map(fn ($grupo) => round($grupo->sum(fn ($l) => (float) ($l['total'] ?? 0)), 4));

        $mensajes = [];

        foreach ($bolsas as $bolsa) {
            if ((float) $bolsa->total <= 0) {
                continue; // Bolsa abierta: sin tope que comprobar.
            }

            $partidaIdsDeEstaBolsa = $bolsa->partidas()->pluck('id');

            // Cuánto de ESTE albarán es dinero que puede salir de ESTA bolsa:
            // líneas cuyo linea_id está ligado (vía linea_partida) a alguna
            // partida de esta bolsa.
            $nuevoImporteDeEstaBolsa = 0.0;
            foreach ($uids as $uid) {
                $lineaBolsa = $lineasBolsa->get($uid);
                if (!$lineaBolsa) {
                    continue;
                }

                $tocaEstaBolsa = $lineaBolsa->partidas()
                    ->whereIn('pedidos_clientes.id', $partidaIdsDeEstaBolsa)
                    ->exists();

                if ($tocaEstaBolsa) {
                    $nuevoImporteDeEstaBolsa += (float) $importePorUid->get($uid, 0.0);
                }
            }

            if ($nuevoImporteDeEstaBolsa <= 0) {
                continue;
            }

            $yaFacturadoDeEstaBolsa = $bolsa->totalAcumulado();

            // Si se edita un albarán que ya tenía imputado algo a esta bolsa,
            // se le devuelve antes de comparar, para no restarlo dos veces.
            $previoDeEsteAlbaran = $albaranId
                ? (float) \App\Models\AlbaranLineaPartida::where('albaran_id', $albaranId)
                    ->whereIn('pedido_id', $partidaIdsDeEstaBolsa)
                    ->sum('importe')
                : 0.0;

            $proyectado = round($yaFacturadoDeEstaBolsa - $previoDeEsteAlbaran + $nuevoImporteDeEstaBolsa, 4);

            if ($proyectado > (float) $bolsa->total + 0.0001) {
                $disponible = max(0, round((float) $bolsa->total - $yaFacturadoDeEstaBolsa + $previoDeEsteAlbaran, 4));

                $mensajes[] = sprintf(
                    'La bolsa global "%s" solo tiene %s € disponibles en total y este albarán le añadiría %s €.',
                    $bolsa->numero ?: ('#' . $bolsa->id),
                    number_format($disponible, 4, ',', '.'),
                    number_format($nuevoImporteDeEstaBolsa, 4, ',', '.')
                );
            }
        }

        if ($mensajes !== []) {
            throw ValidationException::withMessages(['lineas_json' => $mensajes]);
        }
    }

    // ========================================================================
    // OTROS HELPERS DE PEDIDOS Y ESTADOS
    // ========================================================================

    private function resolvePedidoContext(Request $request, int$proyectoId): ?PedidoCliente
    {
        $pedidoId = (int) $request->input('pedido_id',$request->query('pedido_id', 0));
        if ($pedidoId > 0) {$pedido = PedidoCliente::query()->with('cliente')->where('proyecto_id', $proyectoId)->find($pedidoId);
            if ($pedido) return$pedido;
        }

        $numeroPedido = trim((string) $request->input('pedido_cliente',$request->query('pedido_cliente', '')));
        if ($numeroPedido === '') return null;

        return PedidoCliente::query()->with('cliente')->where('proyecto_id', $proyectoId)->where('numero_pedido',$numeroPedido)->first();
    }

    private function buildLineasInicialesFromPedido(PedidoCliente $pedidoCliente, int$proyectoId): array
    {
        $lineas = is_array($pedidoCliente->lista_articulos) ? $pedidoCliente->lista_articulos : [];$lineasPedido = collect($this->normalizePedidoLineas($lineas));

        if ($lineasPedido->isEmpty()) return [];

        return $lineasPedido->map(function (array$linea) {
            $cantidad = round((float) ($linea['cantidad'] ?? 0), 2);
            $linea['cantidad'] =$cantidad;
            $linea['cantidad_max'] = max($cantidad, (float) ($linea['cantidad_max'] ?? $cantidad));
            $precioUnitario = (float) ($linea['precio_unitario'] ?? 0);
            $margen = (float) ($linea['margen'] ?? 0);
            $linea['total'] = round($cantidad * $precioUnitario * (1 + ($margen / 100)), 4);

            return $linea;
        })->values()->all();
    }

    private function calculatePedidoPendienteFacturar(PedidoCliente $pedido, int $proyectoId, ?int $excludeAlbaranId = null): float
    {
        $pedido = PedidoCliente::where('proyecto_id', $proyectoId)->find($pedido->id) ?? $pedido;
        return $pedido->pendienteFacturar($excludeAlbaranId);
    }

    private function resolveVisibleAlbaranProyectoId(AlbaranCliente $albaran): ?int
    {
        $proyectoId = $this->resolveProyectoIdWithFallback((int)$albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !==$proyectoId) return null;
        return $proyectoId;
    }

    private function syncPedidoEstadoFromAlbaran(AlbaranCliente $albaran, int $proyectoId, array$lineasDelAlbaran = []): void
    {
        $pedidos = $albaran->pedidosClientes()->get();$pedidoPrimario = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);$pedidosARecalcular = $pedidos->push($pedidoPrimario)->filter()->unique('id')->all();
        
        $this->recalculatePedidosEstadoFromAlbaran($proyectoId, $pedidosARecalcular,$albaran);
    }

    private function recalculatePedidoEstado(PedidoCliente $pedido, int $proyectoId): void
    {
        $pedido    = PedidoCliente::where('proyecto_id', $proyectoId)->find($pedido->id) ?? $pedido;
        $total     = round((float) ($pedido->total ?? 0), 2);
        $consumido = $pedido->importeConsumido();

        $estado = 'pendiente';
        if ($total > 0 && $consumido > 0) {
            $estado = $consumido >= $total - 0.01 ? 'facturado' : 'facturado_parcial';
        }

        $pedido->forceFill(['estado' => $estado])->save();
    }

    private function recalculatePedidosEstadoFromAlbaran(int $proyectoId, array $pedidos, AlbaranCliente$albaran): void
    {
        foreach (collect($pedidos)->filter() as$pedido) {
            if ($pedido instanceof PedidoCliente) {$this->recalculatePedidoEstado($pedido,$proyectoId);
            }
        }
    }

    private function adjustPedidoLineasFromAlbaran(PedidoCliente $pedido, array $lineasAlbaran, int $direction): void
    {
        if ((bool) ($pedido->bolsa ?? false) || $lineasAlbaran === []) return;

        $pedidoLineas = is_array($pedido->lista_articulos) ? $pedido->lista_articulos : [];

        $indexMap = [];
        foreach ($pedidoLineas as $idx => $lineaPedido) {
            if (!is_array($lineaPedido)) continue;

            $signature = $this->normalizePedidoLineaSignature($lineaPedido);
            if ($signature !== '') $indexMap[$signature][] = $idx;
        }

        foreach ($lineasAlbaran as $lineaAlbaran) {
            if (!is_array($lineaAlbaran)) continue;

            $cantidad = round(max(0, (float) ($lineaAlbaran['cantidad'] ?? 0)), 2);
            if ($cantidad <= 0) continue;

            $signature = $this->normalizePedidoLineaSignature($lineaAlbaran);
            
            if ($signature === '' || !isset($indexMap[$signature])) {
                if ($direction > 0) {
                    $restored = $this->normalizePedidoLineas([$lineaAlbaran]);
                    if (!empty($restored[0])) $pedidoLineas[] = $restored[0];
                }
                continue;
            }

            if ($direction > 0) {
                $idx = $indexMap[$signature][0];
                $pedidoLineas[$idx]['cantidad'] = round((float) ($pedidoLineas[$idx]['cantidad'] ?? 0) + $cantidad, 2);
                $pedidoLineas[$idx]['total'] = round(
                    (float) ($pedidoLineas[$idx]['cantidad'] ?? 0)
                    * max(0, (float) ($pedidoLineas[$idx]['precio_unitario'] ?? ($pedidoLineas[$idx]['precio'] ?? 0)))
                    * (1 + (max(0, (float) ($pedidoLineas[$idx]['margen'] ?? 0)) / 100)), 2);
                continue;
            }

            foreach ($indexMap[$signature] as $idx) {
                $orig = round((float) ($pedidoLineas[$idx]['cantidad'] ?? 0), 2);
                if ($orig <= 0) continue;

                $toSubtract = min($orig, $cantidad);
                $pedidoLineas[$idx]['cantidad'] = round(max(0, $orig - $toSubtract), 2);
                $pedidoLineas[$idx]['total'] = round(
                    (float) ($pedidoLineas[$idx]['cantidad'] ?? 0)
                    * max(0, (float) ($pedidoLineas[$idx]['precio_unitario'] ?? ($pedidoLineas[$idx]['precio'] ?? 0)))
                    * (1 + (max(0, (float) ($pedidoLineas[$idx]['margen'] ?? 0)) / 100)), 2);
                $cantidad = round(max(0, $cantidad - $toSubtract), 2);

                if ($cantidad <= 0) break;
            }
        }

        $pedidoLineas = array_values(array_filter($pedidoLineas, fn ($l) => round((float) ($l['cantidad'] ?? 0), 2) > 0));
        $pedido->forceFill(['lista_articulos' => $this->normalizePedidoLineas($pedidoLineas)])->save();
    }

    private function normalizePedidoLineas(array $lineas): array
    {
        $normalizer = new DocumentLineNormalizer();
        return $normalizer->normalize($normalizer->filter($lineas));
    }

    private function normalizePedidoLineaSignature(array $linea): string
    {
        $descripcion = mb_strtolower(trim((string) ($linea['descripcion'] ?? '')));$medida = mb_strtolower(trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? ''))));

        return $descripcion . '|' .$medida;
    }

    private function albaranAportaPedido(AlbaranCliente $albaran,$lineasPedido): bool
    {
        $lineasAlbaran = collect(is_array($albaran->lista_articulos) ?$albaran->lista_articulos : [])
            ->filter(fn ($linea) => is_array($linea) && !empty(trim((string) ($linea['descripcion'] ?? ''))))
            ->map(fn (array $linea) => $this->normalizePedidoLineaSignature($linea))
            ->filter()
            ->values();

        if ($lineasPedido instanceof \Illuminate\Support\Collection) {
            $lineasPedido =$lineasPedido->values();
        } else {
            $lineasPedido = collect($lineasPedido)->values();
        }

        if ($lineasPedido->isEmpty() || $lineasAlbaran->isEmpty()) return false;

        return $lineasAlbaran->intersect($lineasPedido)->isNotEmpty();
    }

    private function resolvePedidoFromAlbaran(AlbaranCliente $albaran, int$proyectoId): ?PedidoCliente
    {
        $pedido =$albaran->pedidosClientes()->first();

        if ($pedido && (int) $pedido->proyecto_id ===$proyectoId) {
            return $pedido;
        }

        $pedidoNumero = trim((string) ($albaran->pedido_cliente ?? ''));
        if ($pedidoNumero === '') return null;

        return PedidoCliente::query()->where('proyecto_id', $proyectoId)->where('numero_pedido',$pedidoNumero)->first();
    }

    private function adjustArticulosStock(array $lineas, int $proyectoId, int $direction): void
    {
        $consumos = collect($lineas)
            ->filter(fn ($linea) => is_array($linea) && (int) ($linea['articulo_id'] ?? 0) > 0)
            ->groupBy(fn ($linea) => (int) ($linea['articulo_id'] ?? 0))
            ->map(fn ($items) => round($items->sum(fn ($linea) => (float) ($linea['cantidad'] ?? 0)), 2));

        if ($consumos->isEmpty()) return;

        $articulos = Articulo::query()
            ->where('proyecto_id', $proyectoId)
            ->whereIn('id', $consumos->keys())
            ->lockForUpdate() 
            ->get();

        foreach ($articulos as $articulo) {
            $consumo = (float) ($consumos[$articulo->id] ?? 0);
            if ($consumo <= 0) continue;

            $cantidadActual = (float) ($articulo->cantidad ?? 0);
            
            // Redondeo correcto en la suma/resta de stock
            $restante = $direction < 0 
                ? round(max(0, $cantidadActual - $consumo), 2)
                : round($cantidadActual + $consumo, 2);

            $precioUnitario = (float) ($articulo->precio_unitario ?? 0);
            $margen = (float) ($articulo->margen ?? 0);

            $articulo->cantidad = $restante;
            $articulo->facturado = $restante <= 0;
            
            // Sintaxis matemática corregida y redondeo a 2 decimales
            $articulo->total = round($restante * $precioUnitario * (1 + ($margen / 100)), 2);
            
            $articulo->save();
        }
    }

    private function safeReturnUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null) {                       // ruta relativa
            return (str_starts_with($url, '/') && ! str_starts_with($url, '//')) ? $url : null;
        }

        return strcasecmp($host, request()->getHost()) === 0 ? $url : null;
    }
}