<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Personal;
use App\Models\SalidaStock;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Departamento;

class PersonalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // --- GUARDIA DE LA MURALLA (Interruptor Maestro) ---
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

        $buildQuery = function () use ($query, $alertaNombre, $alertaLimite, $user) {
            $personalQuery = Personal::query()->with([
                'proyectos',
                'cursos' => fn ($query) => $query->orderBy('nombre'),
            ]);

            // --- FILTRO GEOGRÁFICO / PROYECTOS (MURO INVISIBLE) ---
            if ($user && $user->role !== 'superadmin') {
                $misProyectosIds = $user->proyectos->pluck('id')->toArray();
                
                $personalQuery->whereHas('proyectos', function ($q) use ($misProyectosIds) {
                    $q->whereIn('proyectos.id', $misProyectosIds);
                });
            }

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
            // Requerimos permiso de bulk para exportar
            $this->authorize('personal.export');
            
            $rows = $buildQuery()->get();

            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($handle, ['ID', 'Nombre', 'Apellido', 'DNI/NIE', 'Telefono', 'Departamento', 'Estado', 'Proyectos']);

                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        $row->apellido,
                        $row->dni_nie,
                        $row->telefono,
                        $row->departamento,
                        $row->activo ? 'Activo' : 'Inactivo',
                        $row->sin_tallas ? 'Sí' : 'No',
                        $row->proyectos->pluck('nombre')->join(' | '),
                    ]);
                }

                fclose($handle);
            }, 'personal.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $personals = $buildQuery()->paginate(12)->withQueryString();
        $personals->getCollection()->transform(function (Personal $personal) use ($alertaLimite) {
            $personal->alerta_revision_medica = $alertaLimite
                ? ($personal->proxima_revision_medica && $personal->proxima_revision_medica->lte($alertaLimite))
                : false;

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
        
        $personalTotalQuery = Personal::query();
        $personalActivosQuery = Personal::where('activo', true);
        $avisosCountQuery = Personal::whereNotNull('proxima_revision_medica');

        if ($user && $user->role !== 'superadmin') {
            $misProyectosIds = $user->proyectos->pluck('id')->toArray();
            
            $filtroProyectos = function ($q) use ($misProyectosIds) {
                $q->whereIn('proyectos.id', $misProyectosIds);
            };

            $personalTotalQuery->whereHas('proyectos', $filtroProyectos);
            $personalActivosQuery->whereHas('proyectos', $filtroProyectos);
            $avisosCountQuery->whereHas('proyectos', $filtroProyectos);
        }

        $personalTotal = $personalTotalQuery->count();
        $personalActivos = $personalActivosQuery->count();

        if ($alertaLimite) {
            $avisosCountQuery->where('proxima_revision_medica', '<=', $alertaLimite);
            $avisosCount = $avisosCountQuery->count();
        } else {
            $avisosCount = 0;
        }

        $cursosCatalogo = Curso::all();
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
            'cursosCatalogo' => $cursosCatalogo,
            'departamentos' => $departamentos,
        ]);
    }

    public function create()
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('personal.create');

        $personal = new Personal(['activo' => true]);
        $departamentos = Departamento::orderBy('nombre')->get();

        return view('personal.edit', [
            'personal' => $personal,
            'isCreate' => true,
            'proyectos' => Proyecto::orderBy('nombre')->get(),
            'departamentos' => $departamentos, 
        ]);
    }

    public function store(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('personal.create');

        $validated = $this->validatePersonal($request);

        // --- FILTRO SANITIZADOR DE DATOS SENSIBLES ---
        if (!auth()->user()->can('personal.medico')) {
            unset($validated['ultima_revision_medica'], $validated['proxima_revision_medica'], $validated['ultima_graduacion'], $validated['proxima_graduacion'], $validated['reconocido_en'], $validated['graduado_en']);
        }
        if (!auth()->user()->can('personal.tallas')) {
            unset($validated['camiseta'], $validated['chaqueta'], $validated['sudadera'], $validated['pantalon'], $validated['calzado'], $validated['casco'], $validated['gafas'], $validated['guantes'], $validated['sin_tallas']);
        }

        $proyectoIds = $validated['proyecto_ids'] ?? [];

        // --- AUTO-ASIGNACIÓN DE SEDE ---
        $user = auth()->user();
        if ($user && $user->role !== 'superadmin') {
            $proyectoIds = $user->proyectos->pluck('id')->toArray();
        }

        if (isset($validated['departamento']) && is_array($validated['departamento'])) {
            $validated['departamento'] = implode(',', $validated['departamento']);
        }

        $validated['activo'] = $request->boolean('activo', true);
        
        // Asignar sin_tallas respetando el filtro
        if (isset($validated['sin_tallas'])) {
            $validated['sin_tallas'] = $request->boolean('sin_tallas', false);
        }

        $personal = Personal::create($validated);
        
        if (!empty($proyectoIds)) {
            $personal->proyectos()->sync($proyectoIds);
        }

        $this->aplicarMacroDeCursos($personal);

        return redirect()->route('personal.index')->with('success', 'Trabajador creado y asignado a tu sede correctamente.');
    }

    public function show(Personal $personal, Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('personal.acciones');
        
        $personal->load([
            'proyectos',
            'cursos' => fn ($query) => $query->orderBy('nombre'),
        ]);

        $cursosCatalogo = Curso::orderBy('nombre')->get();
        $nombreCompleto = trim(preg_replace('/\s+/', ' ', trim((string) $personal->name . ' ' . (string) $personal->apellido)));
        $nombreNormalizado = mb_strtolower($nombreCompleto);

        $salidasQuery = SalidaStock::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        $salidas = $salidasQuery
            ->get()
            ->filter(function (SalidaStock $salida) use ($nombreNormalizado) {
                $solicitante = trim((string) ($salida->solicitante ?? ''));

                if ($solicitante === '') {
                    return false;
                }

                return mb_strtolower(preg_replace('/\s+/', ' ', $solicitante)) === $nombreNormalizado;
            })
            ->filter(function (SalidaStock $salida) use ($request) {
                $fechaDesde = $request->input('fecha_desde');
                $fechaHasta = $request->input('fecha_hasta');

                if ($fechaDesde && $salida->fecha < \Carbon\Carbon::parse($fechaDesde)->startOfDay()) {
                    return false;
                }

                if ($fechaHasta && $salida->fecha > \Carbon\Carbon::parse($fechaHasta)->endOfDay()) {
                    return false;
                }

                $articuloBuscar = trim((string) $request->input('articulo'));
                if ($articuloBuscar !== '') {
                    $articulos = collect((array) $salida->items)
                        ->map(fn ($item) => trim((string) ($item['descripcion'] ?? $item['articulo'] ?? $item['nombre'] ?? '')))
                        ->filter()
                        ->implode(' ');

                    if (stripos($articulos, $articuloBuscar) === false) {
                        return false;
                    }
                }

                return true;
            })
            ->take(8)
            ->values();

        $historicoSalidas = $salidas->flatMap(function (SalidaStock $salida) {
            $items = collect((array) $salida->items)
                ->filter(fn ($item) => is_array($item))
                ->map(function (array $item) {
                    $descripcion = trim((string) ($item['descripcion'] ?? $item['articulo'] ?? $item['nombre'] ?? ''));
                    $cantidad = (float) ($item['cantidad'] ?? 0);

                    return [
                        'descripcion' => $descripcion,
                        'cantidad' => $cantidad,
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
                    'articulo' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'ot' => $salida->ot ?: '—',
                    'estado' => ucfirst($estadoNormalizado === 'aceptado' ? 'entregado' : $estadoNormalizado),
                    'estado_clase' => $estadoNormalizado === 'aceptado' || $estadoNormalizado === 'entregado'
                        ? 'profile-chip profile-chip--ok'
                        : 'profile-chip profile-chip--pending',
                ];
            });
        })->take(8)->values()->all();

        $tallas = [
            ['label' => 'Camiseta', 'value' => $personal->camiseta ?: '—', 'icon' => 'fa-tshirt'],
            ['label' => 'Sudadera', 'value' => $personal->sudadera ?: '—', 'icon' => 'fa-tshirt'],
            ['label' => 'Chaquetón', 'value' => $personal->chaqueta ?: '—', 'icon' => 'fa-vest'],
            ['label' => 'Pantalón', 'value' => $personal->pantalon ?: '—', 'icon' => 'fa-bridge'],
            ['label' => 'Calzado', 'value' => $personal->calzado ?: '—', 'icon' => 'fa-shoe-prints'],
            ['label' => 'Casco', 'value' => $personal->casco ?: '—', 'icon' => 'fa-hard-hat'],
            ['label' => 'Guantes', 'value' => $personal->guantes ?: '—', 'icon' => 'fa-hand-paper'],
            ['label' => 'Gafas', 'value' => $personal->gafas ?: '—', 'icon' => 'fa-glasses'],
        ];

        return view('personal.show', compact('personal', 'historicoSalidas', 'tallas', 'cursosCatalogo'));
    }

    public function edit(Personal $personal)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('personal.edit');

        $departamentos = Departamento::orderBy('nombre')->get();

        return view('personal.edit', [
            'personal' => $personal,
            'isCreate' => false,
            'proyectos' => Proyecto::orderBy('nombre')->get(),
            'departamentos' => $departamentos,
        ]);
    }

    public function update(Request $request, Personal $personal)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('personal.edit');

        $validated = $this->validatePersonal($request, $personal->id);

        // --- FILTRO SANITIZADOR DE DATOS SENSIBLES ---
        if (!auth()->user()->can('personal.medico')) {
            unset($validated['ultima_revision_medica'], $validated['proxima_revision_medica'], $validated['ultima_graduacion'], $validated['proxima_graduacion'], $validated['reconocido_en'], $validated['graduado_en']);
        }
        if (!auth()->user()->can('personal.tallas')) {
            unset($validated['camiseta'], $validated['chaqueta'], $validated['sudadera'], $validated['pantalon'], $validated['calzado'], $validated['casco'], $validated['gafas'], $validated['guantes'], $validated['sin_tallas']);
        }

        $proyectoIds = $validated['proyecto_ids'] ?? null;
        unset($validated['proyecto_ids']);

        if (isset($validated['departamento']) && is_array($validated['departamento'])) {
            $validated['departamento'] = implode(',', $validated['departamento']);
        }

        $validated['activo'] = $request->boolean('activo', true);
        
        // Asignar sin_tallas respetando el filtro
        if (isset($validated['sin_tallas'])) {
            $validated['sin_tallas'] = $request->boolean('sin_tallas', false);
        }

        $personal->update($validated);

        if (is_array($proyectoIds)) {
            $personal->proyectos()->sync($proyectoIds);
        }

        $this->aplicarMacroDeCursos($personal);

        return redirect()->route('personal.index')->with('success', 'Trabajador actualizado correctamente.');
    }

    public function destroy(Personal $personal)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('personal.delete');

        $personal->delete();

        return redirect()->route('personal.index')->with('success', 'Trabajador eliminado correctamente.');
    }

    public function toggleStatus(Personal $personal)
    {
        $this->authorize('personal.edit'); // Requiere permiso de edición
        
        $personal->activo = !$personal->activo; // Invierte el estado (de true a false, o viceversa)
        $personal->save();
        
        $mensaje = $personal->activo ? 'Trabajador dado de ALTA correctamente.' : 'Trabajador dado de BAJA correctamente.';
        return redirect()->back()->with('success', $mensaje);
    }
    
    public function tallas(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('personal.tallas');

        $query = (string) $request->input('q', '');
        $estado = (string) $request->input('estado', 'todos');
        $departamentoFiltro = (string) $request->input('departamento', 'todos'); 
        $export = $request->input('export'); 

        $columns = ['camiseta', 'chaqueta', 'sudadera', 'pantalon', 'calzado', 'guantes', 'casco', 'gafas'];
        $departamentosCatalogo = Departamento::orderBy('nombre')->get();
        
        $user = auth()->user();

        $personals = Personal::query()
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
                $q->whereNull('departamento')
                ->orWhere('departamento', '')
                ->orWhere('departamento', '[]');
            })
            ->when($estado === 'sin_oficina', function ($q) {
                $q->where('sin_tallas', false);
            })
            ->when($departamentoFiltro !== 'todos', function ($q) use ($departamentoFiltro) {
                $q->where('departamento', 'like', "%{$departamentoFiltro}%");
            })
            ->orderBy('name')
            ->get();

        if ($export === 'csv') {
            // Protección adicional para exportación
            $this->authorize('personal.export');

            return response()->streamDownload(function () use ($personals, $columns) {
                $handle = fopen('php://output', 'w');
                
                fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                
                $headers = ['ID RRHH', 'Nombre', 'Apellido', 'Departamento'];
                foreach ($columns as $col) {
                    $headers[] = ucfirst($col);
                }
                fputcsv($handle, $headers, ';'); 

                foreach ($personals as $p) {
                    $deptos = is_string($p->departamento) ? json_decode($p->departamento, true) ?? explode(',', $p->departamento) : (array) $p->departamento;
                    $deptosStr = !empty($deptos) ? strtoupper(implode(', ', $deptos)) : 'SIN DEPARTAMENTO';

                    $row = [
                        $p->id_rrhh ?: '—',
                        $p->name,
                        $p->apellido,
                        $deptosStr
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
            }, 'listado_tallas_epis.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('personal.tallas', [
            'personals' => $personals,
            'query' => $query,
            'estado' => $estado,
            'departamentoFiltro' => $departamentoFiltro,
            'departamentosCatalogo' => $departamentosCatalogo,
            'columns' => $columns,
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
            'tipo_personal' => 'nullable|in:indefinido,temporal',
            'camiseta' => 'nullable|string|max:20',
            'chaqueta' => 'nullable|string|max:20',
            'sudadera' => 'nullable|string|max:20',
            'pantalon' => 'nullable|string|max:20',
            'calzado' => 'nullable|string|max:20',
            'casco' => 'nullable|string|max:20',
            'gafas' => 'nullable|string|max:20',
            'guantes' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'descripcion' => 'nullable|string|max:500',
            'ultima_revision_medica' => 'nullable|date',
            'proxima_revision_medica' => 'nullable|date',
            'ultima_graduacion' => 'nullable|date',
            'proxima_graduacion' => 'nullable|date',
            'reconocido_en' => 'nullable|string|max:255',
            'graduado_en' => 'nullable|string|max:255',
            'proyecto_ids' => 'nullable|array',
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

        return $request->validate($rules);
    }

    /**
     * Asignar un curso masivamente a varios trabajadores
     */
    public function assignBulkCourses(\Illuminate\Http\Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        // Exigimos permiso de edición de cursos para inyectar formación
        $this->authorize('cursos.edit');

        $validated = $request->validate([
            'personal_ids' => 'required|array',
            'personal_ids.*' => 'exists:personal,id',
            'curso_id' => 'required|exists:cursos,id',
            'fecha_realizacion' => 'nullable|date',
            'apto' => 'required|boolean',
            'descripcion_aptitud' => 'nullable|string',
        ]);

        try {
            $pivotData = [
                'fecha_realizacion' => $validated['fecha_realizacion'],
                'apto' => $validated['apto'],
                'descripcion_aptitud' => $validated['descripcion_aptitud'],
            ];

            $personals = \App\Models\Personal::whereIn('id', $validated['personal_ids'])->get();

            foreach ($personals as $personal) {
                $personal->cursos()->syncWithoutDetaching([
                    $validated['curso_id'] => $pivotData
                ]);
            }

            session()->flash('success', 'El curso ha sido asignado correctamente a ' . count($validated['personal_ids']) . ' trabajadores.');

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Hubo un error al procesar la asignación masiva en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar a CSV los trabajadores seleccionados
     */
    public function exportBulk(\Illuminate\Http\Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('personal.export');

        $validated = $request->validate([
            'personal_ids' => 'required|array',
            'personal_ids.*' => 'exists:personal,id',
        ]);

        $rows = \App\Models\Personal::with('proyectos')
                    ->whereIn('id', $validated['personal_ids'])
                    ->orderBy('name')
                    ->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            fputcsv($handle, ['ID RRHH', 'Nombre', 'Apellido', 'DNI/NIE', 'Telefono', 'Departamento', 'Estado', 'Proyectos'], ';');

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
                ], ';');
            }

            fclose($handle);
        }, 'seleccion_personal.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Actualizar departamento masivamente
     */
    public function updateBulkDepartamento(\Illuminate\Http\Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('personal.bulk');

        $validated = $request->validate([
            'personal_ids' => 'required|array',
            'personal_ids.*' => 'exists:personal,id',
            'departamento' => 'nullable|array',
            'departamento.*' => 'string|max:255',
        ]);

        try {
            $personals = \App\Models\Personal::whereIn('id', $validated['personal_ids'])->get();

            foreach ($personals as $personal) {
                $personal->departamento = $validated['departamento'] ?? null;
                $personal->save();
            }

            session()->flash('success', 'Departamento actualizado correctamente a ' . count($validated['personal_ids']) . ' trabajadores.');

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Hubo un error al actualizar los departamentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Motor de automatización: Inyecta los cursos requeridos según el departamento (Puesto) del trabajador.
     * Engancha la tabla `departamentos` (o el nombre) con la matriz de formación `puestos`.
     */
    private function aplicarMacroDeCursos(Personal $personal)
    {
        $deptosActuales = is_string($personal->departamento) 
            ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento) 
            : (array) $personal->departamento;
            
        if (empty($deptosActuales)) {
            return; 
        }

        $nombresNormalizados = array_map(function($d) { return mb_strtoupper(trim($d)); }, $deptosActuales);
        
        $puestos = \App\Models\Puesto::with('cursos')
            ->whereIn('nombre', $nombresNormalizados)
            ->where('activo', true)
            ->get();

        $cursosObligatoriosIds = [];

        foreach ($puestos as $puesto) {
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
                    'descripcion_aptitud' => 'Asignado automáticamente por matriz formativa.'
                ];
            }
            $personal->cursos()->attach($syncData);
        }
    }
}