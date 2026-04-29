@extends('adminlte::page')

@section('title', 'Pedidos de Clientes - MoncobraCRM')

@section('css')
    @vite(['resources/css/pedidos-clientes-index.css'])
@endsection

@section('content_header')
    <div class="pedidos-clientes-header">
        <div class="pedidos-clientes-header__copy">
            <h1>Pedidos de Clientes</h1>
            <p>Trazabilidad completa desde presupuesto hasta albarán.</p>
        </div>

        <a href="{{ route('pedidos-clientes.create') }}" class="pedidos-clientes-create-btn">
            <i class="fas fa-plus" aria-hidden="true"></i>
            Nuevo Pedido
        </a>
    </div>
@endsection

@section('content')
    @php
        $estadosFiltro = ['' => 'Todos'] + ($estadosFiltro ?? []);

        $estadoActual = (string) ($estadoActual ?? '');
        $searchActual = (string) ($searchActual ?? '');
        $desdeActual = (string) ($desdeActual ?? '');
        $hastaActual = (string) ($hastaActual ?? '');
        $variacionPedidosTexto = $variacionPedidosPorcentaje >= 0
            ? '+' . number_format($variacionPedidosPorcentaje, 1, ',', '.') . '%'
            : number_format($variacionPedidosPorcentaje, 1, ',', '.') . '%';
        $urgentesTexto = $albaranesPendientesRelacionados > 0
            ? $albaranesPendientesRelacionados . ' urgentes'
            : 'Sin urgencias';
    @endphp

    <section class="pedidos-clientes-shell">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-circle-check"></i>
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <section class="pedidos-clientes-stats">
            <article class="pedido-stat-card pedido-stat-card--blue">
                <div class="pedido-stat-card__icon"><i class="fas fa-clipboard-list" aria-hidden="true"></i></div>
                <span class="pedido-stat-card__label">Pedidos activos</span>
                <div class="pedido-stat-card__value">{{ number_format($pedidosActivos, 0, ',', '.') }}</div>
                <div class="pedido-stat-card__note"><strong>{{ $variacionPedidosTexto }}</strong> con respecto al mes anterior</div>
            </article>

            <article class="pedido-stat-card pedido-stat-card--orange">
                <div class="pedido-stat-card__icon"><i class="fas fa-truck-loading" aria-hidden="true"></i></div>
                <span class="pedido-stat-card__label">Pendientes de albarán</span>
                <div class="pedido-stat-card__value">{{ number_format($pendientesAlbaran, 0, ',', '.') }}</div>
                <div class="pedido-stat-card__note"><strong>{{ $urgentesTexto }}</strong> requiere atención logística</div>
            </article>

            <article class="pedido-stat-card pedido-stat-card--navy">
                <div class="pedido-stat-card__icon"><i class="fas fa-wallet" aria-hidden="true"></i></div>
                <span class="pedido-stat-card__label">Facturación mensual</span>
                <div class="pedido-stat-card__value pedido-stat-card__value--currency">€{{ number_format($facturacionMensual, 0, ',', '.') }}</div>
                <div class="pedido-stat-card__note"><strong>{{ $porcentajeMeta }}% meta</strong></div>
                <div class="pedido-progress">
                    <span style="width: {{ $porcentajeMeta }}%"></span>
                </div>
            </article>
        </section>

        <article class="pedidos-clientes-card">
            <header class="pedidos-clientes-card__header">
                <div>
                    <h3>Registro general de pedidos</h3>
                    <p>{{ $pedidos->total() }} pedidos</p>
                </div>

                <div class="pedidos-clientes-card__actions">
                    <a href="{{ route('pedidos-clientes.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="pedido-action-btn pedido-action-btn--soft">
                        <i class="fas fa-download" aria-hidden="true"></i>
                        Exportar
                    </a>
                </div>
            </header>

            <div class="pedido-clientes-filters-wrap">
                <form method="GET" action="{{ route('pedidos-clientes.index') }}" class="pedido-clientes-filters">
                    <div class="pedido-filter-field pedido-filter-field--search">
                        <label for="search">Buscar</label>
                        <input type="search" id="search" name="search" value="{{ $searchActual }}" placeholder="Nº pedido o cliente">
                    </div>

                    <div class="pedido-filter-field">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            @foreach ($estadosFiltro as $value => $label)
                                <option value="{{ $value }}" @selected($estadoActual === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pedido-filter-field">
                        <label for="desde">Desde</label>
                        <input type="date" id="desde" name="desde" value="{{ $desdeActual }}">
                    </div>

                    <div class="pedido-filter-field">
                        <label for="hasta">Hasta</label>
                        <input type="date" id="hasta" name="hasta" value="{{ $hastaActual }}">
                    </div>

                    <div class="pedido-filter-actions">
                        <button type="submit" class="pedido-filter-submit">Aplicar</button>
                        <a href="{{ route('pedidos-clientes.index') }}" class="pedido-filter-reset">Limpiar</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive pedidos-clientes-table-wrap">
                <table class="table pedidos-clientes-table">
                    <thead>
                        <tr>
                            <th>Nº Pedido</th>
                            <th>Presupuesto origen</th>
                            <th>Cliente</th>
                            <th>OT</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Albarán asociado</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pedidos as $pedido)
                            @php
                                $fechaPedido = optional($pedido->fecha_pedido);
                            @endphp
                            <tr>
                                <td data-label="Nº Pedido">
                                    <a href="{{ route('pedidos-clientes.show', $pedido) }}" class="pedido-code-link">
                                        {{ $pedido->numero_pedido }}
                                    </a>
                                </td>
                                <td data-label="Presupuesto origen">
                                    @if ($pedido->presupuesto_id && $pedido->ui_presupuesto_numero)
                                        <a href="{{ route('presupuestos.show', $pedido->presupuesto_id) }}" class="pedido-code-link pedido-code-link--soft">
                                            {{ $pedido->ui_presupuesto_numero }}
                                        </a>
                                    @else
                                        <span class="pedido-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Cliente">
                                    <div class="pedido-client-cell">
                                        <strong>{{ $pedido->cliente?->empresa_nombre ?? 'Sin cliente' }}</strong>
                                        @if ($pedido->cliente?->localidad)
                                            <span>{{ $pedido->cliente->localidad }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="OT">
                                    <span class="pedido-ot-pill">{{ $pedido->ot ?: 'Sin OT' }}</span>
                                </td>
                                <td data-label="Fecha">
                                    <span class="pedido-date">{{ $fechaPedido ? $fechaPedido->format('d M Y') : '—' }}</span>
                                </td>
                                <td data-label="Estado">
                                    <span class="{{ $pedido->ui_estado_class ?? 'pedido-chip pedido-chip--pending' }}">{{ $pedido->ui_estado_label ?? 'Pendiente' }}</span>
                                </td>
                                <td data-label="Albarán asociado">
                                    @if ($pedido->albaran_id && $pedido->ui_albaran_numero)
                                        <a href="{{ route('albaranes.show', $pedido->albaran_id) }}" class="pedido-code-link pedido-code-link--soft">
                                            {{ $pedido->ui_albaran_numero }}
                                        </a>
                                    @else
                                        <span class="pedido-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Total" class="text-right">
                                    <strong class="pedido-total">€{{ number_format((float) ($pedido->ui_total ?? 0), 2, ',', '.') }}</strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="pedido-empty-state">
                                        <i class="fas fa-truck"></i>
                                        <h4>No hay pedidos de cliente para mostrar</h4>
                                        <p>Prueba a cambiar los filtros o crea un pedido nuevo para empezar.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pedidos-clientes-pagination">
                {{ $pedidos->links() }}
            </div>
        </article>
    </section>
@endsection
