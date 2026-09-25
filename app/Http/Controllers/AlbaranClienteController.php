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
            $totalAlbaran = round((float) ($albaran->total ?? 0), 2);

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

            // NUEVO: Calcular cuánto dinero le queda "flotando" a este albarán
            $sumaImputada = 0.0;
            if ($albaran->pedidosClientes->isNotEmpty()) {
                $sumaImputada = (float) $albaran->pedidosClientes->sum('pivot.importe_imputado');
                // ESCUDO LEGACY
                if ($sumaImputada <= 0.001) {
                    $sumaImputada = $totalAlbaran;
                }
            } elseif (trim((string) $pedidoNumero) !== '') {
                // Si es un albarán súper antiguo sin pivote
                $sumaImputada = $totalAlbaran;
            }
            $albaran->ui_excedente = max(0, round($totalAlbaran - $sumaImputada, 2));

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
            ->with('cliente')
            ->where('proyecto_id', $proyectoId)
            ->where(function ($query) use ($pedidoContext) {
                $query->whereNull('estado')
                    ->orWhere('estado', '<>', 'facturado');

                if ($pedidoContext) {
                    $query->orWhere('id', $pedidoContext->id);
                }
            })
            ->orderByDesc('id')
            ->get(['id', 'numero_pedido', 'id_cliente', 'ot', 'bolsa']);
        $pedidoBolsa = (bool) ($pedidoContext?->bolsa ?? false);
        $pedidoModoRestringido = false; // <-- Desactivamos el bloqueo por completo
        $lineasIniciales = []; // Ya no cargamos líneas bloqueadas por defecto si quieres total libertad
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
            'pedidos_cliente.*' => 'string|max:255',
            'titulo' => 'nullable|string|max:255',
            'lineas_json' => 'nullable|json',
            'estado' => ['nullable', Rule::in(['pendiente', 'recibido', 'facturado', 'facturado_parcial', 'cancelado'])],
            'archivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $pedidoContext = $this->resolvePedidoContext($request, $proyectoId);
        $pedidoBolsa = (bool) ($pedidoContext?->bolsa ?? false);

        // Parseamos las líneas en memoria antes de entrar a la base de datos
        $lineasRaw = $this->decodeLineasJson($validated['lineas_json'] ?? '[]');
        $this->validateLineasPayload($lineasRaw);
        $lineas = $this->normalizeLineas($validated['lineas_json'] ?? '[]', $lineasRaw);
        $totalAlbaran = round((float) collect($lineas)->sum(fn (array $linea) => (float) ($linea['total'] ?? 0)), 2);

        // EXTRAEMOS EL PDF FUERA DE LA TRANSACCIÓN
        if ($request->hasFile('archivo_pdf')) {
            $validated['archivo_pdf'] = $request->file('archivo_pdf')->store('albaranes', 'public');
        }

        // 1. PREPARAMOS EL ARRAY DE BOLSAS ANTES DE LA TRANSACCIÓN
        $pedidosSeleccionados = $validated['pedidos_cliente'] ?? [];
        $validated['pedido_cliente'] = !empty($pedidosSeleccionados) ? $pedidosSeleccionados[0] : null;
        unset($validated['pedidos_cliente']);

        $excedenteFlotante = 0.0;

        // 2. ABRIMOS LA TRANSACCIÓN (Añadimos $pedidosSeleccionados al 'use')
        DB::transaction(function () use (&$excedenteFlotante, $validated, $proyectoId, $lineas, $totalAlbaran, $pedidosSeleccionados) {
            
            // 3. Obtener número (ahora protegido por el lockForUpdate dentro de la transacción)
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

            // 4. CREAR ALBARÁN (Aquí nace la variable $albaran)
            $albaran = AlbaranCliente::create($validated);

            // 5. Actualizar contadores
            if ($manualCorrelativo !== null) {
                // Si introdujo un número con formato válido, avanzamos el contador tomando el máximo
                $nextValue = max($correlativoActual['correlativo'], $manualCorrelativo + 1);
            } else {
                // Si introdujo un texto libre, congelamos el contador reteniendo el número propuesto
                $nextValue = $correlativoActual['correlativo'];
            }
            
            $this->setContadorValue($proyectoId, 'albaranes_next_correlativo', $nextValue);
            $this->setCorrelativoFormato($proyectoId, $correlativoActual['formato'], 'albaranes_formato_correlativo');
            
            // 6. AQUÍ EJECUTAMOS LA CASCADA MULTI-BOLSA CONTROLADA
            $excedenteFlotante = $this->syncPedidoClienteLink($albaran, $proyectoId, $pedidosSeleccionados);
            $this->syncPedidoEstadoFromAlbaran($albaran, $proyectoId, $lineas);

            $pedido = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);
            if ($pedido && ! (bool) ($pedido->bolsa ?? false)) {
                $this->adjustPedidoLineasFromAlbaran($pedido, $lineas, -1);
            }

            // 7. Restar stock de artículos (Consumos)
            $consumos = collect($lineas)
                ->filter(fn ($linea) => is_array($linea) && (int) ($linea['articulo_id'] ?? 0) > 0)
                ->groupBy(fn ($linea) => (int) ($linea['articulo_id'] ?? 0))
                ->map(fn ($items) => round($items->sum(fn ($linea) => (float) ($linea['cantidad'] ?? 0)), 2));

            if ($consumos->isNotEmpty()) {
                $articulos = Articulo::query()
                    ->where('proyecto_id', $proyectoId)
                    ->whereIn('id', $consumos->keys())
                    // MUY IMPORTANTE: Bloqueamos también los artículos que estamos modificando
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
                    $articulo->total = round($restante * $precioUnitario * (1 + ($margen / 100)), 2);
                    $articulo->save();
                }
            }
        });

        if ($excedenteFlotante > 0.001) {
            $mensajeWarning = "Sobrepasaste el saldo de la bolsa. La acción se ha ejecutado, pero han quedado pendientes " . number_format($excedenteFlotante, 2, ',', '.') . " €. Prepare un nuevo pedido bolsa para absorber el excedente.";
            return redirect()->route('albaranes.index')->with('warning', $mensajeWarning);
        }

        return redirect()->route('albaranes.index')->with('success', 'Albarán procesado correctamente');
    }

    private function resolvePedidoContext(Request $request, int $proyectoId): ?PedidoCliente
    {
        $pedidoId = (int) $request->input('pedido_id', $request->query('pedido_id', 0));
        if ($pedidoId > 0) {
            $pedido = PedidoCliente::query()
                ->with('cliente')
                ->where('proyecto_id', $proyectoId)
                ->find($pedidoId);

            if ($pedido) {
                return $pedido;
            }
        }

        $numeroPedido = trim((string) $request->input('pedido_cliente', $request->query('pedido_cliente', '')));
        if ($numeroPedido === '') {
            return null;
        }

        return PedidoCliente::query()
            ->with('cliente')
            ->where('proyecto_id', $proyectoId)
            ->where('numero_pedido', $numeroPedido)
            ->first();
    }

    private function buildLineasInicialesFromPedido(PedidoCliente $pedidoCliente, int $proyectoId): array
    {
        $lineas = is_array($pedidoCliente->lista_articulos) ? $pedidoCliente->lista_articulos : [];
        $lineasPedido = collect($this->normalizePedidoLineas($lineas));

        if ($lineasPedido->isEmpty()) {
            return [];
        }

        return $lineasPedido->map(function (array $linea) {
            $cantidad = round((float) ($linea['cantidad'] ?? 0), 2);
            $linea['cantidad'] = $cantidad;
            $linea['cantidad_max'] = max($cantidad, (float) ($linea['cantidad_max'] ?? $cantidad));
            $precioUnitario = (float) ($linea['precio_unitario'] ?? 0);
            $margen = (float) ($linea['margen'] ?? 0);
            $linea['total'] = round($cantidad * $precioUnitario * (1 + ($margen / 100)), 2);

            return $linea;
        })->values()->all();
    }

    private function calculatePedidoPendienteFacturar(PedidoCliente $pedido, int $proyectoId, ?int $excludeAlbaranId = null): float
    {
        $pedido = PedidoCliente::query()
            ->where('proyecto_id', $proyectoId)
            ->with(['albaranesPivot', 'albaran', 'albaranes'])
            ->find($pedido->id) ?? $pedido;

        $albaranes = collect($pedido->albaranesPivot ?? [])
            ->merge($pedido->albaran?->id ? collect([$pedido->albaran]) : collect())
            ->merge($pedido->albaranes ?? collect())
            ->filter(fn (AlbaranCliente $albaran) => (int) ($albaran->proyecto_id ?? $proyectoId) === $proyectoId)
            ->unique('id');

        if ($excludeAlbaranId !== null && $excludeAlbaranId > 0) {
            $albaranes = $albaranes->reject(fn (AlbaranCliente $albaran) => (int) $albaran->id === $excludeAlbaranId)->values();
        }

        $totalFacturado = 0.0;
        foreach ($albaranes as $alb) {
            $importePivot = isset($alb->pivot) ? (float) $alb->pivot->importe_imputado : 0.0;
            $importe = ($importePivot > 0.001) ? $importePivot : (float) ($alb->total ?? 0);
            
            $totalFacturado += $importe;
        }

        $pedidoTotal = round((float) ($pedido->total ?? 0), 2);

        return round(max(0, $pedidoTotal - $totalFacturado), 2);
    }

    private function resolveVisibleAlbaranProyectoId(AlbaranCliente $albaran): ?int
    {
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            return null;
        }

        return $proyectoId;
    }

    public function show(AlbaranCliente $albaran)
    {
        // --- GUARDIA DE LA MURALLA ---
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
        // --- GUARDIA DE LA MURALLA ---
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
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.download');

        return $this->pdfViewer($albaran);
    }

    public function streamPdf(AlbaranCliente $albaran)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.download');

        $proyectoId = $this->resolveVisibleAlbaranProyectoId($albaran);

        if ($proyectoId === null) {
            abort(404);
        }

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

        if ($pdfContent === null) {
            abort(404);
        }

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
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.download');

        $proyectoId = $this->resolveVisibleAlbaranProyectoId($albaran);

        if ($proyectoId === null) {
            abort(404);
        }

        $withPresupuesto = (bool) request()->boolean('with_presupuesto', false);
        [$pdfPath, $pdfContent, $fileName] = $this->buildOrResolvePdf($albaran, $withPresupuesto);

        if ($pdfPath && $pdfContent === null) {
            $disk = Storage::disk('public');
            return response()->file($disk->path($pdfPath), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . basename($pdfPath) . '"',
            ]);
        }

        if ($pdfContent === null) {
            abort(404);
        }

        if ($pdfPath) {
            Storage::disk('public')->put($pdfPath, $pdfContent);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function pantallaRoja(AlbaranCliente $albaran)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.manage');

        return $this->edit($albaran);
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

        // IDs de los pedidos que YA están vinculados a este albarán: deben aparecer
        // en el desplegable aunque estén "facturados" (100% imputados), o si no,
        // al guardar el formulario se pierde el vínculo porque nunca llegan a
        // renderizarse como <option> y por tanto nunca se envían seleccionados.
        $pedidosVinculadosIds = $albaran->pedidosClientes->pluck('id');

        $pedidosClientes = PedidoCliente::query()
            ->with('cliente')
            ->where('proyecto_id', $proyectoId)
            ->where(function ($query) use ($pedidosVinculadosIds) {
                $query->whereNull('estado')
                    ->orWhere('estado', '<>', 'facturado')
                    ->orWhereIn('id', $pedidosVinculadosIds);
            })
            ->orderByDesc('id')
            ->get(['id', 'numero_pedido', 'id_cliente', 'ot', 'bolsa']);

        if ($albaran->cliente && !$clientes->contains('id', $albaran->cliente_id)) {
            $clientes->prepend($albaran->cliente);
        }

        return view('albaranes.edit', compact('albaran', 'clientes', 'pedidosClientes'));
    }

    public function editCorrelativo(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
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
            'max',
            'override',
            'suggested',
            'formatoActual',
            'ejemplo',
            'generadosConFormatoActual',
            'ultimosConFormato'
        ));
    }

    public function updateCorrelativo(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
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

    public function updatePantallaRoja(Request $request, AlbaranCliente $albaran)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.manage');

        return $this->update($request, $albaran);
    }

    public function update(Request $request, AlbaranCliente $albaran)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.manage');

        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        $pedidoAnterior = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);
        $lineasAnteriores = is_array($albaran->lista_articulos) ? $albaran->lista_articulos : [];

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $validated = $request->validate([
            'documento' => 'required|string',
            'numero' => 'required|string',
            'fecha' => 'required|date',
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'ot' => 'nullable|string|max:255',
            'pedidos_cliente' => 'nullable|array',
            'pedidos_cliente.*' => 'string|max:255',
            'titulo' => 'nullable|string|max:255',
            'estado' => ['nullable', Rule::in(['pendiente', 'recibido', 'facturado', 'facturado_parcial', 'cancelado'])],
            'lineas_json' => 'nullable|json',
            'return_to' => 'nullable|string|max:2048',
        ]);

        $lineasRaw = $this->decodeLineasJson($validated['lineas_json'] ?? '[]');
        $this->validateLineasPayload($lineasRaw);
        $lineas = $this->normalizeLineas($validated['lineas_json'] ?? '[]', $lineasRaw);
        $total = collect($lineas)->sum(fn (array $linea) => (float) ($linea['total'] ?? 0));

        // 1. PREPARAMOS EL ARRAY DE BOLSAS ANTES DE LA TRANSACCIÓN
        $pedidosSeleccionados = $validated['pedidos_cliente'] ?? [];
        $validated['pedido_cliente'] = !empty($pedidosSeleccionados) ? $pedidosSeleccionados[0] : null;
        unset($validated['pedidos_cliente']);

        $excedenteFlotante = 0.0;

        // ABRIMOS LA TRANSACCIÓN PARA EL UPDATE
        // ABRIMOS LA TRANSACCIÓN PARA EL UPDATE
        DB::transaction(function () use (&$excedenteFlotante, $albaran, $validated, $lineas, $total, $proyectoId, $lineasAnteriores, $pedidosSeleccionados) {
            
            // 1. CAPTURAR TODOS LOS PEDIDOS ANTES DE MODIFICAR NADA
            $pedidosAnteriores = $albaran->pedidosClientes()->get();
            $pedidoPrimarioAnterior = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);
            if ($pedidoPrimarioAnterior && !$pedidosAnteriores->contains('id', $pedidoPrimarioAnterior->id)) {
                $pedidosAnteriores->push($pedidoPrimarioAnterior);
            }

            // Restaurar stock
            $this->adjustArticulosStock($lineasAnteriores, $proyectoId, 1);
            
            // Actualizar registro
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
                'total' => round($total, 2),
            ]);

            // Consumir stock
            $this->adjustArticulosStock($lineas, $proyectoId, -1);

            // 2. EJECUTAMOS LA CASCADA CONTROLADA MULTI-BOLSA
            $excedenteFlotante = $this->syncPedidoClienteLink($albaran, $proyectoId, $pedidosSeleccionados);
            
            // 3. Ajustar líneas si los pedidos vinculados NO son bolsa (modo legacy)
            $pedidoActual = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);

            foreach ($pedidosAnteriores as $pAnt) {
                if (! (bool) ($pAnt->bolsa ?? false)) {
                    $this->adjustPedidoLineasFromAlbaran($pAnt, $lineasAnteriores, 1);
                }
            }

            if ($pedidoActual && ! (bool) ($pedidoActual->bolsa ?? false)) {
                $this->adjustPedidoLineasFromAlbaran($pedidoActual, $lineas, -1);
            }

            // 4. Recalcular los estados de TODOS los afectados (antiguos y nuevos)
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
                $pdfWith = \Barryvdh\DomPDF\Facade\Pdf::loadView('albaranes.pdf', [
                    'albaran' => $albaran,
                    'with_presupuesto' => true,
                    'presupuesto' => $presupuesto,
                ]);
                Storage::disk('public')->put($filePathWith, $pdfWith->output());

                $pdfWithout = \Barryvdh\DomPDF\Facade\Pdf::loadView('albaranes.pdf', [
                    'albaran' => $albaran,
                    'with_presupuesto' => false,
                    'presupuesto' => null,
                ]);
                Storage::disk('public')->put($filePathWithout, $pdfWithout->output());

                $albaran->forceFill(['archivo_pdf' => $filePathWith])->save();
            } elseif (class_exists(\Dompdf\Dompdf::class)) {
                $htmlWith = view('albaranes.pdf', [
                    'albaran' => $albaran,
                    'with_presupuesto' => true,
                    'presupuesto' => $presupuesto,
                ])->render();
                $dompdfWith = new \Dompdf\Dompdf();
                $dompdfWith->setPaper('A4', 'portrait');
                $dompdfWith->loadHtml($htmlWith);
                $dompdfWith->render();
                Storage::disk('public')->put($filePathWith, $dompdfWith->output());

                $htmlWithout = view('albaranes.pdf', [
                    'albaran' => $albaran,
                    'with_presupuesto' => false,
                    'presupuesto' => null,
                ])->render();
                $dompdfWithout = new \Dompdf\Dompdf();
                $dompdfWithout->setPaper('A4', 'portrait');
                $dompdfWithout->loadHtml($htmlWithout);
                $dompdfWithout->render();
                Storage::disk('public')->put($filePathWithout, $dompdfWithout->output());

                $albaran->forceFill(['archivo_pdf' => $filePathWith])->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $returnTo = trim((string) ($validated['return_to'] ?? ''));
        $appUrl = rtrim((string) config('app.url'), '/');

        // Evaluar si hubo excedente y redirigir con Warning
        if ($excedenteFlotante > 0.001) {
            $mensajeWarning = "Sobrepasaste el saldo de la bolsa. La acción se ha ejecutado, pero han quedado pendientes " . number_format($excedenteFlotante, 2, ',', '.') . " €. Prepare un nuevo pedido bolsa para absorber el excedente.";
            
            if ($returnTo !== '' && $appUrl !== '' && str_starts_with($returnTo, $appUrl)) {
                return redirect($returnTo)->with('warning', $mensajeWarning);
            }
            return redirect()->route('albaranes.index')->with('warning', $mensajeWarning);
        }

        // Si todo cuadró perfecto, redirigir con Success
        if ($returnTo !== '' && $appUrl !== '' && str_starts_with($returnTo, $appUrl)) {
            return redirect($returnTo)->with('success', 'Albarán actualizado correctamente.');
        }

        return redirect()->route('albaranes.index')->with('success', 'Albarán actualizado correctamente.');
    }

    public function updateEstado(Request $request, AlbaranCliente $albaran)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('albaranes.manage');

        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $validated = $request->validate([
            'estado' => ['required', Rule::in(['pendiente', 'recibido', 'facturado', 'facturado_parcial', 'cancelado'])],
        ]);

        $albaran->update([
            'estado' => $validated['estado'],
        ]);

        //AVISAR AL PEDIDO:
        $pedidoActual = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);
        if ($pedidoActual) {
            $this->recalculatePedidoEstado($pedidoActual, $proyectoId);
        }

        return redirect()->back()->with('success', 'Estado del albarán actualizado.');
    }

    public function desvincular(Request $request, AlbaranCliente $albaran)
    {
        $this->authorize('albaranes.manage');
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        $pedidoIdEspecifico = (int) $request->input('pedido_id');
        $lineasAnteriores = is_array($albaran->lista_articulos) ? $albaran->lista_articulos : [];

        DB::transaction(function () use ($albaran, $proyectoId, $pedidoIdEspecifico, $lineasAnteriores) {
            
            if ($pedidoIdEspecifico > 0) {
                // MODO QUIRÚRGICO
                $pedidoAfectado = PedidoCliente::find($pedidoIdEspecifico);

                if ($pedidoAfectado) {
                    $albaran->pedidosClientes()->detach($pedidoIdEspecifico);

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
                }
            } else {
                // MODO DESTRUCCIÓN GLOBAL
                $pedidosAfectados = $albaran->pedidosClientes()->get();
                $pedidoPrimario = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);

                $albaran->update(['pedido_cliente' => null]);
                $albaran->pedidosClientes()->detach();

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
            
            $albaran->pedidosClientes()->detach();
            $this->adjustArticulosStock($lineasAlbaran, $proyectoId, 1);
            $albaran->delete();
        });

        // Recalcular estado de TODOS los pedidos que dependían de este albarán
        $pedidosARecalcular = $pedidosAfectados->push($pedidoPrimario)->filter()->unique('id')->all();
        $this->recalculatePedidosEstadoFromAlbaran($proyectoId, $pedidosARecalcular, $albaran);

        // ... Lógica de retroceder el correlativo se mantiene igual ...
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
            DB::table('contadores')->insert([
                'proyecto_id' => $proyectoId,
                'clave' => $clave,
                'valor' => 1,
            ]);
        }
    }

    private function getContadorValue(int $proyectoId, string $clave): int
    {
        return (int) DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', $clave)
            ->lockForUpdate() // <-- Bloquea la fila para otros hilos de ejecución
            ->value('valor');
    }

    private function setContadorValue(int $proyectoId, string $clave, int $valor): void
    {
        $exists = DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', $clave)
            ->exists();

        if ($exists) {
            DB::table('contadores')
                ->where('proyecto_id', $proyectoId)
                ->where('clave', $clave)
                ->update(['valor' => $valor]);
            return;
        }

        DB::table('contadores')->insert([
            'proyecto_id' => $proyectoId,
            'clave' => $clave,
            'valor' => $valor,
        ]);
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

        $numeros = AlbaranCliente::query()
            ->where('proyecto_id', $proyectoId)
            ->where('numero', 'like', $likePattern) 
            ->pluck('numero');

        foreach ($numeros as $numero) {
            if (!is_string($numero)) {
                continue;
            }

            if (preg_match($regex, $numero, $match) === 1) {
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
            ->filter(fn (AlbaranCliente $albaran) => is_string($albaran->numero)
                && preg_match($regex, $albaran->numero) === 1) 
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

        if (preg_match($pattern, $numero, $match) !== 1) {
            return null;
        }

        return (int) $match[1];
    }

    private function normalizeLineas(?string $lineasJson, ?array $decodedLineas = null): array
    {
        $decoded = $decodedLineas ?? json_decode((string) ($lineasJson ?? '[]'), true);
        if (!is_array($decoded)) {
            return [];
        }

        $lineas = [];

        foreach ($decoded as $linea) {
            if (!is_array($linea)) {
                continue;
            }

            $descripcion = trim((string) ($linea['descripcion'] ?? ''));
            if ($descripcion === '') {
                continue;
            }

            $cantidad = round(max(0, (float) ($linea['cantidad'] ?? 0)), 2);
            $precioUnitario = round(max(0, (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0))), 2);
            $margen = round(max(0, (float) ($linea['margen'] ?? 0)), 2);
            $total = round($cantidad * $precioUnitario * (1 + ($margen / 100)), 2);

            $medida = trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? '')));
            $medida = $medida !== '' ? $medida :'und';

            $articulo = trim((string) ($linea['articulo'] ?? ''));
            $articuloId = isset($linea['articulo_id']) ? (int) $linea['articulo_id'] : null;

            $lineas[] = [
                'articulo_id' => $articuloId,
                'articulo' => $articulo,
                'descripcion' => $descripcion,
                'cantidad' => $cantidad,
                'medida' => $medida,
                'precio_unitario' => $precioUnitario,
                'margen' => $margen,
                'total' => $total,
                'precio' => $precioUnitario,
                'unidad' => $medida,
            ];
        }

        return $lineas;
    }

    private function decodeLineasJson(?string $lineasJson): array
    {
        $decoded = json_decode((string) ($lineasJson ?? '[]'), true);
        if (!is_array($decoded)) {
            return [];
        }

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
        if ($archivoPdf !== '') {
            $candidates[] = $archivoPdf;
        }

        $documento = trim((string) ($albaran->documento ?? ''));
        if ($documento !== '' && str_ends_with(strtolower($documento), '.pdf')) {
            $candidates[] = $documento;
        }

        $disk = Storage::disk('public');
        foreach (array_unique($candidates) as $path) {
            if ($disk->exists($path)) {
                return $path;
            }
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
        if ($withPresupuesto) {
            return $basePdfPath;
        }

        if (!$basePdfPath) {
            return 'albaranes/albaran-' . $albaran->id . '-sin-presupuesto.pdf';
        }

        $directory = dirname($basePdfPath);
        $filename = pathinfo($basePdfPath, PATHINFO_FILENAME);

        return $directory . '/' . $filename . '-sin-presupuesto.pdf';
    }

    private function resolveProyectoIdWithFallback(?int $fallbackProyectoId = null): int
    {
        $sessionProyectoId = (int) request()->session()->get('active_proyecto_id');
        if ($sessionProyectoId > 0) {
            return $sessionProyectoId;
        }

        if ($fallbackProyectoId && $fallbackProyectoId > 0) {
            return $fallbackProyectoId;
        }

        return $this->resolveActiveProyectoId(request());
    }

    private function validateProyectoAccess(int $proyectoId): void
    {
        $user = request()->user();
        if (!$user) {
            abort(401);
        }

        $hasAccess = $user->role === 'superadmin'
            || $user->proyectos()->where('proyectos.id', $proyectoId)->exists();

        if (!$hasAccess) {
            abort(403);
        }
    }

    private function syncPedidoClienteLink(AlbaranCliente $albaran, int $proyectoId, array $pedidosNumeros = []): float
    {
        $importeRestante = (float) ($albaran->total ?? 0);
        
        // 1. Capturamos los pedidos vinculados antes de romper
        $pedidosAnteriores = $albaran->pedidosClientes()->get();

        // 2. Rompemos enlaces previos en la tabla pivote
        $albaran->pedidosClientes()->detach();

        // 3. Limpiamos el rastro legacy (albaran_id) en los pedidos soltados
        foreach ($pedidosAnteriores as $pedidoAnterior) {
            if ($pedidoAnterior->albaran_id === $albaran->id) {
                // Buscamos si le queda algún otro albarán enganchado tras el detach
                $remaining = $pedidoAnterior->albaranesPivot()->first(); 
                $pedidoAnterior->forceFill(['albaran_id' => $remaining ? $remaining->id : null])->save();
            }
        }

        if (empty($pedidosNumeros)) {
            return round($importeRestante, 2);
        }

        // 4. Cargamos ÚNICAMENTE las bolsas que el usuario ha seleccionado
        $pedidos = PedidoCliente::query()
            ->where('proyecto_id', $proyectoId)
            ->whereIn('numero_pedido', $pedidosNumeros)
            ->orderBy('fecha_pedido', 'asc') 
            ->orderBy('id', 'asc')
            ->get();

        // 5. Empezamos la cascada controlada
        foreach ($pedidos as $pedido) {
            if ($importeRestante <= 0.001) break;

            if (! (bool) ($pedido->bolsa ?? false)) {
                $albaran->pedidosClientes()->attach($pedido->id, ['importe_imputado' => round($importeRestante, 2)]);
                if (empty($pedido->albaran_id)) {
                    $pedido->forceFill(['albaran_id' => $albaran->id])->save();
                }
                $importeRestante = 0.0;
                break;
            }

            $pendiente = $this->calculatePedidoPendienteFacturar($pedido, $proyectoId, $albaran->id);
            $asignar = max(0, min($importeRestante, $pendiente));
            
            if ($asignar > 0.001) {
                $albaran->pedidosClientes()->attach($pedido->id, ['importe_imputado' => round($asignar, 2)]);
                $importeRestante -= $asignar;

                if (empty($pedido->albaran_id)) {
                    $pedido->forceFill(['albaran_id' => $albaran->id])->save();
                }
            }
        }

        return max(0, round($importeRestante, 2));
    }

    private function syncPedidoEstadoFromAlbaran(AlbaranCliente $albaran, int $proyectoId, array $lineasDelAlbaran = []): void
    {
        // Capturamos TODOS los pedidos que han recibido saldo en la cascada
        $pedidos = $albaran->pedidosClientes()->get();
        $pedidoPrimario = $this->resolvePedidoFromAlbaran($albaran, $proyectoId);
        
        // Unificamos y mandamos a recalcular
        $pedidosARecalcular = $pedidos->push($pedidoPrimario)->filter()->unique('id')->all();
        $this->recalculatePedidosEstadoFromAlbaran($proyectoId, $pedidosARecalcular, $albaran);
    }

    private function recalculatePedidoEstado(PedidoCliente $pedido, int $proyectoId): void
    {
        $pedido = PedidoCliente::query()
            ->where('proyecto_id', $proyectoId)
            ->with(['albaranesPivot', 'albaran', 'albaranes'])
            ->find($pedido->id) ?? $pedido;

        $pedidoTotal = round((float) ($pedido->total ?? 0), 2);

        $albaranes = collect($pedido->albaranesPivot ?? [])
            ->merge($pedido->albaran?->id ? collect([$pedido->albaran]) : collect())
            ->merge($pedido->albaranes ?? collect())
            ->filter(fn (AlbaranCliente $albaran) => (int) ($albaran->proyecto_id ?? $proyectoId) === $proyectoId)
            ->unique('id'); 

        $totalFacturado = 0.0;
        $albaranesAContar = $pedido->bolsa ? $albaranes : $albaranes->where('estado', 'facturado');
        
        foreach ($albaranesAContar as $alb) {
            $importePivot = isset($alb->pivot) ? (float) $alb->pivot->importe_imputado : 0.0;
            // ESCUDO LEGACY
            $importe = ($importePivot > 0.001) ? $importePivot : (float) ($alb->total ?? 0);
            
            $totalFacturado += $importe;
        }
        
        $nuevoEstado = 'pendiente';
        
        if ($pedidoTotal > 0 && $totalFacturado > 0) {
            $nuevoEstado = $totalFacturado >= ($pedidoTotal - 0.01) ? 'facturado' : 'facturado_parcial';
        }

        $pedido->forceFill([
            'estado' => $nuevoEstado,
        ])->save();
    }

    private function recalculatePedidosEstadoFromAlbaran(int $proyectoId, array $pedidos, AlbaranCliente $albaran): void
    {
        foreach (collect($pedidos)->filter() as $pedido) {
            if ($pedido instanceof PedidoCliente) {
                $this->recalculatePedidoEstado($pedido, $proyectoId);
            }
        }
    }

    private function adjustPedidoLineasFromAlbaran(PedidoCliente $pedido, array $lineasAlbaran, int $direction): void
    {
        if ((bool) ($pedido->bolsa ?? false) || $lineasAlbaran === []) {
            return;
        }

        $pedidoLineas = is_array($pedido->lista_articulos) ? $pedido->lista_articulos : [];

        $indexMap = [];
        foreach ($pedidoLineas as $idx => $lineaPedido) {
            if (!is_array($lineaPedido)) {
                continue;
            }

            $signature = $this->normalizePedidoLineaSignature($lineaPedido);
            if ($signature !== '') {
                $indexMap[$signature][] = $idx;
            }
        }

        foreach ($lineasAlbaran as $lineaAlbaran) {
            if (!is_array($lineaAlbaran)) {
                continue;
            }

            $cantidad = round(max(0, (float) ($lineaAlbaran['cantidad'] ?? 0)), 2);
            if ($cantidad <= 0) {
                continue;
            }

            $signature = $this->normalizePedidoLineaSignature($lineaAlbaran);
            
            // Si el artículo libre no existe en el pedido original, 
            // evitamos que rompa y simplemente lo ignoramos o lo añadimos de forma segura
            if ($signature === '' || !isset($indexMap[$signature])) {
                if ($direction > 0) {
                    // Si estamos sumando y es completamente nuevo, podemos opcionalmente agregarlo al pedido
                    $restored = $this->normalizePedidoLineas([$lineaAlbaran]);
                    if (!empty($restored[0])) {
                        $pedidoLineas[] = $restored[0];
                    }
                }
                continue;
            }

            if ($direction > 0) {
                $idx = $indexMap[$signature][0];
                $pedidoLineas[$idx]['cantidad'] = round((float) ($pedidoLineas[$idx]['cantidad'] ?? 0) + $cantidad, 2);
                $pedidoLineas[$idx]['total'] = round(
                    (float) ($pedidoLineas[$idx]['cantidad'] ?? 0)
                    * max(0, (float) ($pedidoLineas[$idx]['precio_unitario'] ?? ($pedidoLineas[$idx]['precio'] ?? 0)))
                    * (1 + (max(0, (float) ($pedidoLineas[$idx]['margen'] ?? 0)) / 100)),
                    2
                );
                continue;
            }

            foreach ($indexMap[$signature] as $idx) {
                $orig = round((float) ($pedidoLineas[$idx]['cantidad'] ?? 0), 2);
                if ($orig <= 0) {
                    continue;
                }

                $toSubtract = min($orig, $cantidad);
                $pedidoLineas[$idx]['cantidad'] = round(max(0, $orig - $toSubtract), 2);
                $pedidoLineas[$idx]['total'] = round(
                    (float) ($pedidoLineas[$idx]['cantidad'] ?? 0)
                    * max(0, (float) ($pedidoLineas[$idx]['precio_unitario'] ?? ($pedidoLineas[$idx]['precio'] ?? 0)))
                    * (1 + (max(0, (float) ($pedidoLineas[$idx]['margen'] ?? 0)) / 100)),
                    2
                );
                $cantidad = round(max(0, $cantidad - $toSubtract), 2);

                if ($cantidad <= 0) {
                    break;
                }
            }
        }

        $pedidoLineas = array_values(array_filter($pedidoLineas, fn ($lineaPedido) => round((float) ($lineaPedido['cantidad'] ?? 0), 2) > 0));
        $pedido->forceFill(['lista_articulos' => $pedidoLineas])->save();
    }

    private function normalizePedidoLineas(array $lineas): array
    {
        return collect($lineas)
            ->filter(fn ($linea) => is_array($linea) && !empty(trim((string) ($linea['descripcion'] ?? ''))))
            ->map(function (array $linea) {
                $cantidad = max(0, (float) ($linea['cantidad'] ?? 0));
                $precioUnitario = max(0, (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0)));
                $margen = max(0, (float) ($linea['margen'] ?? 0));
                $medida = trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? '')));
                $medida = $medida !== '' ? $medida : null;

                return [
                    'articulo_id' => isset($linea['articulo_id']) ? (int) $linea['articulo_id'] : null,
                    'articulo' => trim((string) ($linea['articulo'] ?? '')),
                    'descripcion' => trim((string) ($linea['descripcion'] ?? '')),
                    'cantidad' => round($cantidad, 2),
                    'medida' => $medida,
                    'precio_unitario' => round($precioUnitario, 2),
                    'margen' => round($margen, 2),
                    'total' => round($cantidad * $precioUnitario * (1 + ($margen / 100)), 2),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizePedidoLineaSignature(array $linea): string
    {
        $descripcion = mb_strtolower(trim((string) ($linea['descripcion'] ?? '')));
        $medida = mb_strtolower(trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? ''))));

        return $descripcion . '|' . $medida;
    }

    private function albaranAportaPedido(AlbaranCliente $albaran, $lineasPedido): bool
    {
        $lineasAlbaran = collect(is_array($albaran->lista_articulos) ? $albaran->lista_articulos : [])
            ->filter(fn ($linea) => is_array($linea) && !empty(trim((string) ($linea['descripcion'] ?? ''))))
            ->map(fn (array $linea) => $this->normalizePedidoLineaSignature($linea))
            ->filter()
            ->values();

        if ($lineasPedido instanceof \Illuminate\Support\Collection) {
            $lineasPedido = $lineasPedido->values();
        } else {
            $lineasPedido = collect($lineasPedido)->values();
        }

        if ($lineasPedido->isEmpty() || $lineasAlbaran->isEmpty()) {
            return false;
        }

        return $lineasAlbaran->intersect($lineasPedido)->isNotEmpty();
    }

    private function resolvePedidoFromAlbaran(AlbaranCliente $albaran, int $proyectoId): ?PedidoCliente
    {
        $pedido = $albaran->pedidosClientes()->first();

        if ($pedido && (int) $pedido->proyecto_id === $proyectoId) {
            return $pedido;
        }

        $pedidoNumero = trim((string) ($albaran->pedido_cliente ?? ''));
        if ($pedidoNumero === '') {
            return null;
        }

        return PedidoCliente::query()
            ->where('proyecto_id', $proyectoId)
            ->where('numero_pedido', $pedidoNumero)
            ->first();
    }

    private function adjustArticulosStock(array $lineas, int $proyectoId, int $direction): void
    {
        // 1. Agrupamos las cantidades solo de aquellas líneas que tienen articulo_id numérico válido
        $consumos = collect($lineas)
            ->filter(fn ($linea) => is_array($linea) && (int) ($linea['articulo_id'] ?? 0) > 0)
            ->groupBy(fn ($linea) => (int) ($linea['articulo_id'] ?? 0))
            ->map(fn ($items) => round($items->sum(fn ($linea) => (float) ($linea['cantidad'] ?? 0)), 2));

        if ($consumos->isEmpty()) {
            return; // Compatibilidad con datos antiguos: Si no hay IDs, no hacemos nada y no rompe.
        }

        // 2. Buscamos los artículos aplicando bloqueo pesimista
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
            
            // $direction: -1 (restar del stock), 1 (sumar/restaurar stock)
            $restante = $direction < 0 
                ? round(max(0, $cantidadActual - $consumo), 2) // Max 0 evita stock negativo residual
                : round($cantidadActual + $consumo, 2);

            $precioUnitario = (float) ($articulo->precio_unitario ?? 0);
            $margen = (float) ($articulo->margen ?? 0);

            $articulo->cantidad = $restante;
            $articulo->facturado = $restante <= 0;
                    $articulo->total = round($restante * $precioUnitario * (1 + ($margen / 100)), 2);
            $articulo->save();
        }
    }
}