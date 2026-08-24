@extends('adminlte::page')

@section('title', 'Albaranes del Pedido - MoncobraCRM')

@section('css')
    @vite(['resources/css/pedidos-clientes-index.css'])
@endsection

@section('content_header')
    <div class="pedidos-clientes-header">
        <div class="pedidos-clientes-header__copy">
            <h1>Albaranes del Pedido</h1>
            <p>Gestión y seguimiento de albaranes asociados.</p>
        </div>

        <div class="pedidos-clientes-header__actions">
            <a href="{{ route('pedidos-clientes.index') }}" class="pedidos-clientes-action-btn pedidos-clientes-action-btn--secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Volver
            </a>
            @php
                $pedidoFacturado = (string) ($pedidoCliente->estado ?? '') === 'facturado';
                $crearAlbaranUrl = route('albaranes.create', ['pedido_id' => $pedidoCliente->id, 'pedido_cliente' => $pedidoCliente->numero_pedido, 'cliente_id' => $pedidoCliente->id_cliente, 'ot' => $pedidoCliente->ot]);
            @endphp
            @if ($pedidoFacturado)
                <span
                    class="pedidos-clientes-create-btn pedidos-clientes-create-btn--disabled"
                    aria-disabled="true"
                    title="Este pedido ya está facturado"
                >
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Agregar Albarán
                </span>
            @else
                <a
                    href="{{ $crearAlbaranUrl }}"
                    class="pedidos-clientes-create-btn"
                >
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Agregar Albarán
                </a>
            @endif
        </div>
    </div>
@endsection

@section('content_header')
    <div class="pedidos-clientes-header">
        <div class="pedidos-clientes-header__copy">
            <h1>Albaranes del Pedido</h1>
            <p>Gestión y seguimiento de albaranes asociados.</p>
        </div>

        <div class="pedidos-clientes-header__actions">
            <a href="{{ route('pedidos-clientes.index') }}" class="pedidos-clientes-action-btn pedidos-clientes-action-btn--secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Volver
            </a>
            
            @can('albaranes.manage')
                @php
                    $pedidoFacturado = (string) ($pedidoCliente->estado ?? '') === 'facturado';
                    $crearAlbaranUrl = route('albaranes.create', ['pedido_id' => $pedidoCliente->id, 'pedido_cliente' => $pedidoCliente->numero_pedido, 'cliente_id' => $pedidoCliente->id_cliente, 'ot' => $pedidoCliente->ot]);
                @endphp
                @if ($pedidoFacturado)
                    <span
                        class="pedidos-clientes-create-btn pedidos-clientes-create-btn--disabled"
                        aria-disabled="true"
                        title="Este pedido ya está facturado"
                    >
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        Agregar Albarán
                    </span>
                @else
                    <a
                        href="{{ $crearAlbaranUrl }}"
                        class="pedidos-clientes-create-btn"
                    >
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        Agregar Albarán
                    </a>
                @endif
            @endcan
        </div>
    </div>
@endsection

@section('content')
    <section class="pedidos-clientes-shell">
        <!-- Datos Principales del Pedido -->
        <article class="pedidos-clientes-card albaran-pedido-info">
            <header class="albaran-info-header">
                <h3>Información del Pedido</h3>
            </header>
            <div class="albaran-info-grid">
                <div class="albaran-info-item">
                    <span class="albaran-info-label">Número de Pedido</span>
                    <strong class="albaran-info-value">{{ $pedidoCliente->numero_pedido }}</strong>
                </div>
                <div class="albaran-info-item">
                    <span class="albaran-info-label">Cliente</span>
                    <strong class="albaran-info-value">{{ $pedidoCliente->cliente?->empresa_nombre ?? 'Sin cliente' }}</strong>
                </div>
                <div class="albaran-info-item">
                    <span class="albaran-info-label">OT</span>
                    <strong class="albaran-info-value">{{ $pedidoCliente->ot ?: '—' }}</strong>
                </div>
                <div class="albaran-info-item">
                    <span class="albaran-info-label">Fecha del Pedido</span>
                    <strong class="albaran-info-value">{{ optional($pedidoCliente->fecha_pedido)->format('d/m/Y') ?: '—' }}</strong>
                </div>
            </div>
        </article>

        <!-- Resumen de Pagos -->
        <article class="pedidos-clientes-card albaran-resumen-pagos">
            <header class="albaran-info-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Resumen de Pagos</h3>
                
                @if ($pedidoCliente->bolsa)
                    @can('pedidos.manage')
                    <button type="button" class="pedidos-clientes-action-btn pedidos-clientes-action-btn--primary" data-toggle="modal" data-target="#modalFacturacionManual">
                        <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>
                        Facturar Cuota
                    </button>
                    @endcan
                @endif
            </header>
            <div class="albaran-pagos-grid">
                <div class="albaran-pago-item">
                    <div class="albaran-pago-info">
                        <span class="albaran-pago-label">Total del Pedido</span>
                        <strong class="albaran-pago-amount">€{{ number_format((float) $totalPedido, 2, ',', '.') }}</strong>
                    </div>
                </div>
                <div class="albaran-pago-item albaran-pago-item--paid">
                    <div class="albaran-pago-info">
                        <span class="albaran-pago-label">Total Facturado (Albaranes)</span>
                        <strong class="albaran-pago-amount">€{{ number_format((float) $totalAlbaranes, 2, ',', '.') }}</strong>
                    </div>
                </div>
                <div class="albaran-pago-item albaran-pago-item--pending">
                    <div class="albaran-pago-info">
                        <span class="albaran-pago-label">Pendiente de Facturar</span>
                        <strong class="albaran-pago-amount">€{{ number_format((float) ($pendienteFacturar ?? 0), 2, ',', '.') }}</strong>
                    </div>
                </div>
                <div class="albaran-pago-item albaran-pago-item--count">
                    <div class="albaran-pago-info">
                        <span class="albaran-pago-label">Total de Albaranes</span>
                        <strong class="albaran-pago-amount">{{ $albaranes->total() }}</strong>
                    </div>
                </div>
            </div>

            <!-- Barra de Progreso de Pago -->
            <div class="albaran-pago-progress">
                @php
                    $porcentajePagado = $totalPedido > 0 ? ($totalAlbaranes / $totalPedido) * 100 : 0;
                @endphp
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: {{ $porcentajePagado }}%"></div>
                </div>
                <span class="progress-text">{{ number_format($porcentajePagado, 1, ',', '.') }}% Facturado</span>
            </div>
        </article>

        <!-- Selector unificado de Albaranes y Facturación (Pestañas) -->
        <article class="pedidos-clientes-card">
            <header class="pedidos-clientes-card__header d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3>Gestión Asociada del Pedido</h3>
                    <p class="card-subtitle">Visualiza los albaranes de entrega o la facturación manual por hitos.</p>
                </div>
                <ul class="nav nav-pills card-header-pills mt-2 mt-md-0" id="gestionTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="albaranes-tab" data-toggle="pill" href="#albaranes-panel" role="tab" aria-controls="albaranes-panel" aria-selected="true">
                            <i class="fas fa-file-invoice mr-1" aria-hidden="true"></i> Albaranes ({{ $albaranes->total() }})
                        </a>
                    </li>
                    @if ($pedidoCliente->bolsa)
                        <li class="nav-item ml-2">
                            <a class="nav-link" id="facturacion-tab" data-toggle="pill" href="#facturacion-panel" role="tab" aria-controls="facturacion-panel" aria-selected="false">
                                <i class="fas fa-file-invoice-dollar mr-1" aria-hidden="true"></i> Facturación Asociada ({{ count($facturaciones ?? []) }})
                            </a>
                        </li>
                    @endif
                </ul>
            </header>

            <div class="card-body p-0">
                <div class="tab-content" id="gestionTabContent">
                    <!-- PESTAÑA 1: ALBARANES -->
                    <div class="tab-pane fade show active" id="albaranes-panel" role="tabpanel" aria-labelledby="albaranes-tab">
                        <div class="table-responsive pedidos-clientes-table-wrap m-0">
                            <table class="table pedidos-clientes-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th class="text-right">Importe</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($albaranes as $albaran)
                                        @php
                                            $estadoAlbaran = (string) ($albaran->estado ?: 'pendiente');
                                            $estadoClass = $estadoAlbaran === 'entregado'
                                                ? 'pedido-chip pedido-chip--paid'
                                                : ($estadoAlbaran === 'recibido' ? 'pedido-chip pedido-chip--partial' : 'pedido-chip pedido-chip--pending');
                                        @endphp
                                        <tr>
                                            <td data-label="Número">
                                                @can('albaranes.view')
                                                <a href="{{ route('albaranes.show', $albaran) }}" class="pedido-code-link">{{ $albaran->numero }}</a>
                                                @else
                                                <span class="pedido-muted">{{ $albaran->numero }}</span>
                                                @endcan
                                            </td>
                                            <td data-label="Fecha">
                                                <span class="pedido-date">{{ optional($albaran->fecha)->format('d M Y') ?: '—' }}</span>
                                            </td>
                                            <td data-label="Estado">
                                                <span class="{{ $estadoClass }}">{{ ucfirst($estadoAlbaran) }}</span>
                                            </td>
                                            <td data-label="Importe" class="text-right">
                                                <strong class="pedido-total">€{{ number_format((float) ($albaran->total ?? 0), 2, ',', '.') }}</strong>
                                            </td>
                                            <td data-label="Acciones">
                                                @can('albaranes.view')
                                                <a href="{{ route('albaranes.show', $albaran) }}" class="pedido-action-btn pedido-action-btn--soft">
                                                    <i class="fas fa-eye" aria-hidden="true"></i> Ver
                                                </a>
                                                @else
                                                <span class="text-muted"><i class="fas fa-lock"></i></span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">
                                                <div class="pedido-empty-state">
                                                    <i class="fas fa-file-invoice"></i>
                                                    <h4>No hay albaranes asociados</h4>
                                                    <p>Puedes crear uno nuevo con el botón "Agregar Albarán".</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($albaranes->hasPages())
                            <div class="pedidos-clientes-pagination p-3">
                                {{ $albaranes->links() }}
                            </div>
                        @endif
                    </div>

                    <!-- PESTAÑA 2: FACTURACIÓN MANUAL (Solo si es bolsa) -->
                    @if ($pedidoCliente->bolsa)
                        <div class="tab-pane fade" id="facturacion-panel" role="tabpanel" aria-labelledby="facturacion-tab">
                            <div class="table-responsive pedidos-clientes-table-wrap m-0">
                                <table class="table pedidos-clientes-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Concepto</th>
                                            <th class="text-right">Importe</th>
                                            <th class="text-center" style="width: 80px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($facturaciones ?? [] as $facturacion)
                                            <tr>
                                                <td data-label="Fecha">{{ $facturacion->created_at->format('d M Y') }}</td>
                                                <td data-label="Concepto">{{ $facturacion->concepto }}</td>
                                                <td data-label="Importe" class="text-right">
                                                    <strong class="pedido-total">€{{ number_format((float) $facturacion->importe, 2, ',', '.') }}</strong>
                                                </td>
                                                <td data-label="Acciones" class="text-center">
                                                    @can('pedidos.manage')
                                                    <form action="{{ route('facturacion-manual.destroy', $facturacion->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta cuota? El saldo pendiente se recalculará.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Borrar cuota" style="border: none; background: none; color: #dc3545; cursor: pointer;">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4">
                                                    <div class="pedido-empty-state">
                                                        <i class="fas fa-receipt" style="font-size: 2rem; color: #a0aec0; margin-bottom: 10px; display: block;"></i>
                                                        <h4>No hay facturación manual registrada</h4>
                                                        <p>Utiliza el botón "Facturar Cuota" para registrar la primera certificación.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </article>
    </section>

    @if ($pedidoCliente->bolsa)
    @can('pedidos.manage')
        <!-- Modal Facturar Cuota -->
        <div class="modal fade" id="modalFacturacionManual" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form action="{{ route('pedidos-clientes.facturar-cuota', $pedidoCliente) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalLabel">Añadir Facturación Manual</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="importe">Importe a facturar (€) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" max="{{ max(0, $pendienteFacturar ?? 0) }}" class="form-control" id="importe" name="importe" required placeholder="Ej: 1000">
                                <small class="form-text text-muted">El importe no debería superar el pendiente ({{ number_format((float) ($pendienteFacturar ?? 0), 2, ',', '.') }} €).</small>
                            </div>
                            <div class="form-group">
                                <label for="concepto">Concepto / Descripción <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="concepto" name="concepto" rows="3" required placeholder="Ej: Facturación Hito 1 - Julio 2026"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" style="background-color: #2a6fb0; border-color: #2a6fb0;">Guardar Facturación</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
    @endif
@endsection