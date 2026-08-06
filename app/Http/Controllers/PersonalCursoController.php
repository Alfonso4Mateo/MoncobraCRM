<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Personal;
use Illuminate\Http\Request;

class PersonalCursoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function update(Request $request, Personal $personal)
    {
        $validated = $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'fecha_realizacion' => 'nullable|date',
            'apto' => 'nullable|boolean',
            'descripcion_aptitud' => 'nullable|string|max:2000',
        ]);

        $cursoId = (int) $validated['curso_id'];
        $pivotData = [
            'fecha_realizacion' => $validated['fecha_realizacion'] ?? null,
            'apto' => $request->boolean('apto'),
            'descripcion_aptitud' => $validated['descripcion_aptitud'] ?? null,
        ];

        if ($personal->cursos()->whereKey($cursoId)->exists()) {
            $personal->cursos()->updateExistingPivot($cursoId, $pivotData);
        } else {
            $personal->cursos()->attach($cursoId, $pivotData);
        }

        // CAMBIO CLAVE: Devolvemos JSON en lugar de usar redirect()
        return response()->json(['message' => 'Curso asignado o actualizado correctamente']);
    }

    public function destroy(Personal $personal, Curso $curso)
    {
        $personal->cursos()->detach($curso->id);

        // CAMBIO CLAVE: Devolvemos JSON en lugar de usar redirect()
        return response()->json(['message' => 'Curso desasignado correctamente']);
    }
}