<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Personal;
use App\Models\SalidaStock;
use App\Models\InventarioMovimiento;
use App\Models\EquipoInformatico;
use App\Models\HerramientaPlanta;
use App\Models\AparatoCalibrable;
use App\Models\Proyecto;
use App\Models\Departamento;
use App\Models\Puesto;
use App\Models\PuestoTrabajo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class PersonalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorize('personal.view');

        $query = (string) $request->input('q', '');
        $export = $request->input('export');
        $user = $request->user();
        $alertaDias = $request->has('alerta_dias')
            ? (int) $request->input('alerta_dias', 0)
            : (int) ($user?->personal_alerta_dias ?? 0);
        $alertaDias = in_array($alertaDias, [0, 30, 60, 90, 120, 180], true) ? $alertaDias : 0;
        if ($request->has('alerta_dias') && $user) {
            $user->update(['personal_alerta_dias' => $alertaDias]);
        }
        $alertaLimite = $alertaDias > 0 ? now()->addDays($alertaDias)->endOfDay() : null;

        $alertaNombre = (string) $request->input('alerta_nombre', 'any');
        $alertaNombre = in_array($alertaNombre, ['any', 'with', 'without'], true) ? $alertaNombre : 'any';

        $proyectoActivoId = $this->resolveProyectoForCorrelativo($request);

        $buildQuery = function () use ($query, $alertaNombre, $alertaLimite, $proyectoActivoId) {
            $personalQuery = Personal::query()
                ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoActivoId))
                ->with([
                    'proyectos',
                    'cursos' => fn ($query) => $query->orderBy('nombre'),
                ]);

            if ($query !== '') {
                $personalQuery->where(function ($subQuery) use ($query) {
                    $subQuery
                        ->where('name', 'like', "%{$query}%")
                        ->orWhere('apellido', 'like', "%{$query}%")
                        ->orWhere('dni_nie', 'like', "%{$query}%")
                        ->orWhere('id_rrhh', 'like', "%{$query}%")
                        ->orWhere('telefono', 'like', "%{$query}%")
                        ->orWhere('departamento', 'like', "%{$query}%");
                });
            }

            if ($alertaNombre === 'with') {
                if ($alertaLimite) {
                    $personalQuery->whereNotNull('proxima_revision_medica')
                        ->where('proxima_revision_medica', '<=', $alertaLimite);
                } else {
                    $personalQuery->whereNotNull('proxima_revision_medica');
                }
            }

            if ($alertaNombre === 'without') {
                if ($alertaLimite) {
                    $personalQuery->where(function ($q) use ($alertaLimite) {
                        $q->whereNull('proxima_revision_medica')
                          ->orWhere('proxima_revision_medica', '>', $alertaLimite);
                    });
                } else {
                    $personalQuery->whereNull('proxima_revision_medica');
                }
            }

            return $personalQuery->orderBy('name');
        };

        if ($export === 'csv') {
            $this->authorize('personal.export');

            $rows = $buildQuery()->get();

            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($handle, ['ID', 'Nombre', 'Apellido', 'DNI/NIE', 'Telefono', 'Departamento', 'Estado', 'Sin Tallas', 'Proyectos'], ';');

                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        $row->apellido,
                        $row->dni_nie,
                        $row->telefono,
                        is_array($row->departamento) ? implode(', ', $row->departamento) : $row->departamento,
                        $row->activo ? 'Activo' : 'Inactivo',
                        $row->sin_tallas ? 'Sí' : 'No',
                        $row->proyectos->pluck('nombre')->join(' | '),
                    ], ';');
                }

                fclose($handle);
            }, 'personal.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $personals = $buildQuery()->paginate(12)->withQueryString();
        $personals->getCollection()->transform(function (Personal $personal) use ($alertaLimite) {
            $hoy = \Carbon\Carbon::now()->startOfDay();

            if ($personal->proxima_revision_medica) {
                $prox = \Carbon\Carbon::parse($personal->proxima_revision_medica)->startOfDay();
                if ($hoy->gt($prox)) {
                    $personal->estado_medico = 'caducada';
                } elseif ($alertaLimite && $prox->lte($alertaLimite)) {
                    $personal->estado_medico = 'aviso';
                } else {
                    $personal->estado_medico = 'ok';
                }
            } else {
                $personal->estado_medico = 'ok';
            }

            $personal->alerta_revision_medica = in_array($personal->estado_medico, ['caducada', 'aviso']);

            $cursosAptos = $personal->cursos->filter(fn ($curso) => (bool) ($curso->pivot->apto ?? false))->count();
            $cursosTotales = $personal->cursos->count();

            $personal->cursos_resumen = $personal->cursos
                ->take(3)
                ->map(function ($curso) {
                    $apto = (bool) ($curso->pivot->apto ?? false);

                    return (object) [
                        'nombre' => $curso->nombre,
                        'apto' => $apto,
                        'clase' => $apto ? 'personal-course-pill personal-course-pill--ok' : 'personal-course-pill personal-course-pill--warn',
                    ];
                })
                ->values()
                ->all();

            if ($cursosTotales === 0) {
                $personal->cursos_estado = 'Sin formación';
                $personal->cursos_estado_clase = 'personal-course-status personal-course-status--muted';
            } elseif ($cursosAptos === $cursosTotales) {
                $personal->cursos_estado = 'Formación al día';
                $personal->cursos_estado_clase = 'personal-course-status personal-course-status--ok';
            } else {
                $personal->cursos_estado = 'Revisar formación';
                $personal->cursos_estado_clase = 'personal-course-status personal-course-status--warn';
            }

            return $personal;
        });

        $filtroProyectoActivo = fn ($q) => $q->where('proyectos.id', $proyectoActivoId);

        $personalTotal = Personal::whereHas('proyectos', $filtroProyectoActivo)->count();
        $personalActivos = Personal::where('activo', true)->whereHas('proyectos', $filtroProyectoActivo)->count();

        if ($alertaLimite) {
            $avisosCount = Personal::whereNotNull('proxima_revision_medica')
                ->where('proxima_revision_medica', '<=', $alertaLimite)
                ->whereHas('proyectos', $filtroProyectoActivo)
                ->count();
        } else {
            $avisosCount = 0;
        }

        // Cálculo de tallas faltantes filtrado por el proyecto activo
        $tallasFaltantes = 0;
        $columnasTallas = ['camiseta', 'chaqueta', 'sudadera', 'pantalon', 'calzado', 'guantes', 'casco', 'gafas', 'bolsa_fod'];
        $personalNecesitaEpi = Personal::where('activo', true)
            ->where('sin_tallas', false)
            ->whereHas('proyectos', $filtroProyectoActivo)
            ->get($columnasTallas);

        foreach ($personalNecesitaEpi as $p) {
            foreach ($columnasTallas as $col) {
                if (empty($p->{$col})) {
                    $tallasFaltantes++;
                }
            }
        }

        $cursosCatalogo = Curso::whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoActivoId))
            ->orderBy('nombre')
            ->get();
        $departamentos = Departamento::orderBy('nombre')->get();

        if ($request->ajax()) {
            return view('personal.partials.table', [
                'personals' => $personals,
            ])->render();
        }

        return view('personal.index', [
            'personals' => $personals,
            'query' => $query,
            'alertaDias' => $alertaDias,
            'alertaNombre' => $alertaNombre,
            'avisosCount' => $avisosCount,
            'personalTotal' => $personalTotal,
            'personalActivos' => $personalActivos,
            'tallasFaltantes' => $tallasFaltantes,
            'cursosCatalogo' => $cursosCatalogo,
            'departamentos' => $departamentos,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('personal.create');

        $user = auth()->user();
        $proyectoActivoId = $this->resolveProyectoForCorrelativo($request);
        $personal = new Personal(['activo' => true]);

        $proyectos = ($user && $user->role === 'superadmin')
            ? Proyecto::orderBy('nombre')->get()
            : ($user?->proyectos()->orderBy('nombre')->get() ?? collect());

        $departamentos = Departamento::orderBy('nombre')->get();
        $puestosTrabajoCatalogo = PuestoTrabajo::where('proyecto_id', $proyectoActivoId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('personal.edit', [
            'personal' => $personal,
            'isCreate' => true,
            'proyectoActivoId' => $proyectoActivoId,
            'proyectos' => $proyectos,
            'departamentos' => $departamentos,
            'puestosTrabajoCatalogo' => $puestosTrabajoCatalogo,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('personal.create');

        $validated = $this->validatePersonal($request);

        if (!empty($validated['ultima_revision_medica']) && !empty($validated['puesto_trabajo_id'])) {
            $puestoTrabajo = PuestoTrabajo::find($validated['puesto_trabajo_id']);
            $meses = $puestoTrabajo ? ($puestoTrabajo->periodicidad_meses ?? 12) : 12;

            $fechaCalculada = \Carbon\Carbon::parse($validated['ultima_revision_medica'])
                ->addMonths($meses)
                ->format('Y-m-d');

            if (!empty($validated['proxima_revision_medica']) && $validated['proxima_revision_medica'] !== $fechaCalculada) {
                $validated['revision_medica_manual'] = true;
            } else {
                $validated['proxima_revision_medica'] = $fechaCalculada;
                $validated['revision_medica_manual'] = false;
            }
        }

        if (!auth()->user()->can('personal.medico')) {
            unset($validated['ultima_revision_medica'], $validated['proxima_revision_medica'], $validated['ultima_graduacion'], $validated['proxima_graduacion'], $validated['reconocido_en'], $validated['graduado_en']);
        }
        if (!auth()->user()->can('personal.tallas')) {
            unset($validated['camiseta'], $validated['chaqueta'], $validated['sudadera'], $validated['pantalon'], $validated['calzado'], $validated['casco'], $validated['gafas'], $validated['guantes'], $validated['bolsa_fod'], $validated['sin_tallas']);
        }

        $user = auth()->user();
        $proyectoActivoId = $this->resolveActiveProyectoId($request);
        $proyectoIds = array_values(array_filter(array_map('intval', (array) ($validated['proyecto_ids'] ?? []))));

        if ($user && $user->role !== 'superadmin') {
            $misProyectosIds = $user->proyectos->pluck('id')->map(fn ($id) => (int) $id)->all();
            $proyectoIds = array_values(array_intersect($proyectoIds, $misProyectosIds));
        }

        if (empty($proyectoIds)) {
            $proyectoIds = [$proyectoActivoId];
        }

        $departamentos = array_values(array_filter((array) $request->input('departamento', [])));
        $validated['departamento'] = $departamentos ?: null;

        $validated['activo'] = $request->boolean('activo', true);

        if (isset($validated['sin_tallas'])) {
            $validated['sin_tallas'] = $request->boolean('sin_tallas', false);
        }

        $personal = Personal::create($validated);
        $personal->proyectos()->sync($proyectoIds);

        $this->aplicarMacroDeCursos($personal);

        return redirect()->route('personal.index')->with('success', 'Trabajador creado y asignado correctamente.');
    }

    public function show(Personal $personal, Request $request)
    {
        abort_unless(auth()->user()->canAny(['personal.view', 'personal.acciones']), 403);
        $this->authorize('view', $personal);
        $this->authorizePersonalProyecto($personal);

        $personal->load([
            'proyectos',
            'historialPrl',
            'cursos' => fn ($query) => $query->orderBy('nombre'),
        ]);

        $proyectoActivoId = $this->resolveProyectoForCorrelativo($request);
        $cursosCatalogo = Curso::whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoActivoId))
            ->orderBy('nombre')
            ->get();
        $nombreCompleto = trim(preg_replace('/\s+/', ' ', trim((string) $personal->name . ' ' . (string) $personal->apellido)));
        $nombreNormalizado = mb_strtolower($nombreCompleto);
        $articuloBuscar = trim((string) $request->input('articulo', ''));

        // 1. SALIDAS DEL NUEVO KARDEX (InventarioMovimiento)
        $movimientosKardex = InventarioMovimiento::query()
            ->where('personal_id', $personal->id)
            ->where('tipo', 'SALIDA')
            ->where('estado', 'REGISTRADO')
            ->with(['lineas.variante.articulo'])
            ->when($request->input('fecha_desde'), fn ($q, $fecha) => $q->whereDate('created_at', '>=', $fecha))
            ->when($request->input('fecha_hasta'), fn ($q, $fecha) => $q->whereDate('created_at', '<=', $fecha))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->flatMap(function (InventarioMovimiento $mov) use ($articuloBuscar) {
                return $mov->lineas->map(function ($linea) use ($mov) {
                    $var = $linea->variante;
                    $art = $var?->articulo;
                    $nombreArticulo = trim((string) ($var?->descripcion ?: ($var?->nombre ?: ($art?->nombre ?: $art?->descripcion ?: 'EPI / Material'))));

                    return (object) [
                        'salida_id' => $mov->id,
                        'fecha' => optional($mov->created_at)->format('d M Y'),
                        '_timestamp' => optional($mov->created_at)->timestamp ?? 0,
                        'articulo' => $nombreArticulo,
                        'cantidad' => (int) $linea->cantidad,
                        'ot' => $mov->ot ?: '—',
                        'estado' => 'Entregado',
                        'estado_clase' => 'profile-chip profile-chip--ok',
                    ];
                })->filter(function ($row) use ($articuloBuscar) {
                    if ($articuloBuscar === '') {
                        return true;
                    }
                    return stripos($row->articulo, $articuloBuscar) !== false;
                });
            });

        // 2. SALIDAS LEGACY (SalidaStock)
        $salidasQuery = SalidaStock::query();
        if (Schema::hasColumn((new SalidaStock())->getTable(), 'personal_id')) {
            $salidasQuery->where(function ($query) use ($personal, $nombreNormalizado) {
                $query->where('personal_id', $personal->id)
                    ->orWhere(function ($legacyQuery) use ($nombreNormalizado) {
                        $legacyQuery->whereNull('personal_id')
                            ->whereRaw('LOWER(TRIM(solicitante)) = ?', [$nombreNormalizado]);
                    });
            });
        } else {
            $salidasQuery->whereRaw('LOWER(TRIM(solicitante)) = ?', [$nombreNormalizado]);
        }

        $salidasLegacy = $salidasQuery
            ->where('estado', '<>', 'cancelado')
            ->when($request->input('fecha_desde'), fn ($query, $fecha) => $query->whereDate('fecha', '>=', $fecha))
            ->when($request->input('fecha_hasta'), fn ($query, $fecha) => $query->whereDate('fecha', '<=', $fecha))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->flatMap(function (SalidaStock $salida) use ($articuloBuscar) {
                $items = collect((array) $salida->items)
                    ->filter(fn ($item) => is_array($item))
                    ->map(function (array $item) {
                        return [
                            'descripcion' => trim((string) ($item['descripcion'] ?? $item['articulo'] ?? $item['nombre'] ?? '')),
                            'cantidad' => (float) ($item['cantidad'] ?? 0),
                        ];
                    })
                    ->filter(fn (array $item) => $item['descripcion'] !== '')
                    ->values();

                if ($items->isEmpty()) {
                    $items = collect([['descripcion' => 'Salida de inventario', 'cantidad' => 1]]);
                }

                return $items->map(function (array $item) use ($salida) {
                    $estadoRaw = (string) ($salida->estado ?: 'Pendiente');
                    $estadoNormalizado = mb_strtolower($estadoRaw);

                    return (object) [
                        'salida_id' => $salida->id,
                        'fecha' => optional($salida->fecha)->format('d M Y'),
                        '_timestamp' => optional($salida->fecha)->timestamp ?? 0,
                        'articulo' => $item['descripcion'],
                        'cantidad' => $item['cantidad'],
                        'ot' => $salida->ot ?: '—',
                        'estado' => ucfirst($estadoNormalizado === 'aceptado' ? 'entregado' : $estadoNormalizado),
                        'estado_clase' => $estadoNormalizado === 'aceptado' || $estadoNormalizado === 'entregado'
                            ? 'profile-chip profile-chip--ok'
                            : 'profile-chip profile-chip--pending',
                    ];
                })->filter(function ($row) use ($articuloBuscar) {
                    if ($articuloBuscar === '') {
                        return true;
                    }
                    return stripos($row->articulo, $articuloBuscar) !== false;
                });
            });

        $historicoSalidas = $movimientosKardex
            ->concat($salidasLegacy)
            ->sortByDesc('_timestamp')
            ->take(15)
            ->values()
            ->all();

        $activosAsignados = collect([
            'Equipos IT' => $this->assignedAssetsQuery(new EquipoInformatico(), 'responsable', $personal, $nombreNormalizado)->get(),
            'Maquinaria de planta' => $this->assignedAssetsQuery(new HerramientaPlanta(), 'responsable', $personal, $nombreNormalizado)->get(),
            'Aparatos calibrables' => $this->assignedAssetsQuery(new AparatoCalibrable(), 'responsable', $personal, $nombreNormalizado)->get(),
        ]);

        $tallas = [
            ['label' => 'Camiseta', 'value' => $personal->camiseta ?: '—', 'icon' => 'fa-tshirt'],
            ['label' => 'Sudadera', 'value' => $personal->sudadera ?: '—', 'icon' => 'fa-tshirt'],
            ['label' => 'Chaquetón', 'value' => $personal->chaqueta ?: '—', 'icon' => 'fa-vest'],
            ['label' => 'Pantalón', 'value' => $personal->pantalon ?: '—', 'icon' => 'fa-bridge'],
            ['label' => 'Calzado', 'value' => $personal->calzado ?: '—', 'icon' => 'fa-shoe-prints'],
            ['label' => 'Casco', 'value' => $personal->casco ?: '—', 'icon' => 'fa-hard-hat'],
            ['label' => 'Guantes', 'value' => $personal->guantes ?: '—', 'icon' => 'fa-hand-paper'],
            ['label' => 'Gafas', 'value' => $personal->gafas ?: '—', 'icon' => 'fa-glasses'],
            ['label' => 'Bolsa F.O.D.', 'value' => $personal->bolsa_fod ?: '—', 'icon' => 'fa-bag-shopping'],
        ];

        return view('personal.show', compact('personal', 'historicoSalidas', 'tallas', 'cursosCatalogo', 'activosAsignados'));
    }

    private function assignedAssetsQuery($model, string $legacyColumn, Personal $personal, string $nombreNormalizado)
    {
        $query = $model::query();
        if (Schema::hasColumn($model->getTable(), 'personal_id')) {
            return $query->where(function ($builder) use ($personal, $legacyColumn, $nombreNormalizado) {
                $builder->where('personal_id', $personal->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('personal_id')->whereRaw("LOWER(TRIM({$legacyColumn})) = ?", [$nombreNormalizado]));
            });
        }

        return $query->whereRaw("LOWER(TRIM({$legacyColumn})) = ?", [$nombreNormalizado]);
    }

    public function edit(Request $request, Personal $personal)
    {
        $this->authorize('personal.edit');
        $this->authorizePersonalProyecto($personal);

        $user = auth()->user();
        $proyectoActivoId = $this->resolveProyectoForCorrelativo($request);

        $proyectos = ($user && $user->role === 'superadmin')
            ? Proyecto::orderBy('nombre')->get()
            : ($user?->proyectos()->orderBy('nombre')->get() ?? collect());

        $departamentos = Departamento::orderBy('nombre')->get();
        $puestosTrabajoCatalogo = PuestoTrabajo::where('proyecto_id', $proyectoActivoId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('personal.edit', [
            'personal' => $personal,
            'isCreate' => false,
            'proyectoActivoId' => $proyectoActivoId,
            'proyectos' => $proyectos,
            'departamentos' => $departamentos,
            'puestosTrabajoCatalogo' => $puestosTrabajoCatalogo,
        ]);
    }

    public function update(Request $request, Personal $personal)
    {
        $this->authorize('personal.edit');
        $this->authorizePersonalProyecto($personal);

        $validated = $this->validatePersonal($request, $personal->id);

        if (!empty($validated['ultima_revision_medica']) && !empty($validated['puesto_trabajo_id'])) {
            $puestoTrabajo = PuestoTrabajo::find($validated['puesto_trabajo_id']);
            $meses = $puestoTrabajo ? ($puestoTrabajo->periodicidad_meses ?? 12) : 12;

            $fechaCalculada = \Carbon\Carbon::parse($validated['ultima_revision_medica'])
                ->addMonths($meses)
                ->format('Y-m-d');

            if (!empty($validated['proxima_revision_medica']) && $validated['proxima_revision_medica'] !== $fechaCalculada) {
                $validated['revision_medica_manual'] = true;
            } else {
                $validated['proxima_revision_medica'] = $fechaCalculada;
                $validated['revision_medica_manual'] = false;
            }
        }

        if (!auth()->user()->can('personal.medico')) {
            unset($validated['ultima_revision_medica'], $validated['proxima_revision_medica'], $validated['ultima_graduacion'], $validated['proxima_graduacion'], $validated['reconocido_en'], $validated['graduado_en']);
        }
        if (!auth()->user()->can('personal.tallas')) {
            unset($validated['camiseta'], $validated['chaqueta'], $validated['sudadera'], $validated['pantalon'], $validated['calzado'], $validated['casco'], $validated['gafas'], $validated['guantes'], $validated['bolsa_fod'], $validated['sin_tallas']);
        }

        $proyectoIds = $validated['proyecto_ids'] ?? null;
        unset($validated['proyecto_ids']);

        // Candado obligatorio: no se permite dejar a un trabajador sin ninguna sede asignada
        if (is_array($proyectoIds)) {
            $user = auth()->user();
            $proyectoIdsLimpios = array_values(array_filter(array_map('intval', $proyectoIds)));

            if ($user && $user->role !== 'superadmin') {
                $misProyectosIds = $user->proyectos->pluck('id')->map(fn ($id) => (int) $id)->all();
                // Conservamos las sedes del trabajador de otras delegaciones que el usuario actual no gestiona
                $sedesOtrasDelegaciones = $personal->proyectos()
                    ->whereNotIn('proyectos.id', $misProyectosIds)
                    ->pluck('proyectos.id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $proyectoIdsLimpios = array_values(array_unique(array_merge(
                    $sedesOtrasDelegaciones,
                    array_intersect($proyectoIdsLimpios, $misProyectosIds)
                )));
            }

            if (empty($proyectoIdsLimpios)) {
                return back()
                    ->withErrors(['proyecto_ids' => 'El trabajador debe permanecer asignado al menos a una sede o proyecto. Selecciona primero la nueva sede antes de quitar la anterior.'])
                    ->withInput();
            }

            $personal->proyectos()->sync($proyectoIdsLimpios);
        }

        $departamentos = array_values(array_filter((array) $request->input('departamento', [])));
        $validated['departamento'] = $departamentos ?: null;

        if ($request->has('activo')) {
            $validated['activo'] = $request->boolean('activo');
        } else {
            unset($validated['activo']);
        }

        if (isset($validated['sin_tallas'])) {
            $validated['sin_tallas'] = $request->boolean('sin_tallas', false);
        }

        $personal->update($validated);

        $this->aplicarMacroDeCursos($personal);

        return redirect()->route('personal.index')->with('success', 'Trabajador actualizado correctamente.');
    }

    public function destroy(Personal $personal)
    {
        $this->authorize('personal.delete');
        $this->authorizePersonalProyecto($personal);

        $personal->delete();

        return redirect()->route('personal.index')->with('success', 'Trabajador eliminado correctamente.');
    }

    public function toggleStatus(Personal $personal)
    {
        $this->authorize('personal.edit');
        $this->authorizePersonalProyecto($personal);

        $estabaInactivo = !$personal->activo;
        $personal->activo = !$personal->activo;

        if ($personal->activo && $estabaInactivo) {
            $personal->fecha_reactivacion = now();
            $personal->prl_revisado = false;
        }

        $personal->save();

        $mensaje = $personal->activo ? 'Trabajador dado de ALTA correctamente.' : 'Trabajador dado de BAJA correctamente.';
        return redirect()->back()->with('success', $mensaje);
    }

    public function uploadDocumentosPrl(Request $request, Personal $personal)
    {
        $this->authorize('personal.edit');
        $this->authorizePersonalProyecto($personal);

        $request->validate([
            'tipo_documento' => 'required|in:riesgos,epi',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $tipo = $request->input('tipo_documento');
        $columnaBaseDatos = $tipo === 'riesgos' ? 'documento_riesgos' : 'documento_epi';

        $path = $request->file('archivo')->store('prl', 'public');

        $personal->update([
            $columnaBaseDatos => $path,
        ]);

        $personal->historialPrl()->create([
            'tipo' => $tipo,
            'archivo_path' => $path,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyDocumentosPrl(Personal $personal, $tipo)
    {
        $this->authorize('personal.edit');
        $this->authorizePersonalProyecto($personal);

        if (!in_array($tipo, ['riesgos', 'epi'], true)) {
            return redirect()->back()->with('error', 'Tipo de documento no válido.');
        }

        $columnaBaseDatos = $tipo === 'riesgos' ? 'documento_riesgos' : 'documento_epi';

        if ($personal->{$columnaBaseDatos}) {
            Storage::disk('public')->delete($personal->{$columnaBaseDatos});
        }

        $personal->update([$columnaBaseDatos => null]);

        $nombreDoc = $tipo === 'riesgos' ? 'Riesgos' : 'EPIs';
        return redirect()->back()->with('success', "Documento de {$nombreDoc} eliminado correctamente.");
    }

    public function destroyHistorialPrl(Personal $personal, \App\Models\HistorialPrl $historial)
    {
        $this->authorize('personal.edit');
        $this->authorizePersonalProyecto($personal);

        if ($historial->personal_id !== $personal->id) {
            abort(403, 'Acción no permitida.');
        }

        $esDocumentoActivo = ($personal->documento_riesgos === $historial->archivo_path || $personal->documento_epi === $historial->archivo_path);

        if (!$esDocumentoActivo && !empty($historial->archivo_path)) {
            Storage::disk('public')->delete($historial->archivo_path);
        }

        $historial->delete();

        return redirect()->back()->with('success', 'Registro histórico eliminado de forma segura.');
    }

    public function tallas(Request $request)
    {
        $this->authorize('personal.tallas');

        $query = (string) $request->input('q', '');
        $estado = (string) $request->input('estado', 'todos');
        $departamentoFiltro = (string) $request->input('departamento', 'todos');
        $export = $request->input('export');

        $incluirInactivos = $request->boolean('incluir_inactivos', false);

        $columns = ['camiseta', 'chaqueta', 'sudadera', 'pantalon', 'calzado', 'guantes', 'casco', 'gafas', 'bolsa_fod'];
        $departamentosCatalogo = Departamento::orderBy('nombre')->get();

        $user = auth()->user();
        $proyectoActivoId = $this->resolveProyectoForCorrelativo($request);

        $personals = Personal::query()
            ->whereHas('proyectos', fn ($subQ) => $subQ->where('proyectos.id', $proyectoActivoId))
            ->when(!$incluirInactivos, function ($q) {
                $q->where('activo', true);
            })
            ->when($user && $user->role !== 'superadmin', function ($q) use ($user) {
                $misProyectosIds = $user->proyectos->pluck('id')->toArray();
                $q->whereHas('proyectos', function ($subQ) use ($misProyectosIds) {
                    $subQ->whereIn('proyectos.id', $misProyectosIds);
                });
            })
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($subQ) use ($query) {
                    $subQ->where('name', 'like', "%{$query}%")
                        ->orWhere('apellido', 'like', "%{$query}%")
                        ->orWhere('dni_nie', 'like', "%{$query}%")
                        ->orWhere('departamento', 'like', "%{$query}%")
                        ->orWhere('id_rrhh', 'like', "%{$query}%");
                });
            })
            ->when($estado === 'falta_epi', function ($q) use ($columns) {
                $q->where('sin_tallas', false)
                    ->where(function ($subQ) use ($columns) {
                        foreach ($columns as $col) {
                            $subQ->orWhereNull($col)->orWhere($col, '');
                        }
                    });
            })
            ->when($estado === 'sin_departamento', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereNull('departamento')
                        ->orWhere('departamento', '')
                        ->orWhere('departamento', '[]');
                });
            })
            ->when($estado === 'sin_oficina', function ($q) {
                $q->where('sin_tallas', false);
            })
            ->when($departamentoFiltro !== 'todos', function ($q) use ($departamentoFiltro) {
                $q->where('departamento', 'like', "%{$departamentoFiltro}%");
            })
            ->orderBy('name')
            ->get();

        // Calcular resumen agregado de tallas (excluyendo personal sin_tallas)
        $personalQueNecesitaTalla = $personals->where('sin_tallas', false);
        $resumenTallas = [];
        foreach ($columns as $col) {
            $values = $personalQueNecesitaTalla->pluck($col)
                ->filter(fn ($v) => $v !== null && trim((string) $v) !== '');
            $resumenTallas[$col] = [
                'label' => $col === 'bolsa_fod' ? 'Bolsa F.O.D.' : ucfirst($col),
                'counts' => $values->countBy()->sortKeys(),
                'missing' => $personalQueNecesitaTalla->count() - $values->count(),
            ];
        }

        $proyectoActivo = Proyecto::find($proyectoActivoId);

        if ($export === 'csv') {
            $this->authorize('personal.export');

            return response()->streamDownload(function () use ($personals, $columns, $resumenTallas, $proyectoActivo) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // BLOQUE 1: RESUMEN AGREGADO POR PRENDA Y TALLA
                fputcsv($handle, ['RESUMEN AGREGADO DE TALLAS', $proyectoActivo ? strtoupper($proyectoActivo->nombre) : 'GENERAL', date('d/m/Y H:i')], ';');
                fputcsv($handle, ['Prenda / EPI', 'Talla', 'Cantidad (Trabajadores)'], ';');

                foreach ($resumenTallas as $info) {
                    foreach ($info['counts'] as $talla => $cantidad) {
                        fputcsv($handle, [$info['label'], $talla, $cantidad], ';');
                    }
                    if ($info['missing'] > 0) {
                        fputcsv($handle, [$info['label'], 'SIN ASIGNAR (Faltan)', $info['missing']], ';');
                    }
                }

                fputcsv($handle, [], ';');

                // BLOQUE 2: LISTADO DETALLADO POR TRABAJADOR
                fputcsv($handle, ['LISTADO DETALLADO POR TRABAJADOR'], ';');
                $headers = ['ID RRHH', 'Nombre', 'Apellido', 'Departamento', 'Estado'];
                foreach ($columns as $col) {
                    $headers[] = $col === 'bolsa_fod' ? 'Bolsa F.O.D.' : ucfirst($col);
                }
                fputcsv($handle, $headers, ';');

                foreach ($personals as $p) {
                    $deptos = is_string($p->departamento) ? json_decode($p->departamento, true) ?? explode(',', $p->departamento) : (array) $p->departamento;
                    $deptosStr = !empty($deptos) ? strtoupper(implode(', ', $deptos)) : 'SIN DEPARTAMENTO';

                    $row = [
                        $p->id_rrhh ?: '—',
                        $p->name,
                        $p->apellido,
                        $deptosStr,
                        $p->activo ? 'Activo' : 'De Baja',
                    ];

                    foreach ($columns as $c) {
                        if ($p->sin_tallas) {
                            $row[] = 'N/A';
                        } else {
                            $row[] = $p->{$c} ?: 'Falta';
                        }
                    }

                    fputcsv($handle, $row, ';');
                }

                fclose($handle);
            }, 'listado_tallas_epis_' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        if ($export === 'pdf') {
            $this->authorize('personal.export');

            $fileName = 'resumen_tallas_epis_' . date('Y-m-d') . '.pdf';
            $pdfContent = null;

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadView('personal.pdf-tallas', compact(
                    'personals',
                    'columns',
                    'resumenTallas',
                    'proyectoActivo'
                ))->setPaper('a4', 'landscape')->output();
            } elseif (class_exists(\Dompdf\Dompdf::class)) {
                $html = view('personal.pdf-tallas', compact('personals', 'columns', 'resumenTallas', 'proyectoActivo'))->render();
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->loadHtml($html);
                $dompdf->render();
                $pdfContent = $dompdf->output();
            }

            if ($pdfContent !== null) {
                return $this->respondWithPdfBlob($pdfContent, $fileName);
            }

            return view('personal.pdf-tallas', compact('personals', 'columns', 'resumenTallas', 'proyectoActivo'));
        }

        return view('personal.tallas', [
            'personals' => $personals,
            'query' => $query,
            'estado' => $estado,
            'departamentoFiltro' => $departamentoFiltro,
            'departamentosCatalogo' => $departamentosCatalogo,
            'columns' => $columns,
            'incluirInactivos' => $incluirInactivos,
            'resumenTallas' => $resumenTallas,
        ]);
    }

    /**
     * Sirve el PDF mediante visor Blob en navegador (evita errores de red/corte en PHP built-in server).
     */
    private function respondWithPdfBlob(string $pdfContent, string $fileName)
    {
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

    private function validatePersonal(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'dni_nie' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('personal', 'dni_nie')->ignore($ignoreId),
            ],
            'departamento'   => 'nullable|array',
            'departamento.*' => 'string|max:255',
            'puesto' => 'nullable|string|max:255',
            'puesto_trabajo_id' => 'nullable|exists:puestos_trabajo,id',
            'tipo_personal' => 'nullable|in:indefinido,temporal',
            'camiseta' => 'nullable|string|max:20',
            'chaqueta' => 'nullable|string|max:20',
            'sudadera' => 'nullable|string|max:20',
            'pantalon' => 'nullable|string|max:20',
            'calzado' => 'nullable|string|max:20',
            'casco' => 'nullable|string|max:20',
            'gafas' => 'nullable|string|max:20',
            'guantes' => 'nullable|string|max:20',
            'bolsa_fod' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'descripcion' => 'nullable|string|max:500',
            'ultima_revision_medica' => 'nullable|date',
            'proxima_revision_medica' => 'nullable|date',
            'ultima_graduacion' => 'nullable|date',
            'proxima_graduacion' => 'nullable|date',
            'reconocido_en' => 'nullable|string|max:255',
            'graduado_en' => 'nullable|string|max:255',
            'proyecto_ids' => 'sometimes|required|array|min:1',
            'proyecto_ids.*' => 'exists:proyectos,id',
            'sin_tallas' => 'boolean',
            'activo' => 'boolean',
            'id_rrhh' => [
                'nullable',
                'string',
                'max:10',
                Rule::unique('personal', 'id_rrhh')->ignore($ignoreId),
            ],
        ];

        $messages = [
            'proyecto_ids.required' => 'El trabajador debe estar asignado al menos a una sede o proyecto.',
            'proyecto_ids.min' => 'El trabajador debe estar asignado al menos a una sede o proyecto.',
        ];

        return $request->validate($rules, $messages);
    }

    public function assignBulkCourses(Request $request)
    {
        $this->authorize('cursos.edit');

        $validated = $request->validate([
            'personal_ids' => 'required|array',
            'personal_ids.*' => 'exists:personal,id',
            'curso_id' => 'required|exists:cursos,id',
            'fecha_realizacion' => 'nullable|date',
            'apto' => 'required|boolean',
            'descripcion_aptitud' => 'nullable|string',
        ]);

        $user = auth()->user();
        if ($user && $user->role !== 'superadmin') {
            $misProyectosIds = $user->proyectos->pluck('id')->all();
            $cursoPertenece = Curso::where('id', $validated['curso_id'])
                ->whereHas('proyectos', fn ($q) => $q->whereIn('proyectos.id', $misProyectosIds))
                ->exists();
            abort_unless($cursoPertenece, 403, 'No tienes acceso a un curso de otra sede.');
        }

        try {
            $pivotData = [
                'fecha_realizacion' => $validated['fecha_realizacion'],
                'apto' => $validated['apto'],
                'descripcion_aptitud' => $validated['descripcion_aptitud'],
            ];

            $personals = Personal::whereIn('id', $validated['personal_ids'])
                ->when($user && $user->role !== 'superadmin', function ($q) use ($user) {
                    $q->whereHas('proyectos', fn ($pq) => $pq->whereIn('proyectos.id', $user->proyectos->pluck('id')));
                })
                ->get();

            foreach ($personals as $personal) {
                $personal->cursos()->syncWithoutDetaching([
                    $validated['curso_id'] => $pivotData,
                ]);
            }

            session()->flash('success', 'El curso ha sido asignado correctamente a ' . $personals->count() . ' trabajadores.');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al procesar la asignación masiva en el servidor: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportBulk(Request $request)
    {
        $this->authorize('personal.export');

        $validated = $request->validate([
            'personal_ids' => 'required|array',
            'personal_ids.*' => 'exists:personal,id',
        ]);

        $user = auth()->user();

        $rows = Personal::with('proyectos')
            ->whereIn('id', $validated['personal_ids'])
            ->when($user && $user->role !== 'superadmin', function ($q) use ($user) {
                $q->whereHas('proyectos', fn ($pq) => $pq->whereIn('proyectos.id', $user->proyectos->pluck('id')));
            })
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['ID RRHH', 'Nombre', 'Apellido', 'DNI/NIE', 'Telefono', 'Departamento', 'Puesto', 'Estado', 'Proyectos'], ';');

            foreach ($rows as $row) {
                $deptos = is_string($row->departamento) ? json_decode($row->departamento, true) ?? explode(',', $row->departamento) : (array) $row->departamento;
                $deptosStr = !empty($deptos) ? strtoupper(implode(', ', $deptos)) : 'SIN DEPARTAMENTO';

                fputcsv($handle, [
                    $row->id_rrhh ?: '—',
                    $row->name,
                    $row->apellido,
                    $row->dni_nie,
                    $row->telefono,
                    $deptosStr,
                    $row->puesto ?: '—',
                    $row->activo ? 'Activo' : 'Inactivo',
                    $row->proyectos->pluck('nombre')->join(' | '),
                ], ';');
            }

            fclose($handle);
        }, 'seleccion_personal.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function updateBulkDepartamento(Request $request)
    {
        $this->authorize('personal.bulk');

        $validated = $request->validate([
            'personal_ids' => 'required|array',
            'personal_ids.*' => 'exists:personal,id',
            'departamento' => 'nullable|array',
            'departamento.*' => 'string|max:255',
        ]);

        $user = auth()->user();

        try {
            $personals = Personal::whereIn('id', $validated['personal_ids'])
                ->when($user && $user->role !== 'superadmin', function ($q) use ($user) {
                    $q->whereHas('proyectos', fn ($pq) => $pq->whereIn('proyectos.id', $user->proyectos->pluck('id')));
                })
                ->get();

            foreach ($personals as $personal) {
                $personal->departamento = $validated['departamento'] ?? null;
                $personal->save();
            }

            session()->flash('success', 'Departamento actualizado correctamente a ' . $personals->count() . ' trabajadores.');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al actualizar los departamentos: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function aplicarMacroDeCursos(Personal $personal)
    {
        $puestosActivos = $personal->puestos()->where('puestos.activo', true)->with('cursos')->get();

        $cursosObligatoriosIds = [];
        foreach ($puestosActivos as $puesto) {
            foreach ($puesto->cursos as $curso) {
                $cursosObligatoriosIds[] = $curso->id;
            }
        }

        $cursosObligatoriosIds = array_unique($cursosObligatoriosIds);
        $cursosActualesIds = $personal->cursos()->pluck('cursos.id')->toArray();
        $cursosNuevos = array_diff($cursosObligatoriosIds, $cursosActualesIds);

        if (!empty($cursosNuevos)) {
            $syncData = [];
            foreach ($cursosNuevos as $cursoId) {
                $syncData[$cursoId] = [
                    'apto' => false,
                    'fecha_realizacion' => null,
                    'descripcion_aptitud' => 'Asignado automáticamente por matriz formativa.',
                ];
            }
            $personal->cursos()->attach($syncData);
        }
    }

    public function marcarRevisadoPrl(Personal $personal)
    {
        $this->authorize('cursos.edit');
        $this->authorizePersonalProyecto($personal);

        $personal->update([
            'prl_revisado' => true,
        ]);

        return redirect()->back()->with('success', 'Revisión confirmada. El trabajador ya no aparecerá como pendiente.');
    }

    public function puestos()
    {
        $this->authorize('personal.edit');

        $proyectoId = $this->resolveProyectoForCorrelativo(request());
        $puestos = Puesto::where('proyecto_id', $proyectoId)
            ->orderBy('nombre')
            ->get();

        return view('personal.puestos', compact('puestos'));
    }

    private function authorizePersonalProyecto(Personal $personal): void
    {
        $user = auth()->user();
        if ($user?->role === 'superadmin') {
            return;
        }

        $misProyectos = $user?->proyectos->pluck('id')->all() ?? [];
        $personalProyectos = $personal->proyectos()->pluck('proyectos.id')->all();
        if ($misProyectos === [] || $personalProyectos === [] || empty(array_intersect($misProyectos, $personalProyectos))) {
            abort(403);
        }
    }
}