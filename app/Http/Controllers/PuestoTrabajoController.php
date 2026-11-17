<?php

namespace App\Http\Controllers;

use App\Models\PuestoTrabajo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    private function getMisProyectosIds(): ?array
    {
        $user = auth()->user();
        if (!$user || $user->role === 'superadmin') {
            return null;
        }

        return $user->proyectos->pluck('id')->all();
    }

    private function authorizePuestoTrabajoProyecto(PuestoTrabajo $puestoTrabajo): void
    {
        $misProyectosIds = $this->getMisProyectosIds();
        if ($misProyectosIds === null) {
            return;
        }

        abort_unless(
            in_array((int) $puestoTrabajo->proyecto_id, array_map('intval', $misProyectosIds), true),
            403,
            'No tienes acceso a un puesto de trabajo de otra sede.'
        );
    }

    public function index(): View
    {
        $this->authorize('personal.edit');

        $proyectoId = $this->resolveProyectoForCorrelativo(request());

        $puestosTrabajo = PuestoTrabajo::where('proyecto_id', $proyectoId)
            ->withCount([
                'personal' => fn ($q) => $q->whereHas('proyectos', fn ($pq) => $pq->where('proyectos.id', $proyectoId)),
            ])
            ->orderBy('nombre')
            ->get();

        return view('personal.puestos', compact('puestosTrabajo'));
    }

    public function store(Request $request)
    {
        $this->authorize('personal.edit');

        $proyectoId = $this->resolveActiveProyectoId($request);

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('puestos_trabajo', 'nombre')->where(fn ($q) => $q->where('proyecto_id', $proyectoId)),
            ],
            'periodicidad_meses' => 'nullable|integer|min:1|max:120',
        ]);

        PuestoTrabajo::create([
            'proyecto_id' => $proyectoId,
            'nombre' => mb_strtoupper($validated['nombre']),
            'periodicidad_meses' => $validated['periodicidad_meses'] ?? null,
            'activo' => true,
        ]);

        return redirect()->route('personal.puestos-trabajo.index')
            ->with('success', 'Puesto de trabajo creado correctamente.');
    }

    public function update(Request $request, PuestoTrabajo $puestoTrabajo)
    {
        $this->authorize('personal.edit');
        $this->authorizePuestoTrabajoProyecto($puestoTrabajo);

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('puestos_trabajo', 'nombre')
                    ->where(fn ($q) => $q->where('proyecto_id', $puestoTrabajo->proyecto_id))
                    ->ignore($puestoTrabajo->id),
            ],
            'periodicidad_meses' => 'nullable|integer|min:1|max:120',
        ]);

        $mesesNuevos = isset($validated['periodicidad_meses']) ? (int) $validated['periodicidad_meses'] : null;
        $mesesAnteriores = $puestoTrabajo->periodicidad_meses;

        $puestoTrabajo->update([
            'nombre' => mb_strtoupper($validated['nombre']),
            'periodicidad_meses' => $mesesNuevos,
        ]);

        // Si cambió la periodicidad, propagamos a los trabajadores automáticos de esta sede (usando 12 meses si queda en blanco)
        $mesesEfectivos = $mesesNuevos ?? 12;
        if ($mesesNuevos !== $mesesAnteriores) {
            $trabajadores = $puestoTrabajo->personal()
                ->whereHas('proyectos', fn ($q) => $q->where('proyectos.id', $puestoTrabajo->proyecto_id))
                ->whereNotNull('ultima_revision_medica')
                ->where(function ($q) {
                    $q->where('revision_medica_manual', false)
                        ->orWhereNull('revision_medica_manual');
                })
                ->get();

            foreach ($trabajadores as $trabajador) {
                $nuevaProxima = \Carbon\Carbon::parse($trabajador->ultima_revision_medica)
                    ->addMonths($mesesEfectivos)
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
        $this->authorize('personal.edit');
        $this->authorizePuestoTrabajoProyecto($puestoTrabajo);

        if ($puestoTrabajo->personal()->exists()) {
            return redirect()->route('personal.puestos-trabajo.index')
                ->with('error', 'No se puede eliminar: hay trabajadores con este puesto de trabajo asignado.');
        }

        $puestoTrabajo->delete();

        return redirect()->route('personal.puestos-trabajo.index')
            ->with('success', 'Puesto de trabajo eliminado correctamente.');
    }
}