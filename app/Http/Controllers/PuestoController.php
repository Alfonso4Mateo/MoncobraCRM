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
        $this->authorize('cursos.view');
        // Cargamos los puestos contando cuántos cursos obligatorios, EPIs y personal tienen
        $puestos = Puesto::withCount(['cursos', 'personal', 'epis'])
            ->orderBy('nombre')
            ->get();

        return view('puestos.index', compact('puestos'));
    }

    public function store(Request $request)
    {
        $this->authorize('cursos.edit');
        $request->validate([
            'nombre' => 'required|string|max:255|unique:puestos,nombre',
        ]);

        $puesto = Puesto::create([
            'nombre' => mb_strtoupper($request->nombre),
            'activo' => true,
        ]);

        return redirect()->route('puestos.index')->with('success', 'Puesto creado correctamente.');
    }

    public function edit(Puesto $puesto): View
    {
        $this->authorize('cursos.edit');
        // Recuperamos todos los cursos agrupados por categoría para pintar el "Panel de Normas"
        $cursos = Curso::orderBy('categoria')->orderBy('nombre')->get();
        $cursosPorCategoria = $cursos->groupBy(function ($curso) {
            return $curso->categoria ?: 'Sin categoría';
        });

        $episDisponibles = \App\Models\Epi::where('activo', true)->orderBy('nombre')->get();

        $episAsignados = $puesto->epis->mapWithKeys(function ($epi) {
            return [$epi->id => ['cantidad' => $epi->pivot->cantidad]];
        })->toArray();

        // Extraemos solo los IDs de los cursos que este puesto ya tiene marcados como obligatorios
        $cursosAsignados = $puesto->cursos->pluck('id')->toArray();

        return view('puestos.edit', compact('puesto', 'cursosPorCategoria', 'cursosAsignados', 'episDisponibles', 'episAsignados'));
    }

    public function syncEpis(Request $request, Puesto $puesto)
    {
        $this->authorize('cursos.edit');
        $request->validate([
            'epis' => 'nullable|array',
            'epis.*' => 'exists:epis,id',
            'cantidades' => 'nullable|array',
            'cantidades.*' => 'integer|min:1',
        ]);

        $syncData = [];
        if ($request->has('epis')) {
            foreach ($request->epis as $epiId) {
                // Recuperamos la cantidad introducida; si no viene por seguridad, ponemos 1
                $cantidad = (int) $request->input("cantidades.{$epiId}", 1);
                $syncData[$epiId] = ['cantidad' => max(1, $cantidad)];
            }
        }

        // Aplicamos la inteligencia del sync() de Laravel con el atributo de la tabla pivot
        $puesto->epis()->sync($syncData);

        return redirect()->route('puestos.edit', $puesto->id)->with('success', 'Dotación de EPIs del puesto actualizada correctamente.');
    }

    public function update(Request $request, Puesto $puesto)
    {
        $this->authorize('cursos.edit');
        $request->validate([
            'nombre' => 'required|string|max:255|unique:puestos,nombre,' . $puesto->id,
        ]);

        $puesto->update([
            'nombre' => mb_strtoupper($request->nombre),
        ]);

        return redirect()->route('puestos.index')->with('success', 'Puesto actualizado.');
    }

    public function syncCursos(Request $request, Puesto $puesto)
    {
        $this->authorize('cursos.edit');
        // Validamos que lo que nos llega es un array de IDs que existen en la tabla cursos
        $request->validate([
            'cursos' => 'nullable|array',
            'cursos.*' => 'exists:cursos,id',
        ]);

        // Preparamos el array con los datos adicionales para la tabla pivote (es_obligatorio)
        $syncData = [];
        if ($request->has('cursos')) {
            foreach ($request->cursos as $cursoId) {
                $syncData[$cursoId] = ['es_obligatorio' => true];
            }
        }

        $puesto->cursos()->sync($syncData);
        $cursosObligatorios = array_keys($syncData);
        if ($cursosObligatorios !== []) {
            $puesto->personal()->with('cursos')->get()->each(function ($personal) use ($cursosObligatorios, $puesto) {
                $cursosActuales = $personal->cursos->pluck('id')->all();
                $cursosNuevos = array_diff($cursosObligatorios, $cursosActuales);
                if ($cursosNuevos !== []) {
                    $pivot = [];
                    foreach ($cursosNuevos as $cursoId) {
                        $pivot[$cursoId] = [
                            'apto' => false,
                            'fecha_realizacion' => null,
                            'descripcion_aptitud' => 'Asignado automáticamente por perfil formativo: ' . $puesto->nombre,
                        ];
                    }
                    $personal->cursos()->syncWithoutDetaching($pivot);
                }
            });
        }

        return redirect()->route('puestos.edit', $puesto->id)->with('success', 'Matriz de formación actualizada correctamente.');
    }

    public function destroy(Puesto $puesto)
    {
        $this->authorize('cursos.edit');
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
        $this->authorize('cursos.view');
        $personalRelation = function ($query) {
            if (auth()->user()->role !== 'superadmin') {
                $misProyectosIds = auth()->user()->proyectos->pluck('id')->all();
                $query->whereHas('proyectos', fn ($q) => $q->whereIn('proyectos.id', $misProyectosIds));
            }
        };
        $puesto->load(['cursos', 'personal' => $personalRelation, 'personal.cursos']);
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
                                $diasRestantesStr = abs((int) $diasRestantes) . ' días caducado';
                                $alertas++;
                            } else {
                                $diasRestantesStr = (int) $diasRestantes . ' días restantes';
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
                    'curso_nombre' => $cursoNorma->nombre,
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
        $this->authorize('cursos.view');
        $personalRelation = function ($query) {
            if (auth()->user()->role !== 'superadmin') {
                $misProyectosIds = auth()->user()->proyectos->pluck('id')->all();
                $query->whereHas('proyectos', fn ($q) => $q->whereIn('proyectos.id', $misProyectosIds));
            }
        };
        $puesto->load(['cursos', 'personal' => $personalRelation, 'personal.cursos']);
        $cursosExigidos = $puesto->cursos;

        $fileName = 'matriz_auditoria_' . \Illuminate\Support\Str::slug($puesto->nombre) . '_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function () use ($puesto, $cursosExigidos) {
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
                    optional($trabajador->puestoTrabajo)->nombre ?: '—',
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

    /**
     * Genera el documento PDF oficial con la dotación de EPIs de un puesto (Norma SST-0840-002-1-2 Rev.4).
     */
    /**
     * Genera el documento PDF oficial con la dotación de EPIs de un puesto.
     */
    public function exportEpisPdf(Request $request, Puesto $puesto)
    {
        $this->authorize('cursos.view');

        $puesto->load(['epis' => function ($query) {
            $query->orderBy('id');
        }]);

        $download = $request->input('download', 0) == 1; // Leemos si pulsó "Descargar" o "Visualizar"
        $slug = \Illuminate\Support\Str::slug($puesto->nombre ?: 'puesto-' . $puesto->id);
        $fileName = 'ficha_epis_' . $slug . '.pdf';
        $pdfContent = null;

        // 1. ATRAMPAMOS LOS DATOS DEL MODAL (Con los valores oficiales por defecto)
        $configPdf = [
            'codigo'           => $request->input('codigo_iso', 'SST-0840-002-1-2'),
            'revision'         => $request->input('revision_iso', 'Rev. 4'),
            'fecha_emision'    => $request->input('fecha_emision', now()->format('d/m/Y')),
            'marco_prl'        => $request->input('marco_prl', 'RD 773/1997'),
            'sello_prevencion' => $request->input('sello_prevencion', 'FIRMADO / HOMOLOGADO'),
            'sello_delegacion' => $request->input('sello_delegacion', 'CONFORME / VALIDADO'),
            'dpto_prevencion'  => $request->input('dpto_prevencion', 'Dpto. SST Moncobra, S.A.'), // <--- NUEVO
            'dpto_delegacion'  => $request->input('dpto_delegacion', 'Jefatura de Producción / Planta'), // <--- NUEVO
        ];

        // 2. SE LOS INYECTAMOS A LA VISTA BLADE
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('puestos.pdf-epis-placeholder', compact('puesto', 'configPdf'))
                ->setPaper('a4', 'portrait');
            $pdfContent = $pdf->output();
        } elseif (class_exists(\Dompdf\Dompdf::class)) {
            $html = view('puestos.pdf-epis-placeholder', compact('puesto', 'configPdf'))->render();
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            $pdfContent = $dompdf->output();
        }

        if ($pdfContent === null) {
            return view('puestos.pdf-epis-placeholder', compact('puesto', 'configPdf'));
        }

        if ($download) {
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => strlen($pdfContent),
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Accept-Ranges' => 'none',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
            ]);
        }

        // Visor HTML + Blob en cliente para evitar net::ERR_CONNECTION_RESET en 127.0.0.1:8000
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
}