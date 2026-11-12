<?php

namespace App\Http\Controllers;

use App\Models\InventarioMovimiento;
use App\Services\InventarioStockService;
use Illuminate\Http\Request;
use Throwable;

class InventarioAccionController extends Controller
{
    public function __construct(private readonly InventarioStockService $stockService)
    {
        $this->middleware('auth');
        $this->middleware('permission:inventario.view')->only(['index', 'show']);
        $this->middleware('permission:inventario.manage')->only(['cancel']);
    }

    /**
     * Muestra el registro de acciones de inventario directamente desde el Kardex relacional.
     */
    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'salidas');
        $allowedTabs = ['salidas', 'entradas', 'traslados', 'logs'];
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'salidas';
        }

        $user = $request->user();
        $isAdmin = $user && ($user->role === 'admin' || $user->role === 'superadmin');
        if ($tab === 'logs' && !$isAdmin) {
            abort(403);
        }

        $proyectoId = $this->resolveActiveProyectoId($request);
        $solicitante = trim((string) $request->query('solicitante', ''));
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $perPage = 10;

        $query = InventarioMovimiento::query()
            ->where('proyecto_id', $proyectoId)
            ->with(['almacenOrigen', 'almacenDestino', 'personal', 'usuario', 'lineas.variante.articulo']);

        if ($solicitante !== '') {
            $query->where(function ($q) use ($solicitante) {
                $q->where('observaciones', 'like', '%' . $solicitante . '%')
                  ->orWhere('motivo', 'like', '%' . $solicitante . '%')
                  ->orWhere('numero_documento', 'like', '%' . $solicitante . '%')
                  ->orWhere('ot', 'like', '%' . $solicitante . '%')
                  ->orWhere('documento_referencia', 'like', '%' . $solicitante . '%')
                  ->orWhereHas('personal', function ($qp) use ($solicitante) {
                      $qp->where('name', 'like', '%' . $solicitante . '%')
                         ->orWhere('apellido', 'like', '%' . $solicitante . '%');
                  })
                  ->orWhereHas('usuario', function ($qu) use ($solicitante) {
                      $qu->where('name', 'like', '%' . $solicitante . '%');
                  });
            });
        }

        if (!empty($desde)) {
            $query->whereDate('created_at', '>=', $desde);
        }

        if (!empty($hasta)) {
            $query->whereDate('created_at', '<=', $hasta);
        }

        if ($tab === 'salidas') {
            $query->where('tipo', 'SALIDA')->where('estado', 'REGISTRADO');
            $tipo = 'salida';
        } elseif ($tab === 'entradas') {
            $query->where('tipo', 'ENTRADA')->where('estado', 'REGISTRADO');
            $tipo = 'entrada';
        } elseif ($tab === 'traslados') {
            $query->where('tipo', 'TRASLADO')->where('estado', 'REGISTRADO');
            $tipo = 'traslado';
        } else {
            $tipo = 'log';
        }

        $registros = $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (InventarioMovimiento $mov) {
                $tipoLower = mb_strtolower((string) $mov->tipo);
                $solicitanteNombre = $mov->personal
                    ? trim($mov->personal->name . ' ' . $mov->personal->apellido)
                    : ($mov->usuario?->name ?? 'Sistema');

                $estadoVista = $mov->estado === 'CANCELADO' ? 'cancelado' : 'aceptado';

                return (object) [
                    'tipo' => $tipoLower,
                    'numero' => $mov->numero_documento ?: ('MOV-' . $mov->id),
                    'fecha' => $mov->created_at,
                    'solicitante' => $solicitanteNombre,
                    'iniciales' => $this->makeIniciales($solicitanteNombre),
                    'ot' => $mov->ot,
                    'documento_referencia' => $mov->documento_referencia,
                    'almacen_origen' => $mov->almacenOrigen?->nombre ?? ($mov->almacenDestino?->nombre ?? '—'),
                    'almacen_actual' => $mov->almacenDestino?->nombre,
                    'estado' => $estadoVista,
                    'id' => $mov->id,
                ];
            });

        return view('inventario.acciones.index', compact('tab', 'registros', 'tipo', 'isAdmin'));
    }

    public function show(Request $request, string $tipo, int $id)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $mov = InventarioMovimiento::query()
            ->where('proyecto_id', $proyectoId)
            ->with(['almacenOrigen', 'almacenDestino', 'personal', 'usuario', 'lineas.variante.articulo'])
            ->findOrFail($id);

        $user = $request->user();
        $isAdmin = $user && ($user->role === 'admin' || $user->role === 'superadmin');
        if (!$isAdmin && $mov->estado === 'CANCELADO') {
            abort(403);
        }

        $solicitanteNombre = $mov->personal
            ? trim($mov->personal->name . ' ' . $mov->personal->apellido)
            : ($mov->usuario?->name ?? 'Sistema');

        // Adaptador para que la vista show.blade.php reciba las propiedades que espera
        $registro = (object) [
            'id' => $mov->id,
            'numero_salida' => $mov->numero_documento,
            'numero_entrada' => $mov->numero_documento,
            'numero_traslado' => $mov->numero_documento,
            'numero' => $mov->numero_documento,
            'fecha' => $mov->created_at,
            'solicitante' => $solicitanteNombre,
            'ot' => $mov->ot,
            'almacen_origen' => $mov->almacenOrigen?->nombre ?? ($mov->almacenDestino?->nombre ?? '—'),
            'almacen_actual' => $mov->almacenDestino?->nombre ?? '—',
            'observaciones' => $mov->observaciones ?? $mov->motivo,
            'estado' => $mov->estado === 'CANCELADO' ? 'cancelado' : 'aceptado',
            'documento_meta' => null,
        ];

        $items = $mov->lineas->map(function ($linea) use ($mov) {
            $variante = $linea->variante;
            $articulo = $variante?->articulo;
            $esObsoleta = $variante && (!$variante->activo || str_contains((string) $variante->descripcion, '[OBSOLETO]'));

            return [
                'inventario_id' => $linea->variante_id,
                'codigo' => $variante?->sku ?? $variante?->codigo ?? 'S/C',
                'descripcion' => $variante?->nombre ?: ($articulo?->nombre ?: $articulo?->descripcion ?: 'Ítem'),
                'cantidad' => (int) $linea->cantidad,
                'almacen_origen' => $mov->almacenOrigen?->nombre ?? '—',
                'almacen_actual' => $mov->almacenDestino?->nombre ?? '—',
                'activo' => (bool) ($variante?->activo ?? true),
                'obsoleto' => $esObsoleta,
            ];
        })->all();

        return view('inventario.acciones.show', [
            'tipo' => mb_strtolower((string) $mov->tipo),
            'registro' => $registro,
            'items' => $items,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function cancel(Request $request, string $tipo, int $id)
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'superadmin'], true), 403);

        $proyectoId = $this->resolveActiveProyectoId($request);

        $mov = InventarioMovimiento::query()
            ->with(['lineas.variante.articulo.clase', 'almacenOrigen', 'almacenDestino', 'personal'])
            ->where('proyecto_id', $proyectoId)
            ->findOrFail($id);

        if ($mov->estado === 'CANCELADO') {
            return back()->with('success', 'El movimiento ya estaba cancelado.');
        }

        try {
            $this->stockService->revertir($mov);

            // Enriquecer el log de Spatie con el detalle exacto de los artículos y clases afectados
            if (class_exists(\Spatie\Activitylog\Models\Activity::class) && method_exists($mov, 'obtenerResumenLineasAuditoria')) {
                $resumenArticulos = $mov->obtenerResumenLineasAuditoria();
                $clasesInvolucradas = $mov->obtenerClasesInvolucradasAuditoria();
                $totalUds = (int) $mov->lineas->sum('cantidad'); // <-- Corregido de $movimiento a $mov

                activity('inventario')
                    ->performedOn($mov)
                    ->causedBy($request->user())
                    ->event('updated')
                    ->withProperties([
                        'attributes' => [
                            'numero_documento' => $mov->numero_documento,
                            'tipo' => strtoupper($tipo),
                            'estado' => 'CANCELADO',
                            'clases_afectadas' => $clasesInvolucradas,
                            'total_unidades_revertidas' => $totalUds . ' uds.',
                            'articulos_afectados' => $resumenArticulos,
                        ],
                        'old' => [
                            'estado' => 'REGISTRADO',
                        ],
                    ])
                    ->log("Anulación de movimiento {$tipo} {$mov->numero_documento} y reversión de stock");
            }

        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('inventario.acciones.index', ['tab' => $request->query('tab', 'salidas')])
            ->with('success', 'Movimiento cancelado en el Kardex y saldos revertidos.');
    }

    private function makeIniciales(?string $nombre): string
    {
        $value = trim((string) ($nombre ?? ''));
        if ($value === '') {
            return '—';
        }

        $iniciales = collect(preg_split('/\s+/', $value))
            ->filter()
            ->map(fn ($part) => mb_substr((string) $part, 0, 1))
            ->take(2)
            ->join('');

        return $iniciales !== '' ? mb_strtoupper($iniciales) : '—';
    }
}