<?php

namespace App\Http\Controllers;

use App\Models\Puesto;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PuestoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        // Cargamos los puestos contando cuántos cursos obligatorios y personal tienen
        $puestos = Puesto::withCount(['cursos', 'personal'])
            ->orderBy('nombre')
            ->get();

        return view('puestos.index', compact('puestos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:puestos,nombre',
        ]);

        // Lo guardamos en mayúsculas para mantener consistencia en la base de datos
        Puesto::create([
            'nombre' => mb_strtoupper($request->nombre),
            'activo' => true,
        ]);

        return redirect()->route('puestos.index')->with('success', 'Puesto creado correctamente.');
    }

    public function edit(Puesto $puesto): View
    {
        // Recuperamos todos los cursos agrupados por categoría para pintar el "Panel de Normas"
        $cursos = Curso::orderBy('categoria')->orderBy('nombre')->get();
        $cursosPorCategoria = $cursos->groupBy(function($curso) {
            return $curso->categoria ?: 'Sin categoría';
        });

        // Extraemos solo los IDs de los cursos que este puesto ya tiene marcados como obligatorios
        $cursosAsignados = $puesto->cursos->pluck('id')->toArray();

        return view('puestos.edit', compact('puesto', 'cursosPorCategoria', 'cursosAsignados'));
    }

    public function update(Request $request, Puesto $puesto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:puestos,nombre,' . $puesto->id,
        ]);

        $puesto->update([
            'nombre' => mb_strtoupper($request->nombre),
        ]);

        return redirect()->route('puestos.index')->with('success', 'Nombre del puesto actualizado.');
    }

    public function syncCursos(Request $request, Puesto $puesto)
    {
        // Validamos que lo que nos llega es un array de IDs que existen en la tabla cursos
        $request->validate([
            'cursos' => 'nullable|array',
            'cursos.*' => 'exists:cursos,id'
        ]);

        // Preparamos el array con los datos adicionales para la tabla pivote (es_obligatorio)
        $syncData = [];
        if ($request->has('cursos')) {
            foreach ($request->cursos as $cursoId) {
                $syncData[$cursoId] = ['es_obligatorio' => true];
            }
        }

        /* 
         * AQUI ESTÁ LA MAGIA DE LARAVEL: 
         * El método sync() compara lo que hay en la BD con el array $syncData.
         * Si un curso estaba en la BD pero no en el array, lo desvincula.
         * Si está en el array pero no en la BD, lo vincula.
         * Si está en ambos, no hace nada. Eficiencia pura.
         */
        $puesto->cursos()->sync($syncData);

        return redirect()->route('puestos.edit', $puesto->id)->with('success', 'Matriz de formación actualizada correctamente.');
    }

    public function destroy(Puesto $puesto)
    {
        // Medida de seguridad: No dejamos borrar un puesto si hay gente usándolo
        if ($puesto->personal()->exists()) {
            return redirect()->route('puestos.index')->with('error', 'No se puede eliminar: Hay trabajadores con este puesto asignado.');
        }

        $puesto->delete();
        return redirect()->route('puestos.index')->with('success', 'Puesto eliminado correctamente.');
    }

    /**
     * DASHBOARD WEB: Muestra la matriz visual de cumplimiento
     */
    public function auditoria(Puesto $puesto)
    {
        $puesto->load(['cursos', 'personal.cursos']);
        $cursosExigidos = $puesto->cursos;
        $trabajadores = $puesto->personal;

        $totalTrabajadores = $trabajadores->count();
        $totalCursosExigidos = $cursosExigidos->count();
        $totalEvaluaciones = $totalTrabajadores * $totalCursosExigidos;
        
        $cumplimientos = 0;
        $alertas = 0;

        $hoy = \Carbon\Carbon::now()->startOfDay();

        foreach ($trabajadores as $trabajador) {
            $matrizTemporal = []; 
            
            foreach ($cursosExigidos as $cursoNorma) {
                $cursoTrabajador = $trabajador->cursos->firstWhere('id', $cursoNorma->id);
                
                // Valores por defecto
                $estado = 'pendiente'; 
                $tooltip = 'No realizado o No Apto';
                $fechaRealizacionStr = '—';
                $fechaVencimientoStr = '—';
                $diasRestantesStr = '—';

                if ($cursoTrabajador && $cursoTrabajador->pivot->apto) {
                    if ($cursoTrabajador->pivot->fecha_realizacion) {
                        $fRealizacion = \Carbon\Carbon::parse($cursoTrabajador->pivot->fecha_realizacion)->startOfDay();
                        $fechaRealizacionStr = $fRealizacion->format('d/m/Y');
                        
                        if ($cursoNorma->meses_validez) {
                            $fVencimiento = $fRealizacion->copy()->addMonths($cursoNorma->meses_validez);
                            $fAviso = $fVencimiento->copy()->subDays($cursoNorma->dias_aviso_previo ?? 30);
                            $fechaVencimientoStr = $fVencimiento->format('d/m/Y');
                            
                            $diasRestantes = $hoy->diffInDays($fVencimiento, false);
                            
                            if ($diasRestantes < 0) {
                                $estado = 'caducado';
                                $tooltip = 'Caducó el ' . $fechaVencimientoStr;
                                $diasRestantesStr = abs((int)$diasRestantes) . ' días caducado';
                                $alertas++;
                            } else {
                                $diasRestantesStr = (int)$diasRestantes . ' días restantes';
                                if ($hoy->gte($fAviso)) {
                                    $estado = 'aviso';
                                    $tooltip = 'Caduca pronto: ' . $fechaVencimientoStr;
                                    $cumplimientos++;
                                } else {
                                    $estado = 'vigente';
                                    $tooltip = 'Válido hasta ' . $fechaVencimientoStr;
                                    $cumplimientos++;
                                }
                            }
                        } else {
                            $estado = 'vigente';
                            $fechaVencimientoStr = 'Sin caducidad';
                            $diasRestantesStr = '∞';
                            $tooltip = 'Curso sin caducidad';
                            $cumplimientos++;
                        }
                    }
                }

                // Guardamos todo el detalle para que el modal (clic) pueda leerlo
                $matrizTemporal[$cursoNorma->id] = [
                    'estado' => $estado,
                    'tooltip' => $tooltip,
                    'fecha_realizacion' => $fechaRealizacionStr,
                    'fecha_vencimiento' => $fechaVencimientoStr,
                    'dias_restantes' => $diasRestantesStr,
                    'curso_nombre' => $cursoNorma->nombre
                ];
            }
            
            $trabajador->matriz_cursos = $matrizTemporal;
        }

        $porcentajeCumplimiento = $totalEvaluaciones > 0 ? round(($cumplimientos / $totalEvaluaciones) * 100) : 0;

        return view('puestos.auditoria', compact('puesto', 'cursosExigidos', 'trabajadores', 'totalTrabajadores', 'porcentajeCumplimiento', 'alertas'));
    }

    /**
     * Exporta la matriz de cumplimiento (Auditoría) del Puesto a formato CSV.
     */
    public function exportAuditoria(Puesto $puesto)
    {
        $puesto->load(['cursos', 'personal.cursos']);
        $cursosExigidos = $puesto->cursos;

        $fileName = 'matriz_auditoria_' . \Illuminate\Support\Str::slug($puesto->nombre) . '_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($puesto, $cursosExigidos) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

            // Construir Cabecera Dinámica
            $cabeceras = ['ID RRHH', 'Trabajador', 'Departamento', 'Puesto'];
            foreach ($cursosExigidos as $c) {
                $cabeceras[] = $c->nombre; // 1 Columna por curso
            }
            fputcsv($file, $cabeceras, ';');

            $hoy = \Carbon\Carbon::now()->startOfDay();

            // Rellenar Filas (1 fila por trabajador)
            foreach ($puesto->personal as $trabajador) {
                $deptos = is_string($trabajador->departamento) 
                    ? json_decode($trabajador->departamento, true) ?? explode(',', $trabajador->departamento) 
                    : (array) $trabajador->departamento;
                
                $fila = [
                    $trabajador->id_rrhh ?: '—',
                    trim($trabajador->name . ' ' . $trabajador->apellido),
                    !empty($deptos) ? strtoupper(implode(', ', $deptos)) : 'SIN DEPARTAMENTO',
                    $trabajador->puesto ?: '—'
                ];

                foreach ($cursosExigidos as $cursoNorma) {
                    $cursoTrabajador = $trabajador->cursos->firstWhere('id', $cursoNorma->id);
                    $textoCelda = 'Pendiente';

                    if ($cursoTrabajador && $cursoTrabajador->pivot->apto) {
                        if ($cursoTrabajador->pivot->fecha_realizacion) {
                            $fRealizacion = \Carbon\Carbon::parse($cursoTrabajador->pivot->fecha_realizacion)->startOfDay();
                            
                            if ($cursoNorma->meses_validez) {
                                $fVencimiento = $fRealizacion->copy()->addMonths($cursoNorma->meses_validez);
                                $fAviso = $fVencimiento->copy()->subDays($cursoNorma->dias_aviso_previo ?? 30);
                                
                                if ($hoy->gt($fVencimiento)) {
                                    $textoCelda = 'CADUCADO (' . $fVencimiento->format('d/m/Y') . ')';
                                } elseif ($hoy->gte($fAviso)) {
                                    $textoCelda = 'AVISO (' . $fVencimiento->format('d/m/Y') . ')';
                                } else {
                                    $textoCelda = 'VIGENTE (' . $fVencimiento->format('d/m/Y') . ')';
                                }
                            } else {
                                $textoCelda = 'VIGENTE (Sin caducidad)';
                            }
                        }
                    }
                    $fila[] = $textoCelda;
                }
                fputcsv($file, $fila, ';');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }
}