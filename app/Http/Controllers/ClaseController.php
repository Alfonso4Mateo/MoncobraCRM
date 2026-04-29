<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Inventario;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorizeAdminOrSuperadmin();
        
        $proyectoId = $this->resolveActiveProyectoId(request());
        
        $clases = Clase::query()
            ->where('proyecto_id', $proyectoId)
            ->withCount('inventarios')
            ->orderBy('nombre')
            ->get();

        return view('clases.index', compact('clases', 'proyectoId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorizeAdminOrSuperadmin();
        
        $proyectoId = $this->resolveActiveProyectoId(request());

        return view('clases.create', compact('proyectoId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeAdminOrSuperadmin();
        
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:clases,nombre,NULL,id,proyecto_id,' . $proyectoId,
        ]);

        Clase::create([
            'proyecto_id' => $proyectoId,
            'nombre' => $validated['nombre'],
        ]);

        return redirect()
            ->route('clases.index')
            ->with('success', 'Clase creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Clase $clase)
    {
        $this->authorizeAdminOrSuperadmin();
        
        // Opcional: mostrar detalles de la clase
        $inventarios = $clase->inventarios()->paginate(15);

        return view('clases.show', compact('clase', 'inventarios'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Clase $clase)
    {
        $this->authorizeAdminOrSuperadmin();

        return view('clases.edit', compact('clase'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Clase $clase)
    {
        $this->authorizeAdminOrSuperadmin();

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:clases,nombre,' . $clase->id . ',id,proyecto_id,' . $clase->proyecto_id,
        ]);

        $clase->update($validated);

        return redirect()
            ->route('clases.index')
            ->with('success', 'Clase actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Clase $clase)
    {
        $this->authorizeAdminOrSuperadmin();

        // Verificar que no hay items asociados
        if ($clase->inventarios()->exists()) {
            return redirect()
                ->route('clases.index')
                ->with('error', 'No se puede eliminar la clase porque hay items de inventario asociados.');
        }

        $clase->delete();

        return redirect()
            ->route('clases.index')
            ->with('success', 'Clase eliminada exitosamente.');
    }

    /**
     * Authorize only admin and superadmin users.
     */
    private function authorizeAdminOrSuperadmin(): void
    {
        $user = auth()->user();
        
        if (!$user || !in_array($user->role, ['admin', 'superadmin'])) {
            abort(403, 'No tienes permiso para acceder a esta función.');
        }
    }
}

