<?php

namespace App\Http\Controllers;

use App\Models\AlbaranCliente;
use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\PedidoCliente;
use App\Models\Presupuesto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AlbaranClienteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $buscar = trim((string) $request->query('buscar', ''));
        $desde = trim((string) $request->query('desde', ''));
        $hasta = trim((string) $request->query('hasta', ''));

        $albaranesQuery = AlbaranCliente::query()
            ->with('cliente')
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
                // Ignore invalid dates and keep the query usable.
            }
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
            ->get(['id', 'numero_pedido'])
            ->keyBy(fn (PedidoCliente $pedido) => trim((string) $pedido->numero_pedido));

        $albaranes->getCollection()->transform(function (AlbaranCliente $albaran) use ($presupuestos, $pedidos) {
            $presupuestoNumero = trim((string) $albaran->documento);
            $pedidoNumero = trim((string) $albaran->pedido_cliente);

            $presupuestoRelacionado = $presupuestoNumero !== '' ? $presupuestos->get($presupuestoNumero) : null;
            $pedidoRelacionado = $pedidoNumero !== '' ? $pedidos->get($pedidoNumero) : null;
            $totalAlbaran = round((float) ($albaran->total ?? 0), 2);

            $albaran->ui_presupuesto_id = $presupuestoRelacionado?->id;
            $albaran->ui_total = $totalAlbaran > 0
                ? $totalAlbaran
                : ($presupuestoRelacionado ? (float) $presupuestoRelacionado->total : 0);
            $albaran->ui_pedido_id = $pedidoRelacionado?->id;
            $albaran->estado = $albaran->estado ?: 'pendiente';

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
            'totalAlbaranes',
            'pendientesEntrega',
            'entregadosHoy',
            'variacionMensual',
            'variacionEntregadosHoy'
        ));
    }

    public function create(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);
        $clientes = Cliente::where('proyecto_id', $proyectoId)->orderBy('empresa_nombre')->get();
        $pedidosClientes = PedidoCliente::query()
            ->with('cliente')
            ->where('proyecto_id', $proyectoId)
            ->orderByDesc('id')
            ->get(['id', 'numero_pedido', 'id_cliente', 'ot']);
        $pedidoContext = $this->resolvePedidoContext($request, $proyectoId);
        $lineasIniciales = $pedidoContext ? $this->buildLineasInicialesFromPedido($pedidoContext, $proyectoId) : [];
        $pedidoDefaults = [
            'cliente_id' => $pedidoContext?->id_cliente,
            'pedido_cliente' => $pedidoContext?->numero_pedido,
            'ot' => $pedidoContext?->ot,
            'pedido_id' => $pedidoContext?->id,
        ];

        return view('albaranes.create', compact('clientes', 'pedidosClientes', 'pedidoContext', 'lineasIniciales', 'pedidoDefaults'));
    }

    public function store(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'numero' => 'required|string',
            'fecha' => 'required|date',
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'ot' => 'nullable|string|max:255',
            'pedido_cliente' => 'nullable|string|max:255',
            'titulo' => 'nullable|string|max:255',
            'lineas_json' => 'nullable|json',
            'estado' => ['nullable', Rule::in(['pendiente', 'recibido', 'entregado'])],
            'archivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $lineas = $this->normalizeLineas($validated['lineas_json'] ?? '[]');

        $validated['proyecto_id'] = $proyectoId;
        $validated['documento'] = 'Albarán';
        $validated['estado'] = $validated['estado'] ?? 'pendiente';
        $validated['lista_articulos'] = $lineas === [] ? null : $lineas;
        $validated['total'] = collect($lineas)->sum(fn (array $linea) => (float) ($linea['total'] ?? 0));
        unset($validated['lineas_json']);

        if ($request->hasFile('archivo_pdf')) {
            $validated['archivo_pdf'] = $request->file('archivo_pdf')->store('albaranes', 'public');
        }

        $albaran = AlbaranCliente::create($validated);
        $this->syncPedidoClienteLink($albaran, $proyectoId);

        $articuloIdsFacturados = collect($lineas)
            ->pluck('articulo_id')
            ->filter(fn ($value) => (int) $value > 0)
            ->unique()
            ->values();

        if ($articuloIdsFacturados->isNotEmpty()) {
            Articulo::query()
                ->where('proyecto_id', $proyectoId)
                ->whereIn('id', $articuloIdsFacturados)
                ->update(['facturado' => true]);
        }

        return redirect()->route('albaranes.index')->with('success', 'Albarán creado');
    }

    private function resolvePedidoContext(Request $request, int $proyectoId): ?PedidoCliente
    {
        $pedidoId = (int) $request->query('pedido_id', 0);
        if ($pedidoId > 0) {
            $pedido = PedidoCliente::query()
                ->with('cliente')
                ->where('proyecto_id', $proyectoId)
                ->find($pedidoId);

            if ($pedido) {
                return $pedido;
            }
        }

        $numeroPedido = trim((string) $request->query('pedido_cliente', ''));
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

        return collect($lineas)
            ->filter(fn ($linea) => is_array($linea) && !empty(trim((string) ($linea['descripcion'] ?? ''))))
            ->map(function (array $linea) use ($proyectoId) {
                $numeroReferencia = trim((string) ($linea['articulo'] ?? ''));
                if ($numeroReferencia === '') {
                    return null;
                }

                $articulo = Articulo::query()
                    ->where('proyecto_id', $proyectoId)
                    ->where('numero_referencia', $numeroReferencia)
                    ->where(function ($query) {
                        $query->where('facturado', false)
                            ->orWhereNull('facturado');
                    })
                    ->first();

                if (!$articulo) {
                    return null;
                }

                $cantidad = round(max(0, (float) ($linea['cantidad'] ?? $articulo->cantidad ?? 0)), 2);
                $precioUnitario = round(max(0, (float) ($linea['precio_unitario'] ?? $linea['precio'] ?? $articulo->precio_unitario ?? 0)), 2);
                $margen = round(max(0, (float) ($linea['margen'] ?? $articulo->margen ?? 0)), 2);
                $medida = trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? $articulo->medida ?? '')));
                $medida = $medida !== '' ? $medida : null;
                $descripcion = trim((string) ($linea['descripcion'] ?? $articulo->descripcion ?? ''));
                $total = round($cantidad * $precioUnitario * (1 + ($margen / 100)), 2);

                return [
                    'articulo_id' => $articulo->id,
                    'articulo' => $numeroReferencia,
                    'descripcion' => $descripcion,
                    'cantidad' => $cantidad,
                    'medida' => $medida,
                    'precio_unitario' => $precioUnitario,
                    'margen' => $margen,
                    'total' => $total,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function show(AlbaranCliente $albaran)
    {
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return view('albaranes.preview', [
            'albaran' => $albaran,
            'pdfUrl' => route('albaranes.pdf.file', $albaran),
            'downloadUrl' => route('albaranes.pdf.download', $albaran),
        ]);
    }

    public function pdfViewer(AlbaranCliente $albaran)
    {
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return view('albaranes.preview', [
            'albaran' => $albaran,
            'pdfUrl' => route('albaranes.pdf.file', $albaran),
            'downloadUrl' => route('albaranes.pdf.download', $albaran),
        ]);
    }

    public function preview(AlbaranCliente $albaran)
    {
        return $this->pdfViewer($albaran);
    }

    public function streamPdf(AlbaranCliente $albaran)
    {
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        [$pdfPath, $pdfContent, $fileName] = $this->buildOrResolvePdf($albaran);

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
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        [$pdfPath, $pdfContent, $fileName] = $this->buildOrResolvePdf($albaran);

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
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $clientes = Cliente::where('proyecto_id', $proyectoId)->orderBy('empresa_nombre')->get();

        if ($albaran->cliente && !$clientes->contains('id', $albaran->cliente_id)) {
            $clientes->prepend($albaran->cliente);
        }

        return view('albaranes.pantalla-roja', compact('albaran', 'clientes'));
    }

    public function updatePantallaRoja(Request $request, AlbaranCliente $albaran)
    {
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

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
            'pedido_cliente' => 'nullable|string|max:255',
            'titulo' => 'nullable|string|max:255',
            'estado' => ['required', Rule::in(['pendiente', 'recibido', 'entregado'])],
        ]);

        $albaran->update([
            'documento' => $validated['documento'],
            'numero' => $validated['numero'],
            'fecha' => $validated['fecha'],
            'cliente_id' => $validated['cliente_id'],
            'ot' => $validated['ot'] ?? null,
            'pedido_cliente' => $validated['pedido_cliente'] ?? null,
            'titulo' => $validated['titulo'] ?? null,
            'estado' => $validated['estado'],
        ]);

        $this->syncPedidoClienteLink($albaran, $proyectoId);

        return redirect()
            ->route('albaranes.pantalla-roja', $albaran)
            ->with('success', 'Albarán actualizado correctamente.');
    }

    public function updateEstado(Request $request, AlbaranCliente $albaran)
    {
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        if ($this->isDelivered($albaran)) {
            return redirect()->back()->with('error', 'El albarán ya está entregado y no admite cambios.');
        }

        $validated = $request->validate([
            'estado' => ['required', Rule::in(['pendiente', 'recibido', 'entregado'])],
        ]);

        $albaran->update([
            'estado' => $validated['estado'],
        ]);

        return redirect()->back()->with('success', 'Estado del albarán actualizado.');
    }

    public function destroy(AlbaranCliente $albaran)
    {
        $proyectoId = $this->resolveProyectoIdWithFallback((int) $albaran->proyecto_id);
        $this->validateProyectoAccess($proyectoId);

        if ((int) $albaran->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $albaran->delete();
        return redirect()->route('albaranes.index')->with('success', 'Albarán eliminado');
    }

    private function isDelivered(AlbaranCliente $albaran): bool
    {
        return strtolower((string) ($albaran->estado ?? '')) === 'entregado';
    }

    private function normalizeLineas(?string $lineasJson): array
    {
        $decoded = json_decode((string) ($lineasJson ?? '[]'), true);
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
            $medida = $medida !== '' ? $medida : null;

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
                // compatibilidad con formatos antiguos
                'precio' => $precioUnitario,
                'unidad' => $medida,
            ];
        }

        return $lineas;
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

    private function buildOrResolvePdf(AlbaranCliente $albaran): array
    {
        $disk = Storage::disk('public');
        $pdfPath = $this->resolvePdfPath($albaran);
        $fileName = 'albaran-' . ($albaran->numero ?: $albaran->id) . '.pdf';

        if ($pdfPath && $disk->exists($pdfPath)) {
            return [$pdfPath, null, basename($pdfPath)];
        }

        $pdfContent = null;

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('albaranes.pdf', ['albaran' => $albaran]);
            $pdfContent = $pdf->output();
        } elseif (class_exists(\Dompdf\Dompdf::class)) {
            $html = view('albaranes.pdf', ['albaran' => $albaran])->render();
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            $pdfContent = $dompdf->output();
        }

        if ($pdfContent !== null) {
            $pdfPath = $pdfPath ?: 'albaranes/albaran-' . $albaran->id . '.pdf';
            $albaran->forceFill(['archivo_pdf' => $pdfPath])->save();
        }

        return [$pdfPath, $pdfContent, $fileName];
    }

    /**
     * Resolve proyecto_id with fallback to model's proyecto_id if session is not available.
     * This allows accessing resources even without an active session proyecto.
     */
    private function resolveProyectoIdWithFallback(?int $fallbackProyectoId = null): int
    {
        $sessionProyectoId = (int) request()->session()->get('active_proyecto_id');
        if ($sessionProyectoId > 0) {
            return $sessionProyectoId;
        }

        if ($fallbackProyectoId && $fallbackProyectoId > 0) {
            return $fallbackProyectoId;
        }

        // If no fallback available, try to enforce session (original behavior)
        return $this->resolveActiveProyectoId(request());
    }

    /**
     * Validate user access to a specific proyecto.
     */
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

    private function syncPedidoClienteLink(AlbaranCliente $albaran, int $proyectoId): void
    {
        $pedidoNumero = trim((string) ($albaran->pedido_cliente ?? ''));

        if ($pedidoNumero === '') {
            $albaran->pedidosClientes()->detach();
            return;
        }

        $pedidoId = PedidoCliente::query()
            ->where('proyecto_id', $proyectoId)
            ->where('numero_pedido', $pedidoNumero)
            ->value('id');

        if (!$pedidoId) {
            $albaran->pedidosClientes()->detach();
            return;
        }

        $albaran->pedidosClientes()->sync([$pedidoId]);

        $pedido = PedidoCliente::query()->find($pedidoId);
        if ($pedido && empty($pedido->albaran_id)) {
            $pedido->forceFill(['albaran_id' => $albaran->id])->save();
        }
    }
}
