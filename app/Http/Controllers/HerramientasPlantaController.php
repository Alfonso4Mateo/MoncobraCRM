<?php

namespace App\Http\Controllers;

use App\Models\FamiliaHerramienta;
use App\Models\HerramientaPlanta;
use App\Models\Ubicacion;
use App\Models\Departamento;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\CsvImportService;
use App\Models\ClaseHerramienta;
use Carbon\Carbon;

class HerramientasPlantaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function authorizeActivoProyecto(HerramientaPlanta $item): void
    {
        $user = auth()->user();
        if (!$user || $user->role === 'superadmin') {
            return;
        }

        $misProyectos = $user->proyectos->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(
            in_array((int) $item->proyecto_id, $misProyectos, true),
            403,
            'No tienes acceso a una herramienta de otra sede.'
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

        $query = HerramientaPlanta::with(['ubicacion', 'familia'])
            ->where('proyecto_id', $proyectoActivoId);

        $this->applyFilters($query, $request);

        if ($request->input('export') === 'csv') {
            $rows = (clone $query)->latest()->get();

            return response()->streamDownload(function () use ($rows): void {
                echo chr(0xEF) . chr(0xBB) . chr(0xBF);
                $out = fopen('php://output', 'wb');
                fputcsv($out, ['ID Interno', 'Nombre', 'Código', 'N/S', 'Marca', 'Modelo', 'Estado', 'Responsable', 'Ubicación', 'Próx. Mantenimiento', 'Inspección Legal (OCA)'], ';');

                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id_interno,
                        $row->nombre,
                        $row->codigo ?: '—',
                        $row->numero_serie ?: '—',
                        $row->marca ?: '—',
                        $row->modelo ?: '—',
                        $row->estado_label,
                        $row->responsable ?: 'Almacén',
                        $row->ubicacion->nombre ?? 'Almacén',
                        optional($row->fecha_mantenimiento)->format('d/m/Y') ?: '—',
                        optional($row->fecha_inspeccion)->format('d/m/Y') ?: '—',
                    ], ';');
                }
                fclose($out);
            }, 'herramientas_planta_' . date('Y-m-d') . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $items = $query->latest()->paginate(12)->withQueryString();
        $base = HerramientaPlanta::where('proyecto_id', $proyectoActivoId);
        $stats = [
            'total' => (clone $base)->count(),
            'operativos' => (clone $base)->whereIn('estado', ['operativo', 'asignado'])->count(),
            'mantenimiento' => (clone $base)->whereIn('estado', ['mantenimiento', 'reparacion'])->count(),
            'alertas' => (clone $base)->whereIn('estado', ['mantenimiento', 'reparacion'])->count(),
        ];

        return view('herramientas.planta.index', [
            'items' => $items,
            'stats' => $stats,
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(),
            'estados' => HerramientaPlanta::where('proyecto_id', $proyectoActivoId)->distinct()->orderBy('estado')->pluck('estado'),
            'familias' => FamiliaHerramienta::where('activo', true)->orderBy('nombre')->get(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList($proyectoActivoId),
        ]);
    }

    public function descargarPlantillaCsv()
    {
        $headers = [
            'id_interno', 'nombre', 'codigo', 'numero_serie', 'marca', 'modelo', 'familia',
            'clase', 'departamento', 'ubicacion', 'almacen', 'estado', 'stock', 'unidad_medida',
            'responsable', 'proveedor', 'fecha_adquisicion', 'fecha_mantenimiento',
            'fecha_inspeccion', 'descripcion',
        ];
        $example = [
            'MAQ-001', 'Grupo Electrógeno 50kVA', 'GE-50', 'SN123456', 'Himoinsa', 'HYW-50',
            'Generadores', 'Eléctrica', 'Mantenimiento', 'Nave Central', 'Almacén 1', 'operativo',
            '1', 'und', '', 'Suministros Sur', '2025-01-15', '2026-12-01', '2027-01-15',
            'Equipo principal',
        ];

        return response()->streamDownload(function () use ($headers, $example): void {
            echo chr(0xEF) . chr(0xBB) . chr(0xBF);
            $output = fopen('php://output', 'wb');
            fputcsv($output, $headers, ';');
            fputcsv($output, $example, ';');
            fclose($output);
        }, 'plantilla_herramientas_planta.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importarCsv(Request $request, CsvImportService $csvImportService)
    {
        $request->validate([
            'archivo_csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $proyectoActivoId = $this->resolveActiveProyectoId($request);
        $rows = $csvImportService->read($request->file('archivo_csv'));
        $creadas = 0;
        $actualizadas = 0;

        DB::transaction(function () use ($rows, $proyectoActivoId, &$creadas, &$actualizadas): void {
            foreach ($rows as $row) {
                $nombre = trim((string) ($row['nombre'] ?? ''));
                if ($nombre === '') {
                    continue;
                }

                $idInterno = trim((string) ($row['id_interno'] ?? ''));
                if ($idInterno === '') {
                    $idInterno = $this->generateImportId($nombre, $proyectoActivoId);
                }

                $familia = $this->resolveFamilia($row['familia'] ?? null);
                $ubicacion = $this->resolveUbicacion($row['ubicacion'] ?? null);
                $departamento = $this->resolveDepartamento($row['departamento'] ?? null);
                $clase = trim((string) ($row['clase'] ?? ''));
                if ($clase !== '') {
                    ClaseHerramienta::firstOrCreate(['nombre' => $clase]);
                }

                $estado = mb_strtolower(trim((string) ($row['estado'] ?? '')), 'UTF-8');
                if (!in_array($estado, ['operativo', 'asignado', 'mantenimiento', 'reparacion'], true)) {
                    $estado = 'operativo';
                }

                $item = HerramientaPlanta::updateOrCreate(
                    ['proyecto_id' => $proyectoActivoId, 'id_interno' => $idInterno],
                    [
                        'nombre' => $nombre,
                        'codigo' => $this->value($row, 'codigo'),
                        'numero_serie' => $this->value($row, 'numero_serie'),
                        'marca' => $this->value($row, 'marca'),
                        'modelo' => $this->value($row, 'modelo'),
                        'familia_id' => $familia,
                        'clase' => $clase !== '' ? $clase : null,
                        'departamento_id' => $departamento,
                        'ubicacion_id' => $ubicacion,
                        'almacen' => $this->value($row, 'almacen'),
                        'estado' => $estado,
                        'stock' => $this->numberOrDefault($row['stock'] ?? null, 1),
                        'unidad_medida' => $this->value($row, 'unidad_medida') ?: 'und',
                        'responsable' => $this->value($row, 'responsable'),
                        'proveedor' => $this->value($row, 'proveedor'),
                        'fecha_registro' => now()->toDateString(),
                        'fecha_adquisicion' => $this->parseImportDate($row['fecha_adquisicion'] ?? null),
                        'fecha_mantenimiento' => $this->parseImportDate($row['fecha_mantenimiento'] ?? null),
                        'fecha_inspeccion' => $this->parseImportDate($row['fecha_inspeccion'] ?? null),
                        'descripcion' => $this->value($row, 'descripcion'),
                        'bloqueado' => false,
                    ]
                );

                $item->wasRecentlyCreated ? $creadas++ : $actualizadas++;
            }
        });

        return back()->with('success', "Importación completada: {$creadas} máquinas nuevas registradas y {$actualizadas} actualizadas.");
    }

    private function value(array $row, string $key): ?string
    {
        $value = trim((string) ($row[$key] ?? ''));
        return $value === '' ? null : $value;
    }

    private function numberOrDefault($value, $default): float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $default;
        }

        return (float) str_replace(',', '.', $value);
    }

    private function parseImportDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->toDateString();
            }

            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function generateImportId(string $nombre, int $proyectoId): string
    {
        $base = 'IMP-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]+/', '-', $nombre) ?: 'MAQUINA', 0, 20));
        $candidate = $base;
        $suffix = 1;
        while (HerramientaPlanta::where('proyecto_id', $proyectoId)->where('id_interno', $candidate)->exists()) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }

    private function resolveFamilia($value): ?int
    {
        $value = trim((string) $value);
        return $value === '' ? null : FamiliaHerramienta::firstOrCreate(['nombre' => $value], ['activo' => true])->id;
    }

    private function resolveUbicacion($value): ?int
    {
        $value = trim((string) $value);
        return $value === '' ? null : Ubicacion::firstOrCreate(['nombre' => $value])->id;
    }

    private function resolveDepartamento($value): ?int
    {
        $value = mb_strtolower(trim((string) $value), 'UTF-8');
        return $value === '' ? null : Departamento::firstOrCreate(['nombre' => $value])->id;
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('buscar')) {
            $search = trim((string) $request->string('buscar'));
            $query->where(function ($builder) use ($search) {
                $builder->where('id_interno', 'like', "%{$search}%")->orWhere('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")->orWhere('numero_serie', 'like', "%{$search}%")
                    ->orWhere('marca', 'like', "%{$search}%")->orWhere('modelo', 'like', "%{$search}%")
                    ->orWhere('responsable', 'like', "%{$search}%");
            });
        }

        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->integer('departamento_id'));
        }
        if ($request->filled('clase')) {
            $query->where('clase', (string) $request->string('clase'));
        }
        if ($request->filled('estado')) {
            $query->where('estado', (string) $request->string('estado'));
        }
        if ($request->filled('ubicacion_id')) {
            $query->where('ubicacion_id', $request->integer('ubicacion_id'));
        }
        if ($request->filled('familia')) {
            $query->where('familia_id', $request->integer('familia'));
        }

        if ($request->filled('alerta')) {
            if ($request->alerta === 'oca_vencida') {
                $query->whereDate('fecha_inspeccion', '<', now());
            } elseif ($request->alerta === 'oca_proxima') {
                $query->whereBetween('fecha_inspeccion', [now(), now()->addDays(30)]);
            } elseif ($request->alerta === 'mantenimiento') {
                $query->whereDate('fecha_mantenimiento', '<', now());
            }
        }
    }

    public function create(Request $request)
    {
        $proyectoActivoId = $this->resolveProyectoForCorrelativo($request);

        return view('herramientas.planta.form', [
            'familias' => FamiliaHerramienta::where('activo', true)->orderBy('nombre')->get(),
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList($proyectoActivoId),
        ]);
    }

    public function storeFamilia(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120', 'unique:familias_herramientas,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $familia = FamiliaHerramienta::create($data);

        if ($request->expectsJson()) {
            return response()->json(['id' => $familia->id, 'nombre' => $familia->nombre]);
        }

        return back()->with('success', 'Familia creada correctamente.');
    }

    public function store(Request $request)
    {
        $proyectoActivoId = $this->resolveActiveProyectoId($request);
        $data = $this->validated($request, null, $proyectoActivoId);
        $this->assertPersonalInProyecto($data['personal_id'] ?? null, $proyectoActivoId);
        $data = $this->resolvePersonalAssignment($data);
        $data['proyecto_id'] = $proyectoActivoId;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('herramientas-planta', 'public');
        }

        $item = HerramientaPlanta::create($data);
        return redirect()->route('herramientas.planta.show', $item)->with('success', 'Herramienta registrada correctamente.');
    }

    public function show(HerramientaPlanta $herramientaPlanta)
    {
        $this->authorizeActivoProyecto($herramientaPlanta);

        return view('herramientas.planta.show', [
            'herramienta' => $herramientaPlanta->load('familia', 'personalAsignado', 'ubicacion', 'eventos.usuario'),
            'personalList' => $this->personalList((int) $herramientaPlanta->proyecto_id),
        ]);
    }

    public function storeEvento(Request $request, HerramientaPlanta $herramientaPlanta)
    {
        $this->authorizeActivoProyecto($herramientaPlanta);

        $data = $this->eventValidated($request);
        $data['usuario_id'] = auth()->id();

        $proximoMantenimiento = $data['fecha_mantenimiento'] ?? null;
        unset($data['fecha_mantenimiento']);

        if ($request->hasFile('archivo_adjunto')) {
            $data['archivo_adjunto'] = $request->file('archivo_adjunto')->store('eventos_planta', 'public');
        }

        $herramientaPlanta->eventos()->create($data);

        if ($request->tipo === 'mantenimiento' && $proximoMantenimiento) {
            $herramientaPlanta->update([
                'fecha_mantenimiento' => $proximoMantenimiento,
                'estado' => 'operativo',
                'bloqueado' => false,
                'motivo_bloqueo' => null,
                'dias_estimados_baja' => null,
                'fecha_baja' => null,
            ]);
        }

        return back()->with('success', 'Evento documentado en bitácora y revisión actualizada.');
    }

    public function edit(HerramientaPlanta $herramientaPlanta)
    {
        $this->authorizeActivoProyecto($herramientaPlanta);

        return view('herramientas.planta.form', [
            'item' => $herramientaPlanta,
            'familias' => FamiliaHerramienta::where('activo', true)->orderBy('nombre')->get(),
            'ubicaciones' => Ubicacion::orderBy('nombre')->get(),
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'clasesCatalogo' => ClaseHerramienta::orderBy('nombre')->get(),
            'personalList' => $this->personalList((int) $herramientaPlanta->proyecto_id),
        ]);
    }

    public function update(Request $request, HerramientaPlanta $herramientaPlanta)
    {
        $this->authorizeActivoProyecto($herramientaPlanta);

        $data = $this->validated($request, $herramientaPlanta, (int) $herramientaPlanta->proyecto_id);
        $this->assertPersonalInProyecto($data['personal_id'] ?? null, (int) $herramientaPlanta->proyecto_id);
        $data = $this->resolvePersonalAssignment($data);

        if ($request->hasFile('imagen')) {
            if ($herramientaPlanta->imagen) {
                Storage::disk('public')->delete($herramientaPlanta->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('herramientas-planta', 'public');
        }

        $herramientaPlanta->update($data);
        return redirect()->route('herramientas.planta.show', $herramientaPlanta)->with('success', 'Herramienta actualizada correctamente.');
    }

    public function destroy(HerramientaPlanta $herramientaPlanta)
    {
        $this->authorizeActivoProyecto($herramientaPlanta);

        $herramientaPlanta->delete();
        return redirect()->route('herramientas.index')->with('success', 'Herramienta eliminada correctamente.');
    }

    private function validated(Request $request, ?HerramientaPlanta $item = null, int $proyectoId = 1): array
    {
        return $request->validate([
            'id_interno' => [
                'required',
                'string',
                'max:100',
                Rule::unique('herramientas_planta', 'id_interno')
                    ->where(fn ($q) => $q->where('proyecto_id', $proyectoId))
                    ->ignore($item?->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:100'],
            'numero_serie' => ['nullable', 'string', 'max:150'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:150'],
            'categoria' => ['nullable', 'string', 'max:150'],
            'estado' => ['required', Rule::in(['operativo', 'asignado', 'mantenimiento', 'reparacion'])],
            'ubicacion_id' => ['nullable', 'exists:ubicaciones,id'],
            'responsable' => ['nullable', 'string', 'max:150'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'personal_id' => ['nullable', 'exists:personal,id'],
            'id_proveedor' => ['nullable', 'string', 'max:100'],
            'almacen' => ['nullable', 'string', 'max:150'],
            'stock' => ['required', 'numeric', 'min:0'],
            'unidad_medida' => ['required', 'string', 'max:30'],
            'fecha_registro' => ['required', 'date'],
            'fecha_adquisicion' => ['nullable', 'date'],
            'fecha_mantenimiento' => ['nullable', 'date'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'familia_id' => ['nullable', 'exists:familias_herramientas,id'],
            'familia_energetica' => ['nullable', 'string', 'max:80'],
            'potencia' => ['nullable', 'string', 'max:80'],
            'tension' => ['nullable', 'string', 'max:80'],
            'combustible' => ['nullable', 'string', 'max:80'],
            'criticidad' => ['nullable', 'string', 'max:30'],
            'horas_uso' => ['nullable', 'integer', 'min:0'],
            'bloqueado' => ['required', 'boolean'],
            'motivo_bloqueo' => ['nullable', 'string', 'max:255'],
            'garantia_hasta' => ['nullable', 'date'],
            'fecha_inspeccion' => ['nullable', 'date'],
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

    public function updateEstado(Request $request, HerramientaPlanta $herramientaPlanta)
    {
        $this->authorizeActivoProyecto($herramientaPlanta);

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
            $dataToUpdate['bloqueado'] = false;
            $dataToUpdate['motivo_bloqueo'] = null;
            $dataToUpdate['dias_estimados_baja'] = null;
            $dataToUpdate['fecha_baja'] = null;
        } elseif (in_array($request->estado, ['reparacion', 'mantenimiento'], true)) {
            $dataToUpdate['bloqueado'] = true;
            $dataToUpdate['motivo_bloqueo'] = $request->titulo_evento;
            $dataToUpdate['dias_estimados_baja'] = $request->dias_estimados_baja;
            $dataToUpdate['fecha_baja'] = now();
        }

        $herramientaPlanta->update($dataToUpdate);

        $eventoData = [
            'tipo' => in_array($request->estado, ['mantenimiento', 'reparacion'], true) ? $request->estado : 'otro',
            'titulo' => $request->titulo_evento,
            'descripcion' => 'Cambio de estado en planta a: ' . ucfirst($request->estado),
            'fecha' => now(),
            'usuario_id' => auth()->id(),
        ];

        if ($request->hasFile('archivo_adjunto')) {
            $eventoData['archivo_adjunto'] = $request->file('archivo_adjunto')->store('eventos_planta', 'public');
        }

        $herramientaPlanta->eventos()->create($eventoData);

        return back()->with('success', 'Estado de la maquinaria actualizado y registrado correctamente.');
    }

    public function uploadManual(Request $request, HerramientaPlanta $herramientaPlanta)
    {
        $this->authorizeActivoProyecto($herramientaPlanta);

        $request->validate([
            'manual_pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if ($herramientaPlanta->manual_pdf) {
            Storage::disk('public')->delete($herramientaPlanta->manual_pdf);
        }

        $path = $request->file('manual_pdf')->store('manuales_planta', 'public');
        $herramientaPlanta->update(['manual_pdf' => $path]);

        $herramientaPlanta->eventos()->create([
            'tipo' => 'otro',
            'titulo' => 'Actualización de Documentación',
            'descripcion' => 'Se ha subido o actualizado el manual técnico / normativa de la máquina.',
            'fecha' => now(),
            'usuario_id' => auth()->id(),
        ]);

        return back()->with('success', 'Manual técnico actualizado y disponible para los operarios.');
    }

    public function updateAsignacion(Request $request, HerramientaPlanta $herramientaPlanta)
    {
        $this->authorizeActivoProyecto($herramientaPlanta);

        $request->validate([
            'responsable' => ['nullable', 'string', 'max:150', 'required_without:personal_id'],
            'personal_id' => ['nullable', 'exists:personal,id'],
            'ubicacion_id' => ['nullable', 'exists:ubicaciones,id'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'titulo_evento' => ['required', 'string', 'max:180'],
            'archivo_adjunto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->assertPersonalInProyecto($request->filled('personal_id') ? $request->integer('personal_id') : null, (int) $herramientaPlanta->proyecto_id);

        $personal = $request->filled('personal_id') ? Personal::findOrFail($request->integer('personal_id')) : null;
        $responsable = $personal ? trim($personal->name . ' ' . $personal->apellido) : trim((string) $request->input('responsable'));

        $ubicacionId = $request->filled('ubicacion_id')
            ? $request->integer('ubicacion_id')
            : $this->resolveUbicacion($request->input('ubicacion'));

        $nombreSitio = $ubicacionId ? (Ubicacion::find($ubicacionId)?->nombre ?? 'S/C') : 'S/C';

        $herramientaPlanta->update([
            'estado' => 'asignado',
            'responsable' => $responsable,
            'personal_id' => $personal?->id,
            'ubicacion_id' => $ubicacionId,
            'bloqueado' => false,
        ]);

        $rutaArchivo = null;
        if ($request->hasFile('archivo_adjunto')) {
            $rutaArchivo = $request->file('archivo_adjunto')->store('eventos_planta', 'public');
        }

        $herramientaPlanta->eventos()->create([
            'tipo' => 'responsable',
            'titulo' => $request->titulo_evento,
            'descripcion' => "Entregado a: {$responsable} | Obra: {$nombreSitio}",
            'fecha' => now(),
            'archivo_adjunto' => $rutaArchivo,
            'usuario_id' => auth()->id(),
        ]);

        return back()->with('success', 'Maquinaria asignada a la obra correctamente.');
    }
}