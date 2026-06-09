<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use Carbon\Carbon;
use App\Models\Presupuesto;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PresupuestoController extends Controller
{
    private const MAX_LINEAS = 5000;
    private const MAX_DESCRIPCION = 500000;
    private const MAX_ARTICULO = 1000;
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
        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        $search = trim((string) $request->input('search', ''));
        $fechaDesde = trim((string) $request->input('fecha_desde', ''));
        $fechaHasta = trim((string) $request->input('fecha_hasta', ''));
        $estado = trim((string) $request->input('estado', 'todos'));

        if (!in_array($estado, ['todos', 'pendiente', 'aceptado', 'rechazado', 'pendiente pedido'], true)) {
            $estado = 'todos';
        }

        $presupuestosQuery = Presupuesto::with(['cliente', 'pedidoCliente'])
            ->where('proyecto_id', $proyectoId);

        if ($search !== '') {
            $like = '%' . $search . '%';
            $dateValue = null;

            try {
                $dateValue = Carbon::parse($search)->toDateString();
            } catch (\Throwable $exception) {
                $dateValue = null;
            }

            $presupuestosQuery->where(function ($query) use ($like, $dateValue) {
                $query->where('documento', 'like', $like)
                    ->orWhere('numero', 'like', $like)
                    ->orWhere('ot', 'like', $like)
                    ->orWhereHas('cliente', function ($clienteQuery) use ($like) {
                        $clienteQuery->where('empresa_nombre', 'like', $like);
                    });

                if ($dateValue) {
                    $query->orWhereDate('fecha', $dateValue);
                }
            });
        }

        if ($fechaDesde !== '') {
            $presupuestosQuery->whereDate('fecha', '>=', $fechaDesde);
        }

        if ($fechaHasta !== '') {
            $presupuestosQuery->whereDate('fecha', '<=', $fechaHasta);
        }

        if ($estado !== 'todos') {
            $presupuestosQuery->where('estado', $estado);
        }

        $presupuestos = $presupuestosQuery
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('presupuestos.index', compact('presupuestos', 'search', 'fechaDesde', 'fechaHasta', 'estado'));
    }

    public function create(Request $request)
    {
        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        $clientes = Cliente::where('proyecto_id', $proyectoId)->orderBy('empresa_nombre')->get();
        $siguienteNumero = $this->nextNumeroPresupuestoCorrelativo($proyectoId)['numero'];

        $clienteSeleccionadoId = (int) $request->query('cliente_id', 0);
        if ($clienteSeleccionadoId > 0 && !$clientes->contains('id', $clienteSeleccionadoId)) {
            $clienteSeleccionadoId = 0;
        }

        $volverACliente = $request->boolean('volver_cliente') && $clienteSeleccionadoId > 0;

        $modo = (string) $request->query('modo', 'nuevo');

        return view('presupuestos.create', compact('clientes', 'clienteSeleccionadoId', 'volverACliente', 'modo', 'siguienteNumero'));
    }

    public function store(Request $request)
    {
        $proyectoId = $this->resolveProyectoForCorrelativo($request);

        $redirectClienteId = (int) $request->input('redirect_cliente_id', 0);
        $modo = (string) $request->input('modo', 'nuevo');
        $archivoPdfRule = $modo === 'carga' ? 'required' : 'nullable';

        $validated = $request->validate([
            'documento' => 'required|string|max:50',
            'numero' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('presupuestos', 'numero')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'fecha' => 'required|date',
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'titulo' => 'nullable|string|max:255',
            'ot' => 'nullable|string|max:255',
            'validez_oferta' => 'nullable|string|max:255',
            'exclusiones' => 'nullable|string|max:2000',
            'archivo_pdf' => [$archivoPdfRule, 'file', 'mimes:pdf', 'max:10240'],
            'lista_articulos' => 'nullable|json',
        ]);

        $numeroManual = trim((string) ($validated['numero'] ?? ''));
        $correlativoActual = $this->nextNumeroPresupuestoCorrelativo($proyectoId);
        $formatoActual = $correlativoActual['formato'];
        $numeroFinal = $numeroManual !== '' ? $numeroManual : $correlativoActual['numero'];
        $correlativoFinal = $correlativoActual['correlativo'];

        $manualCorrelativo = $this->extractCorrelativoFromNumero($formatoActual, $numeroFinal);
        if ($manualCorrelativo !== null) {
            $correlativoFinal = $manualCorrelativo;
        }

        $validated['numero'] = $numeroFinal;

        $validated['proyecto_id'] = $proyectoId;
        $validated['numero_correlativo'] = $correlativoFinal;

        if ($request->hasFile('archivo_pdf')) {
            $validated['archivo_pdf'] = $request->file('archivo_pdf')->store('presupuestos', 'public');
        }

        $listaArticulos = json_decode((string) ($validated['lista_articulos'] ?? '[]'), true);
        $listaArticulos = is_array($listaArticulos) ? $listaArticulos : [];
        $lineasFiltradas = collect($listaArticulos)
            ->filter(fn ($item) => is_array($item) && !empty(trim((string) ($item['descripcion'] ?? ''))))
            ->values()
            ->all();

        $this->validateLineasPayload($lineasFiltradas);

        $validated['lista_articulos'] = collect($lineasFiltradas)
            ->map(function (array $item) {
                $cantidad = max(0, (float) ($item['cantidad'] ?? 0));
                $precioUnitario = max(0, (float) ($item['precio_unitario'] ?? ($item['precio'] ?? 0)));
                $margen = max(0, (float) ($item['margen'] ?? 0));

                $cantidadRounded = round($cantidad, 2);
                $precioUnitarioRounded = round($precioUnitario, 2);
                $margenRounded = round($margen, 2);

                // Apply margin to unit price on server-side (only once)
                $precioConMargen = $precioUnitarioRounded * (1 + ($margenRounded / 100));
                $precioConMargenRounded = round($precioConMargen, 2);
                $totalComputed = round($precioConMargenRounded * $cantidadRounded, 2);

                $medida = trim((string) ($item['medida'] ?? ($item['unidad'] ?? '')));
                $medida = $medida !== '' ? $medida : null;

                return [
                    'articulo' => trim((string) ($item['articulo'] ?? '')),
                    'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                    'cantidad' => $cantidadRounded,
                    'medida' => $medida,
                    // compatibilidad con formatos antiguos
                    'unidad' => $medida,
                    'precio_unitario' => $precioUnitarioRounded,
                    'margen' => $margenRounded,
                    'precio_con_margen' => $precioConMargenRounded,
                    'total' => $totalComputed,
                ];
            })
            ->values()
            ->all();

        if ($validated['lista_articulos'] === []) {
            $validated['lista_articulos'] = null;
        }

        $validated['total'] = collect($validated['lista_articulos'] ?? [])->sum(function (array $item) {
            return (float) ($item['total'] ?? 0);
        });
        $validated['estado'] = 'pendiente';

        $presupuesto = Presupuesto::create($validated);
        $this->syncArticulosFromLineas($proyectoId, $validated['lista_articulos'] ?? []);

        $siguienteCorrelativo = max($correlativoActual['correlativo'] + 1, ($manualCorrelativo !== null ? $manualCorrelativo + 1 : 0));
        $this->setContadorValue($proyectoId, 'presupuestos_next_correlativo', $siguienteCorrelativo);

        // Attempt to generate and store a PDF copy of the presupuesto if a PDF library is available
        try {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('presupuestos.pdf', compact('presupuesto'));
                $filePath = 'presupuestos/presupuesto-' . $presupuesto->id . '.pdf';
                Storage::disk('public')->put($filePath, $pdf->output());
                $presupuesto->update(['archivo_pdf' => $filePath]);
            } elseif (class_exists(\Dompdf\Dompdf::class)) {
                $html = view('presupuestos.pdf', compact('presupuesto'))->render();
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->loadHtml($html);
                $dompdf->render();
                $filePath = 'presupuestos/presupuesto-' . $presupuesto->id . '.pdf';
                Storage::disk('public')->put($filePath, $dompdf->output());
                $presupuesto->update(['archivo_pdf' => $filePath]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if ($redirectClienteId > 0 && $redirectClienteId === (int) $validated['cliente_id']) {
            return redirect()->route('clientes.show', $redirectClienteId)->with('success', 'Presupuesto cargado correctamente');
        }

        return redirect()->route('presupuestos.index')->with('success', 'Presupuesto creado');
    }

    public function show(Presupuesto $presupuesto)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $presupuesto->load([
            'cliente',
            'pedidosClientes.cliente',
            'pedidosClientes.albaran',
        ]);

        return view('presupuestos.show', compact('presupuesto'));
    }

    public function viewPdf(Presupuesto $presupuesto)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return $this->renderPdfResponse($presupuesto, false);
    }

    public function downloadPdf(Presupuesto $presupuesto)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return $this->renderPdfResponse($presupuesto, true);
    }

    public function preview(Presupuesto $presupuesto)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $presupuesto->loadMissing('cliente');

        return view('presupuestos.preview', [
            'presupuesto' => $presupuesto,
            'pdfUrl' => route('presupuestos.pdf', $presupuesto),
            'downloadUrl' => route('presupuestos.pdf.download', $presupuesto),
        ]);
    }

    public function edit(Request $request, Presupuesto $presupuesto)
    {
        $proyectoId = $this->resolveProyectoForCorrelativo($request);

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $estado = (string) ($presupuesto->estado ?: 'pendiente');
        if (in_array($estado, ['aceptado', 'rechazado'], true)) {
            return redirect()->route('presupuestos.show', $presupuesto)
                ->with('error', 'No se pueden editar presupuestos ' . $estado . 's.');
        }

        $clientes = Cliente::where('proyecto_id', $proyectoId)->orderBy('empresa_nombre')->get();
        $siguienteNumero = $this->nextNumeroPresupuestoCorrelativo($proyectoId)['numero'];

        if ($presupuesto->cliente && !$clientes->contains('id', $presupuesto->cliente_id)) {
            $clientes->prepend($presupuesto->cliente);
        }

        return view('presupuestos.edit', compact('presupuesto', 'clientes', 'siguienteNumero'));
    }

    public function editCorrelativo(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        $formatoActual = $this->getCorrelativoFormato($proyectoId);
        $stats = $this->correlativoStatsForFormato($proyectoId, $formatoActual);
        $max = $stats['max'];
        $countFormato = $stats['count'];
        $override = DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', 'presupuestos_next_correlativo')
            ->value('valor');

        $suggested = max(($max ?? 0) + 1, (int) ($override ?? 0));
        $ejemplo = $this->formatCorrelativoNumero($formatoActual, $suggested);

        $ultimosConFormato = $stats['ultimos'];

        return view('presupuestos.correlativo', compact(
            'max',
            'override',
            'suggested',
            'formatoActual',
            'ejemplo',
            'countFormato',
            'ultimosConFormato'
        ));
    }

    public function updateCorrelativo(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        $proyectoId = $this->resolveProyectoForCorrelativo($request);

        $validated = $request->validate([
            'formato' => ['required', 'string', 'max:100', 'regex:/^.+-0000$/'],
            'next' => ['required', 'integer', 'min:1'],
        ]);

        $formato = trim((string) $validated['formato']);
        $next = (int) $validated['next'];
        $max = $this->maxCorrelativoForFormato($proyectoId, $formato);

        if ($next <= ($max ?? 0)) {
            return back()->withErrors(['next' => 'El número debe ser mayor que el máximo correlativo existente (' . ($max ?? 0) . ').']);
        }

        $this->setCorrelativoFormato($proyectoId, $formato);
        $this->setContadorValue($proyectoId, 'presupuestos_next_correlativo', $next);

        return redirect()->route('presupuestos.index')->with('success', 'Correlativo actualizado. Siguiente número: ' . $this->formatCorrelativoNumero($formato, $next));
    }

    public function updateEstado(Request $request, Presupuesto $presupuesto)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        if ((int) $presupuesto->proyecto_id !== (int) $proyectoId) {
            abort(404);
        }

        $validated = $request->validate([
            'estado' => ['required', Rule::in(['pendiente', 'aceptado', 'rechazado', 'pendiente pedido'])],
        ]);

        $presupuesto->update([
            'estado' => $validated['estado'],
        ]);

        return redirect()->route('presupuestos.index')->with('success', 'Estado del presupuesto actualizado');
    }

    public function update(Request $request, Presupuesto $presupuesto)
    {
        $proyectoId = $presupuesto->proyecto_id ?: $this->resolveActiveProyectoId($request);

        if ((int) $presupuesto->proyecto_id !== (int) $proyectoId) {
            abort(404);
        }

        // En edición solo se permite cambiar los artículos
        $validated = $request->validate([
            'lista_articulos' => 'nullable|json',
            'validez_oferta' => 'nullable|string|max:255',
            'exclusiones' => 'nullable|string|max:2000',
        ]);

        $listaArticulos = json_decode((string) ($validated['lista_articulos'] ?? '[]'), true);
        $listaArticulos = is_array($listaArticulos) ? $listaArticulos : [];
        $lineasFiltradas = collect($listaArticulos)
            ->filter(fn ($item) => is_array($item) && !empty(trim((string) ($item['descripcion'] ?? ''))))
            ->values()
            ->all();

        $this->validateLineasPayload($lineasFiltradas);

        $articulosNormalizados = collect($lineasFiltradas)
            ->map(function (array $item) {
                $cantidad = max(0, (float) ($item['cantidad'] ?? 0));
                $precioUnitario = max(0, (float) ($item['precio_unitario'] ?? 0));
                $margen = max(0, (float) ($item['margen'] ?? 0));

                $cantidadInt = (int) max(0, round($cantidad, 0));
                $precioUnitarioRounded = round($precioUnitario, 2);
                $margenRounded = round($margen, 2);

                // Apply margin to unit price on server-side (only once)
                $precioConMargen = $precioUnitarioRounded * (1 + ($margenRounded / 100));
                $precioConMargenRounded = round($precioConMargen, 2);
                $totalComputed = round($precioConMargenRounded * $cantidadInt, 2);

                return [
                    'articulo' => trim((string) ($item['articulo'] ?? '')),
                    'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                    'cantidad' => $cantidadInt,
                    'unidad' => isset($item['unidad']) ? trim((string) $item['unidad']) : null,
                    'precio_unitario' => $precioUnitarioRounded,
                    'margen' => $margenRounded,
                    'precio_con_margen' => $precioConMargenRounded,
                    'total' => $totalComputed,
                ];
            })
            ->values()
            ->all();

        $totalComputed = collect($articulosNormalizados)->sum(function (array $item) {
            return (float) ($item['total'] ?? 0);
        });

        $presupuesto->update([
            'lista_articulos' => $articulosNormalizados ?: null,
            'total' => round($totalComputed, 2),
            'validez_oferta' => $validated['validez_oferta'] ?? $presupuesto->validez_oferta ?? null,
            'exclusiones' => $validated['exclusiones'] ?? $presupuesto->exclusiones ?? null,
        ]);

        $this->syncArticulosFromLineas($proyectoId, $articulosNormalizados);

        // Regenerate stored PDF copy after updating the presupuesto so preview shows current data
        try {
            // Ensure we have the latest model state
            $presupuesto->refresh();

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('presupuestos.pdf', compact('presupuesto'));
                $filePath = 'presupuestos/presupuesto-' . $presupuesto->id . '.pdf';
                Storage::disk('public')->put($filePath, $pdf->output());
                $presupuesto->update(['archivo_pdf' => $filePath]);
            } elseif (class_exists(\Dompdf\Dompdf::class)) {
                $html = view('presupuestos.pdf', compact('presupuesto'))->render();
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->loadHtml($html);
                $dompdf->render();
                $filePath = 'presupuestos/presupuesto-' . $presupuesto->id . '.pdf';
                Storage::disk('public')->put($filePath, $dompdf->output());
                $presupuesto->update(['archivo_pdf' => $filePath]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('presupuestos.show', $presupuesto)->with('success', 'Artículos del presupuesto actualizados');
    }

    public function destroy(Presupuesto $presupuesto)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $user = request()->user();
        if (!$user || !in_array($user->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        if ($presupuesto->pedidoCliente()->exists()) {
            $pedidoNumero = $presupuesto->pedidoCliente?->numero_pedido ?? 'sin numero';
            return redirect()->route('presupuestos.index')
                ->with('error', 'No se puede borrar el presupuesto porque tiene el pedido asociado: ' . $pedidoNumero . '.');
        }

        $presupuesto->delete();
        return redirect()->route('presupuestos.index')->with('success', 'Presupuesto eliminado');
    }

    private function nextNumeroPresupuestoCorrelativo(int $proyectoId, bool $consume = false): array
    {
        $formato = $this->getCorrelativoFormato($proyectoId);
        $maxNumero = $this->maxCorrelativoForFormato($proyectoId, $formato);

        $override = DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', 'presupuestos_next_correlativo')
            ->value('valor');

        $next = ($maxNumero ?? 0) + 1;

        if ($override !== null && is_numeric($override) && (int) $override > 0) {
            $next = max($next, (int) $override);
        }

        if ($consume) {
            $this->setContadorValue($proyectoId, 'presupuestos_next_correlativo', $next + 1);
        }

        return [
            'formato' => $formato,
            'correlativo' => $next,
            'numero' => $this->formatCorrelativoNumero($formato, $next),
        ];
    }

    private function getCorrelativoFormato(int $proyectoId): string
    {
        $storedKey = DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', 'like', 'presupuestos_formato_correlativo:%')
            ->orderByDesc('id')
            ->value('clave');

        $formato = '';

        if (is_string($storedKey) && str_starts_with($storedKey, 'presupuestos_formato_correlativo:')) {
            $formato = trim((string) substr($storedKey, strlen('presupuestos_formato_correlativo:')));
        }

        if ($formato === '' || !preg_match('/^.+-000$/', $formato)) {
            return 'PR-' . now()->format('Y') . '-000';
        }

        return $formato;
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

    private function maxCorrelativoForFormato(int $proyectoId, string $formato): int
    {
        return $this->correlativoStatsForFormato($proyectoId, $formato)['max'];
    }

    private function correlativoStatsForFormato(int $proyectoId, string $formato): array
    {
        $prefix = substr($formato, 0, -4);
        $regex = '/^' . preg_quote($prefix, '/') . '(\d{4})$/';

        $numeros = Presupuesto::where('proyecto_id', $proyectoId)
            ->where('numero', 'like', $prefix . '%')
            ->pluck('numero');

        $max = 0;
        $count = 0;

        foreach ($numeros as $numero) {
            if (!is_string($numero)) {
                continue;
            }

            if (preg_match($regex, $numero, $matches) === 1) {
                $count++;
                $value = (int) $matches[1];
                $max = max($max, $value);
            }
        }

        $ultimos = Presupuesto::query()
            ->where('proyecto_id', $proyectoId)
            ->where('numero', 'like', $prefix . '%')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get(['id', 'numero', 'fecha'])
            ->filter(fn (Presupuesto $presupuesto) => is_string($presupuesto->numero)
                && preg_match($regex, $presupuesto->numero) === 1)
            ->take(5);

        return [
            'max' => $max,
            'count' => $count,
            'ultimos' => $ultimos,
        ];
    }

    private function formatCorrelativoNumero(string $formato, int $correlativo): string
    {
        $prefix = substr($formato, 0, -4);

        return $prefix . str_pad((string) max(0, $correlativo), 4, '0', STR_PAD_LEFT);
    }

    private function extractCorrelativoFromNumero(string $formato, string $numero): ?int
    {
        $prefix = substr($formato, 0, -4);
        $pattern = '/^' . preg_quote($prefix, '/') . '(\d{4})$/';

        if (preg_match($pattern, $numero, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function setCorrelativoFormato(int $proyectoId, string $formato): void
    {
        $clave = 'presupuestos_formato_correlativo:' . $formato;

        DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', 'like', 'presupuestos_formato_correlativo:%')
            ->where('clave', '!=', $clave)
            ->delete();

        $exists = DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', $clave)
            ->exists();

        if ($exists) {
            DB::table('contadores')
                ->where('proyecto_id', $proyectoId)
                ->where('clave', $clave)
                ->update(['valor' => 1]);
            return;
        }

        DB::table('contadores')->insert([
            'proyecto_id' => $proyectoId,
            'clave' => $clave,
            'valor' => 1,
        ]);
    }

    private function setContadorValue(int $proyectoId, string $clave, $valor): void
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

    private function renderPdfResponse(Presupuesto $presupuesto, bool $download)
    {
        $disk = Storage::disk('public');

        if ($presupuesto->archivo_pdf && $disk->exists($presupuesto->archivo_pdf)) {
            $path = $disk->path($presupuesto->archivo_pdf);
            $fileName = basename((string) $presupuesto->archivo_pdf);

            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $fileName . '"',
            ]);
        }

        $pdfContent = null;
        $fileName = 'presupuesto-' . ($presupuesto->numero ?: $presupuesto->id) . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('presupuestos.pdf', compact('presupuesto'));
            $pdfContent = $pdf->output();
        } elseif (class_exists(\Dompdf\Dompdf::class)) {
            $html = view('presupuestos.pdf', compact('presupuesto'))->render();
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            $pdfContent = $dompdf->output();
        }

        if ($pdfContent === null) {
            abort(404);
        }

        if ($presupuesto->archivo_pdf) {
            $disk->put($presupuesto->archivo_pdf, $pdfContent);
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
