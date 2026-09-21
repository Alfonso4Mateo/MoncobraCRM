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
        
        <!-- WIDGET GLOBAL DEL CLIENTE (Solo se renderiza si hay OTRAS bolsas activas) -->
        @if(isset($resumenBolsasCliente) && !empty($resumenBolsasCliente['bolsas']))
            <article class="pedidos-clientes-card" style="background: #fef3c7; border: 1px solid #f59e0b; border-left: 4px solid #d97706; padding: 16px; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 14px;">
                    <div style="font-size: 2.2rem; color: #d97706;"><i class="fas fa-boxes"></i></div>
                    <div>
                        <h3 style="margin: 0; color: #92400e; font-size: 1.1rem; font-weight: 800;">Otras bolsas activas de {{ $pedidoCliente->cliente?->empresa_nombre ?? 'este cliente' }}</h3>
                        <p style="margin: 4px 0 0 0; color: #b45309; font-size: 0.95rem;">
                            El sistema detecta <strong>{{ $resumenBolsasCliente['cantidad'] }} bolsa(s) más</strong> activa(s) para este cliente. Cada bolsa es independiente: comprueba cuál corresponde antes de vincular un albarán.
                        </p>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    @foreach ($resumenBolsasCliente['bolsas'] as $bolsa)
                        <div style="background: #fffbeb; padding: 10px 14px; border-radius: 8px; border: 1px solid #fcd34d;">
                            <span style="display: block; font-size: 0.72rem; color: #b45309; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px;">
                                {{ $bolsa['numero_pedido'] ?: 'Sin número' }}{{ $bolsa['ot'] ? ' · OT ' . $bolsa['ot'] : '' }}
                            </span>
                            <strong style="display: block; font-size: 1.25rem; color: #92400e; line-height: 1.3;">
                                {{ number_format($bolsa['saldo_disponible'], 4, ',', '.') }} € libres
                            </strong>
                            <span style="font-size: 0.78rem; color: #92400e;">de {{ number_format($bolsa['total'], 4, ',', '.') }} € totales</span>
                        </div>
                    @endforeach
                </div>
            </article>
        @endif

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
                        <strong class="albaran-pago-amount">€{{ number_format((float) $totalPedido, 4, ',', '.') }}</strong>
                    </div>
                </div>
                <div class="albaran-pago-item albaran-pago-item--paid">
                    <div class="albaran-pago-info">
                        <span class="albaran-pago-label">Total Facturado (Albaranes)</span>
                        <strong class="albaran-pago-amount">€{{ number_format((float) $totalAlbaranes, 4, ',', '.') }}</strong>
                    </div>
                </div>
                <div class="albaran-pago-item albaran-pago-item--pending">
                    <div class="albaran-pago-info">
                        <span class="albaran-pago-label">Pendiente de Facturar</span>
                        <strong class="albaran-pago-amount">€{{ number_format((float) ($pendienteFacturar ?? 0), 4, ',', '.') }}</strong>
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
                                        @php
                                            $totalRealAlbaran = (float) ($albaran->total ?? 0);
                                            
                                            // Si es bolsa, confiamos ciegamente en el pivote (incluso si es 0).
                                            if ($pedidoCliente->bolsa) {
                                                $importeImputado = isset($albaran->pivot) ? (float) $albaran->pivot->importe_imputado : $totalRealAlbaran;
                                            } else {
                                                $importeImputado = (isset($albaran->pivot) && $albaran->pivot->importe_imputado > 0) 
                                                    ? (float) $albaran->pivot->importe_imputado 
                                                    : $totalRealAlbaran;
                                            }
                                        @endphp
                                        
                                        <strong class="pedido-total">€{{ number_format($importeImputado, 4, ',', '.') }}</strong>
                                        
                                        @if($importeImputado < $totalRealAlbaran)
                                            <br>
                                            <small style="color: #64748b; font-size: 0.8em;" title="Total real del albarán: €{{ number_format($totalRealAlbaran, 4, ',', '.') }}">
                                                (de €{{ number_format($totalRealAlbaran, 4, ',', '.') }})
                                            </small>
                                        @endif
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

                                                <!-- BOTÓN DE DESVINCULAR -->
                                                <form action="{{ route('albaranes.desvincular', $albaran) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres desvincular este albarán DE ESTE PEDIDO? El saldo imputado se liberará.');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="pedido_id" value="{{ $pedidoCliente->id }}">
                                                    
                                                    <button type="submit" class="pedido-action-btn" style="color: #dc3545; border: 1px solid #dc3545; background: transparent; padding: 4px 8px; border-radius: 4px;" title="Desvincular del pedido">
                                                        <i class="fas fa-unlink"></i>
                                                    </button>
                                                </form>
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