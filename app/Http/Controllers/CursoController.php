<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Curso;
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
        // --- GUARDIA DE LA MURALLA (Interruptor Maestro) ---
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

        // --- FILTRO GEOGRÁFICO DE CURSOS ---
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

        $todosLosPuestos = \App\Models\Puesto::where('activo', true)->orderBy('nombre')->get();

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
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.edit');

        $currentUser = auth()->user();

        $cursosQuery = Curso::query()
            ->withCount('personal')
            ->orderBy('categoria')
            ->orderBy('nombre');

        // --- FILTRO GEOGRÁFICO DE CURSOS ---
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
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.create');

        return view('cursos.create', [
            'curso' => new Curso(),
            'categorias' => $this->getCategoriasActivas(),
        ]);
    }

    public function store(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.create');

        $validated = $this->validateCurso($request);

        $curso = Curso::create($validated);

        // --- AUTO-ASIGNACIÓN DE SEDE PARA CURSOS ---
        $user = auth()->user();
        if ($user && $user->role !== 'superadmin') {
            $misProyectosIds = $user->proyectos->pluck('id')->toArray();
            if (!empty($misProyectosIds)) {
                $curso->proyectos()->sync($misProyectosIds);
            }
        }

        return redirect()
            ->route('cursos.index')
            ->with('success', 'Curso creado y vinculado a tu sede correctamente.');
    }

    public function edit(Curso $curso)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.edit');
        
        return view('cursos.edit', [
            'curso' => $curso,
            'categorias' => $this->getCategoriasActivas(),
        ]);
    }

    public function show(Curso $curso): View
    {
        // --- GUARDIA DE LA MURALLA (Lo cambiamos al genérico .view para mayor flexibilidad) ---
        $this->authorize('cursos.view');
        
        // Cargamos los trabajadores asociados en una sola consulta, ordenados alfabéticamente
        $curso->load(['personal' => function ($query) {
            $query->orderBy('name')->orderBy('apellido');
        }]);

        return view('cursos.show', [
            'curso' => $curso,
        ]);
    }

    public function update(Request $request, Curso $curso)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.edit');
        
        $validated = $this->validateCurso($request, $curso->id);

        $curso->update($validated);

        return redirect()
            ->route('cursos.index')
            ->with('success', 'Curso actualizado correctamente.');
    }

    public function destroy(Curso $curso)
    {
        // --- GUARDIA DE LA MURALLA ---
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
        // --- GUARDIA DE LA MURALLA ---
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
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.export');

        // 1. Recuperamos el curso y sus trabajadores con los datos de la tabla pivote
        $curso = \App\Models\Curso::with(['personal' => function($query) {
            $query->withPivot('fecha_realizacion', 'apto'); 
        }])->findOrFail($id);

        // 2. Definimos el nombre del archivo
        $fileName = 'asistentes_curso_' . $curso->id . '_' . date('Y-m-d') . '.csv';

        // 3. Preparamos las cabeceras HTTP para forzar la descarga en el navegador
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // 4. Creamos la función de retorno que escribirá los datos
        $callback = function() use($curso) {
            // Abrimos un flujo de salida directamente hacia el navegador
            $file = fopen('php://output', 'w');

            // Añadimos el BOM para que Excel lea las tildes y ñ correctamente
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Escribimos la primera fila (Cabeceras actualizadas con Departamento)
            fputcsv($file, ['ID RRHH', 'Trabajador', 'Departamento', 'Fecha Realización', 'Caducidad', 'Estado'], ';');

            $hoy = \Carbon\Carbon::now()->startOfDay();

            // Recorremos los trabajadores y escribimos una fila por cada uno
            foreach ($curso->personal as $trabajador) {
                
                // CORRECCIÓN 1: Campos correctos para nombre y apellido
                $nombreCompleto = trim($trabajador->name . ' ' . $trabajador->apellido);
                
                // CORRECCIÓN 2: Asegurar el ID RRHH
                $idRrhh = $trabajador->id_rrhh ?: '—';

                // NUEVO 3: Procesar Departamento (Misma lógica que en las vistas)
                $deptos = is_string($trabajador->departamento) 
                    ? json_decode($trabajador->departamento, true) ?? explode(',', $trabajador->departamento) 
                    : (array) $trabajador->departamento;
                $departamentoStr = !empty($deptos) ? strtoupper(implode(', ', $deptos)) : 'SIN DEPARTAMENTO';

                // CORRECCIÓN 4: Prevención de errores con fechas nulas y lógica de estado real
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

                // Escribimos la fila en el CSV
                fputcsv($file, [
                    $idRrhh,
                    $nombreCompleto,
                    $departamentoStr,
                    $fechaRealizacionFormat,
                    $caducidadFormat,
                    $estado
                ], ';');
            }

            fclose($file);
        };

        // 5. Retornamos la respuesta en flujo usando la clase de Symfony de Laravel
        return new \Symfony\Component\HttpFoundation\StreamedResponse($callback, 200, $headers);
    }

    /**
     * Sincroniza los perfiles formativos (puestos) de un trabajador y dispara la macro.
     */
    public function syncPuestos(Request $request, Personal $personal)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.edit');

        $request->validate([
            'puestos' => 'nullable|array',
            'puestos.*' => 'exists:puestos,id'
        ]);

        // 1. Sincronizamos la tabla pivote personal_puesto
        if ($request->has('puestos')) {
            $personal->puestos()->sync($request->puestos);
        } else {
            $personal->puestos()->detach();
        }

        // 2. Disparamos la macro para inyectar los cursos
        $this->aplicarMacroDeCursos($personal);

        return redirect()->back()->with('success', 'Perfiles formativos actualizados. Cursos requeridos inyectados correctamente.');
    }

    /**
     * Motor de automatización (Aislado en el módulo de cursos)
     */
    private function aplicarMacroDeCursos(Personal $personal)
    {
        // Cargamos los puestos con sus cursos obligatorios
        $personal->load('puestos.cursos');

        $cursosObligatoriosIds = [];

        foreach ($personal->puestos as $puesto) {
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
                    'apto' => false, // Aún no lo ha superado
                    'fecha_realizacion' => null,
                    'descripcion_aptitud' => 'Asignado automáticamente por perfil formativo.'
                ];
            }
            $personal->cursos()->attach($syncData);
        }
    }

    /**
     * Añade un perfil formativo y dispara la macro vía AJAX
     */
    public function addPuesto(Request $request, Personal $personal)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.edit');

        $request->validate(['puesto_id' => 'required|exists:puestos,id']);

        // Vinculamos sin duplicar
        $personal->puestos()->syncWithoutDetaching([$request->puesto_id]);

        // Disparamos la macro para inyectar los cursos de ese puesto
        $this->aplicarMacroDeCursos($personal);

        return response()->json([
            'success' => true,
            'message' => 'Perfil añadido y macro ejecutada.'
        ]);
    }

    /**
     * Quita un perfil formativo vía AJAX
     */
    public function removePuesto(Personal $personal, \App\Models\Puesto $puesto)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.edit');

        $personal->puestos()->detach($puesto->id);

        return response()->json([
            'success' => true,
            'message' => 'Perfil eliminado correctamente.'
        ]);
    }

    // 1. MUESTRA LA PANTALLA CON LOS CORREOS Y EL HORARIO
    public function configAlertas()
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.alertas');

        $emailSetting = Setting::where('key', 'alertas_prl_email')->first();
        $emails = [];

        if ($emailSetting && $emailSetting->value) {
            $decoded = json_decode($emailSetting->value, true);
            $emails = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [$emailSetting->value];
        }

        // Leemos el horario actual para mostrarlo en el formulario
        $dia = Setting::where('key', 'alertas_prl_dia')->value('value') ?? '1';
        $hora = Setting::where('key', 'alertas_prl_hora')->value('value') ?? '08:00';

        return view('cursos.config_alertas', compact('emails', 'dia', 'hora'));
    }

    // 2. GUARDA UN CORREO NUEVO EN LA LISTA
    public function storeConfigAlertas(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
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

        // Si el correo no está ya en la lista, lo añadimos
        if (!in_array($request->email, $emails)) {
            $emails[] = $request->email;
            $emailSetting->update(['value' => json_encode($emails)]);
            return redirect()->route('cursos.config.alertas')->with('success', 'Correo añadido a la lista de destinatarios.');
        }

        return redirect()->route('cursos.config.alertas')->with('error', 'Ese correo ya está en la lista.');
    }

    // 3. ELIMINA UN CORREO DE LA LISTA
    public function destroyConfigAlertas(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.alertas');

        $emailToRemove = $request->input('email_to_remove');
        $emailSetting = Setting::where('key', 'alertas_prl_email')->first();

        if ($emailSetting && $emailToRemove) {
            $emails = json_decode($emailSetting->value, true) ?? [];
            
            // Filtramos la lista para quitar el correo que nos mandan borrar
            $emails = array_filter($emails, function($e) use ($emailToRemove) {
                return $e !== $emailToRemove;
            });

            // Guardamos la nueva lista reconstruyendo los índices numéricos
            $emailSetting->update(['value' => json_encode(array_values($emails))]);
        }

        return redirect()->route('cursos.config.alertas')->with('success', 'Correo eliminado de la lista.');
    }

    // 4. GUARDA LOS DÍAS Y HORAS DEL SERVIDOR
    public function storeHorarioAlertas(Request $request)
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.alertas');

        // 1. Validamos los datos de entrada
        $request->validate([
            'dia_envio' => 'required',
            'hora_envio' => 'required'
        ]);

        // 2. Guardado blindado para el DÍA (Evita bloqueos de Asignación Masiva)
        $settingDia = Setting::firstOrNew(['key' => 'alertas_prl_dia']);
        $settingDia->value = $request->dia_envio;
        $settingDia->save();

        // 3. Guardado blindado para la HORA
        $settingHora = Setting::firstOrNew(['key' => 'alertas_prl_hora']);
        $settingHora->value = $request->hora_envio;
        $settingHora->save();

        return redirect()->route('cursos.config.alertas')
                         ->with('success', 'Frecuencia de envío actualizada correctamente.');
    }

    // 5. EJECUCIÓN MANUAL DEL COMANDO DE ALERTAS
    public function enviarAlertaManual()
    {
        // --- GUARDIA DE LA MURALLA ---
        $this->authorize('cursos.alertas');

        // Ejecutamos el comando silenciosamente en el servidor
        Artisan::call('cursos:notificar-caducidades');

        return redirect()->route('cursos.config.alertas')
                         ->with('success', 'Orden de escaneo ejecutada. Si hay caducidades, el correo se ha enviado.');
    }
    
}