<?php

namespace App\Http\Controllers;

use App\Models\PuestoTrabajo;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestión de "Puestos de Trabajo" dentro del módulo de Personal.
 * Cada puesto define la periodicidad (en meses) del reconocimiento médico,
 * que luego se usa para calcular automáticamente la próxima revisión
 * médica de cada trabajador según el puesto que tenga asignado.
 */
class PuestoTrabajoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $puestosTrabajo = PuestoTrabajo::withCount('personal')
            ->orderBy('nombre')
            ->get();

        return view('personal.puestos', compact('puestosTrabajo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:puestos_trabajo,nombre',
            'periodicidad_meses' => 'nullable|integer|min:1|max:120',
        ]);

        PuestoTrabajo::create([
            'nombre' => mb_strtoupper($validated['nombre']),
            'periodicidad_meses' => $validated['periodicidad_meses'] ?? null,
            'activo' => true,
        ]);

        return redirect()->route('personal.puestos-trabajo.index')
            ->with('success', 'Puesto de trabajo creado correctamente.');
    }

    public function update(Request $request, PuestoTrabajo $puestoTrabajo)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:puestos_trabajo,nombre,' . $puestoTrabajo->id,
            'periodicidad_meses' => 'nullable|integer|min:1|max:120',
        ]);

        // AQUÍ ESTÁ LA MAGIA: Forzamos la conversión a entero (int)
        $mesesNuevos = isset($validated['periodicidad_meses']) ? (int) $validated['periodicidad_meses'] : null;
        $mesesAnteriores = $puestoTrabajo->periodicidad_meses;

        // Actualizamos el puesto
        $puestoTrabajo->update([
            'nombre' => mb_strtoupper($validated['nombre']),
            'periodicidad_meses' => $mesesNuevos,
        ]);

        // Si cambió la periodicidad y no es nula, propagamos el cambio a los automáticos
        if ($mesesNuevos && $mesesNuevos !== $mesesAnteriores) {
            $trabajadores = $puestoTrabajo->personal()
                ->whereNotNull('ultima_revision_medica')
                ->where('revision_medica_manual', false)
                ->get();

            foreach ($trabajadores as $trabajador) {
                $nuevaProxima = \Carbon\Carbon::parse($trabajador->ultima_revision_medica)
                    ->addMonths($mesesNuevos) // Ahora ya recibe un número perfecto
                    ->format('Y-m-d');

                $trabajador->update([
                    'proxima_revision_medica' => $nuevaProxima,
                ]);
            }
        }

        return redirect()->route('personal.puestos-trabajo.index')
            ->with('success', 'Puesto de trabajo y revisiones automáticas asociadas actualizados correctamente.');
    }

    public function destroy(PuestoTrabajo $puestoTrabajo)
    {
        // No dejamos borrar un puesto si hay trabajadores usándolo
        if ($puestoTrabajo->personal()->exists()) {
            return redirect()->route('personal.puestos-trabajo.index')
                ->with('error', 'No se puede eliminar: hay trabajadores con este puesto de trabajo asignado.');
        }

        $puestoTrabajo->delete();

        return redirect()->route('personal.puestos-trabajo.index')
            ->with('success', 'Puesto de trabajo eliminado correctamente.');
    }
}
