<?php

namespace App\Http\Controllers;

use App\Models\AparatoCalibrable;
use App\Models\Ubicacion;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class CalibrablesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = AparatoCalibrable::query();
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

        $items = $query->latest()->paginate(12)->withQueryString();
        $statsQuery = AparatoCalibrable::query();
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
            'estados' => AparatoCalibrable::distinct()->orderBy('estado')->pluck('estado'),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => \App\Models\ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList(),
        ]);
    }

    public function create()
    {
        return view('herramientas.calibrables.form', [
            'ubicaciones' => \App\Models\Ubicacion::orderBy('nombre')->get(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => \App\Models\ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data = $this->resolvePersonalAssignment($data);
        if ($request->hasFile('imagen')) $data['imagen'] = $request->file('imagen')->store('calibrables/fotos', 'public');
        if ($request->hasFile('certificado_pdf')) $data['certificado_pdf'] = $request->file('certificado_pdf')->store('calibrables/certificados', 'public');
        
        $aparato = AparatoCalibrable::create($data);
        return redirect()->route('calibrables.show', $aparato)->with('success', 'Aparato calibrable registrado correctamente.');
    }

    public function show(AparatoCalibrable $aparatoCalibrable)
    {
        return view('herramientas.calibrables.show', ['herramienta' => $aparatoCalibrable->load('eventos.usuario', 'personalAsignado'), 'personalList' => $this->personalList()]);
    }

    public function storeEvento(Request $request, AparatoCalibrable $aparatoCalibrable)
    {
        $data = $this->eventValidated($request);
        $data['usuario_id'] = auth()->id();
        
        // 1. Extraemos la próxima calibración antes de crear el evento
        // Lo quitamos de $data con unset() para que no de error SQL de "columna desconocida" en la tabla eventos
        $nuevaCaducidad = $data['proxima_calibracion'] ?? null;
        unset($data['proxima_calibracion']);

        if ($request->hasFile('archivo_adjunto')) {
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')->store('calibrables/eventos', 'public');
        }

        // 2. Guardamos el evento normalmente en la bitácora
        $aparatoCalibrable->eventos()->create($data);

        // 3. LA MAGIA: Si es una calibración nueva, actualizamos el instrumento principal
        if ($request->tipo === 'calibracion' && $nuevaCaducidad) {
            $aparatoCalibrable->update([
                // La fecha de la actuación (evento) es la nueva última calibración
                'ultima_calibracion' => $request->fecha, 
                // Renovamos la caducidad
                'proxima_calibracion' => $nuevaCaducidad,
                // Lo ponemos Operativo automáticamente, asumiendo que ha pasado la revisión
                'estado' => 'operativo', 
                // Limpiamos los datos de baja si venía de un laboratorio
                'dias_estimados_baja' => null,
                'fecha_baja' => null
            ]);
        }

        return back()->with('success', 'Evento añadido a la bitácora y caducidad actualizada correctamente.');
    }

    public function edit(AparatoCalibrable $aparatoCalibrable)
    {
        return view('herramientas.calibrables.form', [
            'item' => $aparatoCalibrable,
            'ubicaciones' => \App\Models\Ubicacion::orderBy('nombre')->get(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => \App\Models\ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList(),
        ]);
    }

    public function update(Request $request, AparatoCalibrable $aparatoCalibrable)
    {
        $data = $this->validated($request, $aparatoCalibrable);
        $data = $this->resolvePersonalAssignment($data);
        
        if ($request->hasFile('imagen')) {
            if ($aparatoCalibrable->imagen) Storage::disk('public')->delete($aparatoCalibrable->imagen);
            $data['imagen'] = $request->file('imagen')->store('calibrables/fotos', 'public');
        }
        
        if ($request->hasFile('certificado_pdf')) {
            if ($aparatoCalibrable->certificado_pdf) Storage::disk('public')->delete($aparatoCalibrable->certificado_pdf);
            $data['certificado_pdf'] = $request->file('certificado_pdf')->store('calibrables/certificados', 'public');
        }
        
        $aparatoCalibrable->update($data);
        return redirect()->route('calibrables.show', $aparatoCalibrable)->with('success', 'Aparato actualizado correctamente.');
    }

    public function destroy(AparatoCalibrable $aparatoCalibrable)
    {
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
            'estado' => ['required', \Illuminate\Validation\Rule::in(['operativo', 'asignado', 'calibracion', 'vencido', 'aislado'])],
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
            'certificado_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'], // Máximo 5MB
            'proxima_calibracion' => ['nullable', 'date'],
            'ultima_calibracion' => ['nullable', 'date'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'departamento_id' => ['nullable', 'exists:departamentos,id'],
            'clase' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function personalList()
    {
        return \App\Models\Personal::where('activo', true)
            ->orderBy('name')
            ->orderBy('apellido')
            ->get(['id', 'name', 'apellido', 'id_rrhh']);
    }

    private function resolvePersonalAssignment(array $data): array
    {
        if (!empty($data['personal_id'])) {
            $personal = \App\Models\Personal::findOrFail($data['personal_id']);
            $data['responsable'] = trim($personal->name . ' ' . $personal->apellido);
        }

        return $data;
    }

    private function eventValidated(Request $request): array
    {
        return $request->validate([
            'tipo' => ['required', \Illuminate\Validation\Rule::in(['averia', 'reparacion', 'calibracion', 'responsable', 'otro'])],
            'titulo' => ['required', 'string', 'max:180'], 
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'fecha' => ['required', 'date'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 
            'proxima_calibracion' => ['nullable', 'date'], 
        ]);
    }

    public function updateEstado(Request $request, AparatoCalibrable $aparatoCalibrable)
    {
        // Validamos el estado y permitimos la subida del documento
        $request->validate([
            'estado' => ['required', \Illuminate\Validation\Rule::in(['operativo', 'calibracion', 'aislado'])],
            'titulo_evento' => ['required', 'string', 'max:180'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'dias_estimados_baja' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        // 1. Actualizamos la ficha principal del instrumento
        $dataToUpdate = ['estado' => $request->estado];

        if ($request->estado === 'operativo') {
            // Vuelve a estar disponible: limpiamos la previsión de baja
            $dataToUpdate['dias_estimados_baja'] = null;
            $dataToUpdate['fecha_baja'] = null;
            $dataToUpdate['responsable'] = null;
            $dataToUpdate['personal_id'] = null;
        } elseif (in_array($request->estado, ['calibracion', 'aislado'])) {
            // Entra en baja (envío a laboratorio o aislamiento): guardamos la aproximación
            $dataToUpdate['dias_estimados_baja'] = $request->dias_estimados_baja;
            $dataToUpdate['fecha_baja'] = now();
        }

        $aparatoCalibrable->update($dataToUpdate);

        // 2. Preparamos los datos para la bitácora
        $eventoData = [
            'tipo' => in_array($request->estado, ['calibracion', 'aislado']) ? $request->estado : 'otro',
            'titulo' => $request->titulo_evento,
            'descripcion' => 'Cambio rápido de estado metrológico a: ' . ucfirst($request->estado),
            'fecha' => now(),
            'usuario_id' => auth()->id(),
        ];

        // 3. Procesamos el PDF o Foto si el operario lo ha adjuntado en el modal
        if ($request->hasFile('archivo_adjunto')) {
            $eventoData['archivo_adjunto'] = $request->file('archivo_adjunto')->store('calibrables/eventos', 'public');
        }

        // 4. Guardamos en el historial
        $aparatoCalibrable->eventos()->create($eventoData);

        return back()->with('success', 'Estado metrológico actualizado y certificado documentado en la bitácora.');
    }

    public function updateAsignacion(Request $request, AparatoCalibrable $aparatoCalibrable)
    {
        $request->validate([
            'responsable' => ['nullable', 'string', 'max:150', 'required_without:personal_id'],
            'personal_id' => ['nullable', 'exists:personal,id'],
            'ubicacion' => ['required', 'string', 'max:150'],
            'titulo_evento' => ['required', 'string', 'max:180'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $personal = $request->filled('personal_id') ? \App\Models\Personal::findOrFail($request->integer('personal_id')) : null;
        $responsable = $personal ? trim($personal->name . ' ' . $personal->apellido) : trim((string) $request->input('responsable'));

        // 1. Asignamos el equipo al área o persona
        $aparatoCalibrable->update([
            'estado' => 'asignado',
            'responsable' => $responsable,
            'personal_id' => $personal?->id,
            'ubicacion' => $request->ubicacion,
        ]);

        // 2. Procesamos el archivo adjunto si existe (ej. albarán de entrega firmado)
        $rutaArchivo = null;
        if ($request->hasFile('archivo_adjunto')) {
            $rutaArchivo = $request->file('archivo_adjunto')->store('calibrables/eventos', 'public');
        }

        // 3. Dejamos constancia de la entrega en la bitácora
        $aparatoCalibrable->eventos()->create([
            'tipo' => 'responsable',
            'titulo' => $request->titulo_evento,
            'descripcion' => "Instrumento entregado a: {$responsable} | Área/Ubicación: {$request->ubicacion}",
            'fecha' => now(),
            'archivo_adjunto' => $rutaArchivo,
            'usuario_id' => auth()->id(),
        ]);

        return back()->with('success', 'Aparato calibrable asignado y documentado correctamente.');
    }
}