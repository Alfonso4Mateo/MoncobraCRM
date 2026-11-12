<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Curso;
use App\Models\Puesto;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;

class CursoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $this->authorize('cursos.view');

        $query = (string) request()->input('q', '');
        $categoria = (string) request()->input('categoria', 'all');
        $personalId = (int) request()->input('personal_id', 0);
        
        $categoryNames = $this->getCategoriasActivas(); 

        if ($categoria !== 'all' && !in_array($categoria, $categoryNames, true)) {
            $categoria = 'all';
        }

        $currentUser = auth()->user();
        
        $personalsQuery = Personal::query()
            ->with(['cursos' => fn ($cursoQuery) => $cursoQuery->orderBy('categoria')->orderBy('nombre')]);

        if ($currentUser->role !== 'superadmin') {
            $misProyectosIds = $currentUser->proyectos->pluck('id')->toArray();
            
            $personalsQuery->whereHas('proyectos', function ($q) use ($misProyectosIds) {
                $q->whereIn('proyectos.id', $misProyectosIds);
            });
        }

        $personals = $personalsQuery
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

        $selectedPersonal = $personalId > 0 ? clone $personalsQuery->where('id', $personalId)->first() : null;

        $cursosQuery = Curso::query()
            ->withCount('personal')
            ->orderBy('categoria')
            ->orderBy('nombre');

        if ($currentUser->role !== 'superadmin') {
            $misProyectosIds = $currentUser->proyectos->pluck('id')->toArray();
            $cursosQuery->whereHas('proyectos', function ($q) use ($misProyectosIds) {
                $q->whereIn('proyectos.id', $misProyectosIds);
            });
        }

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

        $todosLosPuestos = Puesto::where('activo', true)->orderBy('nombre')->get();

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

        $currentUser = auth()->user();

        $cursosQuery = Curso::query()
            ->withCount('personal')
            ->orderBy('categoria')
            ->orderBy('nombre');

        if ($currentUser->role !== 'superadmin') {
            $misProyectosIds = $currentUser->proyectos->pluck('id')->toArray();
            $cursosQuery->whereHas('proyectos', function ($q) use ($misProyectosIds) {
                $q->whereIn('proyectos.id', $misProyectosIds);
            });
        }

        $cursos = $cursosQuery->get();

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

        $validated = $this->validateCurso($request);

        $curso = Curso::create($validated);

        $user = auth()->user();
        $proyectoIds = $user && $user->role === 'superadmin'
            ? Proyecto::pluck('id')->toArray()
            : ($user?->proyectos->pluck('id')->toArray() ?? []);
        if (!empty($proyectoIds)) {
            $curso->proyectos()->sync($proyectoIds);
        }

        return redirect()
            ->route('cursos.index')
            ->with('success', 'Curso creado y vinculado a tu sede correctamente.');
    }

    public function edit(Curso $curso)
    {
        $this->authorize('cursos.edit');
        
        return view('cursos.edit', [
            'curso' => $curso,
            'categorias' => $this->getCategoriasActivas(),
        ]);
    }

    public function show(Curso $curso): View
    {
        $this->authorize('cursos.view');
        
        $curso->load(['personal' => function ($query) {
            if (auth()->user()->role !== 'superadmin') {
                $misProyectosIds = auth()->user()->proyectos->pluck('id')->toArray();
                $query->whereHas('proyectos', fn ($proyectoQuery) => $proyectoQuery->whereIn('proyectos.id', $misProyectosIds));
            }
            $query->orderBy('name')->orderBy('apellido');
        }]);

        return view('cursos.show', [
            'curso' => $curso,
        ]);
    }

    public function update(Request $request, Curso $curso)
    {
        $this->authorize('cursos.edit');
        
        $validated = $this->validateCurso($request, $curso->id);

        $curso->update($validated);

        return redirect()
            ->route('cursos.index')
            ->with('success', 'Curso actualizado correctamente.');
    }

    public function destroy(Curso $curso)
    {
        $this->authorize('cursos.delete');
        
        if ($curso->personal()->exists()) {
            return redirect()
                ->route('cursos.index')
                ->with('error', 'No se puede eliminar el curso porque ya tiene personal asociado.');
        }

        $curso->delete();

        return redirect()
            ->route('cursos.index')
            ->with('success', 'Curso eliminado correctamente.');
    }

    public function alertasCaducidad(): View
    {
        $this->authorize('cursos.alertas');

        $caducidadSql = 'DATE_ADD(curso_personal.fecha_realizacion, INTERVAL cursos.meses_validez MONTH)';
        $avisoSql = "CURDATE() >= DATE_SUB($caducidadSql, INTERVAL cursos.dias_aviso_previo DAY)";
        $caducadoSql = "CURDATE() > $caducidadSql";

        $currentUser = auth()->user();

        $query = DB::table('curso_personal')
            ->join('personal', 'personal.id', '=', 'curso_personal.personal_id')
            ->join('cursos', 'cursos.id', '=', 'curso_personal.curso_id')
            ->whereNotNull('curso_personal.fecha_realizacion')
            ->whereNotNull('cursos.meses_validez')
            ->where(function ($q) use ($avisoSql, $caducadoSql) {
                $q->whereRaw($avisoSql)
                    ->orWhereRaw($caducadoSql);
            });

        if ($currentUser->role !== 'superadmin') {
            $misProyectosIds = $currentUser->proyectos->pluck('id')->toArray();
            
            $query->join('personal_proyecto', 'personal.id', '=', 'personal_proyecto.personal_id')
                  ->whereIn('personal_proyecto.proyecto_id', $misProyectosIds)
                  ->distinct(); 
        }

        $alertas = $query->selectRaw(
                "personal.id as personal_id,
                personal.name as personal_name,
                personal.apellido as personal_apellido,
                personal.dni_nie as personal_dni_nie,
                cursos.id as curso_id,
                cursos.nombre as curso_nombre,
                curso_personal.fecha_realizacion,
                DATE_ADD(curso_personal.fecha_realizacion, INTERVAL cursos.meses_validez MONTH) as fecha_caducidad,
                CASE WHEN $caducadoSql THEN 'Caducado' ELSE 'En Aviso' END as estado"
            )
            ->orderByRaw("CASE WHEN $caducadoSql THEN 0 ELSE 1 END")
            ->orderBy('fecha_caducidad')
            ->get();

        return view('cursos.alertas', [
            'alertas' => $alertas,
        ]);
    }

    private function validateCurso(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'categoria' => ['required', 'string', 'max:255'],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cursos', 'nombre')->ignore($ignoreId),
            ],
            'descripcion' => 'nullable|string|max:2000',
            'meses_validez' => 'nullable|integer|min:1|max:120',
            'dias_aviso_previo' => 'nullable|integer|min:1|max:365',
        ]);
    }

    private function getCategoriasActivas(): array
    {
        $configCategories = array_keys(config('cursos.categories', []));

        $dbCategories = Curso::select('categoria')
            ->distinct()
            ->whereNotNull('categoria')
            ->pluck('categoria')
            ->toArray();

        $todas = array_unique(array_merge($configCategories, $dbCategories));
        sort($todas);

        return $todas;
    }

    public function export($id)
    {
        $this->authorize('cursos.export');

        $curso = Curso::with(['personal' => function($query) {
            if (auth()->user()->role !== 'superadmin') {
                $misProyectosIds = auth()->user()->proyectos->pluck('id')->toArray();
                $query->whereHas('proyectos', fn ($proyectoQuery) => $proyectoQuery->whereIn('proyectos.id', $misProyectosIds));
            }
            $query->withPivot('fecha_realizacion', 'apto'); 
        }])->findOrFail($id);

        $fileName = 'asistentes_curso_' . $curso->id . '_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($curso) {
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

                $fechaRealizacionRaw = $trabajador->pivot->fecha_realizacion;
                $esApto = (bool) $trabajador->pivot->apto;
                
                $fechaRealizacionFormat = '—';
                $caducidadFormat = '—';
                $estado = 'Vigente';

                if (!$esApto) {
                    $estado = 'No Apto';
                } elseif ($fechaRealizacionRaw) {
                    $fechaRealizacion = \Carbon\Carbon::parse($fechaRealizacionRaw)->startOfDay();
                    $fechaRealizacionFormat = $fechaRealizacion->format('d/m/Y');
                    
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
                    $idRrhh ?: '—',
                    $nombreCompleto ?: '—',
                    $departamentoStr ?: '—',
                    optional($trabajador->puestoTrabajo)->nombre ?: '—',
                    $trabajador->dni_nie ?: '—',
                    $trabajador->telefono ?: '—',
                    $fechaRealizacionFormat,
                    $caducidadFormat,
                    $estado
                ], ';');
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function syncPuestos(Request $request, Personal $personal)
    {
        $this->authorize('cursos.edit');

        $request->validate([
            'puestos' => 'nullable|array',
            'puestos.*' => 'exists:puestos,id'
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
    private function aplicarMacroDeCursos(Personal $personal)
    {
        // Solo cargamos los puestos activos del trabajador
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
                    'descripcion_aptitud' => 'Asignado automáticamente por perfil formativo.'
                ];
            }
            $personal->cursos()->syncWithoutDetaching($syncData);
        }
    }

    public function addPuesto(Request $request, Personal $personal)
    {
        $this->authorize('cursos.edit');

        $request->validate(['puesto_id' => 'required|exists:puestos,id']);

        $personal->puestos()->syncWithoutDetaching([$request->puesto_id]);

        $this->aplicarMacroDeCursos($personal);

        return response()->json([
            'success' => true,
            'message' => 'Perfil añadido y macro ejecutada.'
        ]);
    }

    public function removePuesto(Personal $personal, Puesto $puesto)
    {
        $this->authorize('cursos.edit');

        $personal->puestos()->detach($puesto->id);

        return response()->json([
            'success' => true,
            'message' => 'Perfil eliminado correctamente.'
        ]);
    }

    public function configAlertas()
    {
        $this->authorize('cursos.alertas');

        $emailSetting = Setting::where('key', 'alertas_prl_email')->first();
        $emails = [];

        if ($emailSetting && $emailSetting->value) {
            $decoded = json_decode($emailSetting->value, true);
            $emails = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [$emailSetting->value];
        }

        $dia = Setting::where('key', 'alertas_prl_dia')->value('value') ?? '1';
        $hora = Setting::where('key', 'alertas_prl_hora')->value('value') ?? '08:00';

        $enviarCaducidades = Setting::where('key', 'alertas_prl_enviar_caducidades')->value('value');
        $enviarCaducidades = $enviarCaducidades === null ? true : (bool) $enviarCaducidades;

        $enviarPendientes = Setting::where('key', 'alertas_prl_enviar_pendientes')->value('value');
        $enviarPendientes = $enviarPendientes === null ? true : (bool) $enviarPendientes;

        return view('cursos.config_alertas', compact('emails', 'dia', 'hora', 'enviarCaducidades', 'enviarPendientes'));
    }

    public function storeConfigAlertas(Request $request)
    {
        $this->authorize('cursos.alertas');

        $request->validate([
            'email' => 'required|email'
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debes introducir un formato de correo válido.'
        ]);

        $emailSetting = Setting::firstOrCreate(['key' => 'alertas_prl_email'], ['value' => '[]']);
        
        $decoded = json_decode($emailSetting->value, true);
        $emails = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : (empty($emailSetting->value) ? [] : [$emailSetting->value]);

        if (!in_array($request->email, $emails, true)) {
            $emails[] = $request->email;
            $emailSetting->update(['value' => json_encode($emails)]);
            return redirect()->route('cursos.config.alertas')->with('success', 'Correo añadido a la lista de destinatarios.');
        }

        return redirect()->route('cursos.config.alertas')->with('error', 'Ese correo ya está en la lista.');
    }

    public function destroyConfigAlertas(Request $request)
    {
        $this->authorize('cursos.alertas');

        $emailToRemove = $request->input('email_to_remove');
        $emailSetting = Setting::where('key', 'alertas_prl_email')->first();

        if ($emailSetting && $emailToRemove) {
            $emails = json_decode($emailSetting->value, true) ?? [];
            
            $emails = array_filter($emails, function($e) use ($emailToRemove) {
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

        $settingDia = Setting::firstOrNew(['key' => 'alertas_prl_dia']);
        $settingDia->value = $request->dia_envio;
        $settingDia->save();

        $settingHora = Setting::firstOrNew(['key' => 'alertas_prl_hora']);
        $settingHora->value = $request->hora_envio;
        $settingHora->save();

        $settingCaducidades = Setting::firstOrNew(['key' => 'alertas_prl_enviar_caducidades']);
        $settingCaducidades->value = $request->boolean('enviar_caducidades') ? '1' : '0';
        $settingCaducidades->save();

        $settingPendientes = Setting::firstOrNew(['key' => 'alertas_prl_enviar_pendientes']);
        $settingPendientes->value = $request->boolean('enviar_pendientes') ? '1' : '0';
        $settingPendientes->save();

        return redirect()->route('cursos.config.alertas')
                         ->with('success', 'Configuración y frecuencia de envío actualizadas correctamente.');
    }

    public function enviarAlertaManual()
    {
        $this->authorize('cursos.alertas');

        Artisan::call('cursos:notificar-caducidades');

        return redirect()->route('cursos.config.alertas')
                         ->with('success', 'Orden de escaneo ejecutada. Si hay caducidades, el correo se ha enviado.');
    }
    
    public function enviarAlertaManualPendientes()
    {
        $this->authorize('cursos.alertas');

        Artisan::call('cursos:notificar-pendientes');

        return redirect()->route('cursos.config.alertas')
                         ->with('success', 'Aviso manual procesado. Si hay trabajadores pendientes de revisión, el correo se ha enviado.');
    }
}