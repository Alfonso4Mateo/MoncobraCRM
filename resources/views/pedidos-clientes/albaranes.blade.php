@extends('adminlte::page')

@section('title', 'Albaranes del Pedido - MoncobraCRM')

@section('css')
    @vite(['resources/css/pedidos-clientes-index.css'])
@endsection

@section('content_header')
    <div class="pedidos-clientes-header">
        <div class="pedidos-clientes-header__copy">
            <h1>Albaranes de {{ $pedidoCliente->numero_pedido }}</h1>
            <p>Detalle de albaranes asociados al pedido seleccionado.</p>
        </div>

        <a
            href="{{ route('albaranes.create', ['pedido_cliente' => $pedidoCliente->numero_pedido, 'cliente_id' => $pedidoCliente->id_cliente, 'ot' => $pedidoCliente->ot]) }}"
            class="pedidos-clientes-create-btn"
        >
            <i class="fas fa-plus" aria-hidden="true"></i>
            Agregar Albarán
        </a>
    </div>
@endsection

@section('content')
    <section class="pedidos-clientes-shell">
        <article class="pedidos-clientes-card pedido-albaranes-resumen-card">
            <div class="pedido-albaranes-resumen-grid">
                <div class="pedido-albaranes-resumen-item">
                    <span>Total del pedido</span>
                    <strong>€{{ number_format((float) $totalPedido, 2, ',', '.') }}</strong>
                </div>
                <div class="pedido-albaranes-resumen-item">
                    <span>Total albaranes asociados</span>
                    <strong>€{{ number_format((float) $totalAlbaranes, 2, ',', '.') }}</strong>
                </div>
                <div class="pedido-albaranes-resumen-item">
                    <span>Cantidad de albaranes</span>
                    <strong>{{ number_format($albaranes->total(), 0, ',', '.') }}</strong>
                </div>
                <div class="pedido-albaranes-resumen-item">
                    <span>Pendiente por facturar</span>
                    <strong>€{{ number_format((float) ($pendienteFacturar ?? 0), 2, ',', '.') }}</strong>
                </div>
            </div>
        </article>

        <article class="pedidos-clientes-card">
            <header class="pedidos-clientes-card__header">
                <div>
                    <h3>Listado de albaranes asociados</h3>
                    <p>{{ $albaranes->total() }} albaranes</p>
                </div>

                <div class="pedidos-clientes-card__actions">
                    <a href="{{ route('pedidos-clientes.index') }}" class="pedido-action-btn pedido-action-btn--soft">
                        Volver a pedidos
                    </a>
                </div>
            </header>

            <div class="table-responsive pedidos-clientes-table-wrap">
                <table class="table pedidos-clientes-table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th class="text-right">Coste</th>
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
                                <td data-label="Cliente">
                                    <span>{{ $albaran->cliente?->empresa_nombre ?? 'Sin cliente' }}</span>
                                </td>
                                <td data-label="Estado">
                                    <span class="{{ $estadoClass }}">{{ ucfirst($estadoAlbaran) }}</span>
                                </td>
                                <td data-label="Coste" class="text-right">
                                    <strong class="pedido-total">€{{ number_format((float) ($albaran->total ?? 0), 2, ',', '.') }}</strong>
                                </td>
                                <td data-label="Acciones">
                                    <a href="{{ route('albaranes.show', $albaran) }}" class="pedido-action-btn pedido-action-btn--soft">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
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

            <div class="pedidos-clientes-pagination">
                {{ $albaranes->links() }}
            </div>
        </article>
    </section>
@endsection
