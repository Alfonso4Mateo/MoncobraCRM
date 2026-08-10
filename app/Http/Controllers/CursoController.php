<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Curso;
use Illuminate\Support\Facades\DB;
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

    public function index(): View
    {
       $query = (string) request()->input('q', '');
        $categoria = (string) request()->input('categoria', 'all');
        $personalId = (int) request()->input('personal_id', 0);
        
        $categoryNames = $this->getCategoriasActivas(); 

        if ($categoria !== 'all' && !in_array($categoria, $categoryNames, true)) {
            $categoria = 'all';
        }

        $personals = Personal::query()
            ->with(['cursos' => fn ($cursoQuery) => $cursoQuery->orderBy('categoria')->orderBy('nombre')])
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

        $selectedPersonal = $personalId > 0 ? Personal::find($personalId) : null;

        $cursosQuery = Curso::query()
            ->withCount('personal')
            ->orderBy('categoria')
            ->orderBy('nombre');

        if ($categoria !== 'all') {
            $cursosQuery->where('categoria', $categoria);
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

        return view('cursos.index', compact(
            'personals',
            'selectedPersonal',
            'cursos',
            'categorias',
            'cursosPorCategoria',
            'categoria',
            'query',
        ));
    }

    public function gestion(): View
    {
        $cursos = Curso::query()
            ->withCount('personal')
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get();

        return view('cursos.gestion', [
            'cursos' => $cursos,
        ]);
    }

    public function create()
    {
        return view('cursos.create', [
            'curso' => new Curso(),
            'categorias' => $this->getCategoriasActivas(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCurso($request);

        Curso::create($validated);

        return redirect()
            ->route('cursos.index')
            ->with('success', 'Curso creado correctamente.');
    }

    public function edit(Curso $curso)
    {
        return view('cursos.edit', [
            'curso' => $curso,
            'categorias' => $this->getCategoriasActivas(),
        ]);
    }

    public function show(Curso $curso): View
    {
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
        $validated = $this->validateCurso($request, $curso->id);

        $curso->update($validated);

        return redirect()
            ->route('cursos.index')
            ->with('success', 'Curso actualizado correctamente.');
    }

    public function destroy(Curso $curso)
    {
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
        $caducidadSql = 'DATE_ADD(curso_personal.fecha_realizacion, INTERVAL cursos.meses_validez MONTH)';
        $avisoSql = "CURDATE() >= DATE_SUB($caducidadSql, INTERVAL cursos.dias_aviso_previo DAY)";
        $caducadoSql = "CURDATE() > $caducidadSql";

        $alertas = DB::table('curso_personal')
            ->join('personal', 'personal.id', '=', 'curso_personal.personal_id')
            ->join('cursos', 'cursos.id', '=', 'curso_personal.curso_id')
            ->whereNotNull('curso_personal.fecha_realizacion')
            ->whereNotNull('cursos.meses_validez')
            ->where(function ($query) use ($avisoSql, $caducadoSql) {
                $query->whereRaw($avisoSql)
                    ->orWhereRaw($caducadoSql);
            })
            ->selectRaw(
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
}