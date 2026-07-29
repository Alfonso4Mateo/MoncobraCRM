<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Clase;
use App\Models\Cliente;
use App\Models\AlbaranCliente;
use App\Models\Presupuesto;
use App\Models\Inventario;
use App\Models\Personal;
use App\Models\Proyecto;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index(): View
    {
        $today = now();
        $metrics = $this->collectDashboardMetrics($today);
        $dashboardPanels = $this->buildDashboardPanels($metrics);

        $dashboardPanels = $this->applyPanelOrder(
            $dashboardPanels,
            auth()->user()->dashboard_panel_order
        );

        return view('dashboard', [
            'dashboardPanels' => $dashboardPanels,
            'dashboardVersion' => '2.1.0',
        ]);
    }

    /**
     * Persist dashboard panel order for current user.
     */
    public function updatePanelOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'panel_order' => ['required', 'array', 'min:1'],
            'panel_order.*' => ['required', 'string', 'max:120'],
        ]);

        $availableIds = $this->availablePanelIds();

        $ordered = collect($validated['panel_order'])
            ->filter(fn (string $panelId): bool => in_array($panelId, $availableIds, true))
            ->values()
            ->all();

        $missing = array_values(array_diff($availableIds, $ordered));
        $finalOrder = array_values(array_unique(array_merge($ordered, $missing)));

        $request->user()->forceFill([
            'dashboard_panel_order' => $finalOrder,
        ])->save();

        return response()->json([
            'status' => 'ok',
            'panel_order' => $finalOrder,
        ]);
    }

    /**
     * Apply stored user order while keeping new panels at the end.
     *
     * @param array<int, array<string, mixed>> $panels
     * @param array<int, string>|null $storedOrder
     * @return array<int, array<string, mixed>>
     */
    private function applyPanelOrder(array $panels, ?array $storedOrder): array
    {
        if (empty($storedOrder) || !is_array($storedOrder)) {
            return $panels;
        }

        $panelMap = collect($panels)->keyBy('id');
        $sortedPanels = [];

        foreach ($storedOrder as $panelId) {
            if ($panelMap->has($panelId)) {
                $sortedPanels[] = $panelMap->get($panelId);
                $panelMap->forget($panelId);
            }
        }

        return array_merge($sortedPanels, $panelMap->values()->all());
    }

    /**
     * Get all current panel IDs available to users.
     *
     * @return array<int, string>
     */
    private function availablePanelIds(): array
    {
        return [
            'clientes',
            'presupuestos',
            'albaranes',
            'inventario',
            'pedidos',
            'almacenes',
            'documentos',
            'bolsa',
            'personal',
            'clases',
            'proyectos',
        ];
    }

    /**
     * @return array<string, int>
     */
    private function collectDashboardMetrics(Carbon $today): array
    {
        $countFromFirstAvailableTable = function (array $tables, ?callable $constraint = null): int {
            foreach ($tables as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                $query = DB::table($table);
                if ($constraint) {
                    $query = $constraint($query, $table);
                }

                return (int) $query->count();
            }

            return 0;
        };

        $todayConstraint = function ($query, string $table) use ($today) {
            if (Schema::hasColumn($table, 'created_at')) {
                return $query->whereDate('created_at', $today);
            }

            if (Schema::hasColumn($table, 'fecha')) {
                return $query->whereDate('fecha', $today);
            }

            return $query->whereRaw('1 = 0');
        };

        return [
            'totalClientes' => Cliente::count(),
            'clientesUltimos30Dias' => Cliente::where('created_at', '>=', now()->subDays(30))->count(),
            'totalPresupuestos' => Presupuesto::count(),
            'presupuestosUltimos30Dias' => Presupuesto::where('created_at', '>=', now()->subDays(30))->count(),
            'albaranesHoy' => AlbaranCliente::whereDate('created_at', $today)->count(),
            'albaranesMes' => AlbaranCliente::whereMonth('created_at', $today->month)
                ->whereYear('created_at', $today->year)
                ->count(),
            'totalInventario' => Inventario::count(),
            'stockBajo' => Inventario::whereColumn('stock_actual', '<=', 'stock_minimo')->count(),
            'totalClases' => Clase::count(),
            'totalAlmacenes' => Almacen::count(),
            'totalPersonal' => Personal::count(),
            'personalActivos' => Personal::where('activo', true)->count(),
            'totalPedidos' => $countFromFirstAvailableTable(['pedidos_clientes', 'pedidos']),
            'pedidosHoy' => $countFromFirstAvailableTable(['pedidos_clientes', 'pedidos'], $todayConstraint),
            'totalDocumentos' => $countFromFirstAvailableTable(['documentos']),
            'documentosHoy' => $countFromFirstAvailableTable(['documentos'], $todayConstraint),
            'totalProyectos' => Proyecto::count(),
            'asignacionesProyecto' => $countFromFirstAvailableTable(['proyecto_user']),
        ];
    }

    /**
     * @param array<string, int> $metrics
     * @return array<int, array<string, mixed>>
     */
    private function buildDashboardPanels(array $metrics): array
    {
        $dashboardPanels = [
            [
                'id' => 'clientes',
                'category' => 'CLIENTES',
                'title' => 'Area Clientes',
                'icon' => 'fas fa-users',
                'tone' => 'clientes',
                'description' => 'Gestion de empresas y contactos con acceso rapido al listado.',
                'route' => route('clientes.index'),
                'cta' => 'Gestionar clientes',
                'secondary_route' => route('clientes.create'),
                'secondary_text' => 'Nuevo cliente',
                'metrics' => [
                    ['label' => 'Total', 'value' => $metrics['totalClientes']],
                    ['label' => 'Nuevos 30d', 'value' => $metrics['clientesUltimos30Dias']],
                ],
            ],
            [
                'id' => 'presupuestos',
                'category' => 'VENTAS',
                'title' => 'Presupuestos',
                'icon' => 'fas fa-file-invoice-dollar',
                'tone' => 'presupuestos',
                'description' => 'Seguimiento de propuestas economicas emitidas y recientes.',
                'route' => route('presupuestos.index'),
                'cta' => 'Ver presupuestos',
                'secondary_route' => route('presupuestos.create'),
                'secondary_text' => 'Nuevo presupuesto',
                'metrics' => [
                    ['label' => 'Total', 'value' => $metrics['totalPresupuestos']],
                    ['label' => 'Ultimos 30d', 'value' => $metrics['presupuestosUltimos30Dias']],
                ],
            ],
            [
                'id' => 'albaranes',
                'category' => 'LOGISTICA',
                'title' => 'Albaranes',
                'icon' => 'fas fa-file-alt',
                'tone' => 'albaranes',
                'description' => 'Control de albaranes emitidos hoy y del mes actual.',
                'route' => route('albaranes.index'),
                'cta' => 'Ver albaranes',
                'secondary_route' => route('albaranes.create'),
                'secondary_text' => 'Nuevo albaran',
                'metrics' => [
                    ['label' => 'Emitidos hoy', 'value' => $metrics['albaranesHoy']],
                    ['label' => 'Este mes', 'value' => $metrics['albaranesMes']],
                ],
            ],
            [
                'id' => 'inventario',
                'category' => 'ALMACEN',
                'title' => 'Inventario',
                'icon' => 'fas fa-warehouse',
                'tone' => 'inventario',
                'description' => 'Vigilancia de stock critico, variantes dinamicas y acceso a altas rapidas.',
                'route' => route('inventario.index'),
                'cta' => 'Gestionar inventario',
                'secondary_route' => route('inventario.item.create'),
                'secondary_text' => 'Nuevo item',
                'metrics' => [
                    ['label' => 'Stock critico', 'value' => $metrics['stockBajo']],
                    ['label' => 'Productos', 'value' => $metrics['totalInventario']],
                ],
            ],
            [
                'id' => 'pedidos',
                'category' => 'PEDIDOS',
                'title' => 'Pedidos Cliente',
                'icon' => 'fas fa-dolly',
                'tone' => 'pedidos',
                'description' => 'Entrada directa al modulo de pedidos y sus emisiones.',
                'route' => route('pedidos-clientes.index'),
                'cta' => 'Ver pedidos',
                'secondary_route' => route('pedidos-clientes.create'),
                'secondary_text' => 'Nuevo pedido',
                'metrics' => [
                    ['label' => 'Total', 'value' => $metrics['totalPedidos']],
                    ['label' => 'Emitidos hoy', 'value' => $metrics['pedidosHoy']],
                ],
            ],
        ];

        $dashboardPanels[] = [
            'id' => 'almacenes',
            'category' => 'ALMACEN',
            'title' => 'Almacenes',
            'icon' => 'fas fa-warehouse',
            'tone' => 'clientes',
            'description' => 'Alta y administración de almacenes operativos del proyecto.',
            'route' => route('almacenes.create'),
            'cta' => 'Abrir almacén',
            'metrics' => [
                ['label' => 'Total', 'value' => $metrics['totalAlmacenes']],
                ['label' => 'Inventario', 'value' => $metrics['totalInventario']],
            ],
        ];

        $dashboardPanels[] = [
            'id' => 'documentos',
            'category' => 'DOCUMENTOS',
            'title' => 'Documentos',
            'icon' => 'fas fa-folder-open',
            'tone' => 'documentos',
            'description' => 'Acceso al historico documental del sistema.',
            'route' => route('documentos.index'),
            'cta' => 'Ver documentos',
            'metrics' => [
                ['label' => 'Registros', 'value' => $metrics['totalDocumentos']],
                ['label' => 'Actualizados hoy', 'value' => $metrics['documentosHoy']],
            ],
        ];

        if (auth()->user()->can('manage-users')) {
            $dashboardPanels[] = [
                'id' => 'personal',
                'category' => 'RRHH',
                'title' => 'Personal',
                'icon' => 'fas fa-users-cog',
                'tone' => 'presupuestos',
                'description' => 'Acceso al registro de trabajadores y su información asociada.',
                'route' => route('personal.index'),
                'cta' => 'Ver personal',
                'secondary_route' => route('personal.create'),
                'secondary_text' => 'Nuevo trabajador',
                'metrics' => [
                    ['label' => 'Total', 'value' => $metrics['totalPersonal']],
                    ['label' => 'Activos', 'value' => $metrics['personalActivos']],
                ],
            ];
        }

        if (auth()->user()->can('manage-users') || auth()->user()->role === 'admin' || auth()->user()->role === 'superadmin') {
            $dashboardPanels[] = [
                'id' => 'clases',
                'category' => 'INVENTARIO',
                'title' => 'Clases',
                'icon' => 'fas fa-tags',
                'tone' => 'documentos',
                'description' => 'Clasificación de productos y agrupación de inventario por categorías.',
                'route' => route('clases.index'),
                'cta' => 'Ver clases',
                'secondary_route' => route('clases.create'),
                'secondary_text' => 'Nueva clase',
                'metrics' => [
                    ['label' => 'Total', 'value' => $metrics['totalClases']],
                    ['label' => 'Productos', 'value' => $metrics['totalInventario']],
                ],
            ];
        }

        if (auth()->user()->can('manage-projects')) {
            $dashboardPanels[] = [
                'id' => 'proyectos',
                'category' => 'PROYECTOS',
                'title' => 'Gestión de Proyectos',
                'icon' => 'fas fa-code-branch',
                'tone' => 'bolsa',
                'description' => 'Administración de proyectos y sus asignaciones activas.',
                'route' => route('herramientas.proyectos.index'),
                'cta' => 'Abrir proyectos',
                'secondary_route' => route('herramientas.proyectos.create'),
                'secondary_text' => 'Nuevo proyecto',
                'metrics' => [
                    ['label' => 'Proyectos', 'value' => $metrics['totalProyectos']],
                    ['label' => 'Asignaciones', 'value' => $metrics['asignacionesProyecto']],
                ],
            ];
        }

        return $dashboardPanels;
    }
}
