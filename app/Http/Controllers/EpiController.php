<?php

namespace App\Http\Controllers;

use App\Models\Epi;
use App\Models\Puesto;
use Illuminate\Http\Request;

class EpiController extends Controller
{
    public function index()
    {
        $epis = Epi::all();
        return view('epis.index', compact('epis'));
    }

    public function create()
    {
        $puestos = Puesto::all();
        return view('epis.create', compact('puestos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'activo' => 'required|boolean',
            'puestos' => 'nullable|array',
            'puestos.*' => 'exists:puestos,id',
        ]);

        $epi = Epi::create($validated);

        if (isset($validated['puestos'])) {
            $epi->puestos()->sync($validated['puestos']);
        }

        return redirect()->route('epis.index')->with('success', 'EPI creado exitosamente.');
    }
}