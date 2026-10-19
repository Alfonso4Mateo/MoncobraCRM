<?php

namespace App\Http\Controllers;

use App\Models\EquipoInformatico;
use App\Models\HerramientaPlanta;
use App\Models\AparatoCalibrable;
use App\Models\ClaseHerramienta;
use Illuminate\Http\Request;

class ClaseHerramientaController extends Controller
{
    public function storeAjax(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:clases_herramientas,nombre'],
        ]);

        $clase = ClaseHerramienta::create(['nombre' => $request->nombre]);

        return response()->json([
            'id' => $clase->id,
            'nombre' => $clase->nombre
        ]);
    }

    public function destroyAjax(ClaseHerramienta $claseHerramienta)
    {
        $clase = $claseHerramienta->nombre;
        if (EquipoInformatico::where('clase', $clase)->exists()
            || HerramientaPlanta::where('clase', $clase)->exists()
            || AparatoCalibrable::where('clase', $clase)->exists()) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar la clase porque está en uso por equipos o herramientas.'], 422);
        }

        $claseHerramienta->delete();
        return response()->json(['success' => true]);
    }
}