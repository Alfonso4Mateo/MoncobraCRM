<?php

namespace App\Http\Controllers;

use App\Models\CentroCoste;
use Illuminate\Http\Request;

class CentroCosteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('presupuestos.view');
        $centrosCoste = CentroCoste::orderBy('codigo')->get();
        return view('presupuestos.centros-costes', compact('centrosCoste'));
    }

    public function store(Request $request)
    {
        $this->authorize('presupuestos.manage');
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
        $this->authorize('presupuestos.manage');
        $centroCoste->delete();
        return redirect()->route('centros-costes.index')
                         ->with('success', 'Centro de coste eliminado.');
    }
}