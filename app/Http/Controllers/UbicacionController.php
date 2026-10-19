<?php

namespace App\Http\Controllers;

use App\Models\EquipoInformatico;
use App\Models\HerramientaPlanta;
use App\Models\AparatoCalibrable;
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
        if (EquipoInformatico::where('ubicacion_id', $ubicacion->id)->exists()
            || HerramientaPlanta::where('ubicacion_id', $ubicacion->id)->exists()
            || AparatoCalibrable::where('ubicacion_id', $ubicacion->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar la ubicación porque está asignada a equipos o herramientas.'], 422);
        }

        $ubicacion->delete();
        return response()->json(['success' => true]);
    }
}