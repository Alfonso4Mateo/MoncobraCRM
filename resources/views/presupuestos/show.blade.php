@extends('adminlte::page')

@section('title', 'Detalle de Presupuesto - MoncobraCRM')

@php
    $estado = (string) ($presupuesto->estado ?: 'pendiente');
    $estadoClass = match (strtolower($estado)) {
        'aceptado' => 'estado-pill estado-aceptado',
        'rechazado' => 'estado-pill estado-rechazado',
        'pendiente pedido' => 'estado-pill estado-pendiente-pedido',
        default => 'estado-pill estado-pendiente',
    };
    $items = is_array($presupuesto->lista_articulos) ? $presupuesto->lista_articulos : [];
    $puedeEditar = $estado !== 'aceptado' && $estado !== 'rechazado';
@endphp

@section('css')
    @vite(['resources/css/presupuestos-detail.css'])
@endsection

@section('content_header')
    <div class="presupuesto-detail-header">
        <div class="presupuesto-detail-header__copy">
            <h1>Detalle de Presupuesto</h1>
            <p>Consulta información general y desglose de artículos asociados.</p>
        </div>

        <div class="presupuesto-detail-header__actions">
            
            <a href="{{ route('presupuestos.index') }}" class="presupuesto-detail-btn presupuesto-detail-btn--ghost">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Volver
            </a>

            @can('presupuestos.download')
                <div class="dropdown d-inline-block">
                    <button class="presupuesto-detail-btn presupuesto-detail-btn--ghost dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item font-weight-bold text-secondary" href="{{ route('presupuestos.preview', $presupuesto) }}"><i class="fas fa-eye mr-2"></i> Previsualizar</a>
                        <a class="dropdown-item font-weight-bold text-secondary" href="{{ route('presupuestos.pdf.download', $presupuesto) }}"><i class="fas fa-download mr-2"></i> Descargar</a>
                    </div>
                </div>
            @endcan

            @can('presupuestos.manage')
                @if ($puedeEditar)
                    <a href="{{ route('presupuestos.edit', $presupuesto) }}" class="presupuesto-detail-btn presupuesto-detail-btn--primary" title="Editar presupuesto">
                        <i class="fas fa-pen" aria-hidden="true"></i>
                        Editar
                    </a>
                @else
                    <button type="button" class="presupuesto-detail-btn presupuesto-detail-btn--primary" disabled title="No puedes editar presupuestos {{ $estado }}s">
                        <i class="fas fa-pen" aria-hidden="true"></i>
                        Editar
                    </button>
                @endif
                <a href="{{ route('presupuestos.revision', $presupuesto) }}" class="presupuesto-detail-btn presupuesto-detail-btn--success" title="Crear revisión a partir de este presupuesto">
                    <i class="fas fa-copy" aria-hidden="true"></i>
                    Crear Revisión
                </a>
            @endcan
        </div>
    </div>
@endsection

@section('content')
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-circle-exclamation"></i>
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-circle-check"></i>
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @php
        // Restauramos la lógica para manejar correctamente la relación HasOne
        $relacionPedido = $presupuesto->pedidosClientes;
        $pedidosClientes = $relacionPedido ? collect([$relacionPedido]) : collect();
        
        $totalArticulos = count($items);
        $totalPedidos = $pedidosClientes->count();
        $totalRevisiones = isset($historialRevisiones) ? $historialRevisiones->count() : 0;
    @endphp

    <section class="presupuesto-detail-shell">
        
        <!-- TARJETA LIMPIA: RESUMEN -->
        <article class="clean-card">
            <div class="clean-card__header">
                <h2>Resumen del presupuesto</h2>
            </div>
            <div class="clean-card__body">
                <div class="clean-grid">
                    <!-- Fila 1 (1 + 1 + 1 = 3) -->
                    <div class="clean-field">
                        <span class="clean-label">Documento</span>
                        <strong class="clean-value">{{ $presupuesto->documento ?: 'N/D' }}</strong>
                    </div>
                    <div class="clean-field">
                        <span class="clean-label">Número</span>
                        <strong class="clean-value">{{ $presupuesto->numero ?: 'N/D' }}</strong>
                    </div>
                    <div class="clean-field">
                        <span class="clean-label">Fecha</span>
                        <strong class="clean-value">{{ optional($presupuesto->fecha)->format('d/m/Y') ?: 'N/D' }}</strong>
                    </div>
                    
                    <!-- Fila 2 (2 + 1 = 3) -->
                    <div class="clean-field span-2">
                        <span class="clean-label">Cliente</span>
                        <strong class="clean-value">{{ $presupuesto->cliente?->empresa_nombre ?? 'Sin cliente' }}</strong>
                    </div>
                    <div class="clean-field">
                        <span class="clean-label">Estado</span>
                        <div class="mt-1"><span class="{{ $estadoClass }}">{{ ucfirst($estado) }}</span></div>
                    </div>
                    
                    <!-- Fila 3 (2 + 1 = 3) -->
                    <div class="clean-field span-2">
                        <span class="clean-label">Título</span>
                        <strong class="clean-value">{{ $presupuesto->titulo ?: 'Sin título' }}</strong>
                    </div>
                    <div class="clean-field">
                        <span class="clean-label">OT</span>
                        <strong class="clean-value">{{ $presupuesto->ot ?: 'Sin OT' }}</strong>
                    </div>

                    <!-- Fila 4 (1 + 2 = 3) -->
                    <div class="clean-field">
                        <span class="clean-label">Solicitante</span>
                        <strong class="clean-value">{{ $presupuesto->solicitante ?: 'N/D' }}</strong>
                    </div>
                    <div class="clean-field span-2">
                        <span class="clean-label">Destinatario</span>
                        <strong class="clean-value">{{ $presupuesto->destinatario ?: 'N/D' }}</strong>
                    </div>
                </div>

                <!-- Total movido visualmente a la derecha -->
                <div class="clean-total-row">
                    <span class="clean-label text-uppercase">Total Presupuesto</span>
                    <strong class="clean-total-value">{{ number_format((float) ($presupuesto->total ?? 0), 2, ',', '.') }} EUR</strong>
                </div>
            </div>
        </article>

        <!-- SISTEMA DE TABS PARA ALIGERAR LA INTERFAZ -->
        <div class="clean-tabs mt-4">
            <ul class="nav nav-tabs" id="presupuestoTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active font-weight-bold text-secondary" id="articulos-tab" data-toggle="tab" href="#articulos" role="tab" aria-selected="true">
                        <i class="fas fa-box mr-1"></i> Artículos ({{ $totalArticulos }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold text-secondary" id="pedidos-tab" data-toggle="tab" href="#pedidos" role="tab" aria-selected="false">
                        <i class="fas fa-shopping-cart mr-1"></i> Pedidos ({{ $totalPedidos }})
                    </a>
                </li>
                @if($totalRevisiones > 1)
                <li class="nav-item">
                    <a class="nav-link font-weight-bold text-secondary" id="historial-tab" data-toggle="tab" href="#historial" role="tab" aria-selected="false">
                        <i class="fas fa-history mr-1"></i> Historial de Revisiones ({{ $totalRevisiones }})
                    </a>
                </li>
                @endif
            </ul>

            <div class="tab-content clean-tab-content bg-white border border-top-0 rounded-bottom" id="presupuestoTabsContent">
                
                <!-- TAB 1: ARTÍCULOS -->
                <div class="tab-pane fade show active p-4" id="articulos" role="tabpanel" aria-labelledby="articulos-tab">
                    @if (empty($items))
                        <p class="text-muted text-center my-4 py-4"><i class="fas fa-inbox text-light fa-3x mb-3"></i><br>Este presupuesto no tiene artículos cargados.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table clean-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Artículo</th>
                                        <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Descripción</th>
                                        <th class="text-right text-secondary text-uppercase" style="font-size: 0.75rem;">Cantidad</th>
                                        <th class="text-right text-secondary text-uppercase" style="font-size: 0.75rem;">P. Unitario</th>
                                        <th class="text-right text-secondary text-uppercase" style="font-size: 0.75rem;">Margen (%)</th>
                                        <th class="text-right text-secondary text-uppercase" style="font-size: 0.75rem;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td class="font-weight-bold text-dark">{{ $item['articulo'] ?? '-' }}</td>
                                            <td class="text-dark">{{ $item['descripcion'] ?? '-' }}</td>
                                            <td class="text-right">{{ number_format((float) ($item['cantidad'] ?? 0), 2, ',', '.') }}</td>
                                            <td class="text-right">{{ number_format((float) ($item['precio_unitario'] ?? 0), 2, ',', '.') }} €</td>
                                            <td class="text-right text-muted">{{ number_format((float) ($item['margen'] ?? 0), 2, ',', '.') }}%</td>
                                            <td class="text-right font-weight-bold text-dark">{{ number_format((float) ($item['total'] ?? 0), 2, ',', '.') }} €</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- TAB 2: PEDIDOS -->
                <div class="tab-pane fade p-4" id="pedidos" role="tabpanel" aria-labelledby="pedidos-tab">
                    @if ($pedidosClientes->isEmpty())
                        <p class="text-muted text-center my-4 py-4"><i class="fas fa-inbox text-light fa-3x mb-3"></i><br>No hay pedidos vinculados a este presupuesto.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table clean-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Nº Pedido</th>
                                        <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Cliente</th>
                                        <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Fecha</th>
                                        <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Estado</th>
                                        <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Albarán</th>
                                        <th class="text-right text-secondary text-uppercase" style="font-size: 0.75rem;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pedidosClientes as $pedido)
                                        @php
                                            $estadoPedido = (string) ($pedido->estado ?: 'pendiente');
                                            $estadoPedidoClass = match ($estadoPedido) {
                                                'facturado' => 'estado-pill estado-aceptado',
                                                'facturado_parcial' => 'estado-pill estado-pendiente-pedido',
                                                default => 'estado-pill estado-pendiente',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="font-weight-bold">
                                                @can('pedidos.view')
                                                    <a href="{{ route('pedidos-clientes.show', $pedido) }}" class="text-primary">{{ $pedido->numero_pedido ?: ('PED-' . $pedido->id) }}</a>
                                                @else
                                                    <span class="text-muted">{{ $pedido->numero_pedido ?: ('PED-' . $pedido->id) }}</span>
                                                @endcan
                                            </td>
                                            <td class="text-dark">{{ $pedido->cliente?->empresa_nombre ?? 'Sin cliente' }}</td>
                                            <td class="text-dark">{{ optional($pedido->fecha_pedido)->format('d/m/Y') ?: '—' }}</td>
                                            <td><span class="{{ $estadoPedidoClass }}">{{ ucfirst(str_replace('_', ' ', $estadoPedido)) }}</span></td>
                                            <td>
                                                @if ($pedido->albaran)
                                                    @can('albaranes.view')
                                                        <a href="{{ route('albaranes.show', $pedido->albaran) }}" class="text-primary">{{ $pedido->albaran->numero ?? 'Ver albarán' }}</a>
                                                    @else
                                                        <span class="text-muted">{{ $pedido->albaran->numero ?? 'Ver albarán' }}</span>
                                                    @endcan
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-right font-weight-bold text-dark">{{ number_format((float) ($pedido->total ?? 0), 2, ',', '.') }} €</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- TAB 3: HISTORIAL -->
                @if($totalRevisiones > 1)
                <div class="tab-pane fade p-4" id="historial" role="tabpanel" aria-labelledby="historial-tab">
                    <div class="table-responsive">
                        <table class="table clean-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Número</th>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Fecha</th>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Título</th>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Total</th>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Estado</th>
                                    <th class="text-right text-secondary text-uppercase" style="font-size: 0.75rem;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($historialRevisiones as $revision)
                                    <tr class="{{ $revision->id === $presupuesto->id ? 'bg-light' : '' }}">
                                        <td class="font-weight-bold text-dark">
                                            {{ $revision->numero }} 
                                            {!! $revision->parent_id === null ? '<span class="badge badge-primary ml-1">Padre</span>' : '' !!}
                                        </td>
                                        <td class="text-dark">{{ optional($revision->fecha)->format('d/m/Y') }}</td>
                                        <td class="text-dark">{{ $revision->titulo ?: 'Sin título' }}</td>
                                        <td class="text-dark">{{ number_format((float) ($revision->total ?? 0), 2, ',', '.') }} €</td>
                                        <td>
                                            @php
                                                $revEstadoClass = match (strtolower($revision->estado)) {
                                                    'aceptado' => 'estado-pill estado-aceptado',
                                                    'rechazado' => 'estado-pill estado-rechazado',
                                                    'pendiente pedido' => 'estado-pill estado-pendiente-pedido',
                                                    default => 'estado-pill estado-pendiente',
                                                };
                                            @endphp
                                            <span class="{{ $revEstadoClass }}">{{ ucfirst($revision->estado) }}</span>
                                        </td>
                                        <td class="text-right">
                                            @if($revision->id !== $presupuesto->id)
                                                <a href="{{ route('presupuestos.show', $revision) }}" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">Ver</a>
                                            @else
                                                <span class="text-muted font-weight-bold" style="font-size: 0.75rem;">ACTUAL</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>
@endsection