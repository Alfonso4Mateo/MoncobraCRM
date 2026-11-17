<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Personal;
use App\Models\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PersonalCursoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Comprueba que el trabajador pertenezca a alguno de los proyectos del usuario autenticado
     * (salvo que sea superadmin).
     */
    private function authorizePersonalProyecto(Personal $personal): void
    {
        $user = auth()->user();
        if (!$user || $user->role === 'superadmin') {
            return;
        }

        $misProyectosIds = $user->proyectos->pluck('id')->all();
        $pertenece = !empty($misProyectosIds)
            && $personal->proyectos()->whereIn('proyectos.id', $misProyectosIds)->exists();

        abort_unless($pertenece, 403, 'No tienes permisos para gestionar trabajadores de otra sede.');
    }

    // 1. ASIGNAR / ACTUALIZAR / SUBIR DIPLOMA
    public function update(Request $request, Personal $personal)
    {
        $this->authorize('cursos.edit');
        $this->authorizePersonalProyecto($personal);

        $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'fecha_realizacion' => 'nullable|date',
            'apto' => 'required|boolean',
            'descripcion_aptitud' => 'nullable|string|max:2000',
            'archivo_diploma' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB
        ]);

        $cursoId = (int) $request->curso_id;

        DB::transaction(function () use ($request, $personal, $cursoId) {
            $pivotData = [
                'fecha_realizacion' => $request->fecha_realizacion ?: null,
                'apto' => $request->boolean('apto'),
                'descripcion_aptitud' => $request->descripcion_aptitud,
            ];

            // Guardamos el diploma físico y creamos el registro en el módulo Documentos
            if ($request->hasFile('archivo_diploma')) {
                $file = $request->file('archivo_diploma');

                // Lo guardamos en su ruta original para mantener compatibilidad con RRHH
                $path = $file->store('diplomas', 'public');
                $pivotData['archivo_diploma'] = $path;

                // Determinar el proyecto/sede adecuado del trabajador con fallback dinámico
                $user = auth()->user();
                $trabajadorProyectosIds = $personal->proyectos()->pluck('proyectos.id')->all();
                $proyectoId = null;

                if (!empty($trabajadorProyectosIds)) {
                    if ($user && $user->role !== 'superadmin') {
                        $misProyectosIds = $user->proyectos->pluck('id')->all();
                        $coincidentes = array_values(array_intersect($trabajadorProyectosIds, $misProyectosIds));
                        $proyectoId = $coincidentes[0] ?? $trabajadorProyectosIds[0];
                    } else {
                        $proyectoId = $trabajadorProyectosIds[0];
                    }
                }

                if (!$proyectoId) {
                    $proyectoId = $this->resolveProyectoForCorrelativo($request);
                }

                $nombreCompleto = trim($personal->name . ' ' . $personal->apellido);
                $nombrePuesto = optional($personal->puestoTrabajo)->nombre ?: ($personal->puesto ?: '—');

                Documento::create([
                    'proyecto_id' => $proyectoId,
                    'tipo' => 'certificados',
                    'cliente' => $nombreCompleto . ' (ID: ' . ($personal->id_rrhh ?? 'Sin ID') . ')',
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => basename($path),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'meta' => [
                        'nombre_trabajador' => $nombreCompleto,
                        'id_rrhh' => $personal->id_rrhh ?? '—',
                        'puesto' => $nombrePuesto,
                        'curso_id' => $cursoId,
                        'uploaded_at' => now()->toDateTimeString(),
                        'uploaded_by' => auth()->id(),
                    ],
                    'user_id' => auth()->id(),
                ]);
            }

            $personal->cursos()->syncWithoutDetaching([$cursoId => $pivotData]);
        });

        return response()->json(['success' => true, 'message' => 'Curso actualizado.']);
    }

    // 2. DESASIGNAR CURSO
    public function destroy(Request $request, Personal $personal, Curso $curso)
    {
        $this->authorize('cursos.edit');
        $this->authorizePersonalProyecto($personal);

        $personal->cursos()->detach($curso->id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Curso desasignado correctamente']);
        }

        return redirect()->back()->with('success', 'Curso desasignado correctamente.');
    }

    // 3. RENOVAR CURSO (Mueve el actual al histórico)
    public function renovar(Personal $personal, Curso $curso)
    {
        $this->authorize('cursos.edit');
        $this->authorizePersonalProyecto($personal);

        $cursoTrabajador = $personal->cursos()->where('cursos.id', $curso->id)->first();

        if ($cursoTrabajador && $cursoTrabajador->pivot->fecha_realizacion) {
            DB::transaction(function () use ($personal, $curso, $cursoTrabajador) {
                DB::table('historial_cursos')->insert([
                    'personal_id' => $personal->id,
                    'curso_id' => $curso->id,
                    'fecha_realizacion' => $cursoTrabajador->pivot->fecha_realizacion,
                    'apto' => (bool) $cursoTrabajador->pivot->apto,
                    'archivo_diploma' => $cursoTrabajador->pivot->archivo_diploma,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $personal->cursos()->updateExistingPivot($curso->id, [
                    'fecha_realizacion' => null,
                    'apto' => false,
                    'archivo_diploma' => null,
                    'descripcion_aptitud' => 'Renovación iniciada el ' . now()->format('d/m/Y'),
                ]);
            });

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'No hay datos para renovar'], 400);
    }

    // 4. VER HISTORIAL
    public function historial(Personal $personal, Curso $curso)
    {
        abort_unless(auth()->user()?->canAny(['cursos.view', 'cursos.edit']), 403);
        $this->authorizePersonalProyecto($personal);

        $cursoActual = $personal->cursos()->where('cursos.id', $curso->id)->first();

        $actual = null;
        if ($cursoActual && $cursoActual->pivot->fecha_realizacion) {
            $actual = [
                'fecha' => Carbon::parse($cursoActual->pivot->fecha_realizacion)->format('d/m/Y'),
                'apto' => (bool) $cursoActual->pivot->apto,
                'estado' => 'Vigente (Actual)',
                'diploma_url' => $cursoActual->pivot->archivo_diploma
                    ? Storage::url($cursoActual->pivot->archivo_diploma)
                    : null,
            ];
        }

        $historialRaw = DB::table('historial_cursos')
            ->where('personal_id', $personal->id)
            ->where('curso_id', $curso->id)
            ->orderByDesc('fecha_realizacion')
            ->get();

        $historico = $historialRaw->map(function ($h) {
            return [
                'fecha' => Carbon::parse($h->fecha_realizacion)->format('d/m/Y'),
                'apto' => (bool) $h->apto,
                'estado' => 'Archivado',
                'diploma_url' => $h->archivo_diploma
                    ? Storage::url($h->archivo_diploma)
                    : null,
            ];
        });

        return response()->json(['actual' => $actual, 'historico' => $historico]);
    }
}