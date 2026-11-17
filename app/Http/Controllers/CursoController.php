<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Curso;
use App\Models\Puesto;
use App\Models\Proyecto;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CursoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Devuelve los IDs de proyectos asignados al usuario autenticado,
     * o null si es superadmin (acceso global).
     */
    private function getMisProyectosIds(): ?array
    {
        $user = auth()->user();
        if (!$user || $user->role === 'superadmin') {
            return null;
        }

        return $user->proyectos->pluck('id')->toArray();
    }

    /**
     * Comprueba que el curso pertenezca a alguno de los proyectos del usuario.
     */
    private function authorizeCursoProyecto(Curso $curso): void
    {
        $misProyectosIds = $this->getMisProyectosIds();
        if ($misProyectosIds === null) {
            return;
        }

        $pertenece = $curso->proyectos()->whereIn('proyectos.id', $misProyectosIds)->exists();
        abort_unless($pertenece, 403, 'No tienes acceso a un curso de otra sede.');
    }

    /**
     * Comprueba que el trabajador pertenezca a alguno de los proyectos del usuario.
     */
    private function authorizePersonalProyecto(Personal $personal): void
    {
        $misProyectosIds = $this->getMisProyectosIds();
        if ($misProyectosIds === null) {
            return;
        }

        $pertenece = $personal->proyectos()->whereIn('proyectos.id', $misProyectosIds)->exists();
        abort_unless($pertenece, 403, 'No tienes acceso a trabajadores de otra sede.');
    }

    public function index(): View
    {
        abort_unless(auth()->user()?->canAny(['cursos.view', 'cursos.edit', 'cursos.create']), 403);

        $query = (string) request()->input('q', '');
        $categoria = (string) request()->input('categoria', 'all');
        $personalId = (int) request()->input('personal_id', 0);

        $categoryNames = $this->getCategoriasActivas();

        if ($categoria !== 'all' && !in_array($categoria, $categoryNames, true)) {
            $categoria = 'all';
        }

        $proyectoActivoId = $this->resolveProyectoForCorrelativo(request());

        $personalsQuery = Personal::query()
            ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoActivoId))
            ->with([
                'puestoTrabajo',
                'puestos' => fn ($q) => $q->where('puestos.proyecto_id', $proyectoActivoId),
                'cursos' => function ($cursoQuery) use ($proyectoActivoId) {
                    $cursoQuery->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoActivoId))
                        ->orderBy('categoria')
                        ->orderBy('nombre');
                },
            ]);

        $personals = (clone $personalsQuery)
            ->when($query !== '', function ($personalQuery) use ($query) {
                $personalQuery->where(function ($subQuery) use ($query) {
                    $subQuery->where('name', 'like', "%{$query}%")
                        ->orWhere('apellido', 'like', "%{$query}%")
                        ->orWhere('dni_nie', 'like', "%{$query}%")
                        ->orWhere('telefono', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->get();

        $selectedPersonal = $personalId > 0
            ? (clone $personalsQuery)->where('id', $personalId)->first()
            : null;

        $cursosQuery = Curso::query()
            ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoActivoId))
            ->withCount([
                'personal' => fn ($q) => $q->whereHas('proyectos', fn ($pq) => $pq->where('proyectos.id', $proyectoActivoId)),
            ])
            ->orderBy('categoria')
            ->orderBy('nombre');

        $cursos = $cursosQuery->get();
        $categorias = $categoryNames;

        $cursosPorCategoria = collect($categoryNames)
            ->mapWithKeys(function (string $category) use ($cursos, $categoria) {
                if ($categoria !== 'all' && $categoria !== $category) {
                    return [$category => collect()];
                }

                return [
                    $category => $cursos->where('categoria', $category)->values(),
                ];
            })
            ->filter(fn ($items) => $items->isNotEmpty());

        $uncategorized = $cursos->whereNull('categoria')->values();
        if ($uncategorized->isNotEmpty() && $categoria === 'all') {
            $cursosPorCategoria->put('Sin categoría', $uncategorized);
        }

        $todosLosPuestos = Puesto::where('proyecto_id', $proyectoActivoId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('cursos.index', compact(
            'personals',
            'selectedPersonal',
            'cursos',
            'categorias',
            'cursosPorCategoria',
            'categoria',
            'query',
            'todosLosPuestos'
        ));
    }

    public function gestion(): View
    {
        $this->authorize('cursos.edit');

        $proyectoActivoId = $this->resolveProyectoForCorrelativo(request());

        $cursos = Curso::query()
            ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoActivoId))
            ->withCount([
                'personal' => fn ($q) => $q->whereHas('proyectos', fn ($pq) => $pq->where('proyectos.id', $proyectoActivoId)),
            ])
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get();

        return view('cursos.gestion', compact('cursos'));
    }

    public function create()
    {
        $this->authorize('cursos.create');

        return view('cursos.create', [
            'curso' => new Curso(),
            'categorias' => $this->getCategoriasActivas(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('cursos.create');

        $proyectoId = $this->resolveActiveProyectoId($request);
        $validated = $this->validateCurso($request);

        $curso = Curso::create($validated);
        $curso->proyectos()->sync([$proyectoId]);

        return redirect()
            ->route('cursos.index')
            ->with('success', 'Curso creado y vinculado a tu sede correctamente.');
    }

    public function edit(Curso $curso)
    {
        $this->authorize('cursos.edit');
        $this->authorizeCursoProyecto($curso);

        return view('cursos.edit', [
            'curso' => $curso,
            'categorias' => $this->getCategoriasActivas(),
        ]);
    }

    public function show(Curso $curso): View
    {
        abort_unless(auth()->user()?->canAny(['cursos.view', 'cursos.edit']), 403);
        $this->authorizeCursoProyecto($curso);

        $misProyectosIds = $this->getMisProyectosIds();

        $curso->load([
            'personal' => function ($query) use ($misProyectosIds) {
                if ($misProyectosIds !== null) {
                    $query->whereHas('proyectos', fn ($proyectoQuery) => $proyectoQuery->whereIn('proyectos.id', $misProyectosIds));
                }
                $query->with('puestoTrabajo')->orderBy('name')->orderBy('apellido');
            },
        ]);

        return view('cursos.show', [
            'curso' => $curso,
        ]);
    }

    public function update(Request $request, Curso $curso)
    {
        $this->authorize('cursos.edit');
        $this->authorizeCursoProyecto($curso);

        $validated = $this->validateCurso($request, $curso->id);

        $curso->update($validated);

        return redirect()
            ->route('cursos.gestion')
            ->with('success', 'Curso actualizado correctamente.');
    }

    public function destroy(Curso $curso)
    {
        $this->authorize('cursos.delete');
        $this->authorizeCursoProyecto($curso);

        if ($curso->personal()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar el curso porque ya tiene personal asociado.');
        }

        $curso->delete();

        return redirect()
            ->route('cursos.gestion')
            ->with('success', 'Curso eliminado correctamente.');
    }

    public function alertasCaducidad(): View
    {
        $this->authorize('cursos.alertas');

        $caducidadSql = 'DATE_ADD(curso_personal.fecha_realizacion, INTERVAL cursos.meses_validez MONTH)';
        $avisoSql = "CURDATE() >= DATE_SUB($caducidadSql, INTERVAL COALESCE(cursos.dias_aviso_previo, 30) DAY)";
        $caducadoSql = "CURDATE() > $caducidadSql";
        $noAptoSql = "(curso_personal.apto = 0 AND curso_personal.fecha_realizacion IS NOT NULL)";

        $misProyectosIds = $this->getMisProyectosIds();

        $query = DB::table('curso_personal')
            ->join('personal', 'personal.id', '=', 'curso_personal.personal_id')
            ->join('cursos', 'cursos.id', '=', 'curso_personal.curso_id')
            ->where('personal.activo', true)
            ->whereNotNull('curso_personal.fecha_realizacion')
            ->where(function ($q) use ($avisoSql, $caducadoSql, $noAptoSql) {
                $q->whereRaw($noAptoSql)
                    ->orWhere(function ($sub) use ($avisoSql, $caducadoSql) {
                        $sub->where('curso_personal.apto', true)
                            ->whereNotNull('cursos.meses_validez')
                            ->where(function ($inner) use ($avisoSql, $caducadoSql) {
                                $inner->whereRaw($avisoSql)
                                    ->orWhereRaw($caducadoSql);
                            });
                    });
            });

        if ($misProyectosIds !== null) {
            $query->whereExists(function ($subQuery) use ($misProyectosIds) {
                $subQuery->select(DB::raw(1))
                    ->from('personal_proyecto')
                    ->whereColumn('personal_proyecto.personal_id', 'personal.id')
                    ->whereIn('personal_proyecto.proyecto_id', $misProyectosIds);
            });
        }

        $alertas = $query->selectRaw(
                "personal.id as personal_id,
                personal.name as personal_name,
                personal.apellido as personal_apellido,
                personal.dni_nie as personal_dni_nie,
                cursos.id as curso_id,
                cursos.nombre as curso_nombre,
                curso_personal.fecha_realizacion,
                CASE WHEN cursos.meses_validez IS NOT NULL THEN $caducidadSql ELSE NULL END as fecha_caducidad,
                CASE
                    WHEN $noAptoSql THEN 'No Apto'
                    WHEN $caducadoSql THEN 'Caducado'
                    ELSE 'En Aviso'
                END as estado"
            )
            ->orderByRaw("CASE WHEN $noAptoSql THEN 0 WHEN $caducadoSql THEN 1 ELSE 2 END")
            ->orderBy('fecha_caducidad')
            ->get();

        return view('cursos.alertas', [
            'alertas' => $alertas,
        ]);
    }

    private function validateCurso(Request $request, ?int $ignoreId = null): array
    {
        $misProyectosIds = $this->getMisProyectosIds();

        return $request->validate([
            'categoria' => ['required', 'string', 'max:255'],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cursos', 'nombre')
                    ->where(function ($query) use ($misProyectosIds) {
                        if ($misProyectosIds !== null) {
                            $query->whereIn('id', function ($sub) use ($misProyectosIds) {
                                $sub->select('curso_id')
                                    ->from('curso_proyecto')
                                    ->whereIn('proyecto_id', $misProyectosIds);
                            });
                        }
                    })
                    ->ignore($ignoreId),
            ],
            'descripcion' => 'nullable|string|max:2000',
            'meses_validez' => 'nullable|integer|min:1|max:120',
            'dias_aviso_previo' => 'nullable|integer|min:1|max:365',
        ]);
    }

    private function getCategoriasActivas(): array
    {
        $configCategories = array_keys(config('cursos.categories', []));
        $proyectoActivoId = $this->resolveProyectoForCorrelativo(request());

        $dbCategories = Curso::select('categoria')
            ->distinct()
            ->whereNotNull('categoria')
            ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoActivoId))
            ->pluck('categoria')
            ->toArray();

        $todas = array_unique(array_merge($configCategories, $dbCategories));
        sort($todas);

        return $todas;
    }

    public function export($id)
    {
        abort_unless(auth()->user()?->canAny(['cursos.export', 'cursos.view', 'cursos.edit']), 403);

        $misProyectosIds = $this->getMisProyectosIds();

        $curso = Curso::with([
            'personal' => function ($query) use ($misProyectosIds) {
                if ($misProyectosIds !== null) {
                    $query->whereHas('proyectos', fn ($proyectoQuery) => $proyectoQuery->whereIn('proyectos.id', $misProyectosIds));
                }
                $query->with('puestoTrabajo')
                    ->withPivot('fecha_realizacion', 'apto')
                    ->orderBy('name')
                    ->orderBy('apellido');
            },
        ])->findOrFail($id);

        $this->authorizeCursoProyecto($curso);

        $fileName = 'asistentes_curso_' . $curso->id . '_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function () use ($curso) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['ID RRHH', 'Trabajador', 'Departamento', 'Puesto', 'DNI / NIE', 'Teléfono', 'Fecha Realización', 'Caducidad', 'Estado'], ';');

            $hoy = \Carbon\Carbon::now()->startOfDay();

            foreach ($curso->personal as $trabajador) {
                $nombreCompleto = trim($trabajador->name . ' ' . $trabajador->apellido);
                $idRrhh = $trabajador->id_rrhh ?: '—';

                $deptos = is_string($trabajador->departamento)
                    ? json_decode($trabajador->departamento, true) ?? explode(',', $trabajador->departamento)
                    : (array) $trabajador->departamento;
                $departamentoStr = !empty($deptos) ? strtoupper(implode(', ', $deptos)) : 'SIN DEPARTAMENTO';

                $nombrePuesto = optional($trabajador->puestoTrabajo)->nombre ?: ($trabajador->puesto ?: '—');

                $fechaRealizacionRaw = $trabajador->pivot->fecha_realizacion;
                $esApto = (bool) $trabajador->pivot->apto;

                $fechaRealizacionFormat = '—';
                $caducidadFormat = '—';
                $estado = 'Vigente';

                if ($fechaRealizacionRaw) {
                    $fechaRealizacion = \Carbon\Carbon::parse($fechaRealizacionRaw)->startOfDay();
                    $fechaRealizacionFormat = $fechaRealizacion->format('d/m/Y');
                }

                if (!$esApto) {
                    $estado = $fechaRealizacionRaw ? 'No Apto' : 'Pendiente de realizar';
                } elseif ($fechaRealizacionRaw) {
                    if ($curso->meses_validez) {
                        $fechaCaducidad = $fechaRealizacion->copy()->addMonths($curso->meses_validez);
                        $fechaAviso = $fechaCaducidad->copy()->subDays($curso->dias_aviso_previo ?? 30);
                        $caducidadFormat = $fechaCaducidad->format('d/m/Y');

                        if ($hoy->gt($fechaCaducidad)) {
                            $estado = 'Caducado';
                        } elseif ($hoy->gte($fechaAviso)) {
                            $estado = 'En Aviso';
                        }
                    } else {
                        $caducidadFormat = 'Sin caducidad';
                    }
                } else {
                    $estado = 'Pendiente de fecha';
                }

                fputcsv($file, [
                    $idRrhh,
                    $nombreCompleto ?: '—',
                    $departamentoStr,
                    $nombrePuesto,
                    $trabajador->dni_nie ?: '—',
                    $trabajador->telefono ?: '—',
                    $fechaRealizacionFormat,
                    $caducidadFormat,
                    $estado,
                ], ';');
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function exportPdf(Curso $curso)
    {
        abort_unless(auth()->user()?->canAny(['cursos.export', 'cursos.view', 'cursos.edit']), 403);
        $this->authorizeCursoProyecto($curso);

        $misProyectosIds = $this->getMisProyectosIds();

        $curso->load([
            'personal' => function ($query) use ($misProyectosIds) {
                if ($misProyectosIds !== null) {
                    $query->whereHas('proyectos', fn ($pq) => $pq->whereIn('proyectos.id', $misProyectosIds));
                }
                $query->with('puestoTrabajo')
                    ->withPivot('fecha_realizacion', 'apto')
                    ->orderBy('name')
                    ->orderBy('apellido');
            },
        ]);

        $fileName = 'asistentes_curso_' . \Illuminate\Support\Str::slug($curso->nombre) . '_' . date('Y-m-d') . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('cursos.pdf-asistentes', compact('curso'))
                ->setPaper('a4', 'landscape')
                ->download($fileName);
        }

        if (class_exists(\Dompdf\Dompdf::class)) {
            $html = view('cursos.pdf-asistentes', compact('curso'))->render();
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->loadHtml($html);
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
        }

        return view('cursos.pdf-asistentes', compact('curso'));
    }

    public function syncPuestos(Request $request, Personal $personal)
    {
        abort_unless(auth()->user()?->canAny(['cursos.edit', 'cursos.normas']), 403);
        $this->authorizePersonalProyecto($personal);

        $request->validate([
            'puestos' => 'nullable|array',
            'puestos.*' => 'exists:puestos,id',
        ]);

        if ($request->has('puestos')) {
            $personal->puestos()->sync($request->puestos);
        } else {
            $personal->puestos()->detach();
        }

        $this->aplicarMacroDeCursos($personal);

        return redirect()->back()->with('success', 'Perfiles formativos actualizados. Cursos requeridos inyectados correctamente.');
    }

    /**
     * Motor de automatización (Alineado con perfiles activos)
     */
    private function aplicarMacroDeCursos(Personal $personal): void
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
                    'descripcion_aptitud' => 'Asignado automáticamente por perfil formativo.',
                ];
            }
            $personal->cursos()->syncWithoutDetaching($syncData);
        }
    }

    public function addPuesto(Request $request, Personal $personal)
    {
        abort_unless(auth()->user()?->canAny(['cursos.edit', 'cursos.normas']), 403);
        $this->authorizePersonalProyecto($personal);

        $request->validate(['puesto_id' => 'required|exists:puestos,id']);

        $puesto = Puesto::findOrFail($request->puesto_id);
        $misProyectosIds = $this->getMisProyectosIds();
        if ($misProyectosIds !== null) {
            abort_unless(
                in_array((int) $puesto->proyecto_id, array_map('intval', $misProyectosIds), true),
                403,
                'No tienes acceso a un perfil formativo de otra sede.'
            );
        }

        $personal->puestos()->syncWithoutDetaching([$puesto->id]);

        $this->aplicarMacroDeCursos($personal);

        return response()->json([
            'success' => true,
            'message' => 'Perfil añadido y macro ejecutada.',
        ]);
    }

    public function removePuesto(Personal $personal, Puesto $puesto)
    {
        abort_unless(auth()->user()?->canAny(['cursos.edit', 'cursos.normas']), 403);
        $this->authorizePersonalProyecto($personal);

        $misProyectosIds = $this->getMisProyectosIds();
        if ($misProyectosIds !== null) {
            abort_unless(
                in_array((int) $puesto->proyecto_id, array_map('intval', $misProyectosIds), true),
                403,
                'No tienes acceso a un perfil formativo de otra sede.'
            );
        }

        $personal->puestos()->detach($puesto->id);

        return response()->json([
            'success' => true,
            'message' => 'Perfil eliminado correctamente.',
        ]);
    }

    private function prlSettingKey(string $baseKey, int $proyectoId): string
    {
        return "{$baseKey}_proyecto_{$proyectoId}";
    }

    public function configAlertas(Request $request)
    {
        $this->authorize('cursos.alertas');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        $proyecto = Proyecto::find($proyectoId);

        $emailKey = $this->prlSettingKey('alertas_prl_email', $proyectoId);
        $emailSetting = Setting::where('key', $emailKey)->first()
            ?? Setting::where('key', 'alertas_prl_email')->first();
        $emails = [];

        if ($emailSetting && $emailSetting->value) {
            $decoded = json_decode($emailSetting->value, true);
            $emails = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [$emailSetting->value];
        }

        $dia = Setting::where('key', $this->prlSettingKey('alertas_prl_dia', $proyectoId))->value('value')
            ?? Setting::where('key', 'alertas_prl_dia')->value('value')
            ?? '1';
        $hora = Setting::where('key', $this->prlSettingKey('alertas_prl_hora', $proyectoId))->value('value')
            ?? Setting::where('key', 'alertas_prl_hora')->value('value')
            ?? '08:00';

        $enviarCaducidades = Setting::where('key', $this->prlSettingKey('alertas_prl_enviar_caducidades', $proyectoId))->value('value');
        $enviarCaducidades = $enviarCaducidades === null ? true : (bool) $enviarCaducidades;

        $enviarPendientes = Setting::where('key', $this->prlSettingKey('alertas_prl_enviar_pendientes', $proyectoId))->value('value');
        $enviarPendientes = $enviarPendientes === null ? true : (bool) $enviarPendientes;

        return view('cursos.config_alertas', compact('emails', 'dia', 'hora', 'enviarCaducidades', 'enviarPendientes', 'proyecto'));
    }

    public function storeConfigAlertas(Request $request)
    {
        $this->authorize('cursos.alertas');

        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debes introducir un formato de correo válido.',
        ]);

        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        $emailKey = $this->prlSettingKey('alertas_prl_email', $proyectoId);

        $emailSetting = Setting::firstOrCreate(['key' => $emailKey], ['value' => '[]']);

        $decoded = json_decode($emailSetting->value, true);
        $emails = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : (empty($emailSetting->value) ? [] : [$emailSetting->value]);

        if (!in_array($request->email, $emails, true)) {
            $emails[] = $request->email;
            $emailSetting->update(['value' => json_encode($emails)]);
            return redirect()->route('cursos.config.alertas')->with('success', 'Correo añadido a la lista de destinatarios de este proyecto.');
        }

        return redirect()->route('cursos.config.alertas')->with('error', 'Ese correo ya está en la lista.');
    }

    public function destroyConfigAlertas(Request $request)
    {
        $this->authorize('cursos.alertas');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);
        $emailKey = $this->prlSettingKey('alertas_prl_email', $proyectoId);

        $emailToRemove = $request->input('email_to_remove');
        $emailSetting = Setting::where('key', $emailKey)->first();

        if ($emailSetting && $emailToRemove) {
            $emails = json_decode($emailSetting->value, true) ?? [];

            $emails = array_filter($emails, function ($e) use ($emailToRemove) {
                return $e !== $emailToRemove;
            });

            $emailSetting->update(['value' => json_encode(array_values($emails))]);
        }

        return redirect()->route('cursos.config.alertas')->with('success', 'Correo eliminado de la lista.');
    }

    public function storeHorarioAlertas(Request $request)
    {
        $this->authorize('cursos.alertas');

        $request->validate([
            'dia_envio' => 'required',
            'hora_envio' => 'required',
            'enviar_caducidades' => 'nullable|boolean',
            'enviar_pendientes' => 'nullable|boolean',
        ]);

        $proyectoId = $this->resolveProyectoForCorrelativo($request);

        Setting::updateOrCreate(
            ['key' => $this->prlSettingKey('alertas_prl_dia', $proyectoId)],
            ['value' => $request->dia_envio]
        );

        Setting::updateOrCreate(
            ['key' => $this->prlSettingKey('alertas_prl_hora', $proyectoId)],
            ['value' => $request->hora_envio]
        );

        Setting::updateOrCreate(
            ['key' => $this->prlSettingKey('alertas_prl_enviar_caducidades', $proyectoId)],
            ['value' => $request->boolean('enviar_caducidades') ? '1' : '0']
        );

        Setting::updateOrCreate(
            ['key' => $this->prlSettingKey('alertas_prl_enviar_pendientes', $proyectoId)],
            ['value' => $request->boolean('enviar_pendientes') ? '1' : '0']
        );

        return redirect()->route('cursos.config.alertas')
                         ->with('success', 'Configuración y frecuencia de envío actualizadas para este proyecto.');
    }

    public function enviarAlertaManual(Request $request)
    {
        $this->authorize('cursos.alertas');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);

        Artisan::call('cursos:notificar-caducidades', [
            '--proyecto' => $proyectoId,
        ]);

        return redirect()->route('cursos.config.alertas')
                         ->with('success', 'Orden de escaneo ejecutada para este proyecto. Si hay caducidades, el correo se ha enviado.');
    }

    public function enviarAlertaManualPendientes(Request $request)
    {
        $this->authorize('cursos.alertas');

        $proyectoId = $this->resolveProyectoForCorrelativo($request);

        Artisan::call('cursos:notificar-pendientes', [
            '--proyecto' => $proyectoId,
        ]);

        return redirect()->route('cursos.config.alertas')
                         ->with('success', 'Aviso manual procesado para este proyecto. Si hay trabajadores pendientes de revisión, el correo se ha enviado.');
    }
}