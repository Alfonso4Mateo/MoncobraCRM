<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Epi;
use App\Models\Puesto;
use App\Models\PuestoEpiDocumento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PuestoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function authorizePuestosRead(): void
    {
        abort_unless(
            auth()->user()?->canAny(['cursos.view', 'cursos.normas', 'cursos.export', 'personal.export']),
            403
        );
    }

    private function authorizePuestosWrite(): void
    {
        abort_unless(
            auth()->user()?->canAny(['cursos.normas', 'cursos.edit']),
            403
        );
    }

    private function getMisProyectosIds(): ?array
    {
        $user = auth()->user();
        if (!$user || $user->role === 'superadmin') {
            return null;
        }

        return $user->proyectos->pluck('id')->all();
    }

    private function authorizePuestoProyecto(Puesto $puesto): void
    {
        $misProyectosIds = $this->getMisProyectosIds();
        if ($misProyectosIds === null) {
            return;
        }

        abort_unless(
            in_array((int) $puesto->proyecto_id, array_map('intval', $misProyectosIds), true),
            403,
            'No tienes acceso a un perfil formativo de otra sede.'
        );
    }

    public function index(): View
    {
        $this->authorizePuestosRead();

        $proyectoId = $this->resolveProyectoForCorrelativo(request());

        $puestos = Puesto::where('proyecto_id', $proyectoId)
            ->with('documentoEpiVigente')
            ->withCount([
                'epis',
                'todosLosEpis',
                'cursos' => fn ($query) => $query->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoId)),
                'personal' => fn ($query) => $query->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoId)),
            ])
            ->orderBy('nombre')
            ->get();

        return view('puestos.index', compact('puestos'));
    }

    public function store(Request $request)
    {
        $this->authorizePuestosWrite();

        $proyectoId = $this->resolveActiveProyectoId($request);

        $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('puestos', 'nombre')->where(fn ($q) => $q->where('proyecto_id', $proyectoId)),
            ],
        ]);

        Puesto::create([
            'proyecto_id' => $proyectoId,
            'nombre' => mb_strtoupper($request->nombre),
            'activo' => true,
        ]);

        return redirect()->route('puestos.index')->with('success', 'Puesto creado correctamente.');
    }

    public function edit(Puesto $puesto): View
    {
        $this->authorizePuestosWrite();
        $this->authorizePuestoProyecto($puesto);

        $proyectoId = (int) $puesto->proyecto_id;

        $cursos = Curso::whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $proyectoId))
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get();

        $cursosPorCategoria = $cursos->groupBy(function ($curso) {
            return $curso->categoria ?: 'Sin categoría';
        });

        // Solo EPIs activos de ESTA sede disponibles para asignar
        $episDisponibles = Epi::where('proyecto_id', $proyectoId)
            ->where('activo', true)
            ->with(['inventario', 'inventarioVariante.items', 'inventarioArticulo'])
            ->orderBy('nombre')
            ->get();

        $episAsignados = $puesto->epis()
            ->get()
            ->mapWithKeys(function ($epi) {
                return [$epi->id => ['cantidad' => (int) ($epi->pivot->cantidad ?? 1)]];
            })
            ->toArray();

        $episInactivosAsignados = $puesto->todosLosEpis()
            ->where('epis.activo', false)
            ->orderBy('nombre')
            ->get();

        $puesto->load(['documentosEpis.autor', 'documentoEpiVigente']);

        $cursosAsignados = $puesto->cursos->pluck('id')->toArray();

        return view('puestos.edit', compact(
            'puesto',
            'cursosPorCategoria',
            'cursosAsignados',
            'episDisponibles',
            'episAsignados',
            'episInactivosAsignados'
        ));
    }

    public function syncEpis(Request $request, Puesto $puesto)
    {
        $this->authorizePuestosWrite();
        $this->authorizePuestoProyecto($puesto);

        $request->validate([
            'epis' => 'nullable|array',
            'epis.*' => 'exists:epis,id',
            'cantidades' => 'nullable|array',
            'cantidades.*' => 'integer|min:1',
        ]);

        $syncData = [];
        if ($request->has('epis')) {
            // Filtrar para garantizar que solo se vinculan EPIs del mismo proyecto
            $episValidosIds = Epi::where('proyecto_id', $puesto->proyecto_id)
                ->whereIn('id', $request->epis)
                ->pluck('id')
                ->all();

            foreach ($episValidosIds as $epiId) {
                $cantidad = (int) $request->input("cantidades.{$epiId}", 1);
                $syncData[$epiId] = ['cantidad' => max(1, $cantidad)];
            }
        }

        $inactivosMantenidosIds = array_map('intval', (array) $request->input('epis_inactivos_mantener', []));

        if (!empty($inactivosMantenidosIds)) {
            $inactivosConservar = $puesto->todosLosEpis()
                ->where('epis.activo', false)
                ->whereIn('epis.id', $inactivosMantenidosIds)
                ->get();

            foreach ($inactivosConservar as $epiInactivo) {
                if (!isset($syncData[$epiInactivo->id])) {
                    $syncData[$epiInactivo->id] = ['cantidad' => (int) ($epiInactivo->pivot->cantidad ?? 1)];
                }
            }
        }

        $puesto->todosLosEpis()->sync($syncData);

        return redirect()
            ->route('puestos.edit', ['puesto' => $puesto->id, 'tab' => 'epis'])
            ->with('success', 'Dotación de EPIs del puesto actualizada correctamente. Recuerda homologar una nueva revisión oficial si deseas publicar los cambios.');
    }

    public function update(Request $request, Puesto $puesto)
    {
        $this->authorizePuestosWrite();
        $this->authorizePuestoProyecto($puesto);

        $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('puestos', 'nombre')
                    ->where(fn ($q) => $q->where('proyecto_id', $puesto->proyecto_id))
                    ->ignore($puesto->id),
            ],
        ]);

        $puesto->update([
            'nombre' => mb_strtoupper($request->nombre),
        ]);

        return redirect()->route('puestos.edit', $puesto->id)->with('success', 'Nombre del puesto actualizado correctamente.');
    }

    public function syncCursos(Request $request, Puesto $puesto)
    {
        $this->authorizePuestosWrite();
        $this->authorizePuestoProyecto($puesto);

        $request->validate([
            'cursos' => 'nullable|array',
            'cursos.*' => 'exists:cursos,id',
        ]);

        $syncData = [];
        if ($request->has('cursos')) {
            $cursosValidosIds = Curso::whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $puesto->proyecto_id))
                ->whereIn('id', $request->cursos)
                ->pluck('id')
                ->all();

            foreach ($cursosValidosIds as $cursoId) {
                $syncData[$cursoId] = ['es_obligatorio' => true];
            }
        }

        $puesto->cursos()->sync($syncData);

        $cursosObligatorios = array_keys($syncData);
        if ($cursosObligatorios !== []) {
            $puesto->personal()
                ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $puesto->proyecto_id))
                ->with('cursos')
                ->get()
                ->each(function ($personal) use ($cursosObligatorios, $puesto) {
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

        return redirect()
            ->route('puestos.edit', ['puesto' => $puesto->id, 'tab' => 'cursos'])
            ->with('success', 'Matriz de formación actualizada correctamente.');
    }

    public function destroy(Puesto $puesto)
    {
        $this->authorizePuestosWrite();
        $this->authorizePuestoProyecto($puesto);

        if ($puesto->personal()->exists()) {
            return redirect()->route('puestos.index')->with('error', 'No se puede eliminar: Hay trabajadores con este puesto asignado.');
        }

        $puesto->delete();
        return redirect()->route('puestos.index')->with('success', 'Puesto eliminado correctamente.');
    }

    public function auditoria(Puesto $puesto)
    {
        $this->authorizePuestosRead();
        $this->authorizePuestoProyecto($puesto);

        $proyectoId = (int) $puesto->proyecto_id;

        $puesto->load([
            'cursos' => fn ($q) => $q->whereHas('proyectos', fn ($pq) => $pq->where('proyectos.id', $proyectoId))->orderBy('nombre'),
            'personal' => fn ($q) => $q->whereHas('proyectos', fn ($pq) => $pq->where('proyectos.id', $proyectoId))->orderBy('name')->orderBy('apellido'),
            'personal.cursos',
            'personal.puestoTrabajo',
        ]);

        $cursosExigidos = $puesto->cursos;
        $trabajadores = $puesto->personal;

        $totalTrabajadores = $trabajadores->count();
        $totalCursosExigidos = $cursosExigidos->count();
        $totalEvaluaciones = $totalTrabajadores * $totalCursosExigidos;

        $cumplimientos = 0;
        $alertas = 0;

        $hoy = Carbon::now()->startOfDay();

        foreach ($trabajadores as $trabajador) {
            $matrizTemporal = [];

            foreach ($cursosExigidos as $cursoNorma) {
                $cursoTrabajador = $trabajador->cursos->firstWhere('id', $cursoNorma->id);

                $estado = 'pendiente';
                $tooltip = 'No realizado o No Apto';
                $fechaRealizacionStr = '—';
                $fechaVencimientoStr = '—';
                $diasRestantesStr = '—';

                if ($cursoTrabajador && $cursoTrabajador->pivot->apto && $cursoTrabajador->pivot->fecha_realizacion) {
                    $fRealizacion = Carbon::parse($cursoTrabajador->pivot->fecha_realizacion)->startOfDay();
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
                } else {
                    $alertas++;
                }

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

        return view('puestos.auditoria', compact(
            'puesto',
            'cursosExigidos',
            'trabajadores',
            'totalTrabajadores',
            'porcentajeCumplimiento',
            'alertas'
        ));
    }

    public function exportAuditoria(Puesto $puesto)
    {
        $this->authorizePuestosRead();
        $this->authorizePuestoProyecto($puesto);

        $proyectoId = (int) $puesto->proyecto_id;

        $puesto->load([
            'cursos' => fn ($q) => $q->whereHas('proyectos', fn ($pq) => $pq->where('proyectos.id', $proyectoId))->orderBy('nombre'),
            'personal' => fn ($q) => $q->whereHas('proyectos', fn ($pq) => $pq->where('proyectos.id', $proyectoId))->orderBy('name')->orderBy('apellido'),
            'personal.cursos',
            'personal.puestoTrabajo',
        ]);

        $cursosExigidos = $puesto->cursos;

        $fileName = 'matriz_auditoria_' . Str::slug($puesto->nombre) . '_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function () use ($puesto, $cursosExigidos) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $cabeceras = ['ID RRHH', 'Trabajador', 'Departamento', 'Puesto'];
            foreach ($cursosExigidos as $c) {
                $cabeceras[] = $c->nombre;
            }
            fputcsv($file, $cabeceras, ';');

            $hoy = Carbon::now()->startOfDay();

            foreach ($puesto->personal as $trabajador) {
                $deptos = is_string($trabajador->departamento)
                    ? json_decode($trabajador->departamento, true) ?? explode(',', $trabajador->departamento)
                    : (array) $trabajador->departamento;

                $nombrePuesto = optional($trabajador->puestoTrabajo)->nombre ?: ($trabajador->puesto ?: '—');

                $fila = [
                    $trabajador->id_rrhh ?: '—',
                    trim($trabajador->name . ' ' . $trabajador->apellido),
                    !empty($deptos) ? strtoupper(implode(', ', $deptos)) : 'SIN DEPARTAMENTO',
                    $nombrePuesto,
                ];

                foreach ($cursosExigidos as $cursoNorma) {
                    $cursoTrabajador = $trabajador->cursos->firstWhere('id', $cursoNorma->id);
                    $textoCelda = 'Pendiente';

                    if ($cursoTrabajador) {
                        if (!$cursoTrabajador->pivot->apto && $cursoTrabajador->pivot->fecha_realizacion) {
                            $textoCelda = 'NO APTO';
                        } elseif ($cursoTrabajador->pivot->apto && $cursoTrabajador->pivot->fecha_realizacion) {
                            $fRealizacion = Carbon::parse($cursoTrabajador->pivot->fecha_realizacion)->startOfDay();

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

    private function buildEpisPdfBinary(Puesto $puesto, array $configPdf): ?string
    {
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('puestos.pdf-epis-placeholder', compact('puesto', 'configPdf'))
                ->setPaper('a4', 'portrait')
                ->output();
        }

        if (class_exists(\Dompdf\Dompdf::class)) {
            $html = view('puestos.pdf-epis-placeholder', compact('puesto', 'configPdf'))->render();
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            return $dompdf->output();
        }

        return null;
    }

    private function respondWithPdf(string $pdfContent, string $fileName, bool $download)
    {
        if ($download) {
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => strlen($pdfContent),
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Accept-Ranges' => 'none',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
            ]);
        }

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

    public function homologarEpisPdf(Request $request, Puesto $puesto)
    {
        $this->authorizePuestosWrite();
        $this->authorizePuestoProyecto($puesto);

        $validated = $request->validate([
            'codigo_iso'       => 'required|string|max:60',
            'revision_iso'     => 'required|string|max:30',
            'fecha_emision'    => 'required|string|max:20',
            'marco_prl'        => 'required|string|max:100',
            'sello_prevencion' => 'required|string|max:120',
            'sello_delegacion' => 'required|string|max:120',
            'dpto_prevencion'  => 'required|string|max:150',
            'dpto_delegacion'  => 'required|string|max:150',
        ]);

        $puesto->load(['epis' => function ($query) {
            $query->where('epis.activo', true)->orderBy('id');
        }]);

        try {
            $fechaCarbon = Carbon::createFromFormat('d/m/Y', $validated['fecha_emision']);
        } catch (\Throwable $e) {
            $fechaCarbon = Carbon::parse($validated['fecha_emision']);
        }

        $configPdf = [
            'codigo'           => $validated['codigo_iso'],
            'revision'         => $validated['revision_iso'],
            'fecha_emision'    => $fechaCarbon->format('d/m/Y'),
            'marco_prl'        => $validated['marco_prl'],
            'sello_prevencion' => $validated['sello_prevencion'],
            'sello_delegacion' => $validated['sello_delegacion'],
            'dpto_prevencion'  => $validated['dpto_prevencion'],
            'dpto_delegacion'  => $validated['dpto_delegacion'],
        ];

        $pdfContent = $this->buildEpisPdfBinary($puesto, $configPdf);

        if ($pdfContent === null) {
            return redirect()
                ->route('puestos.edit', ['puesto' => $puesto->id, 'tab' => 'epis'])
                ->with('error', 'No se encontró un motor PDF (DomPDF) instalado en el servidor.');
        }

        $slugPuesto = Str::slug($puesto->nombre ?: 'puesto-' . $puesto->id);
        $slugRev = Str::slug($validated['revision_iso']);
        $fileName = "ficha_epis_p{$puesto->proyecto_id}_{$slugPuesto}_{$slugRev}_" . now()->format('YmdHis') . '.pdf';
        $storagePath = 'fichas_epis/' . $fileName;

        $snapshot = $puesto->epis->map(function ($epi) {
            return [
                'id'          => $epi->id,
                'nombre'      => $epi->nombre,
                'categoria'   => $epi->categoria,
                'descripcion' => $epi->descripcion,
                'cantidad'    => (int) ($epi->pivot->cantidad ?? 1),
            ];
        })->values()->all();

        DB::transaction(function () use ($puesto, $validated, $fechaCarbon, $storagePath, $pdfContent, $snapshot) {
            PuestoEpiDocumento::where('puesto_id', $puesto->id)
                ->where('es_vigente', true)
                ->update(['es_vigente' => false]);

            Storage::disk('public')->put($storagePath, $pdfContent);

            PuestoEpiDocumento::create([
                'puesto_id'        => $puesto->id,
                'codigo_iso'       => $validated['codigo_iso'],
                'revision'         => $validated['revision_iso'],
                'fecha_emision'    => $fechaCarbon->toDateString(),
                'marco_prl'        => $validated['marco_prl'],
                'sello_prevencion' => $validated['sello_prevencion'],
                'sello_delegacion' => $validated['sello_delegacion'],
                'dpto_prevencion'  => $validated['dpto_prevencion'],
                'dpto_delegacion'  => $validated['dpto_delegacion'],
                'file_path'        => $storagePath,
                'epis_snapshot'    => $snapshot,
                'es_vigente'       => true,
                'user_id'          => auth()->id(),
            ]);
        });

        return redirect()
            ->route('puestos.edit', ['puesto' => $puesto->id, 'tab' => 'epis'])
            ->with('success', "Ficha técnica {$validated['codigo_iso']} ({$validated['revision_iso']}) homologada y archivada correctamente.");
    }

    public function verRevisionEpisPdf(Request $request, Puesto $puesto, PuestoEpiDocumento $documento)
    {
        $this->authorizePuestosRead();
        $this->authorizePuestoProyecto($puesto);
        abort_unless((int) $documento->puesto_id === (int) $puesto->id, 404);
        abort_unless(Storage::disk('public')->exists($documento->file_path), 404, 'El archivo PDF archivado no existe en el almacenamiento.');

        $download = (int) $request->input('download', 0) === 1;
        $slugPuesto = Str::slug($puesto->nombre ?: 'puesto-' . $puesto->id);
        $slugRev = Str::slug($documento->revision);
        $fileName = "ficha_epis_{$slugPuesto}_{$slugRev}.pdf";

        $pdfContent = Storage::disk('public')->get($documento->file_path);

        return $this->respondWithPdf($pdfContent, $fileName, $download);
    }

    public function exportEpisPdf(Request $request, Puesto $puesto)
    {
        $this->authorizePuestosRead();
        $this->authorizePuestoProyecto($puesto);

        $download = (int) $request->input('download', 0) === 1;
        $isPreview = (int) $request->input('preview', 0) === 1;

        if (!$isPreview) {
            $vigente = $puesto->documentoEpiVigente;
            if ($vigente && Storage::disk('public')->exists($vigente->file_path)) {
                $slugPuesto = Str::slug($puesto->nombre ?: 'puesto-' . $puesto->id);
                $slugRev = Str::slug($vigente->revision);
                $fileName = "ficha_epis_{$slugPuesto}_{$slugRev}.pdf";
                $pdfContent = Storage::disk('public')->get($vigente->file_path);

                return $this->respondWithPdf($pdfContent, $fileName, $download);
            }
        }

        $puesto->load(['epis' => function ($query) {
            $query->where('epis.activo', true)->orderBy('id');
        }]);

        $slug = Str::slug($puesto->nombre ?: 'puesto-' . $puesto->id);
        $fileName = 'borrador_ficha_epis_' . $slug . '.pdf';

        $configPdf = [
            'codigo'           => $request->input('codigo_iso', 'SST-0840-002-1-2'),
            'revision'         => $request->input('revision_iso', 'Rev. 1 (Borrador)'),
            'fecha_emision'    => $request->input('fecha_emision', now()->format('d/m/Y')),
            'marco_prl'        => $request->input('marco_prl', 'RD 773/1997'),
            'sello_prevencion' => $request->input('sello_prevencion', 'BORRADOR NO HOMOLOGADO'),
            'sello_delegacion' => $request->input('sello_delegacion', 'PENDIENTE DE VALIDACIÓN'),
            'dpto_prevencion'  => $request->input('dpto_prevencion', 'Dpto. SST Moncobra, S.A.'),
            'dpto_delegacion'  => $request->input('dpto_delegacion', 'Jefatura de Producción / Planta'),
        ];

        $pdfContent = $this->buildEpisPdfBinary($puesto, $configPdf);

        if ($pdfContent === null) {
            return view('puestos.pdf-epis-placeholder', compact('puesto', 'configPdf'));
        }

        return $this->respondWithPdf($pdfContent, $fileName, $download);
    }
}