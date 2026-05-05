<?php

namespace App\Http\Controllers;

use App\Models\AlbaranCliente;
use App\Models\EntradaStock;
use App\Models\PedidoCliente;
use App\Models\Presupuesto;
use App\Models\SalidaStock;
use App\Models\TrasladoStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DocumentosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $tipos = $this->documentTypes();
        $tipoActual = (string) $request->query('tipo', 'albaranes');

        if (! array_key_exists($tipoActual, $tipos)) {
            $tipoActual = 'albaranes';
        }

        $counts = $this->documentCounts();
        $documentosAll = $this->buildDocumentos($tipoActual);

        if ($documentosAll->isEmpty()) {
            $documentosAll = collect($this->fallbackDocuments($tipoActual));
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $documentosAll = $this->filterDocuments($documentosAll, $search);
        }

        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));

        $documentos = new LengthAwarePaginator(
            $documentosAll->forPage($page, $perPage)->values(),
            $documentosAll->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->except('page', 'doc'),
            ]
        );

        $docId = (string) $request->query('doc', '');
        $documentoActivo = $docId !== ''
            ? $documentosAll->firstWhere('id', $docId)
            : null;

        if (! $documentoActivo) {
            $documentoActivo = $documentos->first();
        }

        return view('documentos.index', [
            'tipos' => $tipos,
            'tipoActual' => $tipoActual,
            'counts' => $counts,
            'documentos' => $documentos,
            'documentoActivo' => $documentoActivo,
        ]);
    }

    public function create()
    {
        return view('documentos.create', [
            'tipos' => $this->documentTypes(),
        ]);
    }

    private function documentTypes(): array
    {
        return [
            'albaranes' => ['label' => 'Albaranes', 'icon' => 'fa-file-lines'],
            'presupuestos' => ['label' => 'Presupuestos', 'icon' => 'fa-file-invoice'],
            'pedidos' => ['label' => 'Pedidos', 'icon' => 'fa-cart-shopping'],
            'entradas' => ['label' => 'Registro entrada', 'icon' => 'fa-truck-ramp-box'],
            'salidas' => ['label' => 'Registro salida', 'icon' => 'fa-arrow-up-from-box'],
            'traslados' => ['label' => 'Traslados', 'icon' => 'fa-shuffle'],
        ];
    }

    private function documentCounts(): array
    {
        return [
            'albaranes' => AlbaranCliente::count(),
            'presupuestos' => Presupuesto::count(),
            'pedidos' => PedidoCliente::count(),
            'entradas' => EntradaStock::count(),
            'salidas' => SalidaStock::count(),
            'traslados' => TrasladoStock::count(),
        ];
    }

    private function buildDocumentos(string $tipo): Collection
    {
        return match ($tipo) {
            'albaranes' => $this->fromAlbaranes(),
            'presupuestos' => $this->fromPresupuestos(),
            'pedidos' => $this->fromPedidos(),
            'entradas' => $this->fromEntradas(),
            'salidas' => $this->fromSalidas(),
            'traslados' => $this->fromTraslados(),
            default => collect(),
        };
    }

    private function fromAlbaranes(): Collection
    {
        return AlbaranCliente::with('cliente')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(function (AlbaranCliente $albaran) {
                $estado = $this->estadoMap($albaran->estado);
                $codigo = $albaran->numero ?: ($albaran->documento ?: 'ALB-' . str_pad((string) $albaran->id, 4, '0', STR_PAD_LEFT));

                return [
                    'id' => (string) $albaran->id,
                    'codigo' => $codigo,
                    'fecha' => optional($albaran->fecha)->format('d/m/Y') ?: '—',
                    'persona' => $albaran->cliente?->empresa_nombre ?: 'Sin cliente',
                    'estado' => $estado['label'],
                    'estado_clase' => $estado['class'],
                    'total' => $this->formatTotal($albaran->total),
                    'totales' => $this->totales($albaran->total),
                    'tipo' => 'albaranes',
                    'titulo' => $albaran->titulo ?: 'Documento sin titulo',
                    'meta' => [
                        ['label' => 'Cliente', 'value' => $albaran->cliente?->empresa_nombre ?: 'Sin cliente'],
                        ['label' => 'OT', 'value' => $albaran->ot ?: '—'],
                        ['label' => 'Pedido', 'value' => $albaran->pedido_cliente ?: '—'],
                        ['label' => 'Documento', 'value' => $albaran->documento ?: '—'],
                    ],
                    'lineas' => $this->lineasFromArray($albaran->lista_articulos),
                    'acciones' => $this->accionesAlbaran($albaran),
                ];
            });
    }

    private function fromPresupuestos(): Collection
    {
        return Presupuesto::with('cliente')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(function (Presupuesto $presupuesto) {
                $estado = $this->estadoMap($presupuesto->estado);
                $codigo = $presupuesto->numero ?: ($presupuesto->documento ?: 'PRE-' . str_pad((string) $presupuesto->id, 4, '0', STR_PAD_LEFT));

                return [
                    'id' => (string) $presupuesto->id,
                    'codigo' => $codigo,
                    'fecha' => optional($presupuesto->fecha)->format('d/m/Y') ?: '—',
                    'persona' => $presupuesto->cliente?->empresa_nombre ?: 'Sin cliente',
                    'estado' => $estado['label'],
                    'estado_clase' => $estado['class'],
                    'total' => $this->formatTotal($presupuesto->total),
                    'totales' => $this->totales($presupuesto->total),
                    'tipo' => 'presupuestos',
                    'titulo' => $presupuesto->titulo ?: 'Presupuesto sin titulo',
                    'meta' => [
                        ['label' => 'Cliente', 'value' => $presupuesto->cliente?->empresa_nombre ?: 'Sin cliente'],
                        ['label' => 'OT', 'value' => $presupuesto->ot ?: '—'],
                        ['label' => 'Documento', 'value' => $presupuesto->documento ?: '—'],
                        ['label' => 'Numero', 'value' => $presupuesto->numero ?: '—'],
                    ],
                    'lineas' => $this->lineasFromArray($presupuesto->lista_articulos),
                    'acciones' => $this->accionesPresupuesto($presupuesto),
                ];
            });
    }

    private function fromPedidos(): Collection
    {
        return PedidoCliente::with('cliente')
            ->orderByDesc('fecha_pedido')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(function (PedidoCliente $pedido) {
                $estado = $this->estadoMap($pedido->estado);
                $codigo = $pedido->numero_pedido ?: 'PED-' . str_pad((string) $pedido->id, 4, '0', STR_PAD_LEFT);

                return [
                    'id' => (string) $pedido->id,
                    'codigo' => $codigo,
                    'fecha' => optional($pedido->fecha_pedido)->format('d/m/Y') ?: '—',
                    'persona' => $pedido->cliente?->empresa_nombre ?: 'Sin cliente',
                    'estado' => $estado['label'],
                    'estado_clase' => $estado['class'],
                    'total' => $this->formatTotal($pedido->total),
                    'totales' => $this->totales($pedido->total),
                    'tipo' => 'pedidos',
                    'titulo' => 'Pedido cliente',
                    'meta' => [
                        ['label' => 'Cliente', 'value' => $pedido->cliente?->empresa_nombre ?: 'Sin cliente'],
                        ['label' => 'OT', 'value' => $pedido->ot ?: '—'],
                        ['label' => 'Numero pedido', 'value' => $pedido->numero_pedido ?: '—'],
                        ['label' => 'Presupuesto', 'value' => $pedido->presupuesto_id ?: '—'],
                    ],
                    'lineas' => $this->lineasFromArray($pedido->lista_articulos),
                    'acciones' => $this->accionesPedido($pedido),
                ];
            });
    }

    private function fromEntradas(): Collection
    {
        return EntradaStock::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(function (EntradaStock $entrada) {
                $estado = $this->estadoMap($entrada->estado);
                $total = $this->itemsTotal($entrada->items);

                return [
                    'id' => (string) $entrada->id,
                    'codigo' => $entrada->numero_entrada,
                    'fecha' => optional($entrada->fecha)->format('d/m/Y') ?: '—',
                    'persona' => $entrada->solicitante ?: 'Sin responsable',
                    'estado' => $estado['label'],
                    'estado_clase' => $estado['class'],
                    'total' => $this->formatTotal($total),
                    'totales' => $this->totales($total),
                    'tipo' => 'entradas',
                    'titulo' => 'Entrada de stock',
                    'meta' => [
                        ['label' => 'Solicitante', 'value' => $entrada->solicitante ?: '—'],
                        ['label' => 'OT', 'value' => $entrada->ot ?: '—'],
                        ['label' => 'Almacen origen', 'value' => $entrada->almacen_origen ?: '—'],
                        ['label' => 'Estado', 'value' => $estado['label']],
                    ],
                    'lineas' => $this->lineasFromArray($entrada->items),
                    'acciones' => $this->accionesMovimiento('entrada', $entrada->id),
                ];
            });
    }

    private function fromSalidas(): Collection
    {
        return SalidaStock::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(function (SalidaStock $salida) {
                $estado = $this->estadoMap($salida->estado);
                $total = $this->itemsTotal($salida->items);

                return [
                    'id' => (string) $salida->id,
                    'codigo' => $salida->numero_salida,
                    'fecha' => optional($salida->fecha)->format('d/m/Y') ?: '—',
                    'persona' => $salida->solicitante ?: 'Sin responsable',
                    'estado' => $estado['label'],
                    'estado_clase' => $estado['class'],
                    'total' => $this->formatTotal($total),
                    'totales' => $this->totales($total),
                    'tipo' => 'salidas',
                    'titulo' => 'Salida de stock',
                    'meta' => [
                        ['label' => 'Solicitante', 'value' => $salida->solicitante ?: '—'],
                        ['label' => 'OT', 'value' => $salida->ot ?: '—'],
                        ['label' => 'Almacen origen', 'value' => $salida->almacen_origen ?: '—'],
                        ['label' => 'Estado', 'value' => $estado['label']],
                    ],
                    'lineas' => $this->lineasFromArray($salida->items),
                    'acciones' => $this->accionesMovimiento('salida', $salida->id),
                ];
            });
    }

    private function fromTraslados(): Collection
    {
        return TrasladoStock::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(function (TrasladoStock $traslado) {
                $estado = $this->estadoMap($traslado->estado);
                $total = $this->itemsTotal($traslado->items);

                return [
                    'id' => (string) $traslado->id,
                    'codigo' => $traslado->numero_traslado,
                    'fecha' => optional($traslado->fecha)->format('d/m/Y') ?: '—',
                    'persona' => $traslado->solicitante ?: 'Sin responsable',
                    'estado' => $estado['label'],
                    'estado_clase' => $estado['class'],
                    'total' => $this->formatTotal($total),
                    'totales' => $this->totales($total),
                    'tipo' => 'traslados',
                    'titulo' => 'Traslado de stock',
                    'meta' => [
                        ['label' => 'Solicitante', 'value' => $traslado->solicitante ?: '—'],
                        ['label' => 'OT', 'value' => $traslado->ot ?: '—'],
                        ['label' => 'Almacen origen', 'value' => $traslado->almacen_origen ?: '—'],
                        ['label' => 'Almacen destino', 'value' => $traslado->almacen_actual ?: '—'],
                    ],
                    'lineas' => $this->lineasFromArray($traslado->items),
                    'acciones' => $this->accionesMovimiento('traslado', $traslado->id),
                ];
            });
    }

    private function estadoMap(?string $estado): array
    {
        $estado = strtolower(trim((string) $estado));

        return match ($estado) {
            'entregado', 'completado', 'aceptado' => ['label' => 'COMPLETADO', 'class' => 'status-success'],
            'pendiente' => ['label' => 'PENDIENTE', 'class' => 'status-warning'],
            'rechazado', 'cancelado', 'anulado' => ['label' => 'RECHAZADO', 'class' => 'status-danger'],
            'borrador' => ['label' => 'BORRADOR', 'class' => 'status-muted'],
            default => ['label' => $estado !== '' ? strtoupper($estado) : 'SIN ESTADO', 'class' => 'status-muted'],
        };
    }

    private function formatTotal($value): string
    {
        if (! is_numeric($value)) {
            return '—';
        }

        return number_format((float) $value, 2, ',', '.') . ' €';
    }

    private function itemsTotal($items): ?float
    {
        if (! is_array($items)) {
            return null;
        }

        $total = 0.0;
        $hasValue = false;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (isset($item['total']) && is_numeric($item['total'])) {
                $total += (float) $item['total'];
                $hasValue = true;
                continue;
            }

            if (isset($item['importe']) && is_numeric($item['importe'])) {
                $total += (float) $item['importe'];
                $hasValue = true;
                continue;
            }

            $cantidad = isset($item['cantidad']) && is_numeric($item['cantidad']) ? (float) $item['cantidad'] : null;
            $precio = isset($item['precio']) && is_numeric($item['precio']) ? (float) $item['precio'] : null;

            if ($cantidad !== null && $precio !== null) {
                $total += $cantidad * $precio;
                $hasValue = true;
            }
        }

        return $hasValue ? $total : null;
    }

    private function lineasFromArray($lineas): array
    {
        if (! is_array($lineas)) {
            return [];
        }

        return collect($lineas)
            ->filter(fn ($item) => is_array($item))
            ->take(5)
            ->map(function (array $item, int $index) {
                $nombre = $item['descripcion']
                    ?? $item['nombre']
                    ?? $item['producto']
                    ?? $item['item']
                    ?? ('Item ' . ($index + 1));

                $cantidad = $item['cantidad'] ?? null;
                $importe = $item['total'] ?? $item['importe'] ?? null;

                return [
                    'nombre' => $nombre,
                    'cantidad' => $cantidad,
                    'importe' => $importe,
                ];
            })
            ->values()
            ->all();
    }

    private function filterDocuments(Collection $documentos, string $search): Collection
    {
        $needle = Str::lower($search);

        return $documentos->filter(function (array $doc) use ($needle) {
            $metaValues = collect($doc['meta'] ?? [])->pluck('value')->implode(' ');
            $haystack = Str::lower(implode(' ', [
                $doc['codigo'] ?? '',
                $doc['fecha'] ?? '',
                $doc['persona'] ?? '',
                $doc['estado'] ?? '',
                $doc['titulo'] ?? '',
                $metaValues,
            ]));

            return Str::contains($haystack, $needle);
        })->values();
    }

    private function totales($total): array
    {
        if (! is_numeric($total)) {
            return ['base' => '—', 'iva' => '—', 'total' => '—'];
        }

        $total = (float) $total;
        $base = $total / 1.21;
        $iva = $total - $base;

        return [
            'base' => number_format($base, 2, ',', '.') . ' €',
            'iva' => number_format($iva, 2, ',', '.') . ' €',
            'total' => number_format($total, 2, ',', '.') . ' €',
        ];
    }

    private function accionesAlbaran(AlbaranCliente $albaran): array
    {
        return [
            ['label' => 'Ver PDF', 'icon' => 'fa-file-pdf', 'url' => route('albaranes.pdf', $albaran)],
            ['label' => 'Descargar', 'icon' => 'fa-cloud-arrow-down', 'url' => route('albaranes.pdf.file', $albaran)],
            ['label' => 'Editar', 'icon' => 'fa-pen', 'url' => route('albaranes.pantalla-roja', $albaran)],
        ];
    }

    private function accionesPresupuesto(Presupuesto $presupuesto): array
    {
        return [
            ['label' => 'Ver PDF', 'icon' => 'fa-file-pdf', 'url' => route('presupuestos.pdf', $presupuesto)],
            ['label' => 'Editar', 'icon' => 'fa-pen', 'url' => route('presupuestos.edit', $presupuesto)],
        ];
    }

    private function accionesPedido(PedidoCliente $pedido): array
    {
        return [
            ['label' => 'Ver detalle', 'icon' => 'fa-eye', 'url' => route('pedidos-clientes.show', $pedido)],
        ];
    }

    private function accionesMovimiento(string $tipo, int $id): array
    {
        return [
            ['label' => 'Ver detalle', 'icon' => 'fa-eye', 'url' => route('inventario.acciones.show', ['tipo' => $tipo, 'id' => $id])],
        ];
    }

    private function fallbackDocuments(string $tipo): array
    {
        $fechaBase = Carbon::now();

        return match ($tipo) {
            'albaranes' => [
                $this->fallbackRow('1', 'ALB-2024-0124', $fechaBase->copy()->subDays(2), 'Aceros Industriales', 'COMPLETADO', 'status-success', '14.560,00 €'),
                $this->fallbackRow('2', 'ALB-2024-0125', $fechaBase->copy()->subDays(3), 'Suministros Globales', 'PENDIENTE', 'status-warning', '2.430,50 €'),
                $this->fallbackRow('3', 'ALB-2024-0126', $fechaBase->copy()->subDays(5), 'Mecanica Precision', 'COMPLETADO', 'status-success', '8.900,00 €'),
            ],
            'presupuestos' => [
                $this->fallbackRow('1', 'PRE-2024-032', $fechaBase->copy()->subDays(1), 'Logistica Norte', 'BORRADOR', 'status-muted', '1.200,00 €'),
                $this->fallbackRow('2', 'PRE-2024-031', $fechaBase->copy()->subDays(4), 'Aceros Industriales', 'PENDIENTE', 'status-warning', '6.450,00 €'),
            ],
            'pedidos' => [
                $this->fallbackRow('1', 'PED-2024-087', $fechaBase->copy()->subDays(2), 'Suministros Globales', 'EN PROCESO', 'status-info', '3.120,00 €'),
            ],
            'entradas' => [
                $this->fallbackRow('1', 'ENT-2024-011', $fechaBase->copy()->subDays(1), 'J. Rodriguez', 'COMPLETADO', 'status-success', '—'),
            ],
            'salidas' => [
                $this->fallbackRow('1', 'SAL-2024-008', $fechaBase->copy()->subDays(3), 'C. Navarro', 'COMPLETADO', 'status-success', '—'),
            ],
            'traslados' => [
                $this->fallbackRow('1', 'TRS-2024-003', $fechaBase->copy()->subDays(6), 'J. Rodriguez', 'PENDIENTE', 'status-warning', '—'),
            ],
            default => [],
        };
    }

    private function fallbackRow(
        string $id,
        string $codigo,
        Carbon $fecha,
        string $persona,
        string $estado,
        string $estadoClase,
        string $total
    ): array {
        return [
            'id' => $id,
            'codigo' => $codigo,
            'fecha' => $fecha->format('d/m/Y'),
            'persona' => $persona,
            'estado' => $estado,
            'estado_clase' => $estadoClase,
            'total' => $total,
            'totales' => ['base' => '—', 'iva' => '—', 'total' => $total],
            'tipo' => 'fallback',
            'titulo' => 'Documento de ejemplo',
            'meta' => [
                ['label' => 'Referencia', 'value' => $codigo],
                ['label' => 'Responsable', 'value' => $persona],
                ['label' => 'Estado', 'value' => $estado],
                ['label' => 'Total', 'value' => $total],
            ],
            'lineas' => [
                ['nombre' => 'Linea base', 'cantidad' => 1, 'importe' => null],
                ['nombre' => 'Linea secundaria', 'cantidad' => 2, 'importe' => null],
            ],
            'acciones' => [],
        ];
    }
}
