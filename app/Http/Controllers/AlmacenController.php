<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\InventarioMovimiento;
use App\Models\InventarioStockAlmacen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlmacenController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:inventario.view')->only(['index']);
        $this->middleware('permission:inventario.manage')->except(['index']);
    }

    public function create(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        return view('almacenes.create', [
            'proyectoId' => $proyectoId,
        ]);
    }

    public function index(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);
        $almacenes = Almacen::query()
            ->where('proyecto_id', $proyectoId)
            ->withSum(['stocks as unidades' => fn ($query) => $query], 'cantidad')
            ->withCount('stocks')
            ->orderBy('nombre')
            ->paginate(15);

        return view('almacenes.index', compact('almacenes'));
    }

    public function edit(Request $request, Almacen $almacen)
    {
        abort_unless((int) $almacen->proyecto_id === $this->resolveActiveProyectoId($request), 404);

        return view('almacenes.edit', compact('almacen'));
    }

    public function update(Request $request, Almacen $almacen)
    {
        abort_unless((int) $almacen->proyecto_id === $this->resolveActiveProyectoId($request), 404);

        $validated = $request->validate([
            'nombre' => ['nullable', 'string', 'max:255'],
            'nombre_almacen' => ['nullable', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'activo' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'descripcion_almacen' => ['nullable', 'string', 'max:2000'],
        ]);

        $nombre = trim((string) ($validated['nombre'] ?? $validated['nombre_almacen'] ?? ''));

        validator(['nombre' => $nombre], [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('almacenes', 'nombre')
                    ->where(fn ($query) => $query->where('proyecto_id', $almacen->proyecto_id))
                    ->ignore($almacen->id),
            ],
        ])->validate();

        $almacen->update([
            'nombre' => $nombre,
            'codigo' => $validated['codigo'] ?? null,
            'descripcion' => $validated['descripcion'] ?? $validated['descripcion_almacen'] ?? null,
            'activo' => (bool) ($validated['activo'] ?? false),
        ]);

        return redirect()->route('almacenes.index')->with('success', 'Almacén actualizado correctamente.');
    }

    public function destroy(Request $request, Almacen $almacen)
    {
        abort_unless((int) $almacen->proyecto_id === $this->resolveActiveProyectoId($request), 404);

        if (InventarioStockAlmacen::where('almacen_id', $almacen->id)->where('cantidad', '>', 0)->exists()) {
            return back()->with('error', 'No se puede eliminar un almacén que todavía tiene existencias.');
        }

        $tieneHistorial = InventarioMovimiento::where('almacen_origen_id', $almacen->id)
            ->orWhere('almacen_destino_id', $almacen->id)
            ->exists();

        if ($tieneHistorial) {
            return back()->with('error', 'Este almacén tiene movimientos históricos en el Kardex. Edítalo y desmárcalo como activo en lugar de eliminarlo.');
        }

        $almacen->delete();

        return redirect()->route('almacenes.index')->with('success', 'Almacén eliminado correctamente.');
    }

    public function store(Request $request)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'nombre' => ['required_without:nombre_almacen', 'nullable', 'string', 'max:255'],
            'nombre_almacen' => ['nullable', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'activo' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'descripcion_almacen' => ['nullable', 'string', 'max:2000'],
        ]);

        $nombre = trim((string) ($validated['nombre'] ?? $validated['nombre_almacen'] ?? ''));

        validator(['nombre' => $nombre], [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('almacenes', 'nombre')
                    ->where(fn ($query) => $query->where('proyecto_id', $proyectoId)),
            ],
        ])->validate();

        $almacen = Almacen::create([
            'proyecto_id' => $proyectoId,
            'nombre' => $nombre,
            'codigo' => $validated['codigo'] ?? null,
            'descripcion' => $validated['descripcion'] ?? $validated['descripcion_almacen'] ?? null,
            'activo' => (bool) ($validated['activo'] ?? true),
        ]);

        return redirect()
            ->route('almacenes.index')
            ->with('success', 'Almacén creado correctamente: ' . $almacen->nombre);
    }
}