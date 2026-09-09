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
                    <span class="albaran-info-label">CC (Centro de Coste)</span>
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
        <!-- Listado de Albaranes -->
        <article class="pedidos-clientes-card">
            <header class="pedidos-clientes-card__header">
                <div>
                    <h3>Albaranes Asociados</h3>
                    <p class="card-subtitle">Listado de entregas logísticas vinculadas a este pedido.</p>
                </div>
            </header>

            <div class="card-body p-0">
                <div class="table-responsive pedidos-clientes-table-wrap m-0">
                    <table class="table pedidos-clientes-table mb-0">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th class="text-right">Importe</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($albaranes as $albaran)
                                @php
                                    $estadoAlbaran = (string) ($albaran->estado ?: 'pendiente');
                                    // Nuevos colores para los 3 estados
                                    $estadoClass = match($estadoAlbaran) {
                                        'facturado' => 'pedido-chip pedido-chip--paid',
                                        'recibido' => 'pedido-chip pedido-chip--partial',
                                        default => 'pedido-chip pedido-chip--pending',
                                    };
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
                                    <td data-label="Acciones" class="text-center">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            @can('albaranes.view')
                                            <a href="{{ route('albaranes.show', $albaran) }}" class="pedido-action-btn pedido-action-btn--soft" title="Ver PDF">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </a>
                                            @endcan

                                            @can('albaranes.manage')
                                                @if($estadoAlbaran !== 'facturado')
                                                    <!-- BOTÓN DIRECTO PARA PASAR A FACTURADO -->
                                                    <form action="{{ route('albaranes.estado.update', $albaran) }}" method="POST" onsubmit="return confirm('¿Marcar este albarán como FACTURADO? Se bloqueará y el importe se sumará a la facturación del pedido.');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="facturado">
                                                        <button type="submit" class="pedido-action-btn" style="color: #28a745; border: 1px solid #28a745; background: transparent; padding: 4px 8px; border-radius: 4px;" title="Marcar como Facturado">
                                                            <i class="fas fa-check-double"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
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
        </article>
    </section>
@endsection