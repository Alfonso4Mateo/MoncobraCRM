@extends('adminlte::page')

@section('title', 'Perfil Cliente - MoncobraCRM')

@section('content')
    @php
        $historialRegistros = $historialActivo === 'pedidos'
            ? $pedidos
            : ($historialActivo === 'albaranes' ? $albaranes : $presupuestos);
    @endphp

    <section class="cliente-show-ui">
        @if (session('success'))
            <div class="cliente-show-success" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                {{ session('success') }}
            </div>
        @endif

        <header class="cliente-show-topbar">
            <nav aria-label="breadcrumb" class="cliente-show-breadcrumbs">
                <a href="{{ route('clientes.index') }}">Clientes</a>
                <span><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                <strong>{{ $cliente->empresa_nombre }}</strong>
                <span><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                <span>Historial de Operaciones</span>
            </nav>
        </header>

        <section class="cliente-show-head">
            <div>
                <h1>Historial del Cliente: {{ $cliente->empresa_nombre }}</h1>
                <p>Gestión integral del flujo de pedidos y trazabilidad de fabricación.</p>
            </div>
            <div class="cliente-show-actions">
                @can('presupuestos.manage')
                <a href="{{ route('presupuestos.create', ['cliente_id' => $cliente->id, 'volver_cliente' => 1]) }}" class="btn-nuevo-presupuesto">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Nuevo Presupuesto
                </a>
                @endcan
            </div>
        </section>

        <article class="cliente-show-card">
            <header class="cliente-show-card-head">
                <div class="cliente-show-card-copy">
                    <h2>Selector de Historial</h2>
                    <p>Alterna entre presupuestos, pedidos y albaranes asociados al cliente seleccionado.</p>
                </div>

                <div class="cliente-show-card-controls">
                    <nav class="cliente-show-tabs" aria-label="Seleccionar historial">
                        @can('presupuestos.view')
                        <a href="{{ route('clientes.show', ['cliente' => $cliente->id, 'historial' => 'presupuestos']) }}" class="cliente-show-tab {{ $historialActivo === 'presupuestos' ? 'is-active' : '' }}">
                            Presupuestos
                        </a>
                        @endcan
                        @can('pedidos.view')
                        <a href="{{ route('clientes.show', ['cliente' => $cliente->id, 'historial' => 'pedidos']) }}" class="cliente-show-tab {{ $historialActivo === 'pedidos' ? 'is-active' : '' }}">
                            Pedidos
                        </a>
                        @endcan
                        @can('albaranes.view')
                        <a href="{{ route('clientes.show', ['cliente' => $cliente->id, 'historial' => 'albaranes']) }}" class="cliente-show-tab {{ $historialActivo === 'albaranes' ? 'is-active' : '' }}">
                            Albaranes
                        </a>
                        @endcan
                    </nav>
                </div>
            </header>

            <div class="table-responsive cliente-show-table-wrap">
                @if ($historialActivo === 'presupuestos')
                    <form method="GET" action="{{ route('clientes.show', $cliente->id) }}" class="cliente-show-search-form">
                        <input type="hidden" name="historial" value="presupuestos">
                        <div class="cliente-show-search-grid">
                            <div class="cliente-show-search-field cliente-show-search-field--wide">
                                <label for="busqueda-presupuestos">Buscar</label>
                                <input id="busqueda-presupuestos" type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Nº presupuesto, OT, título, documento o ID">
                            </div>
                            <div class="cliente-show-search-field">
                                <label for="fecha-desde-presupuestos">Desde</label>
                                <input id="fecha-desde-presupuestos" type="date" name="fecha_desde" value="{{ $fechaDesde }}">
                            </div>
                            <div class="cliente-show-search-field">
                                <label for="fecha-hasta-presupuestos">Hasta</label>
                                <input id="fecha-hasta-presupuestos" type="date" name="fecha_hasta" value="{{ $fechaHasta }}">
                            </div>
                            <div class="cliente-show-search-field">
                                <label for="estado-presupuestos">Estado</label>
                                <select id="estado-presupuestos" name="estado">
                                    <option value="todos" {{ $estadoFiltro === 'todos' ? 'selected' : '' }}>Todos</option>
                                    <option value="pendiente" {{ $estadoFiltro === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="recibido" {{ $estadoFiltro === 'recibido' ? 'selected' : '' }}>Recibido</option>
                                    <option value="entregado" {{ $estadoFiltro === 'entregado' ? 'selected' : '' }}>Entregado</option>
                                </select>
                            </div>
                            <div class="cliente-show-search-actions">
                                <button type="submit" class="cliente-show-search-btn">Buscar</button>
                                <a href="{{ route('clientes.show', ['cliente' => $cliente->id, 'historial' => 'presupuestos']) }}" class="cliente-show-search-reset">Limpiar</a>
                            </div>
                        </div>
                    </form>

                    <table class="table cliente-show-table">
                        <thead>
                            <tr>
                                <th>OT</th>
                                <th>N° Presupuesto</th>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($presupuestos as $presupuesto)
                                <tr>
                                    <td class="ot-cell">{{ $presupuesto->ot ?: 'OT-' . str_pad((string) $presupuesto->id, 4, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        @canany(['presupuestos.download', 'clientes.export'])
                                            @if ($presupuesto->archivo_pdf)
                                                <a href="{{ route('presupuestos.pdf', $presupuesto->id) }}" target="_blank" rel="noopener" class="presupuesto-numero-link">
                                                    {{ $presupuesto->numero ?: 'SIN-NÚMERO' }}
                                                </a>
                                            @else
                                                <span class="presupuesto-numero-disabled">{{ $presupuesto->numero ?: 'SIN-NÚMERO' }}</span>
                                            @endif
                                        @else
                                            <span class="presupuesto-numero-disabled">{{ $presupuesto->numero ?: 'SIN-NÚMERO' }}</span>
                                        @endcanany
                                    </td>
                                    <td>{{ $presupuesto->fecha ? $presupuesto->fecha->format('d/m/Y') : 'N/D' }}</td>
                                    <td>{{ $presupuesto->titulo ?: ($presupuesto->documento ?: 'Sin descripción') }}</td>
                                    <td>
                                        <span class="estado-pill estado-{{ $presupuesto->ui_estado }}">{{ $presupuesto->ui_estado_label }}</span>
                                    </td>
                                    <td class="total-cell">
                                        @if ($presupuesto->ui_total !== null)
                                            {{ number_format($presupuesto->ui_total, 2, ',', '.') }} €
                                        @else
                                            N/D
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @canany(['presupuestos.download', 'clientes.export'])
                                            @if ($presupuesto->archivo_pdf)
                                                <a href="{{ route('presupuestos.pdf', $presupuesto->id) }}" target="_blank" rel="noopener" class="accion-pdf-btn">
                                                    <i class="far fa-file-pdf" aria-hidden="true"></i>
                                                    Ver PDF
                                                </a>
                                            @else
                                                <span class="accion-sin-pdf">Sin PDF</span>
                                            @endif
                                        @else
                                            <span class="text-muted" title="Sin permisos de descarga"><i class="fas fa-lock"></i></span>
                                        @endcanany
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">No hay presupuestos registrados para este cliente.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @elseif ($historialActivo === 'pedidos')
                    <form method="GET" action="{{ route('clientes.show', $cliente->id) }}" class="cliente-show-search-form">
                        <input type="hidden" name="historial" value="pedidos">
                        <div class="cliente-show-search-grid">
                            <div class="cliente-show-search-field cliente-show-search-field--wide">
                                <label for="busqueda-pedidos">Buscar</label>
                                <input id="busqueda-pedidos" type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Nº pedido, OT, presupuesto, estado o texto de líneas">
                            </div>
                            <div class="cliente-show-search-field">
                                <label for="fecha-desde-pedidos">Desde</label>
                                <input id="fecha-desde-pedidos" type="date" name="fecha_desde" value="{{ $fechaDesde }}">
                            </div>
                            <div class="cliente-show-search-field">
                                <label for="fecha-hasta-pedidos">Hasta</label>
                                <input id="fecha-hasta-pedidos" type="date" name="fecha_hasta" value="{{ $fechaHasta }}">
                            </div>
                            <div class="cliente-show-search-field">
                                <label for="estado-pedidos">Estado</label>
                                <select id="estado-pedidos" name="estado">
                                    <option value="todos" {{ $estadoFiltro === 'todos' ? 'selected' : '' }}>Todos</option>
                                    <option value="pendiente" {{ $estadoFiltro === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="facturado_parcial" {{ $estadoFiltro === 'facturado_parcial' ? 'selected' : '' }}>Facturado parcial</option>
                                    <option value="facturado" {{ $estadoFiltro === 'facturado' ? 'selected' : '' }}>Facturado</option>
                                </select>
                            </div>
                            <div class="cliente-show-search-actions">
                                <button type="submit" class="cliente-show-search-btn">Buscar</button>
                                <a href="{{ route('clientes.show', ['cliente' => $cliente->id, 'historial' => 'pedidos']) }}" class="cliente-show-search-reset">Limpiar</a>
                            </div>
                        </div>
                    </form>

                    <table class="table cliente-show-table">
                        <thead>
                            <tr>
                                <th>Nº Pedido</th>
                                <th>Presupuesto origen</th>
                                <th>OT</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Albaranes</th>
                                <th>Total</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pedidos as $pedido)
                                <tr>
                                    <td>
                                        @can('pedidos.view')
                                        <a href="{{ route('pedidos-clientes.show', $pedido) }}" class="presupuesto-numero-link">
                                            {{ $pedido->numero_pedido }}
                                        </a>
                                        @else
                                        <span class="presupuesto-numero-disabled">{{ $pedido->numero_pedido }}</span>
                                        @endcan
                                    </td>
                                    <td>
                                        @if ($pedido->ui_presupuesto_numero)
                                            <span class="pedido-meta-link">{{ $pedido->ui_presupuesto_numero }}</span>
                                        @else
                                            <span class="presupuesto-numero-disabled">—</span>
                                        @endif
                                    </td>
                                    <td class="ot-cell">{{ $pedido->ot ?: 'Sin OT' }}</td>
                                    <td>{{ optional($pedido->fecha_pedido)->format('d/m/Y') ?: 'N/D' }}</td>
                                    <td>
                                        <span class="estado-pill estado-{{ str_replace('_', '-', $pedido->ui_estado) }}">{{ $pedido->ui_estado_label }}</span>
                                    </td>
                                    <td>{{ $pedido->ui_albaranes_count > 0 ? $pedido->ui_albaranes_count : '0' }}</td>
                                    <td class="total-cell">
                                        {{ number_format((float) ($pedido->ui_total ?? 0), 2, ',', '.') }} €
                                    </td>
                                    <td class="text-center">
                                        <div class="cliente-show-action-group">
                                            @can('pedidos.view')
                                            <a href="{{ route('pedidos-clientes.show', $pedido) }}" class="cliente-show-mini-btn">
                                                Ver pedido
                                            </a>
                                            @endcan
                                            @can('albaranes.view')
                                            <a href="{{ route('pedidos-clientes.albaranes', $pedido) }}" class="cliente-show-mini-btn cliente-show-mini-btn--soft">
                                                Albaranes
                                            </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">No hay pedidos registrados para este cliente.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <form method="GET" action="{{ route('clientes.show', $cliente->id) }}" class="cliente-show-search-form">
                        <input type="hidden" name="historial" value="albaranes">
                        <div class="cliente-show-search-grid">
                            <div class="cliente-show-search-field cliente-show-search-field--wide">
                                <label for="busqueda-albaranes">Buscar</label>
                                <input id="busqueda-albaranes" type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Nº albarán, pedido, OT, título, estado o texto de líneas">
                            </div>
                            <div class="cliente-show-search-field">
                                <label for="fecha-desde-albaranes">Desde</label>
                                <input id="fecha-desde-albaranes" type="date" name="fecha_desde" value="{{ $fechaDesde }}">
                            </div>
                            <div class="cliente-show-search-field">
                                <label for="fecha-hasta-albaranes">Hasta</label>
                                <input id="fecha-hasta-albaranes" type="date" name="fecha_hasta" value="{{ $fechaHasta }}">
                            </div>
                            <div class="cliente-show-search-field">
                                <label for="estado-albaranes">Estado</label>
                                <select id="estado-albaranes" name="estado">
                                    <option value="todos" {{ $estadoFiltro === 'todos' ? 'selected' : '' }}>Todos</option>
                                    <option value="pendiente" {{ $estadoFiltro === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="recibido" {{ $estadoFiltro === 'recibido' ? 'selected' : '' }}>Recibido</option>
                                    <option value="entregado" {{ $estadoFiltro === 'entregado' ? 'selected' : '' }}>Entregado</option>
                                </select>
                            </div>
                            <div class="cliente-show-search-actions">
                                <button type="submit" class="cliente-show-search-btn">Buscar</button>
                                <a href="{{ route('clientes.show', ['cliente' => $cliente->id, 'historial' => 'albaranes']) }}" class="cliente-show-search-reset">Limpiar</a>
                            </div>
                        </div>
                    </form>

                    <table class="table cliente-show-table">
                        <thead>
                            <tr>
                                <th>Nº Albarán</th>
                                <th>Pedido asociado</th>
                                <th>OT</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($albaranes as $albaran)
                                <tr>
                                    <td>
                                        @canany(['albaranes.download', 'clientes.export'])
                                            <a href="{{ route('albaranes.pdf', $albaran) }}" target="_blank" rel="noopener" class="presupuesto-numero-link">
                                                {{ $albaran->numero ?: 'SIN-NÚMERO' }}
                                            </a>
                                        @else
                                            <span class="presupuesto-numero-disabled">{{ $albaran->numero ?: 'SIN-NÚMERO' }}</span>
                                        @endcanany
                                    </td>
                                    <td>
                                        @if ($albaran->ui_pedido_id)
                                            <a href="{{ route('pedidos-clientes.show', $albaran->ui_pedido_id) }}" class="pedido-meta-link">
                                                {{ $albaran->ui_pedido_numero }}
                                            </a>
                                        @elseif ($albaran->ui_pedido_numero)
                                            <span class="pedido-meta-link">{{ $albaran->ui_pedido_numero }}</span>
                                        @else
                                            <span class="presupuesto-numero-disabled">—</span>
                                        @endif
                                    </td>
                                    <td class="ot-cell">{{ $albaran->ot ?: 'Sin OT' }}</td>
                                    <td>{{ optional($albaran->fecha)->format('d/m/Y') ?: 'N/D' }}</td>
                                    <td>
                                        <span class="estado-pill estado-{{ str_replace('_', '-', $albaran->ui_estado) }}">{{ $albaran->ui_estado_label }}</span>
                                    </td>
                                    <td class="total-cell">
                                        @if ($albaran->ui_total !== null)
                                            {{ number_format((float) $albaran->ui_total, 2, ',', '.') }} €
                                        @else
                                            N/D
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="cliente-show-action-group">
                                            @canany(['albaranes.download', 'clientes.export'])
                                                @if ($albaran->archivo_pdf)
                                                    <a href="{{ route('albaranes.pdf', $albaran) }}" target="_blank" rel="noopener" class="cliente-show-mini-btn">
                                                        Ver PDF
                                                    </a>
                                                @else
                                                    <span class="accion-sin-pdf">Sin PDF</span>
                                                @endif
                                                <a href="{{ route('albaranes.preview', $albaran) }}" class="cliente-show-mini-btn cliente-show-mini-btn--soft">
                                                    Previsualizar
                                                </a>
                                            @else
                                                <span class="text-muted" title="Sin permisos de descarga"><i class="fas fa-lock"></i></span>
                                            @endcanany
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">No hay albaranes registrados para este cliente.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>

            <footer class="cliente-show-footer">
                <p>Mostrando {{ $historialRegistros->count() }} de {{ $historialRegistros->total() }} registros</p>

                @if ($historialRegistros->hasPages())
                    <nav aria-label="Paginación historial" class="cliente-show-pagination">
                        <a href="{{ $historialRegistros->onFirstPage() ? '#' : $historialRegistros->previousPageUrl() }}" class="page-btn {{ $historialRegistros->onFirstPage() ? 'is-disabled' : '' }}">Anterior</a>

                        @foreach ($historialRegistros->getUrlRange(max(1, $historialRegistros->currentPage() - 1), min($historialRegistros->lastPage(), $historialRegistros->currentPage() + 1)) as $page => $url)
                            <a href="{{ $url }}" class="page-number {{ $page === $historialRegistros->currentPage() ? 'is-active' : '' }}">{{ $page }}</a>
                        @endforeach

                        <a href="{{ $historialRegistros->hasMorePages() ? $historialRegistros->nextPageUrl() : '#' }}" class="page-btn {{ $historialRegistros->hasMorePages() ? '' : 'is-disabled' }}">Siguiente</a>
                    </nav>
                @endif
            </footer>
        </article>

        <div class="cliente-show-back">
            <a href="{{ route('clientes.index') }}" class="btn-volver-listado">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Atrás al listado
            </a>
        </div>
    </section>
@endsection

@section('css')
    @vite(['resources/css/clientes-show.css'])
@endsection
