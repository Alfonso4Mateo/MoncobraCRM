<?php

namespace App\Http\Controllers;

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
        $claseHerramienta->delete();
        return response()->json(['success' => true]);
    }
}