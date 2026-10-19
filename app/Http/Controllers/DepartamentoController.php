<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\EquipoInformatico;
use App\Models\HerramientaPlanta;
use App\Models\AparatoCalibrable;
use App\Models\Personal;
use Illuminate\Http\Request;

class DepartamentoController extends Controller
{
    // Método para crear (el botón +)
    public function store(Request $request)
    {
        // Validamos que venga un nombre y no esté repetido
        $request->validate(['nombre' => 'required|string|unique:departamentos,nombre']);
        
        // Lo creamos guardándolo en minúsculas por coherencia
        $depto = Departamento::create(['nombre' => mb_strtolower(trim($request->nombre), 'UTF-8')]);
        
        // Respondemos a JavaScript que todo ha ido bien y le mandamos el departamento
        return response()->json(['success' => true, 'departamento' => $depto]);
    }

    // Método para borrar (el botón -)
    public function destroy($nombre)
    {
        $enUso = EquipoInformatico::where('departamento', $nombre)->exists()
            || HerramientaPlanta::where('departamento', $nombre)->exists()
            || AparatoCalibrable::where('departamento', $nombre)->exists()
            || Personal::whereJsonContains('departamento', $nombre)->exists()
            || Personal::where('departamento', $nombre)->exists();
        if ($enUso) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar el departamento porque está en uso.'], 422);
        }

        Departamento::where('nombre', $nombre)->delete();
        
        return response()->json(['success' => true]);
    }
}