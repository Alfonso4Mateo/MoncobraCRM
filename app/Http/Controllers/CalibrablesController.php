<?php

namespace App\Http\Controllers;

use App\Models\AparatoCalibrable;
use App\Models\Ubicacion;
use App\Models\Departamento;
use App\Models\Personal;
use App\Models\ClaseHerramienta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class CalibrablesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function authorizeActivoProyecto(AparatoCalibrable $item): void
    {
        $user = auth()->user();
        if (!$user || $user->role === 'superadmin') {
            return;
        }

        $misProyectos = $user->proyectos->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(
            in_array((int) $item->proyecto_id, $misProyectos, true),
            403,
            'No tienes acceso a un aparato calibrable de otra sede.'
        );
    }

    private function assertPersonalInProyecto(?int $personalId, int $proyectoId): void
    {
        if (!$personalId) {
            return;
        }

        $pertenece = Personal::where('id', $personalId)
            ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoId))
            ->exists();

        abort_unless($pertenece, 422, 'El trabajador seleccionado no pertenece a esta sede.');
    }

    public function index(Request $request)
    {
        $proyectoActivoId = $this->resolveProyectoForCorrelativo($request);

        $query = AparatoCalibrable::with('ubicacion')
            ->where('proyecto_id', $proyectoActivoId);

        $search = trim((string) $request->query('buscar'));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('numero_serie', 'like', "%{$search}%")
                    ->orWhere('marca', 'like', "%{$search}%")
                    ->orWhere('modelo', 'like', "%{$search}%")
                    ->orWhere('responsable', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', (string) $request->string('estado'));
        }
        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->integer('departamento_id'));
        }
        if ($request->filled('clase')) {
            $query->where('clase', (string) $request->string('clase'));
        }
        if ($request->filled('ubicacion_id')) {
            $query->where('ubicacion_id', $request->integer('ubicacion_id'));
        }

        if ($request->filled('alerta')) {
            if ($request->alerta === 'caducado') {
                $query->whereDate('proxima_calibracion', '<', now());
            } elseif ($request->alerta === 'proximo') {
                $query->whereBetween('proxima_calibracion', [now(), now()->addDays(30)]);
            }
        }

        if ($request->input('export') === 'csv') {
            $rows = (clone $query)->latest()->get();

            return response()->streamDownload(function () use ($rows): void {
                echo chr(0xEF) . chr(0xBB) . chr(0xBF);
                $out = fopen('php://output', 'wb');
                fputcsv($out, ['Código', 'Nombre', 'Magnitud', 'N/S', 'Estado', 'Responsable', 'Ubicación', 'Certificado', 'Última Calibración', 'Próxima Calibración'], ';');

                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->codigo ?: '—',
                        $row->nombre,
                        $row->instrumento ?: '—',
                        $row->numero_serie ?: '—',
                        $row->estado_label,
                        $row->responsable ?: 'Almacén',
                        $row->ubicacion->nombre ?? 'Almacén',
                        $row->certificado ?: '—',
                        optional($row->ultima_calibracion)->format('d/m/Y') ?: '—',
                        optional($row->proxima_calibracion)->format('d/m/Y') ?: '—',
                    ], ';');
                }
                fclose($out);
            }, 'metrologia_calibrables_' . date('Y-m-d') . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $items = $query->latest()->paginate(12)->withQueryString();
        $statsQuery = AparatoCalibrable::where('proyecto_id', $proyectoActivoId);
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'operativos' => (clone $statsQuery)->whereIn('estado', ['operativo', 'asignado'])->count(),
            'proximos' => (clone $statsQuery)->whereBetween('proxima_calibracion', [now()->toDateString(), now()->addDays(15)->toDateString()])->count(),
            'vencidos' => (clone $statsQuery)->whereIn('estado', ['vencido', 'aislado'])->count(),
            'externos' => (clone $statsQuery)->where('laboratorio_externo', true)->count(),
            'mantenimiento' => (clone $statsQuery)->where('estado', 'calibracion')->count(),
            'alertas' => (clone $statsQuery)->whereIn('estado', ['vencido', 'aislado', 'calibracion'])->count(),
        ];

        return view('herramientas.calibrables.index', [
            'items' => $items,
            'stats' => $stats,
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(),
            'estados' => AparatoCalibrable::where('proyecto_id', $proyectoActivoId)->distinct()->orderBy('estado')->pluck('estado'),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList($proyectoActivoId),
        ]);
    }

    public function create(Request $request)
    {
        $proyectoActivoId = $this->resolveProyectoForCorrelativo($request);

        return view('herramientas.calibrables.form', [
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList($proyectoActivoId),
        ]);
    }

    public function store(Request $request)
    {
        $proyectoActivoId = $this->resolveActiveProyectoId($request);
        $data = $this->validated($request);
        $this->assertPersonalInProyecto($data['personal_id'] ?? null, $proyectoActivoId);
        $data = $this->resolvePersonalAssignment($data);
        $data['proyecto_id'] = $proyectoActivoId;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('calibrables/fotos', 'public');
        }
        if ($request->hasFile('certificado_pdf')) {
            $data['certificado_pdf'] = $request->file('certificado_pdf')->store('calibrables/certificados', 'public');
        }

        $aparato = AparatoCalibrable::create($data);
        return redirect()->route('calibrables.show', $aparato)->with('success', 'Aparato calibrable registrado correctamente.');
    }

    public function show(AparatoCalibrable $aparatoCalibrable)
    {
        $this->authorizeActivoProyecto($aparatoCalibrable);

        return view('herramientas.calibrables.show', [
            'herramienta' => $aparatoCalibrable->load('eventos.usuario', 'personalAsignado', 'ubicacion'),
            'personalList' => $this->personalList((int) $aparatoCalibrable->proyecto_id),
        ]);
    }

    public function storeEvento(Request $request, AparatoCalibrable $aparatoCalibrable)
    {
        $this->authorizeActivoProyecto($aparatoCalibrable);

        $data = $this->eventValidated($request);
        $data['usuario_id'] = auth()->id();

        $nuevaCaducidad = $data['proxima_calibracion'] ?? null;
        unset($data['proxima_calibracion']);

        if ($request->hasFile('archivo_adjunto')) {
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')->store('calibrables/eventos', 'public');
        }

        $aparatoCalibrable->eventos()->create($data);

        if ($request->tipo === 'calibracion' && $nuevaCaducidad) {
            $aparatoCalibrable->update([
                'ultima_calibracion' => $request->fecha,
                'proxima_calibracion' => $nuevaCaducidad,
                'estado' => 'operativo',
                'dias_estimados_baja' => null,
                'fecha_baja' => null,
            ]);
        }

        return back()->with('success', 'Evento añadido a la bitácora y caducidad actualizada correctamente.');
    }

    public function edit(AparatoCalibrable $aparatoCalibrable)
    {
        $this->authorizeActivoProyecto($aparatoCalibrable);

        return view('herramientas.calibrables.form', [
            'item' => $aparatoCalibrable,
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList((int) $aparatoCalibrable->proyecto_id),
        ]);
    }

    public function update(Request $request, AparatoCalibrable $aparatoCalibrable)
    {
        $this->authorizeActivoProyecto($aparatoCalibrable);

        $data = $this->validated($request, $aparatoCalibrable);
        $this->assertPersonalInProyecto($data['personal_id'] ?? null, (int) $aparatoCalibrable->proyecto_id);
        $data = $this->resolvePersonalAssignment($data);

        if ($request->hasFile('imagen')) {
            if ($aparatoCalibrable->imagen) {
                Storage::disk('public')->delete($aparatoCalibrable->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('calibrables/fotos', 'public');
        }

        if ($request->hasFile('certificado_pdf')) {
            if ($aparatoCalibrable->certificado_pdf) {
                Storage::disk('public')->delete($aparatoCalibrable->certificado_pdf);
            }
            $data['certificado_pdf'] = $request->file('certificado_pdf')->store('calibrables/certificados', 'public');
        }

        $aparatoCalibrable->update($data);
        return redirect()->route('calibrables.show', $aparatoCalibrable)->with('success', 'Aparato actualizado correctamente.');
    }

    public function destroy(AparatoCalibrable $aparatoCalibrable)
    {
        $this->authorizeActivoProyecto($aparatoCalibrable);

        $aparatoCalibrable->delete();
        return redirect()->route('calibrables.index')->with('success', 'Aparato calibrable eliminado correctamente.');
    }

    private function validated(Request $request, ?AparatoCalibrable $aparato = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:150'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:150'],
            'estado' => ['required', Rule::in(['operativo', 'asignado', 'calibracion', 'vencido', 'aislado'])],
            'ubicacion_id' => ['nullable', 'exists:ubicaciones,id'],
            'responsable' => ['nullable', 'string', 'max:150'],
            'personal_id' => ['nullable', 'exists:personal,id'],
            'instrumento' => ['nullable', 'string', 'max:100'],
            'rango_medida' => ['nullable', 'string', 'max:150'],
            'clase_exactitud' => ['nullable', 'string', 'max:80'],
            'tolerancia' => ['nullable', 'string', 'max:80'],
            'incertidumbre' => ['nullable', 'string', 'max:80'],
            'norma_calibracion' => ['nullable', 'string', 'max:100'],
            'laboratorio' => ['nullable', 'string', 'max:150'],
            'certificado' => ['nullable', 'string', 'max:150'],
            'certificado_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'proxima_calibracion' => ['nullable', 'date'],
            'ultima_calibracion' => ['nullable', 'date'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'departamento_id' => ['nullable', 'exists:departamentos,id'],
            'clase' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function personalList(int $proyectoId)
    {
        return Personal::where('activo', true)
            ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoId))
            ->orderBy('name')
            ->orderBy('apellido')
            ->get(['id', 'name', 'apellido', 'id_rrhh']);
    }

    private function resolvePersonalAssignment(array $data): array
    {
        if (!empty($data['personal_id'])) {
            $personal = Personal::findOrFail($data['personal_id']);
            $data['responsable'] = trim($personal->name . ' ' . $personal->apellido);
        }

        return $data;
    }

    private function eventValidated(Request $request): array
    {
        return $request->validate([
            'tipo' => ['required', Rule::in(['averia', 'reparacion', 'calibracion', 'responsable', 'otro'])],
            'titulo' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'fecha' => ['required', 'date'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'proxima_calibracion' => ['nullable', 'date'],
        ]);
    }

    public function updateEstado(Request $request, AparatoCalibrable $aparatoCalibrable)
    {
        $this->authorizeActivoProyecto($aparatoCalibrable);

        $request->validate([
            'estado' => ['required', Rule::in(['operativo', 'calibracion', 'aislado'])],
            'titulo_evento' => ['required', 'string', 'max:180'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'dias_estimados_baja' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $dataToUpdate = ['estado' => $request->estado];

        if ($request->estado === 'operativo') {
            $dataToUpdate['dias_estimados_baja'] = null;
            $dataToUpdate['fecha_baja'] = null;
            $dataToUpdate['responsable'] = null;
            $dataToUpdate['personal_id'] = null;
        } elseif (in_array($request->estado, ['calibracion', 'aislado'], true)) {
            $dataToUpdate['dias_estimados_baja'] = $request->dias_estimados_baja;
            $dataToUpdate['fecha_baja'] = now();
        }

        $aparatoCalibrable->update($dataToUpdate);

        $eventoData = [
            'tipo' => in_array($request->estado, ['calibracion', 'aislado'], true) ? $request->estado : 'otro',
            'titulo' => $request->titulo_evento,
            'descripcion' => 'Cambio rápido de estado metrológico a: ' . ucfirst($request->estado),
            'fecha' => now(),
            'usuario_id' => auth()->id(),
        ];

        if ($request->hasFile('archivo_adjunto')) {
            $eventoData['archivo_adjunto'] = $request->file('archivo_adjunto')->store('calibrables/eventos', 'public');
        }

        $aparatoCalibrable->eventos()->create($eventoData);

        return back()->with('success', 'Estado metrológico actualizado y certificado documentado en la bitácora.');
    }

    public function updateAsignacion(Request $request, AparatoCalibrable $aparatoCalibrable)
    {
        $this->authorizeActivoProyecto($aparatoCalibrable);

        $request->validate([
            'responsable' => ['nullable', 'string', 'max:150', 'required_without:personal_id'],
            'personal_id' => ['nullable', 'exists:personal,id'],
            'ubicacion_id' => ['nullable', 'exists:ubicaciones,id'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'titulo_evento' => ['required', 'string', 'max:180'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->assertPersonalInProyecto($request->filled('personal_id') ? $request->integer('personal_id') : null, (int) $aparatoCalibrable->proyecto_id);

        $personal = $request->filled('personal_id') ? Personal::findOrFail($request->integer('personal_id')) : null;
        $responsable = $personal ? trim($personal->name . ' ' . $personal->apellido) : trim((string) $request->input('responsable'));

        $ubicacionId = $request->filled('ubicacion_id')
            ? $request->integer('ubicacion_id')
            : (trim((string) $request->input('ubicacion')) !== '' ? Ubicacion::firstOrCreate(['nombre' => trim((string) $request->input('ubicacion'))])->id : null);

        $nombreSitio = $ubicacionId ? (Ubicacion::find($ubicacionId)?->nombre ?? 'S/C') : 'S/C';

        $aparatoCalibrable->update([
            'estado' => 'asignado',
            'responsable' => $responsable,
            'personal_id' => $personal?->id,
            'ubicacion_id' => $ubicacionId,
        ]);

        $rutaArchivo = null;
        if ($request->hasFile('archivo_adjunto')) {
            $rutaArchivo = $request->file('archivo_adjunto')->store('calibrables/eventos', 'public');
        }

        $aparatoCalibrable->eventos()->create([
            'tipo' => 'responsable',
            'titulo' => $request->titulo_evento,
            'descripcion' => "Instrumento entregado a: {$responsable} | Área/Ubicación: {$nombreSitio}",
            'fecha' => now(),
            'archivo_adjunto' => $rutaArchivo,
            'usuario_id' => auth()->id(),
        ]);

        return back()->with('success', 'Aparato calibrable asignado y documentado correctamente.');
    }
}