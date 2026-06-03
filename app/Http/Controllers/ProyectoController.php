<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\User; // <-- ¡Añadido para poder consultar los usuarios!
use Illuminate\Http\Request;

class ProyectoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proyectos = Proyecto::with('usuarios')->paginate(10);
        return view('proyectos.index', compact('proyectos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('proyectos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:proyectos'],
            'localizacion' => ['required', 'string', 'max:255'],
        ]);

        Proyecto::create($validated);

        return redirect()->route('herramientas.proyectos.index')
                        ->with('success', 'Proyecto creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Proyecto $proyecto)
    {
        // Cargamos los usuarios actuales del proyecto
        $proyecto->load('usuarios');

        // Buscamos a los usuarios activos que AÚN NO están en este proyecto para el desplegable
        $availableUsers = User::whereDoesntHave('proyectos', function($query) use ($proyecto) {
            $query->where('proyectos.id', $proyecto->id);
        })->where('activo', true)->orderBy('name')->get();

        return view('proyecto.show', compact('proyecto', 'availableUsers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Proyecto $proyecto)
    {
        return view('proyecto.edit', compact('proyecto'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proyecto $proyecto)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:proyectos,nombre,' . $proyecto->id],
            'localizacion' => ['required', 'string', 'max:255'],
        ]);

        $proyecto->update($validated);

        return redirect()->route('herramientas.proyectos.show', $proyecto)
                        ->with('success', 'Proyecto actualizado correctamente.');
    }

    /**
     * Assign a user to the project.
     */
    public function assignUser(Request $request, Proyecto $proyecto)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        // Añadimos el usuario a la tabla intermedia sin borrar los que ya están
        $proyecto->usuarios()->syncWithoutDetaching([$request->user_id]);

        return redirect()->route('herramientas.proyectos.show', $proyecto)
                        ->with('success', 'Usuario asignado al proyecto correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proyecto $proyecto)
    {
        $proyecto->delete();

        return redirect()->route('herramientas.proyectos.index')
                        ->with('success', 'Proyecto eliminado correctamente.');
    }
}