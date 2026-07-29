<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;

class DepartamentoController extends Controller
{
    // Método para crear (el botón +)
    public function store(Request $request)
    {
        // Validamos que venga un nombre y no esté repetido
        $request->validate(['nombre' => 'required|string|unique:departamentos,nombre']);
        
        // Lo creamos guardándolo en minúsculas por coherencia
        $depto = Departamento::create(['nombre' => strtolower($request->nombre)]);
        
        // Respondemos a JavaScript que todo ha ido bien y le mandamos el departamento
        return response()->json(['success' => true, 'departamento' => $depto]);
    }

    // Método para borrar (el botón -)
    public function destroy($nombre)
    {
        Departamento::where('nombre', $nombre)->delete();
        
        return response()->json(['success' => true]);
    }
}