<?php

namespace App\Http\Controllers;

use App\Models\EntradaStock;
use App\Models\Inventario;
use App\Models\SalidaStock;
use App\Models\TrasladoStock;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventarioAccionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Muestra el registro de acciones de inventario según la pestaña (Salidas, Entradas, Traslados, Logs).
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

        $applyFilters = function ($query) use ($proyectoId, $solicitante, $desde, $hasta) {
            $query->where('proyecto_id', $proyectoId);

            if ($solicitante !== '') {
                $query->where('solicitante', 'like', '%' . $solicitante . '%');
            }

            if (!empty($desde)) {
                $query->whereDate('fecha', '>=', $desde);
            }

            if (!empty($hasta)) {
                $query->whereDate('fecha', '<=', $hasta);
            }

            return $query;
        };

        if ($tab === 'salidas') {
            $registros = $applyFilters(SalidaStock::query())
                ->where('estado', 'aceptado')
                ->orderByDesc('fecha')
                ->paginate($perPage)
                ->withQueryString()
                ->through(function (SalidaStock $row) {
                    return (object) [
                        'tipo' => 'salida',
                        'numero' => $row->numero_salida,
                        'fecha' => $row->fecha,
                        'solicitante' => $row->solicitante,
                        'iniciales' => $this->makeIniciales($row->solicitante),
                        'ot' => $row->ot,
                        'almacen_origen' => $row->almacen_origen,
                        'almacen_actual' => null,
                        'estado' => $row->estado,
                        'id' => $row->id,
                    ];
                });

            $tipo = 'salida';
        } elseif ($tab === 'entradas') {
            $registros = $applyFilters(EntradaStock::query())
                ->where('estado', 'aceptado')
                ->orderByDesc('fecha')
                ->paginate($perPage)
                ->withQueryString()
                ->through(function (EntradaStock $row) {
                    return (object) [
                        'tipo' => 'entrada',
                        'numero' => $row->numero_entrada,
                        'fecha' => $row->fecha,
                        'solicitante' => $row->solicitante,
                        'iniciales' => $this->makeIniciales($row->solicitante),
                        'ot' => $row->ot,
                        'almacen_origen' => $row->almacen_origen,
                        'almacen_actual' => null,
                        'estado' => $row->estado,
                        'id' => $row->id,
                    ];
                });

            $tipo = 'entrada';
        } elseif ($tab === 'traslados') {
            $registros = $applyFilters(TrasladoStock::query())
                ->where('estado', 'aceptado')
                ->orderByDesc('fecha')
                ->paginate($perPage)
                ->withQueryString()
                ->through(function (TrasladoStock $row) {
                    return (object) [
                        'tipo' => 'traslado',
                        'numero' => $row->numero_traslado,
                        'fecha' => $row->fecha,
                        'solicitante' => $row->solicitante,
                        'iniciales' => $this->makeIniciales($row->solicitante),
                        'ot' => $row->ot,
                        'almacen_origen' => $row->almacen_origen,
                        'almacen_actual' => $row->almacen_actual,
                        'estado' => $row->estado,
                        'id' => $row->id,
                    ];
                });

            $tipo = 'traslado';
        } else {
            $page = max(1, (int) $request->query('page', 1));

            $salidas = $applyFilters(SalidaStock::query())->get()->map(fn ($row) => (object) [
                'tipo' => 'salida',
                'numero' => $row->numero_salida,
                'fecha' => $row->fecha,
                'solicitante' => $row->solicitante,
                'iniciales' => $this->makeIniciales($row->solicitante),
                'ot' => $row->ot,
                'almacen_origen' => $row->almacen_origen,
                'almacen_actual' => null,
                'estado' => $row->estado,
                'id' => $row->id,
            ]);

            $entradas = $applyFilters(EntradaStock::query())->get()->map(fn ($row) => (object) [
                'tipo' => 'entrada',
                'numero' => $row->numero_entrada,
                'fecha' => $row->fecha,
                'solicitante' => $row->solicitante,
                'iniciales' => $this->makeIniciales($row->solicitante),
                'ot' => $row->ot,
                'almacen_origen' => $row->almacen_origen,
                'almacen_actual' => null,
                'estado' => $row->estado,
                'id' => $row->id,
            ]);

            $traslados = $applyFilters(TrasladoStock::query())->get()->map(fn ($row) => (object) [
                'tipo' => 'traslado',
                'numero' => $row->numero_traslado,
                'fecha' => $row->fecha,
                'solicitante' => $row->solicitante,
                'iniciales' => $this->makeIniciales($row->solicitante),
                'ot' => $row->ot,
                'almacen_origen' => $row->almacen_origen,
                'almacen_actual' => $row->almacen_actual,
                'estado' => $row->estado,
                'id' => $row->id,
            ]);

            /** @var Collection $all */
            $all = $salidas->concat($entradas)->concat($traslados)
                ->sortByDesc(fn ($row) => $row->fecha)
                ->values();

            $total = $all->count();
            $slice = $all->slice(($page - 1) * $perPage, $perPage)->values();

            $registros = new LengthAwarePaginator(
                $slice,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            $tipo = 'log';
        }

        return view('inventario.acciones.index', compact('tab', 'registros', 'tipo', 'isAdmin'));
    }

    public function show(Request $request, string $tipo, int $id)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $registro = $this->findRegistroOrFail($tipo, $id, $proyectoId);

        $user = $request->user();
        $isAdmin = $user && ($user->role === 'admin' || $user->role === 'superadmin');
        if (!$isAdmin && (string) $registro->estado === 'cancelado') {
            abort(403);
        }

        return view('inventario.acciones.show', [
            'tipo' => $tipo,
            'registro' => $registro,
            'items' => (array) ($registro->items ?? []),
        ]);
    }

    public function cancel(Request $request, string $tipo, int $id)
    {
        $proyectoId = $this->resolveActiveProyectoId($request);

        $registro = $this->findRegistroOrFail($tipo, $id, $proyectoId);

        if ((string) $registro->estado === 'cancelado') {
            return back()->with('success', 'El registro ya estaba cancelado.');
        }

        $items = collect((array) ($registro->items ?? []))
            ->filter(fn ($item) => is_array($item) || is_object($item))
            ->map(function ($item) {
                $data = (array) $item;
                return [
                    'inventario_id' => isset($data['inventario_id']) ? (int) $data['inventario_id'] : null,
                    'cantidad' => isset($data['cantidad']) ? (int) $data['cantidad'] : null,
                    'almacen_origen' => isset($data['almacen_origen']) ? (string) $data['almacen_origen'] : null,
                ];
            })
            ->filter(fn ($item) => !empty($item['inventario_id']))
            ->values();

        try {
            DB::transaction(function () use ($tipo, $registro, $items, $proyectoId) {
                if ($tipo === 'salida') {
                    foreach ($items as $item) {
                        $inv = Inventario::query()
                            ->where('proyecto_id', $proyectoId)
                            ->where('id', $item['inventario_id'])
                            ->lockForUpdate()
                            ->first();
                        if (!$inv) {
                            continue;
                        }

                        $inv->stock_actual = (int) $inv->stock_actual + (int) ($item['cantidad'] ?? 0);
                        $inv->save();
                    }
                } elseif ($tipo === 'entrada') {
                    foreach ($items as $item) {
                        $inv = Inventario::query()
                            ->where('proyecto_id', $proyectoId)
                            ->where('id', $item['inventario_id'])
                            ->lockForUpdate()
                            ->first();
                        if (!$inv) {
                            continue;
                        }

                        $newStock = (int) $inv->stock_actual - (int) ($item['cantidad'] ?? 0);
                        if ($newStock < 0) {
                            throw new \RuntimeException('No se puede cancelar la entrada porque dejaría stock negativo.');
                        }

                        $inv->stock_actual = $newStock;
                        $inv->save();
                    }
                } else {
                    foreach ($items as $item) {
                        $inv = Inventario::query()
                            ->where('proyecto_id', $proyectoId)
                            ->where('id', $item['inventario_id'])
                            ->lockForUpdate()
                            ->first();
                        if (!$inv) {
                            continue;
                        }

                        $almacenOrigen = (string) ($item['almacen_origen'] ?? '');
                        if ($almacenOrigen !== '') {
                            $inv->almacen = $almacenOrigen;
                            $inv->save();
                        }
                    }
                }

                $registro->estado = 'cancelado';
                $registro->save();
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('inventario.acciones.index', ['tab' => $request->query('tab', 'salidas')])
            ->with('success', 'Registro cancelado y cambios revertidos.');
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

    private function findRegistroOrFail(string $tipo, int $id, int $proyectoId): SalidaStock|EntradaStock|TrasladoStock
    {
        if ($tipo === 'salida') {
            return SalidaStock::query()->where('proyecto_id', $proyectoId)->findOrFail($id);
        }

        if ($tipo === 'entrada') {
            return EntradaStock::query()->where('proyecto_id', $proyectoId)->findOrFail($id);
        }

        return TrasladoStock::query()->where('proyecto_id', $proyectoId)->findOrFail($id);
    }
}
