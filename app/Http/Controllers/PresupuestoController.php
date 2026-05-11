<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Presupuesto;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PresupuestoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);
        $search = trim((string) $request->input('search', ''));

        $presupuestosQuery = Presupuesto::with('cliente')
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

        $presupuestos = $presupuestosQuery
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('presupuestos.index', compact('presupuestos', 'search'));
    }

    public function create(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);
        $clientes = Cliente::where('proyecto_id', $proyectoId)->orderBy('empresa_nombre')->get();
        $siguienteNumero = $this->nextNumeroPresupuestoCorrelativo($proyectoId);

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
        $proyectoId = $this->resolveActiveProyectoId($request);

        $redirectClienteId = (int) $request->input('redirect_cliente_id', 0);
        $modo = (string) $request->input('modo', 'nuevo');
        $archivoPdfRule = $modo === 'carga' ? 'required' : 'nullable';

        $validated = $request->validate([
            'documento' => 'required|string|max:50',
            'numero' => 'required|string|max:50',
            'fecha' => 'required|date',
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'titulo' => 'nullable|string|max:255',
            'ot' => 'nullable|string|max:255',
            'archivo_pdf' => [$archivoPdfRule, 'file', 'mimes:pdf', 'max:10240'],
            'lista_articulos' => 'nullable|json',
        ]);

        $validated['proyecto_id'] = $proyectoId;
        $validated['numero_correlativo'] = $this->nextNumeroPresupuestoCorrelativo($proyectoId);

        if ($request->hasFile('archivo_pdf')) {
            $validated['archivo_pdf'] = $request->file('archivo_pdf')->store('presupuestos', 'public');
        }

        $listaArticulos = json_decode((string) ($validated['lista_articulos'] ?? '[]'), true);
        $validated['lista_articulos'] = collect(is_array($listaArticulos) ? $listaArticulos : [])
            ->filter(fn ($item) => is_array($item) && !empty(trim((string) ($item['descripcion'] ?? ''))))
            ->map(function (array $item) {
                $cantidad = max(0, (float) ($item['cantidad'] ?? 0));
                $precioUnitario = max(0, (float) ($item['precio_unitario'] ?? 0));
                $margen = max(0, (float) ($item['margen'] ?? 0));
                $total = max(0, (float) ($item['total'] ?? 0));

                return [
                    'articulo' => trim((string) ($item['articulo'] ?? '')),
                    'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                    'cantidad' => round($cantidad, 2),
                    'precio_unitario' => round($precioUnitario, 2),
                    'margen' => round($margen, 2),
                    'total' => round($total, 2),
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
        $proyectoId = $this->resolveActiveProyectoId($request);

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $clientes = Cliente::where('proyecto_id', $proyectoId)->orderBy('empresa_nombre')->get();
        $siguienteNumero = $this->nextNumeroPresupuestoCorrelativo($proyectoId);

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

        $proyectoId = $this->resolveActiveProyectoId($request);
        $max = Presupuesto::where('proyecto_id', $proyectoId)->max('numero_correlativo');
        $override = DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', 'presupuestos_next_correlativo')
            ->value('valor');

        $suggested = max(($max ?? 0) + 1, (int) ($override ?? 0));

        return view('presupuestos.correlativo', compact('max', 'override', 'suggested'));
    }

    public function updateCorrelativo(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        $proyectoId = $this->resolveActiveProyectoId($request);
        $max = Presupuesto::where('proyecto_id', $proyectoId)->max('numero_correlativo');

        $validated = $request->validate([
            'next' => ['required', 'integer', 'min:1'],
        ]);

        $next = (int) $validated['next'];

        if ($next <= ($max ?? 0)) {
            return back()->withErrors(['next' => 'El número debe ser mayor que el máximo correlativo existente (' . ($max ?? 0) . ').']);
        }

        $exists = DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', 'presupuestos_next_correlativo')
            ->exists();

        if ($exists) {
            DB::table('contadores')
                ->where('proyecto_id', $proyectoId)
                ->where('clave', 'presupuestos_next_correlativo')
                ->update(['valor' => $next]);
        } else {
            DB::table('contadores')->insert([
                'proyecto_id' => $proyectoId,
                'clave' => 'presupuestos_next_correlativo',
                'valor' => $next,
            ]);
        }

        return redirect()->route('presupuestos.index')->with('success', 'Siguiente número correlativo fijado en ' . $next);
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

        $validated = $request->validate([
            'documento' => 'required|string|max:50',
            'numero' => 'required|string|max:50',
            'fecha' => 'required|date',
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
            'titulo' => 'nullable|string|max:255',
            'ot' => 'nullable|string|max:255',
            'total' => 'nullable|numeric|min:0',
            'estado' => ['nullable', Rule::in(['pendiente', 'aceptado', 'rechazado', 'pendiente pedido'])],
        ]);

        $validated['proyecto_id'] = $proyectoId;
        $validated['total'] = isset($validated['total']) ? round((float) $validated['total'], 2) : (float) $presupuesto->total;
        $validated['estado'] = $validated['estado'] ?? ($presupuesto->estado ?: 'pendiente');

        $presupuesto->update($validated);
        return redirect()->route('presupuestos.index')->with('success', 'Presupuesto actualizado');
    }

    public function destroy(Presupuesto $presupuesto)
    {
        $proyectoId = $this->resolveActiveProyectoId(request());

        if ((int) $presupuesto->proyecto_id !== $proyectoId) {
            abort(404);
        }

        $presupuesto->delete();
        return redirect()->route('presupuestos.index')->with('success', 'Presupuesto eliminado');
    }

    private function nextNumeroPresupuestoCorrelativo(int $proyectoId): string
    {
        $maxNumero = Presupuesto::where('proyecto_id', $proyectoId)
            ->pluck('numero_correlativo')
            ->filter(fn ($numero) => is_numeric($numero))
            ->map(fn ($numero) => (int) $numero)
            ->max();

        $override = DB::table('contadores')
            ->where('proyecto_id', $proyectoId)
            ->where('clave', 'presupuestos_next_correlativo')
            ->value('valor');

        if ($override !== null && is_numeric($override) && (int) $override > ($maxNumero ?? 0)) {
            // Reserve the override value and increment it so next call uses the following
            $next = (int) $override;
            DB::table('contadores')
                ->where('proyecto_id', $proyectoId)
                ->where('clave', 'presupuestos_next_correlativo')
                ->update(['valor' => $next + 1]);

            return (string) $next;
        }

        return (string) (($maxNumero ?? 0) + 1);
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
}
