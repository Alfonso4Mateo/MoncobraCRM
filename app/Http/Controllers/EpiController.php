<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEpiRequest;
use App\Http\Requests\UpdateEpiRequest;
use App\Models\Epi;
use App\Models\Puesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EpiController extends Controller
{
    /**
     * Categorías sugeridas por defecto en el datalist del formulario,
     * además de las que ya existan realmente en la base de datos.
     * Es una lista abierta: el usuario puede escribir cualquier otra.
     */
    private const CATEGORIAS_SUGERIDAS = [
        'Protección Craneal',
        'Protección Ocular y Facial',
        'Protección Auditiva',
        'Protección Respiratoria',
        'Protección de Manos y Brazos',
        'Protección de Pies y Piernas',
        'Ropa de Trabajo / Vestuario',
        'Trabajos en Altura',
        'Otros',
    ];

    public function __construct()
    {
        // Blindaje: Solo usuarios autenticados pueden acceder a este controlador
        $this->middleware('auth');
    }

    public function index(): View
    {
        // Cargamos los EPIs. withCount para el badge de "X puestos",
        // y with('puestos') para poder precargar el modal de edición
        // con los puestos y cantidades ya asignados sin hacer AJAX extra.
        $epis = Epi::withCount('puestos')
            ->with('puestos')
            ->orderBy('nombre')
            ->get();

        // Agrupamos por categoría para pintar el catálogo por secciones
        $episPorCategoria = $epis
            ->groupBy(fn ($epi) => $epi->categoria ?: 'Sin categoría')
            ->sortKeys();

        // Puestos para el selector de vinculación (crear/editar)
        $puestos = Puesto::where('activo', true)->orderBy('nombre')->get();

        // Categorías para el datalist: las que ya existen + las sugeridas por defecto
        $categoriasDisponibles = $epis->pluck('categoria')
            ->filter()
            ->unique()
            ->merge(self::CATEGORIAS_SUGERIDAS)
            ->unique()
            ->sort()
            ->values();

        return view('epis.index', compact('episPorCategoria', 'puestos', 'categoriasDisponibles'));
    }

    public function store(StoreEpiRequest $request)
    {
        $validated = $request->validated();

        // Envolvemos en transacción: si falla el sync() de puestos a mitad,
        // no queremos que quede el EPI creado pero mal vinculado.
        $epi = DB::transaction(function () use ($validated) {
            $epi = Epi::create([
                'nombre' => mb_strtoupper($validated['nombre']),
                'descripcion' => $validated['descripcion'] ?? null,
                'categoria' => $validated['categoria'] ?? null,
                // Se crea siempre activo: el alta no tiene control para marcarlo inactivo.
                'activo' => true,
            ]);

            if (!empty($validated['puestos'])) {
                $syncData = [];
                foreach ($validated['puestos'] as $puestoId) {
                    $cantidad = $validated['cantidades'][$puestoId] ?? 1;
                    $syncData[$puestoId] = ['cantidad' => $cantidad];
                }
                $epi->puestos()->sync($syncData);
            }

            return $epi;
        });

        $mensaje = 'EPI creado y vinculado a los perfiles exitosamente.';

        if ($request->wantsJson()) {
            return $this->jsonCardResponse($epi, $mensaje);
        }

        return redirect()->route('epis.index')->with('success', $mensaje);
    }

    public function update(UpdateEpiRequest $request, Epi $epi)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $epi) {
            $epi->update([
                'nombre' => mb_strtoupper($validated['nombre']),
                'descripcion' => $validated['descripcion'] ?? null,
                'categoria' => $validated['categoria'] ?? null,
            ]);

            // Re-sincronizamos la vinculación a puestos igual que en el alta.
            // Si el usuario desmarca un puesto en la edición, sync() lo desvincula.
            $syncData = [];
            if (!empty($validated['puestos'])) {
                foreach ($validated['puestos'] as $puestoId) {
                    $cantidad = $validated['cantidades'][$puestoId] ?? 1;
                    $syncData[$puestoId] = ['cantidad' => $cantidad];
                }
            }
            $epi->puestos()->sync($syncData);
        });

        $mensaje = 'EPI actualizado correctamente.';

        if ($request->wantsJson()) {
            return $this->jsonCardResponse($epi, $mensaje);
        }

        return redirect()->route('epis.index')->with('success', $mensaje);
    }

    /**
     * Da de baja o reactiva un EPI (toggle del flag 'activo').
     * Un EPI dado de baja deja de aparecer como disponible para asignar
     * a nuevos puestos, pero no se borra ni se desvincula de los puestos
     * que ya lo tenían asignado.
     */
    public function toggleActivo(Request $request, Epi $epi)
    {
        $epi->update(['activo' => !$epi->activo]);

        $mensaje = $epi->activo
            ? 'EPI reactivado correctamente.'
            : 'EPI dado de baja. Ya no aparecerá disponible para asignar a nuevos puestos.';

        if ($request->wantsJson()) {
            return $this->jsonCardResponse($epi, $mensaje);
        }

        return redirect()->route('epis.index')->with('success', $mensaje);
    }

    public function destroy(Request $request, Epi $epi)
    {
        // Medida de seguridad: no dejamos borrar un EPI si sigue vinculado a algún puesto.
        if ($epi->puestos()->exists()) {
            $mensaje = 'No se puede eliminar: este EPI está vinculado a uno o más puestos. Quítalo de esos puestos o dalo de baja en su lugar.';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $mensaje], 422);
            }

            return redirect()->route('epis.index')->with('error', $mensaje);
        }

        $epi->delete();
        $mensaje = 'EPI eliminado correctamente.';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $mensaje]);
        }

        return redirect()->route('epis.index')->with('success', $mensaje);
    }

    /**
     * Respuesta JSON común para store/update/toggleActivo: devuelve el HTML
     * ya renderizado de la tarjeta, para que el frontend lo inserte/reemplace
     * sin recargar la página.
     */
    private function jsonCardResponse(Epi $epi, string $mensaje)
    {
        $epi->load('puestos');
        $epi->loadCount('puestos');

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'categoria' => $epi->categoria ?: 'Sin categoría',
            'html' => view('epis.card', ['epi' => $epi])->render(),
        ]);
    }
}