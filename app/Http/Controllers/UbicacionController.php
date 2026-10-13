<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    public function storeAjax(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:ubicaciones,nombre'],
        ]);

        $ubicacion = Ubicacion::create(['nombre' => $request->nombre]);

        return response()->json([
            'id' => $ubicacion->id,
            'nombre' => $ubicacion->nombre
        ]);
    }

    public function destroyAjax(Ubicacion $ubicacion)
    {
        $ubicacion->delete();
        return response()->json(['success' => true]);
    }
}