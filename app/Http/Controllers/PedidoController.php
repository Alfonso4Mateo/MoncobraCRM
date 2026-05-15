<?php

namespace App\Http\Controllers;

use App\Models\AlbaranCliente;
use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\PedidoCliente;
use App\Models\Presupuesto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
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

        $pedidosQuery = PedidoCliente::query()
            ->with(['cliente', 'presupuesto', 'albaran', 'albaranesPivot'])
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
            } catch (\Throwable $exception) {
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

            return $pedido;
        });

        return view('pedidos-clientes.index', [
            'pedidos' => $pedidos,
            'searchActual' => $search,
            'estadoActual' => $estado,
            'desdeActual' => $desde,
            'hastaActual' => $hasta,
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

        $clientes = Cliente::query()
            ->where('proyecto_id', $proyectoId)
            ->orderBy('empresa_nombre')
            ->get();

        $presupuestos = Presupuesto::query()
            ->where('proyecto_id', $proyectoId)
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

        $numeroPedidoAuto = $this->resolveNextPedidoClienteNumber($proyectoId);
        $fechaPedido = (string) $request->query('fecha_pedido', now()->toDateString());

        $lineasInicialesRaw = is_array($presupuestoSeleccionado?->lista_articulos)
            ? $presupuestoSeleccionado->lista_articulos
            : [];

        $lineasIniciales = collect($lineasInicialesRaw)
            ->filter(fn ($linea) => is_array($linea) && !empty(trim((string) ($linea['descripcion'] ?? ''))))
            ->map(function (array $linea) {
                $cantidad = max(0, (float) ($linea['cantidad'] ?? 0));
                $precioUnitario = max(0, (float) ($linea['precio_unitario'] ?? 0));
                $margen = max(0, (float) ($linea['margen'] ?? 0));
                $total = $cantidad * $precioUnitario * (1 + ($margen / 100));

                return [
                    'articulo' => trim((string) ($linea['articulo'] ?? '')),
                    'descripcion' => trim((string) ($linea['descripcion'] ?? '')),
                    'cantidad' => round($cantidad, 2),
                    'medida' => trim((string) ($linea['medida'] ?? $linea['unidad'] ?? '')),
                    'precio_unitario' => round($precioUnitario, 2),
                    'margen' => round($margen, 2),
                    'total' => round($total, 2),
                ];
            })
            ->values()
            ->all();

        $baseImponible = round((float) ($presupuestoSeleccionado?->total ?? 0), 2);
        $totalPedido = $baseImponible;
        $presupuestosParaPedido = $presupuestos->map(function (Presupuesto $presupuesto) {
            $lineas = is_array($presupuesto->lista_articulos) ? $presupuesto->lista_articulos : [];

            return [
                'id' => $presupuesto->id,
                'cliente_id' => $presupuesto->cliente_id,
                'numero' => $presupuesto->numero,
                'titulo' => $presupuesto->titulo,
                'cliente_nombre' => $presupuesto->cliente?->empresa_nombre,
                'lineas' => collect($lineas)
                    ->filter(fn ($linea) => is_array($linea) && !empty(trim((string) ($linea['descripcion'] ?? ''))))
                    ->map(function (array $linea) {
                        $cantidad = max(0, (float) ($linea['cantidad'] ?? 0));
                        $precioUnitario = max(0, (float) ($linea['precio_unitario'] ?? 0));
                        $margen = max(0, (float) ($linea['margen'] ?? 0));
                        $total = $cantidad * $precioUnitario * (1 + ($margen / 100));

                        return [
                            'articulo' => trim((string) ($linea['articulo'] ?? '')),
                            'descripcion' => trim((string) ($linea['descripcion'] ?? '')),
                            'cantidad' => round($cantidad, 2),
                            'medida' => trim((string) ($linea['medida'] ?? $linea['unidad'] ?? '')),
                            'precio_unitario' => round($precioUnitario, 2),
                            'margen' => round($margen, 2),
                            'total' => round($total, 2),
                        ];
                    })
                    ->values()
                    ->all(),
            ];
        })->values()->all();

        return view('pedidos-clientes.create', compact(
            'clientes',
            'clienteSeleccionado',
            'clienteSeleccionadoId',
            'presupuestos',
            'presupuestoSeleccionado',
            'presupuestoSeleccionadoId',
            'numeroPedidoAuto',
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

        $validated = $request->validate([
            'numero_pedido' => 'required|string|max:80',
            'fecha_pedido' => 'required|date',
            'id_cliente' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],
            'ot' => 'nullable|string|max:100',
            'presupuesto_id' => 'nullable|integer|exists:presupuestos,id',
            'estado' => 'nullable|string|max:30',
            'total' => 'nullable|numeric|min:0',
            'lista_articulos' => 'nullable|json',
        ]);

        $clienteValido = Cliente::query()
            ->where('proyecto_id', $proyectoId)
            ->where('id', $validated['id_cliente'])
            ->exists();

        if (!$clienteValido) {
            abort(404);
        }

        $presupuestoId = null;
        if (!empty($validated['presupuesto_id'])) {
            $presupuestoId = Presupuesto::query()
                ->where('proyecto_id', $proyectoId)
                ->where('id', $validated['presupuesto_id'])
                ->value('id');
        }

        $lineas = json_decode((string) ($validated['lista_articulos'] ?? '[]'), true);
        $lineas = is_array($lineas) ? $lineas : [];

        $lineas = collect($lineas)
            ->filter(fn ($linea) => is_array($linea) && !empty(trim((string) ($linea['descripcion'] ?? ''))))
            ->map(function (array $linea) {
                $cantidad = max(0, (float) ($linea['cantidad'] ?? 0));
                $precioUnitario = max(0, (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0)));
                $margen = max(0, (float) ($linea['margen'] ?? 0));

                $total = $cantidad * $precioUnitario * (1 + ($margen / 100));

                $medida = trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? '')));
                $medida = $medida !== '' ? $medida : null;

                return [
                    'articulo' => trim((string) ($linea['articulo'] ?? '')),
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

        PedidoCliente::create([
            'id_cliente' => $validated['id_cliente'],
            'proyecto_id' => $proyectoId,
            'numero_pedido' => $validated['numero_pedido'],
            'fecha_pedido' => Carbon::parse($validated['fecha_pedido'])->toDateString(),
            'ot' => $validated['ot'] ?? null,
            'presupuesto_id' => $presupuestoId,
            'albaran_id' => null,
            'estado' => $validated['estado'] ?? 'pendiente',
            'total' => round($total, 2),
            'lista_articulos' => $lineas ?: null,
        ]);

        $this->syncArticulosFromLineas($proyectoId, $lineas);

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
        $pendienteFacturar = round(max(0, $totalPedido - $totalAlbaranes), 2);

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

        return view('pedidos-clientes.albaranes', [
            'pedidoCliente' => $pedidoCliente,
            'albaranes' => $albaranes,
            'totalPedido' => $totalPedido,
            'totalAlbaranes' => $totalAlbaranes,
            'pendienteFacturar' => $pendienteFacturar,
            'titulo' => 'Albaranes del pedido',
            'breadcrumb' => 'Albaranes del pedido',
        ]);
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

    private function resolveNextPedidoClienteNumber(int $proyectoId): string
    {
        $nextIndex = (int) PedidoCliente::query()
            ->where('proyecto_id', $proyectoId)
            ->count() + 1;

        return sprintf('PC-%s-%03d', now()->format('Y'), $nextIndex);
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
        $fileName = 'pedido-' . ($pedido->numero_pedido ?: $pedido->id) . '.pdf';

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
}
