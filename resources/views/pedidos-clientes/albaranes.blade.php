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
            <a
                href="{{ route('albaranes.create', ['pedido_id' => $pedidoCliente->id, 'pedido_cliente' => $pedidoCliente->numero_pedido, 'cliente_id' => $pedidoCliente->id_cliente, 'ot' => $pedidoCliente->ot]) }}"
                class="pedidos-clientes-create-btn"
            >
                <i class="fas fa-plus" aria-hidden="true"></i>
                Agregar Albarán
            </a>
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
            <header class="albaran-info-header">
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
                        <span class="albaran-pago-label">Total Pagado (Albaranes)</span>
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
                <span class="progress-text">{{ number_format($porcentajePagado, 1, ',', '.') }}% Pagado</span>
            </div>
        </article>

        <!-- Lista de Albaranes -->
        <article class="pedidos-clientes-card">
            <header class="pedidos-clientes-card__header">
                <div>
                    <h3>Albaranes Asociados</h3>
                    <p class="card-subtitle">{{ $albaranes->total() }} {{ \Illuminate\Support\Str::plural('albarán', $albaranes->total()) }}</p>
                </div>
            </header>

            <div class="table-responsive pedidos-clientes-table-wrap">
                <table class="table pedidos-clientes-table">
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
                                    <a href="{{ route('albaranes.show', $albaran) }}" class="pedido-code-link">{{ $albaran->numero }}</a>
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
                                    <a href="{{ route('albaranes.show', $albaran) }}" class="pedido-action-btn pedido-action-btn--soft">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                        Ver
                                    </a>
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
                <div class="pedidos-clientes-pagination">
                    {{ $albaranes->links() }}
                </div>
            @endif
        </article>
    </section>
@endsection
