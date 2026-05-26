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
            <a href="{{ route('presupuestos.preview', $presupuesto) }}" class="presupuesto-detail-btn presupuesto-detail-btn--soft">
                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                Previsualizar
            </a>
            <a href="{{ route('presupuestos.pdf.download', $presupuesto) }}" class="presupuesto-detail-btn presupuesto-detail-btn--soft">
                <i class="fas fa-download" aria-hidden="true"></i>
                Descargar PDF
            </a>
            @if ($puedeEditar)
                <a href="{{ route('presupuestos.edit', $presupuesto) }}" class="presupuesto-detail-btn presupuesto-detail-btn--soft" title="Editar presupuesto">
                    <i class="fas fa-pen" aria-hidden="true"></i>
                    Editar
                </a>
            @else
                <button type="button" class="presupuesto-detail-btn presupuesto-detail-btn--soft" disabled title="No puedes editar presupuestos {{ $estado }}s">
                    <i class="fas fa-pen" aria-hidden="true"></i>
                    Editar
                </button>
            @endif
            <a href="{{ route('presupuestos.index') }}" class="presupuesto-detail-btn presupuesto-detail-btn--ghost">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Volver
            </a>
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
        $pedidosClientes = $presupuesto->pedidosClientes;

        if ($pedidosClientes instanceof \Illuminate\Support\Collection) {
            $pedidosClientes = $pedidosClientes;
        } elseif ($pedidosClientes) {
            $pedidosClientes = collect([$pedidosClientes]);
        } else {
            $pedidosClientes = collect();
        }
    @endphp

    <section class="presupuesto-detail-shell">
        <article class="presupuesto-detail-card">
            <header class="presupuesto-detail-card__head">
                <h2>Resumen del presupuesto</h2>
            </header>
            <div class="presupuesto-detail-card__body">
                <div class="presupuesto-summary-grid">
                    <div class="summary-item">
                        <span>Documento</span>
                        <strong>{{ $presupuesto->documento ?: 'N/D' }}</strong>
                    </div>
                    <div class="summary-item">
                        <span>Número</span>
                        <strong>{{ $presupuesto->numero ?: 'N/D' }}</strong>
                    </div>
                    <div class="summary-item">
                        <span>Fecha</span>
                        <strong>{{ optional($presupuesto->fecha)->format('d/m/Y') ?: 'N/D' }}</strong>
                    </div>
                    <div class="summary-item">
                        <span>Estado</span>
                        <strong><span class="{{ $estadoClass }}">{{ ucfirst($estado) }}</span></strong>
                    </div>
                    <div class="summary-item summary-item--wide">
                        <span>Cliente</span>
                        <strong>{{ $presupuesto->cliente?->empresa_nombre ?? 'Sin cliente' }}</strong>
                    </div>
                    <div class="summary-item summary-item--wide">
                        <span>Título</span>
                        <strong>{{ $presupuesto->titulo ?: 'Sin título' }}</strong>
                    </div>
                    <div class="summary-item">
                        <span>OT</span>
                        <strong>{{ $presupuesto->ot ?: 'Sin OT' }}</strong>
                    </div>
                    <div class="summary-item">
                        <span>Total</span>
                        <strong>{{ number_format((float) ($presupuesto->total ?? 0), 2, ',', '.') }} EUR</strong>
                    </div>
                </div>
            </div>
        </article>

        <article class="presupuesto-detail-card">
            <header class="presupuesto-detail-card__head">
                <h2>Pedidos vinculados</h2>
            </header>
            <div class="presupuesto-detail-card__body">
                @if ($pedidosClientes->isEmpty())
                    <p class="presupuesto-empty-items">No hay pedidos vinculados a este presupuesto.</p>
                @else
                    <div class="table-responsive presupuesto-items-table-wrap">
                        <table class="table presupuesto-items-table mb-0">
                            <thead>
                                <tr>
                                    <th>Nº Pedido</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Albarán</th>
                                    <th class="text-right">Total</th>
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
                                        <td data-label="Nº Pedido">
                                            <a href="{{ route('pedidos-clientes.show', $pedido) }}" class="pedido-code-link">
                                                {{ $pedido->numero_pedido ?: ('PED-' . $pedido->id) }}
                                            </a>
                                        </td>
                                        <td data-label="Cliente">
                                            {{ $pedido->cliente?->empresa_nombre ?? 'Sin cliente' }}
                                        </td>
                                        <td data-label="Fecha">
                                            {{ optional($pedido->fecha_pedido)->format('d/m/Y') ?: '—' }}
                                        </td>
                                        <td data-label="Estado">
                                            <span class="{{ $estadoPedidoClass }}">{{ ucfirst(str_replace('_', ' ', $estadoPedido)) }}</span>
                                        </td>
                                        <td data-label="Albarán">
                                            @if ($pedido->albaran)
                                                <a href="{{ route('albaranes.show', $pedido->albaran) }}" class="pedido-code-link pedido-code-link--soft">
                                                    {{ $pedido->albaran->numero ?? 'Ver albarán' }}
                                                </a>
                                            @else
                                                <span class="pedido-muted">—</span>
                                            @endif
                                        </td>
                                        <td data-label="Total" class="text-right">
                                            {{ number_format((float) ($pedido->total ?? 0), 2, ',', '.') }} EUR
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>

        <article class="presupuesto-detail-card">
            <header class="presupuesto-detail-card__head">
                <h2>Lista de artículos</h2>
            </header>
            <div class="presupuesto-detail-card__body p-0">
                @if (empty($items))
                    <p class="presupuesto-empty-items">Este presupuesto no tiene artículos cargados.</p>
                @else
                    <div class="table-responsive presupuesto-items-table-wrap">
                        <table class="table presupuesto-items-table mb-0">
                            <thead>
                                <tr>
                                    <th>Artículo</th>
                                    <th>Descripción</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">Precio unitario</th>
                                    <th class="text-right">Margen (%)</th>
                                    <th class="text-right">Total línea</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td data-label="Artículo">{{ $item['articulo'] ?? '-' }}</td>
                                        <td data-label="Descripción">{{ $item['descripcion'] ?? '-' }}</td>
                                        <td data-label="Cantidad" class="text-right">{{ number_format((float) ($item['cantidad'] ?? 0), 2, ',', '.') }}</td>
                                        <td data-label="Precio unitario" class="text-right">{{ number_format((float) ($item['precio_unitario'] ?? 0), 2, ',', '.') }} EUR</td>
                                        <td data-label="Margen (%)" class="text-right">{{ number_format((float) ($item['margen'] ?? 0), 2, ',', '.') }}</td>
                                        <td data-label="Total línea" class="text-right">{{ number_format((float) ($item['total'] ?? 0), 2, ',', '.') }} EUR</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>
    </section>
@endsection
