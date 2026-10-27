<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use Carbon\Carbon;
use App\Models\Presupuesto;
use App\Models\Cliente;
use App\Models\CentroCoste;
use App\Services\DocumentLineNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class PresupuestoController extends Controller
{
    private const MAX_LINEAS = 5000;
    private const MAX_DESCRIPCION = 500000;
    private const MAX_ARTICULO = 2000;
    private const MAX_MEDIDA = 200;
    private const MAX_CANTIDAD = 1000000;
    private const MAX_PRECIO = 10000000;
    private const MAX_MARGEN = 1000;
    private DocumentLineNormalizer $lineNormalizer;

    /**
     * Inicializa el controlador con el servicio compartido de normalización.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->lineNormalizer = new DocumentLineNormalizer();
    }

    public function index(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.view');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        $search = trim((string) $request->input('search', ''));
        $fechaDesde = trim((string) $request->input('fecha_desde', ''));
        $fechaHasta = trim((string) $request->input('fecha_hasta', ''));
        $estado = trim((string) $request->input('estado', 'todos'));

        if (!in_array($estado, ['todos', 'pendiente', 'aceptado', 'rechazado', 'pedido_pendiente'], true)) {
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
                    ->orWhere('titulo', 'like', $like)
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
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.manage');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        $clientes = Cliente::where('proyecto_id', $proyectoId)->orderBy('empresa_nombre')->get();
        $siguienteNumero = $this->nextNumeroPresupuestoCorrelativo($proyectoId)['numero'];

        $clienteSeleccionadoId = (int) $request->query('cliente_id', 0);
        if ($clienteSeleccionadoId > 0 && !$clientes->contains('id', $clienteSeleccionadoId)) {
            $clienteSeleccionadoId = 0;
        }

        $volverACliente = $request->boolean('volver_cliente') && $clienteSeleccionadoId > 0;

        $modo = (string) $request->query('modo', 'nuevo');

        // Recuperamos los Centros de Coste
        $centrosCoste = CentroCoste::orderBy('codigo')->get();

        // Un solo return fusionando tus variables con las nuevas
        return view('presupuestos.create', compact(
            'clientes', 
            'clienteSeleccionadoId', 
            'volverACliente', 
            'modo', 
            'siguienteNumero', 
            'centrosCoste'
        ));
    }

    public function store(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.manage');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);

        $redirectClienteId = (int) $request->input('redirect_cliente_id', 0);
        $modo = (string) $request->input('modo', 'nuevo');
        $archivoPdfRule = $modo === 'carga' ? 'required' : 'nullable';

        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                Rule::exists('presupuestos', 'id')
                    ->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'documento' => 'required|string|max:50',
            'numero' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('presupuestos', 'numero')
                    ->where(fn ($query) => $query->where('proyecto_id', $proyectoId))
                    ->whereNull('deleted_at'),
            ],
            'fecha' => 'required|date',
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'titulo' => 'nullable|string|max:255',
            'ot' => 'nullable|string|max:255',
            'solicitante' => 'nullable|string|max:255',
            'destinatario' => 'nullable|string|max:255',
            'validez_oferta' => 'nullable|string|max:255',
            'exclusiones' => 'nullable|string|max:2000',
            'archivo_pdf' => [$archivoPdfRule, 'file', 'mimes:pdf', 'max:10240'],
            'lista_articulos' => 'nullable|json',
            'descripcion_general' => 'nullable|string|max:5000',
        ]);

        $lineasFiltradas = $this->lineNormalizer->filter($validated['lista_articulos'] ?? '[]');

        $lineasFiltradas = array_map(function (array $linea) {
            $medida = trim((string) ($linea['medida'] ?? $linea['unidad'] ?? ''));
            $linea['medida'] = $medida === '' ? 'und' : $medida;
            return $linea;
        }, $lineasFiltradas);

        $this->validateLineasPayload($lineasFiltradas);

        $validated['lista_articulos'] = $this->lineNormalizer->normalize($lineasFiltradas);

        if ($validated['lista_articulos'] === []) {
            $validated['lista_articulos'] = null;
        }

        $validated['total'] = round((float) collect($validated['lista_articulos'] ?? [])
            ->sum(fn (array $item) => (float) ($item['total'] ?? 0)), 2);
        $validated['estado'] = 'pendiente';

        // --- PROTECCIÓN BACKEND CONTRA DOBLE CLICK (Double Submit) ---
        // Buscamos si existe un presupuesto idéntico creado hace menos de 10 segundos
        $dobleEnvio = Presupuesto::where('proyecto_id', $proyectoId)
            ->where('cliente_id', $validated['cliente_id'])
            ->where('total', $validated['total'])
            ->where('created_at', '>=', Carbon::now()->subSeconds(10))
            ->first();

        if ($dobleEnvio) {
            // Si el usuario presionó dos veces, simulamos éxito y omitimos la duplicidad
            if ($redirectClienteId > 0 && $redirectClienteId === (int) $validated['cliente_id']) {
                return redirect()->route('clientes.show', $redirectClienteId)->with('success', 'Presupuesto cargado correctamente');
            }
            return redirect()->route('presupuestos.index')->with('success', 'Presupuesto creado');
        }
        // --- FIN PROTECCIÓN DOBLE CLICK ---

        // Lógica de archivo y revisión general (fuera de la transacción para no bloquear BD)
        $esRevision = !empty($validated['parent_id']);
        $numeroManual = trim((string) ($validated['numero'] ?? ''));

        if ($request->hasFile('archivo_pdf')) {
            $validated['archivo_pdf'] = $request->file('archivo_pdf')->store('presupuestos', 'public');
        }

        // --- TRANSACCIÓN PARA ASEGURAR ATOMICIDAD DEL CONTADOR ---
        $presupuesto = DB::transaction(function () use (&$validated, $proyectoId, $esRevision, $numeroManual) {
            
            // Bloqueamos la fila del contador para que peticiones simultáneas esperen
            DB::table('contadores')
                ->where('proyecto_id', $proyectoId)
                ->where('clave', 'presupuestos_next_correlativo')
                ->lockForUpdate()
                ->first();

            $correlativoActual = $this->nextNumeroPresupuestoCorrelativo($proyectoId);
            $formatoActual = $correlativoActual['formato'];
            $numeroFinal = $numeroManual !== '' ? $numeroManual : $correlativoActual['numero'];
            
            $correlativoFinal = $correlativoActual['correlativo'];
            $manualCorrelativo = null;

            // Si NO es revisión extraemos el correlativo; si lo es, lo ponemos a 0 para no gastarlo
            if (!$esRevision) {
                $manualCorrelativo = $this->extractCorrelativoFromNumero($formatoActual, $numeroFinal);
                if ($manualCorrelativo !== null) {
                    $correlativoFinal = $manualCorrelativo;
                }
            } else {
                $correlativoFinal = 0; 
            }

            $validated['numero'] = $numeroFinal;
            $validated['proyecto_id'] = $proyectoId;
            $validated['numero_correlativo'] = $correlativoFinal;

            // Unir la fecha seleccionada con la hora, minutos y segundos actuales del sistema
            $validated['fecha'] = \Carbon\Carbon::parse($validated['fecha'])
                ->setTimeFrom(\Carbon\Carbon::now('Europe/Madrid'))
                ->toDateTimeString();

            // Una única llamada limpia de creación (Laravel automáticamente coge descripcion_general de $validated)
            $presupuesto = Presupuesto::create($validated);
            
            $this->syncArticulosFromLineas($proyectoId, $validated['lista_articulos'] ?? []);

            // Avanzar contador global solo si es nuevo
            if (!$esRevision) {
                if ($manualCorrelativo !== null) {
                    $siguienteCorrelativo = max($correlativoActual['correlativo'], $manualCorrelativo + 1);
                } else {
                    $siguienteCorrelativo = $correlativoActual['correlativo'];
                }
                
                $this->setContadorValue($proyectoId, 'presupuestos_next_correlativo', $siguienteCorrelativo);
            }

            return $presupuesto;
        });
        // --- FIN DE LA TRANSACCIÓN ---

        // Generar y guardar copia en PDF (solo si no subió uno manual en modo carga)
        if (!$request->hasFile('archivo_pdf')) {
            try {
                if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('presupuestos.pdf', compact('presupuesto'));
                    $filePath = "presupuestos/{$presupuesto->numero}.pdf";
                    Storage::disk('public')->put($filePath, $pdf->output());
                    $presupuesto->updateQuietly(['archivo_pdf' => $filePath]);
                } elseif (class_exists(\Dompdf\Dompdf::class)) {
                    $html = view('presupuestos.pdf', compact('presupuesto'))->render();
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->setPaper('A4', 'portrait');
                    $dompdf->loadHtml($html);
                    $dompdf->render();
                    $filePath = "presupuestos/{$presupuesto->numero}.pdf";
                    Storage::disk('public')->put($filePath, $dompdf->output());
                    $presupuesto->updateQuietly(['archivo_pdf' => $filePath]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($redirectClienteId > 0 && $redirectClienteId === (int) $validated['cliente_id']) {
            return redirect()->route('clientes.show', $redirectClienteId)->with('success', 'Presupuesto cargado correctamente');
        }

        return redirect()->route('presupuestos.index')->with('success', 'Presupuesto creado');
    }

    public function show(Presupuesto $presupuesto)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.view');

        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $presupuesto->load([
            'cliente',
            'pedidosClientes.cliente',
            'pedidosClientes.albaran',
        ]);
        // Buscamos cuál es el presupuesto raíz original
        $raizId = $presupuesto->parent_id ?? $presupuesto->id;
        
        // Extraemos todos los presupuestos que compartan esta misma raíz de forma segura
        $historialRevisiones = Presupuesto::where(function ($query) use ($raizId) {
            $query->where('id', $raizId)
                  ->orWhere('parent_id', $raizId);
        })
        ->orderBy('id', 'asc')
        ->get();
        $activities = \Spatie\Activitylog\Models\Activity::with('causer')
            ->forSubject($presupuesto)
            ->latest()
            ->limit(8)
            ->get();

        return view('presupuestos.show', compact('presupuesto', 'historialRevisiones', 'activities'));
    }

    public function viewPdf(Presupuesto $presupuesto)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.download');

        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return $this->renderPdfResponse($presupuesto, false);
    }

    public function downloadPdf(Presupuesto $presupuesto)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.download');

        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        return $this->renderPdfResponse($presupuesto, true);
    }

    public function preview(Presupuesto $presupuesto)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.download');

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
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.manage');

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
        
        // 1. Añadimos la consulta para obtener los Centros de Coste
        $centrosCoste = CentroCoste::orderBy('codigo')->get();

        if ($presupuesto->cliente && !$clientes->contains('id', $presupuesto->cliente_id)) {
            $clientes->prepend($presupuesto->cliente);
        }

        // 2. Añadimos 'centrosCoste' a la función compact() para enviarlo a la vista
        return view('presupuestos.edit', compact('presupuesto', 'clientes', 'siguienteNumero', 'centrosCoste'));
    }
    public function editCorrelativo(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.manage');

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
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.manage');

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
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.manage');

        $proyectoId = $this->resolveActiveProyectoId($request);

        if ((int) $presupuesto->proyecto_id !== (int) $proyectoId) {
            abort(404);
        }

        $estado = (string) ($presupuesto->estado ?: 'pendiente');
        if (in_array($estado, ['aceptado', 'rechazado'], true)) {
            return redirect()->route('presupuestos.show', $presupuesto)
                ->with('error', 'No se pueden editar presupuestos ' . $estado . 's.');
        }

        $validated = $request->validate([
            'estado' => ['required', Rule::in(['pendiente', 'aceptado', 'rechazado', 'pedido_pendiente'])],
        ]);

        $presupuesto->update([
            'estado' => $validated['estado'],
        ]);

        return redirect()->route('presupuestos.index')->with('success', 'Estado del presupuesto actualizado');
    }

    public function update(Request $request, Presupuesto $presupuesto)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.manage');

        $proyectoId = $presupuesto->proyecto_id ?: $this->resolveActiveProyectoId($request);

        if ((int) $presupuesto->proyecto_id !== (int) $proyectoId) {
            abort(404);
        }

        // --- PROTECCIÓN BACKEND CONTRA DOBLE CLICK EN ACTUALIZAR ---
        // Si el usuario hace doble clic, omitimos el segundo para no colapsar la BBDD haciendo el PDF 2 veces.
        if ($presupuesto->updated_at && $presupuesto->updated_at->diffInSeconds(now()) < 5) {
            return redirect()->route('presupuestos.show', $presupuesto)->with('success', 'Presupuesto actualizado correctamente');
        }

        $user = $request->user();

        // Reglas base
        $rules = [
            'lista_articulos' => 'nullable|json',
            'validez_oferta' => 'nullable|string|max:255',
            'exclusiones' => 'nullable|string|max:2000',
            'descripcion_general' => 'nullable|string|max:5000', // <--- NUEVO CAMPO AÑADIDO
            'solicitante' => 'nullable|string|max:255', 
            'destinatario' => 'nullable|string|max:255', 
        ];

        // Añadimos validación estricta para todos
        $rules['documento'] = 'required|string|max:50';
        $rules['numero'] = [
            'nullable',
            'string',
            'max:50',
            Rule::unique('presupuestos', 'numero')
                ->where(fn ($query) => $query->where('proyecto_id', $proyectoId))
                ->whereNull('deleted_at')
                ->ignore($presupuesto->id),
        ];
        $rules['fecha'] = 'required|date';
        $rules['cliente_id'] = [
            'required',
            Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
        ];
        $rules['titulo'] = 'nullable|string|max:255';
        $rules['ot'] = 'nullable|string|max:255';

        $validated = $request->validate($rules);

        $lineasFiltradas = $this->lineNormalizer->filter($validated['lista_articulos'] ?? '[]');

        $lineasFiltradas = array_map(function (array $linea) {
            $medida = trim((string) ($linea['medida'] ?? $linea['unidad'] ?? ''));
            $linea['medida'] = $medida === '' ? 'und' : $medida;
            return $linea;
        }, $lineasFiltradas);

        $this->validateLineasPayload($lineasFiltradas);

        $articulosNormalizados = $this->lineNormalizer->normalize($lineasFiltradas);

        $totalComputed = collect($articulosNormalizados)->sum(function (array $item) {
            return (float) ($item['total'] ?? 0);
        });

        // Unir la fecha seleccionada con la hora, minutos y segundos actuales del sistema
        $validated['fecha'] = \Carbon\Carbon::parse($validated['fecha'])
            ->setTimeFrom(\Carbon\Carbon::now('Europe/Madrid'))
            ->toDateTimeString();

        // Preparamos el array de datos básicos incluyendo la fecha mutada con su hora real
        $updateData = [
            'lista_articulos' => $articulosNormalizados ?: null,
            'total' => round($totalComputed, 2),
            'validez_oferta' => $validated['validez_oferta'] ?? $presupuesto->validez_oferta ?? null,
            'exclusiones' => $validated['exclusiones'] ?? $presupuesto->exclusiones ?? null,
            'descripcion_general' => $validated['descripcion_general'] ?? null, // <--- NUEVO CAMPO AÑADIDO
            'solicitante' => $validated['solicitante'] ?? null, 
            'destinatario' => $validated['destinatario'] ?? null, 
            'documento' => $validated['documento'],
            'numero' => $validated['numero'] ?? null,
            'fecha' => $validated['fecha'], // <--- Ahora sí viaja con la hora incorporada a la base de datos
            'cliente_id' => $validated['cliente_id'],
            'titulo' => $validated['titulo'] ?? null,
            'ot' => $validated['ot'] ?? null,
        ];

        // Lanzamos la actualización del registro
        $presupuesto->update($updateData);

        $this->syncArticulosFromLineas($proyectoId, $articulosNormalizados);

        try {
            $presupuesto->refresh();

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('presupuestos.pdf', compact('presupuesto'));
                $filePath = "presupuestos/{$presupuesto->numero}.pdf";
                Storage::disk('public')->put($filePath, $pdf->output());
                $presupuesto->updateQuietly(['archivo_pdf' => $filePath]);
            } elseif (class_exists(\Dompdf\Dompdf::class)) {
                $html = view('presupuestos.pdf', compact('presupuesto'))->render();
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->loadHtml($html);
                $dompdf->render();
                $filePath = "presupuestos/{$presupuesto->numero}.pdf";
                Storage::disk('public')->put($filePath, $dompdf->output());
                $presupuesto->updateQuietly(['archivo_pdf' => $filePath]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('presupuestos.show', $presupuesto)->with('success', 'Presupuesto actualizado correctamente');
    }

    public function destroy(Presupuesto $presupuesto)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('presupuestos.delete');

        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        if ($presupuesto->pedidoCliente()->exists()) {
            $pedidoNumero = $presupuesto->pedidoCliente?->numero_pedido ?? 'sin numero';
            return redirect()->route('presupuestos.index')
                ->with('error', 'No se puede borrar el presupuesto porque tiene el pedido asociado: ' . $pedidoNumero . '.');
        }

        // 1. Guardamos el número en una variable antes de borrar el presupuesto
        $numeroBorrado = (string) $presupuesto->numero;

        // 2. Borramos el presupuesto
        $presupuesto->delete();

        // 3. LÓGICA INTELIGENTE: Retroceder el contador si es necesario
        $formato = $this->getCorrelativoFormato($proyectoId);
        $correlativoBorrado = $this->extractCorrelativoFromNumero($formato, $numeroBorrado);

        if ($correlativoBorrado !== null) {
            // Miramos qué número iba a sugerir el programa a continuación
            $override = DB::table('contadores')
                ->where('proyecto_id', $proyectoId)
                ->where('clave', 'presupuestos_next_correlativo')
                ->value('valor');

            // Si el programa iba a sugerir justo el siguiente al que hemos borrado, le restamos 1
            if ($override !== null && (int) $override === ($correlativoBorrado + 1)) {
                $this->setContadorValue($proyectoId, 'presupuestos_next_correlativo', $correlativoBorrado);
            }
        }

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

        if ($formato === '' || !preg_match('/^.+-0000$/', $formato)) {
            return 'PR' . now()->format('Y') . '-0000';
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
        $pdfContent = null;
        $fileName = ($presupuesto->numero ?: 'presupuesto-' . $presupuesto->id) . '.pdf';

        // 1. Si el PDF ya existe en disco y no está vacío, lo leemos en memoria (evitando response()->file())
        if ($presupuesto->archivo_pdf && $disk->exists($presupuesto->archivo_pdf) && $disk->size($presupuesto->archivo_pdf) > 0) {
            $pdfContent = $disk->get($presupuesto->archivo_pdf);
            $fileName = basename((string) $presupuesto->archivo_pdf);
        } else {
            // 2. Si no existe en disco, lo generamos al vuelo con DomPDF
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

            if ($pdfContent !== null) {
                $filePath = $presupuesto->archivo_pdf ?: "presupuestos/{$fileName}";
                $disk->put($filePath, $pdfContent);
                if (!$presupuesto->archivo_pdf) {
                    $presupuesto->updateQuietly(['archivo_pdf' => $filePath]);
                }
            }
        }

        if ($pdfContent === null) {
            abort(404);
        }

        // 3. Si es descarga directa, enviamos el bloque entero sin Accept-Ranges
        if ($download) {
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => strlen($pdfContent),
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Accept-Ranges' => 'none',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
            ]);
        }

        // 4. Si es visualización en navegador/iframe, entregamos HTML + Blob en cliente
        // para evitar que el visor PDF de Chrome lance peticiones Range que reseteen el socket en Windows
        $payloadJson = json_encode([
            'mime' => 'application/pdf',
            'filename' => $fileName,
            'base64' => base64_encode($pdfContent),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $html = '<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>' . e($fileName) . '</title>
<style>html,body{height:100%;margin:0;background:#202124}embed{display:block;width:100%;height:100%;border:0}</style>
</head>
<body><div id="visor" style="height:100%"></div>
<script>
(function () {
    const data = ' . $payloadJson . ';
    const binary = atob(data.base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
    const blobUrl = URL.createObjectURL(new Blob([bytes], { type: data.mime }));
    const visor = document.getElementById("visor");
    visor.innerHTML = "<embed type=\"application/pdf\">";
    visor.firstElementChild.src = blobUrl;
    window.addEventListener("unload", function () { URL.revokeObjectURL(blobUrl); });
}());
</script>
</body></html>';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
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

            Articulo::firstOrCreate(
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

    public function revision(Request $request, Presupuesto $presupuesto)
    {
        $this->authorize('presupuestos.manage');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }
        $clientes = Cliente::where('proyecto_id', $proyectoId)->orderBy('empresa_nombre')->get();

        // 1. Encontrar al Padre Original de esta familia
        $raizId = $presupuesto->parent_id ?? $presupuesto->id;
        $padreOriginal = Presupuesto::where('proyecto_id', $proyectoId)->find($raizId);

        // 2. Extraer la raíz del número (ej: Aibus28-0002)
        // CAMBIO EXPERTO: Usamos preg_split para que corte la raíz tanto si el padre 
        // tiene el formato antiguo (-Revision-) como el nuevo ( Rev-)
        $numeroBase = preg_split('/(-Revision-| Rev-)/', $padreOriginal->numero)[0];

        // 3. Traer TODOS los números de esta familia (Padre + Hijos)
        $numerosFamilia = Presupuesto::where('proyecto_id', $proyectoId)
            ->where(function ($query) use ($raizId) {
            $query->where('id', $raizId)
                  ->orWhere('parent_id', $raizId);
        })->pluck('numero');

        // 4. Analizar los números para encontrar la revisión más alta actual
        $maxRevision = 0;
        // CAMBIO EXPERTO: El patrón regex ahora busca ambos formatos "(?:-Revision-| Rev-)"
        // Así nos aseguramos de que el contador no se reinicie si clonas un presupuesto viejo.
        $regex = '/' . preg_quote($numeroBase, '/') . '(?:-Revision-| Rev-)(\d+)/';

        foreach ($numerosFamilia as $num) {
            if ($num && preg_match($regex, $num, $matches)) {
                $revNum = (int)$matches[1];
                if ($revNum > $maxRevision) {
                    $maxRevision = $revNum;
                }
            }
        }

        // 5. Asignar el siguiente número
        $siguienteRevision = $maxRevision + 1;
        // CAMBIO ESTÉTICO: Aquí generamos el formato que querías
        $nuevoNumero = $numeroBase . ' Rev-' . $siguienteRevision;

        // 6. Clonar y limpiar el título
        $nuevaRevision = $presupuesto->replicate();
        
        $tituloBase = str_replace(' - Revisión', '', $presupuesto->titulo ?? '');
        $nuevaRevision->titulo = $tituloBase ? $tituloBase . ' - Revisión' : 'Revisión';
        
        $nuevaRevision->parent_id = $raizId;
        $nuevaRevision->fecha = now()->toDateString();
        $nuevaRevision->estado = 'pendiente'; 
        $nuevaRevision->numero = $nuevoNumero;
        $centrosCoste = CentroCoste::orderBy('codigo')->get();

        return view('presupuestos.create', [
            'presupuesto' => $nuevaRevision,
            'clientes' => $clientes,
            'clienteSeleccionadoId' => $nuevaRevision->cliente_id,
            'volverACliente' => false,
            'modo' => 'revision',
            'siguienteNumero' => $nuevoNumero,
            'centrosCoste' => $centrosCoste
        ]);
    }
}