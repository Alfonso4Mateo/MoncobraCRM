<?php

namespace App\Http\Controllers;

use App\Models\CentroCoste;
use Illuminate\Http\Request;

class CentroCosteController extends Controller
{
    public function index()
    {
        $centrosCoste = CentroCoste::orderBy('codigo')->get();
        return view('presupuestos.centros-costes', compact('centrosCoste'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:50|unique:centros_costes',
            'descripcion' => 'required|string|max:255',
        ]);

        CentroCoste::create($request->only('codigo', 'descripcion'));

        return redirect()->route('centros-costes.index')
                         ->with('success', 'Centro de coste añadido correctamente.');
    }

    public function destroy(CentroCoste $centroCoste)
    {
        $centroCoste->delete();
        return redirect()->route('centros-costes.index')
                         ->with('success', 'Centro de coste eliminado.');
    }
}