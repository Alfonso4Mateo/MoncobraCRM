<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlmacenController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        return view('almacenes.create', [
            'proyectoId' => $proyectoId,
        ]);
    }

    public function store(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'nombre_almacen' => [
                'required',
                'string',
                'max:255',
                Rule::unique('almacenes', 'nombre')->where(fn ($q) => $q->where('proyecto_id', $proyectoId)),
            ],
            'descripcion_almacen' => 'nullable|string|max:2000',
        ]);

        $almacen = Almacen::create([
            'proyecto_id' => $proyectoId,
            'nombre' => $validated['nombre_almacen'],
            'descripcion' => $validated['descripcion_almacen'] ?? null,
        ]);

        return redirect()
            ->route('almacenes.create')
            ->with('success', 'Almacén creado correctamente: ' . $almacen->nombre);
    }
}