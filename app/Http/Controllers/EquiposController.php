<?php

namespace App\Http\Controllers;

use App\Models\EquipoInformatico;
use App\Models\Ubicacion;
use App\Models\Departamento;
use App\Models\Personal;
use App\Models\ClaseHerramienta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class EquiposController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function authorizeActivoProyecto(EquipoInformatico $item): void
    {
        $user = auth()->user();
        if (!$user || $user->role === 'superadmin') {
            return;
        }

        $misProyectos = $user->proyectos->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(
            in_array((int) $item->proyecto_id, $misProyectos, true),
            403,
            'No tienes acceso a un equipo de otra sede.'
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

        $query = EquipoInformatico::with('ubicacion')
            ->where('activo', true)
            ->where('proyecto_id', $proyectoActivoId);

        $this->applyFilters($query, $request);

        if ($request->input('export') === 'csv') {
            $rows = (clone $query)->latest()->get();

            return response()->streamDownload(function () use ($rows): void {
                echo chr(0xEF) . chr(0xBB) . chr(0xBF);
                $out = fopen('php://output', 'wb');
                fputcsv($out, ['Código', 'Nombre', 'Tipo', 'N/S', 'Marca', 'Modelo', 'Estado', 'Responsable', 'Ubicación', 'Próx. Mantenimiento', 'Fin Ciclo (EOL)'], ';');

                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->codigo ?: '—',
                        $row->nombre,
                        $row->tipo_equipo ?: '—',
                        $row->numero_serie ?: '—',
                        $row->marca ?: '—',
                        $row->modelo ?: '—',
                        $row->estado_label,
                        $row->responsable ?: 'Rack IT',
                        $row->ubicacion->nombre ?? 'Almacén',
                        optional($row->fecha_mantenimiento)->format('d/m/Y') ?: '—',
                        optional($row->soporte_hasta)->format('d/m/Y') ?: '—',
                    ], ';');
                }
                fclose($out);
            }, 'parque_informatico_' . date('Y-m-d') . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $items = $query->latest()->paginate(12)->withQueryString();
        $base = EquipoInformatico::where('activo', true)->where('proyecto_id', $proyectoActivoId);
        $stats = [
            'total' => (clone $base)->count(),
            'operativos' => (clone $base)->whereIn('estado', ['operativo', 'asignado'])->count(),
            'mantenimiento' => (clone $base)->whereIn('estado', ['mantenimiento', 'reparacion'])->count(),
            'alertas' => (clone $base)->whereIn('estado', ['mantenimiento', 'reparacion'])->count(),
        ];

        return view('herramientas.equipos.index', [
            'items' => $items,
            'stats' => $stats,
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(),
            'estados' => EquipoInformatico::where('activo', true)->where('proyecto_id', $proyectoActivoId)->distinct()->orderBy('estado')->pluck('estado'),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList($proyectoActivoId),
        ]);
    }

    public function create(Request $request)
    {
        $proyectoActivoId = $this->resolveProyectoForCorrelativo($request);

        return view('herramientas.equipos.form', [
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
            $data['imagen'] = $request->file('imagen')->store('equipos', 'public');
        }

        $item = EquipoInformatico::create($data);
        return redirect()->route('equipos.show', $item)->with('success', 'Equipo registrado correctamente.');
    }

    public function show(EquipoInformatico $equipo)
    {
        $this->authorizeActivoProyecto($equipo);

        return view('herramientas.equipos.show', [
            'equipo' => $equipo->load('eventos.usuario', 'personalAsignado', 'ubicacion'),
            'personalList' => $this->personalList((int) $equipo->proyecto_id),
        ]);
    }

    public function storeEvento(Request $request, EquipoInformatico $equipo)
    {
        $this->authorizeActivoProyecto($equipo);

        $data = $this->eventValidated($request);
        $data['usuario_id'] = auth()->id();

        $proximoMantenimiento = $data['fecha_mantenimiento'] ?? null;
        unset($data['fecha_mantenimiento']);

        if ($request->hasFile('archivo_adjunto')) {
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')->store('eventos_it', 'public');
        }

        $equipo->eventos()->create($data);

        if ($request->tipo === 'mantenimiento' && $proximoMantenimiento) {
            $equipo->update([
                'fecha_mantenimiento' => $proximoMantenimiento,
                'estado' => 'operativo',
                'dias_estimados_baja' => null,
                'fecha_baja' => null,
            ]);
        }

        return back()->with('success', 'Parte documentado en bitácora y fecha de revisión actualizada.');
    }

    public function edit(EquipoInformatico $equipo)
    {
        $this->authorizeActivoProyecto($equipo);

        return view('herramientas.equipos.form', [
            'item' => $equipo,
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList((int) $equipo->proyecto_id),
        ]);
    }

    public function update(Request $request, EquipoInformatico $equipo)
    {
        $this->authorizeActivoProyecto($equipo);

        $data = $this->validated($request, $equipo);
        $this->assertPersonalInProyecto($data['personal_id'] ?? null, (int) $equipo->proyecto_id);
        $data = $this->resolvePersonalAssignment($data);

        if ($request->hasFile('imagen')) {
            if ($equipo->imagen) {
                Storage::disk('public')->delete($equipo->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('equipos', 'public');
        }

        $equipo->update($data);
        return redirect()->route('equipos.show', $equipo)->with('success', 'Equipo actualizado correctamente.');
    }

    public function destroy(EquipoInformatico $equipo)
    {
        $this->authorizeActivoProyecto($equipo);

        $equipo->update(['activo' => false]);
        $equipo->delete();

        return redirect()->route('equipos.index')->with('success', 'Equipo archivado correctamente.');
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('buscar')) {
            $search = trim((string) $request->string('buscar'));
            $query->where(function ($builder) use ($search) {
                $builder->where('nombre', 'like', "%{$search}%")->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('numero_serie', 'like', "%{$search}%")->orWhere('marca', 'like', "%{$search}%")
                    ->orWhere('modelo', 'like', "%{$search}%")->orWhere('responsable', 'like', "%{$search}%");
            });
        }
        if ($request->filled('estado')) {
            $query->where('estado', (string) $request->string('estado'));
        }
        if ($request->filled('ubicacion_id')) {
            $query->where('ubicacion_id', $request->integer('ubicacion_id'));
        }
        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->integer('departamento_id'));
        }
        if ($request->filled('clase')) {
            $query->where('clase', (string) $request->string('clase'));
        }
        if ($request->filled('alerta')) {
            if ($request->alerta === 'obsoleto') {
                $query->whereDate('soporte_hasta', '<', now());
            } elseif ($request->alerta === 'mantenimiento') {
                $query->whereDate('fecha_mantenimiento', '<', now());
            }
        }
    }

    private function validated(Request $request, ?EquipoInformatico $equipo = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:150'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:150'],
            'categoria' => ['nullable', 'string', 'max:150'],
            'estado' => ['required', Rule::in(['operativo', 'asignado', 'mantenimiento', 'reparacion'])],
            'ubicacion_id' => ['nullable', 'exists:ubicaciones,id'],
            'responsable' => ['nullable', 'string', 'max:150'],
            'personal_id' => ['nullable', 'exists:personal,id'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'fecha_adquisicion' => ['nullable', 'date'],
            'fecha_mantenimiento' => ['nullable', 'date'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'tipo_equipo' => ['nullable', 'string', 'max:100'],
            'sistema_operativo' => ['nullable', 'string', 'max:100'],
            'procesador' => ['nullable', 'string', 'max:150'],
            'memoria_ram' => ['nullable', 'string', 'max:80'],
            'almacenamiento' => ['nullable', 'string', 'max:100'],
            'mac_address' => ['nullable', 'string', 'max:50'],
            'ip_address' => ['nullable', 'string', 'max:50'],
            'garantia_hasta' => ['nullable', 'date'],
            'soporte_hasta' => ['nullable', 'date'],
            'condicion_fisica' => ['nullable', 'string', 'max:40'],
            'etiqueta_color' => ['nullable', 'string', 'max:30'],
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
            'tipo' => ['required', Rule::in(['averia', 'reparacion', 'mantenimiento', 'responsable', 'otro'])],
            'titulo' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'fecha' => ['required', 'date'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'fecha_mantenimiento' => ['nullable', 'date'],
        ]);
    }

    public function updateEstado(Request $request, EquipoInformatico $equipo)
    {
        $this->authorizeActivoProyecto($equipo);

        $request->validate([
            'estado' => ['required', Rule::in(['operativo', 'mantenimiento', 'reparacion'])],
            'titulo_evento' => ['required', 'string', 'max:180'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'dias_estimados_baja' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $dataToUpdate = ['estado' => $request->estado];

        if ($request->estado === 'operativo') {
            $dataToUpdate['responsable'] = null;
            $dataToUpdate['personal_id'] = null;
            $dataToUpdate['dias_estimados_baja'] = null;
            $dataToUpdate['fecha_baja'] = null;
        } elseif (in_array($request->estado, ['reparacion', 'mantenimiento'], true)) {
            $dataToUpdate['dias_estimados_baja'] = $request->dias_estimados_baja;
            $dataToUpdate['fecha_baja'] = now();
        }

        $equipo->update($dataToUpdate);

        $rutaArchivo = null;
        if ($request->hasFile('archivo_adjunto')) {
            $rutaArchivo = $request->file('archivo_adjunto')->store('eventos_it', 'public');
        }

        $equipo->eventos()->create([
            'tipo' => in_array($request->estado, ['mantenimiento', 'reparacion'], true) ? $request->estado : 'otro',
            'titulo' => $request->titulo_evento,
            'descripcion' => 'Cambio rápido de estado a: ' . ucfirst($request->estado) . ($request->estado === 'operativo' ? ' (Devuelto a stock)' : ''),
            'fecha' => now(),
            'archivo_adjunto' => $rutaArchivo,
            'usuario_id' => auth()->id(),
        ]);

        return back()->with('success', 'Estado actualizado y registrado en la bitácora correctamente.');
    }

    public function updateAsignacion(Request $request, EquipoInformatico $equipo)
    {
        $this->authorizeActivoProyecto($equipo);

        $request->validate([
            'responsable' => ['nullable', 'string', 'max:150', 'required_without:personal_id'],
            'personal_id' => ['nullable', 'exists:personal,id'],
            'ubicacion_id' => ['nullable', 'exists:ubicaciones,id'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'titulo_evento' => ['required', 'string', 'max:180'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->assertPersonalInProyecto($request->filled('personal_id') ? $request->integer('personal_id') : null, (int) $equipo->proyecto_id);

        $personal = $request->filled('personal_id') ? Personal::findOrFail($request->integer('personal_id')) : null;
        $responsable = $personal ? trim($personal->name . ' ' . $personal->apellido) : trim((string) $request->input('responsable'));

        $ubicacionId = $request->filled('ubicacion_id')
            ? $request->integer('ubicacion_id')
            : (trim((string) $request->input('ubicacion')) !== '' ? Ubicacion::firstOrCreate(['nombre' => trim((string) $request->input('ubicacion'))])->id : null);

        $nombreSitio = $ubicacionId ? (Ubicacion::find($ubicacionId)?->nombre ?? 'S/C') : 'S/C';

        $equipo->update([
            'estado' => 'asignado',
            'responsable' => $responsable,
            'personal_id' => $personal?->id,
            'ubicacion_id' => $ubicacionId,
        ]);

        $rutaArchivo = null;
        if ($request->hasFile('archivo_adjunto')) {
            $rutaArchivo = $request->file('archivo_adjunto')->store('eventos_it', 'public');
        }

        $equipo->eventos()->create([
            'tipo' => 'responsable',
            'titulo' => $request->titulo_evento,
            'descripcion' => "Equipo entregado a: {$responsable} | Puesto: {$nombreSitio}",
            'fecha' => now(),
            'archivo_adjunto' => $rutaArchivo,
            'usuario_id' => auth()->id(),
        ]);

        return back()->with('success', 'Equipo informático asignado correctamente.');
    }
}