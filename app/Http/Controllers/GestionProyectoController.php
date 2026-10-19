<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GestionProyectoController extends Controller
{
    /**
     * Muestra los proyectos en formato tarjetas para superadmins.
     */
    public function index(): View
    {
        $proyectos = Proyecto::query()
            ->withCount('usuarios')
            ->orderBy('nombre')
            ->get();

        return view('proyecto.index', compact('proyectos'));
    }

    /**
     * Muestra la pantalla para crear un proyecto.
     */
    public function create(): View
    {
        return view('proyecto.create');
    }

    /**
     * Muestra el detalle completo de un proyecto.
     */
    public function show(Proyecto $proyecto): View
    {
        $proyecto->load([
            'usuarios' => fn ($query) => $query->orderBy('name'),
        ])->loadCount('usuarios');

        return view('proyecto.show', compact('proyecto'));
    }

    /**
     * Muestra la pantalla para editar un proyecto.
     */
    public function edit(Proyecto $proyecto): View
    {
        return view('proyecto.edit', compact('proyecto'));
    }

    /**
     * Guarda un proyecto nuevo.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:proyectos,nombre'],
            'localizacion' => ['required', 'string', 'max:255'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        $data = [
            'nombre' => $validated['nombre'],
            'localizacion' => $validated['localizacion'],
            'imagen' => null,
        ];

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('proyectos', 'public');
        }

        $proyecto = Proyecto::create($data);
        User::where('role', 'superadmin')->each(
            fn (User $user) => $user->proyectos()->syncWithoutDetaching([$proyecto->id])
        );

        return redirect()
            ->route('herramientas.proyectos.index')
            ->with('success', 'Proyecto creado correctamente.');
    }

    /**
     * Actualiza un proyecto existente.
     */
    public function update(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:proyectos,nombre,' . $proyecto->id],
            'localizacion' => ['required', 'string', 'max:255'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'eliminar_imagen' => ['nullable', 'boolean'],
        ]);

        $data = [
            'nombre' => $validated['nombre'],
            'localizacion' => $validated['localizacion'],
            'imagen' => $proyecto->imagen,
        ];

        if ($request->boolean('eliminar_imagen')) {
            $storagePath = $this->toStorageRelativePath($proyecto->imagen);

            if ($storagePath) {
                Storage::disk('public')->delete($storagePath);
            }

            $data['imagen'] = null;
        }

        if ($request->hasFile('imagen')) {
            $storagePath = $this->toStorageRelativePath($proyecto->imagen);

            if ($storagePath) {
                Storage::disk('public')->delete($storagePath);
            }

            $data['imagen'] = $request->file('imagen')->store('proyectos', 'public');
        }

        $proyecto->update($data);

        return redirect()
            ->route('herramientas.proyectos.show', $proyecto)
            ->with('success', 'Proyecto actualizado correctamente.');
    }

    /**
     * Convierte una ruta de imagen al formato relativo del disco public.
     */
    private function toStorageRelativePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        return str_starts_with($path, 'storage/')
            ? ltrim(substr($path, strlen('storage/')), '/')
            : ltrim($path, '/');
    }
}
