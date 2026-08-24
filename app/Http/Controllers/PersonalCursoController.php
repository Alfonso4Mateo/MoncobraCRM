<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Personal;
use App\Models\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PersonalCursoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // 1. ASIGNAR / ACTUALIZAR / SUBIR DIPLOMA
    public function update(Request $request, Personal $personal)
    {
        $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'fecha_realizacion' => 'nullable|date',
            'apto' => 'required|boolean',
            'descripcion_aptitud' => 'nullable|string',
            'archivo_diploma' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB
        ]);

        $cursoId = $request->curso_id;

        $pivotData = [
            'fecha_realizacion' => $request->fecha_realizacion,
            'apto' => $request->apto,
            'descripcion_aptitud' => $request->descripcion_aptitud,
        ];

        // Guardamos el diploma físico y creamos el registro en el módulo Documentos
        if ($request->hasFile('archivo_diploma')) {
            $file = $request->file('archivo_diploma');
            
            // Lo guardamos en su ruta original para no romper el historial de RRHH
            $path = $file->store('diplomas', 'public');
            $pivotData['archivo_diploma'] = $path;

            // --- INYECCIÓN EN EL MÓDULO DE DOCUMENTOS ---
            // Recuperamos el proyecto/sede del trabajador (o usamos 1 por defecto si no lo tiene)
            // Asegúrate de que esto coincida con la lógica de sedes de tu aplicación
            $proyectoId = $personal->proyectos()->first()->id ?? 1; 

            Documento::create([
                'proyecto_id' => $proyectoId,
                'tipo' => 'certificados',
                'cliente' => trim($personal->name . ' ' . $personal->apellido) . ' (ID: ' . ($personal->id_rrhh ?? 'Sin ID') . ')',
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => basename($path),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'meta' => [
                    'nombre_trabajador' => trim($personal->name . ' ' . $personal->apellido),
                    'id_rrhh' => $personal->id_rrhh ?? '—',
                    'puesto' => $personal->puesto ?? '—',
                    'curso_id' => $cursoId,
                    'uploaded_at' => now()->toDateTimeString(),
                    'uploaded_by' => auth()->id(),
                ],
                'user_id' => auth()->id(),
            ]);
        }

        $personal->cursos()->syncWithoutDetaching([$cursoId => $pivotData]);

        return response()->json(['success' => true, 'message' => 'Curso actualizado.']);
    }

    // 2. DESASIGNAR CURSO
    public function destroy(Request $request, Personal $personal, Curso $curso)
    {
        $personal->cursos()->detach($curso->id);
        
        // Si la petición viene de Javascript (AJAX), devolvemos JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Curso desasignado correctamente']);
        }
        
        // Si viene del formulario tradicional (ficha trabajador), redirigimos
        return redirect()->back()->with('success', 'Curso desasignado correctamente.');
    }

    // 3. RENOVAR CURSO (Mueve el actual al histórico)
    public function renovar(Personal $personal, Curso $curso)
    {
        $cursoTrabajador = $personal->cursos()->where('cursos.id', $curso->id)->first();

        if ($cursoTrabajador && $cursoTrabajador->pivot->fecha_realizacion) {
            
            DB::table('historial_cursos')->insert([
                'personal_id' => $personal->id,
                'curso_id' => $curso->id,
                'fecha_realizacion' => $cursoTrabajador->pivot->fecha_realizacion,
                'apto' => $cursoTrabajador->pivot->apto,
                'archivo_diploma' => $cursoTrabajador->pivot->archivo_diploma,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $personal->cursos()->updateExistingPivot($curso->id, [
                'fecha_realizacion' => null,
                'apto' => false,
                'archivo_diploma' => null, 
                'descripcion_aptitud' => 'Renovación iniciada el ' . now()->format('d/m/Y')
            ]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'No hay datos para renovar'], 400);
    }

    // 4. VER HISTORIAL
    // 4. VER HISTORIAL
    public function historial(Personal $personal, Curso $curso)
    {
        $cursoActual = $personal->cursos()->where('cursos.id', $curso->id)->first();
        
        $actual = null;
        if ($cursoActual && $cursoActual->pivot->fecha_realizacion) {
            $actual = [
                'fecha' => Carbon::parse($cursoActual->pivot->fecha_realizacion)->format('d/m/Y'),
                'apto' => (bool) $cursoActual->pivot->apto,
                'estado' => 'Vigente (Actual)',
                // NUEVO: Enviamos la URL del diploma si existe
                'diploma_url' => $cursoActual->pivot->archivo_diploma ? \Illuminate\Support\Facades\Storage::url($cursoActual->pivot->archivo_diploma) : null,
            ];
        }

        $historialRaw = DB::table('historial_cursos')
            ->where('personal_id', $personal->id)
            ->where('curso_id', $curso->id)
            ->orderByDesc('fecha_realizacion')
            ->get();

        $historico = $historialRaw->map(function($h) {
            return [
                'fecha' => Carbon::parse($h->fecha_realizacion)->format('d/m/Y'),
                'apto' => (bool) $h->apto,
                'estado' => 'Archivado',
                // NUEVO: Enviamos la URL del diploma histórico
                'diploma_url' => $h->archivo_diploma ? \Illuminate\Support\Facades\Storage::url($h->archivo_diploma) : null,
            ];
        });

        return response()->json(['actual' => $actual, 'historico' => $historico]);
    }
}