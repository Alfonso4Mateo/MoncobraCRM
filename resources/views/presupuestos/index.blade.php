@extends('adminlte::page')

@section('title', 'Presupuestos - MoncobraCRM')

@section('css')
    @vite(['resources/css/presupuestos-index.css'])
@endsection

@section('content_header')
    <div class="presupuestos-header">
        <div class="presupuestos-header__copy">
            <h1>Seguimiento de Presupuestos</h1>
            <p>Visualiza, filtra y gestiona las ofertas comerciales del proyecto activo.</p>
        </div>

        <div class="presupuestos-actions">
            @if(auth()->check() && in_array(auth()->user()->role, ['admin','superadmin'], true))
                <a href="{{ route('presupuestos.correlativo.edit') }}" class="presupuestos-create-btn">
                    <i class="fas fa-cog"></i>
                    Ajustar correlativo
                </a>
            @endif

            <a href="{{ route('presupuestos.create') }}" class="presupuestos-create-btn">
                <i class="fas fa-plus"></i>
                Nuevo Presupuesto
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="presupuestos-shell">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-circle-check"></i>
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="presupuestos-filters-card">
            <form method="GET" action="{{ route('presupuestos.index') }}" class="presupuestos-search-form">
                    <div class="presupuestos-search-copy">
                    <span class="presupuestos-search-label">Buscador</span>
                    <h2>Encuentra presupuestos por cliente, OT o fecha</h2>
                    <p>Escribe un texto, una OT, un cliente o una fecha para localizar el registro.</p>
                </div>

                <div class="presupuestos-search-controls">
                    <div class="presupuestos-input-group">
                        <i class="fas fa-search"></i>
                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Buscar por cliente, OT o fecha"
                            autocomplete="off"
                        >
                    </div>

                    <div class="presupuestos-actions">
                        <button type="submit" class="presupuestos-search-btn">
                            Buscar
                        </button>

                        @if($search !== '')
                            <a href="{{ route('presupuestos.index') }}" class="presupuestos-reset-btn">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="presupuestos-card">
            <div class="presupuestos-card__header">
                <div>
                    <h3>Listado de presupuestos</h3>
                    <p>{{ $presupuestos->total() }} resultados encontrados</p>
                </div>

                <div class="presupuestos-card__meta">
                    <span class="meta-pill">Proyecto activo</span>
                    <span class="meta-pill meta-pill--soft">Página {{ $presupuestos->currentPage() }} de {{ $presupuestos->lastPage() }}</span>
                </div>
            </div>

            <div class="table-responsive presupuestos-table-wrap">
                <table class="table presupuestos-table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>OT</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($presupuestos as $presupuesto)
                            <tr>
                                <td data-label="Número">
                                    <span class="presupuesto-reference">{{ $presupuesto->numero }}</span>
                                </td>
                                <td data-label="Fecha">
                                    <span class="presupuesto-date">
                                        {{ optional($presupuesto->fecha)->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td data-label="Cliente">
                                    <div class="presupuesto-client">
                                        <strong>{{ $presupuesto->cliente?->empresa_nombre ?? 'Sin cliente' }}</strong>
                                        @if($presupuesto->cliente?->localidad)
                                            <span>{{ $presupuesto->cliente->localidad }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="OT">
                                    <span class="presupuesto-ot">
                                        {{ $presupuesto->ot ?: 'Sin OT' }}
                                    </span>
                                </td>
                                <td data-label="Total">
                                    <span class="presupuesto-total">
                                        {{ number_format((float) ($presupuesto->total ?? 0), 2, ',', '.') }} EUR
                                    </span>
                                </td>
                                <td data-label="Estado">
                                    @php
                                        $estado = (string) ($presupuesto->estado ?: 'pendiente');
                                        $estadoClass = match ($estado) {
                                            'aceptado' => 'estado-pill estado-aceptado',
                                            'rechazado' => 'estado-pill estado-rechazado',
                                            'pendiente pedido' => 'estado-pill estado-pendiente-pedido',
                                            default => 'estado-pill estado-pendiente',
                                        };
                                    @endphp
                                    <span class="{{ $estadoClass }}">{{ ucfirst($estado) }}</span>
                                </td>
                                <td data-label="Acciones" class="text-right">
                                    <div class="presupuesto-action-group">
                                        <a href="{{ route('presupuestos.show', $presupuesto) }}" class="presupuesto-action-btn presupuesto-action-btn--view" aria-label="Ver presupuesto" title="Ver presupuesto">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('presupuestos.edit', $presupuesto) }}" class="presupuesto-action-btn presupuesto-action-btn--edit" aria-label="Editar presupuesto" title="Editar presupuesto">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="{{ route('pedidos-clientes.create', ['presupuesto_id' => $presupuesto->id]) }}" class="presupuesto-action-btn presupuesto-action-btn--order" aria-label="Crear pedido" title="Crear pedido">
                                            <i class="fas fa-cart-plus"></i>
                                        </a>

                                        @php
                                            $dropdownId = 'presupuesto-estado-dropdown-' . $presupuesto->id;
                                        @endphp
                                        <div class="dropdown presupuesto-dropdown">
                                            <button
                                                type="button"
                                                class="presupuesto-action-btn--state dropdown-toggle"
                                                id="{{ $dropdownId }}"
                                                data-toggle="dropdown"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                aria-label="Más acciones"
                                                title="Más acciones"
                                            >
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="{{ $dropdownId }}">
                                                <h6 class="dropdown-header">Cambiar estado</h6>
                                                <form method="POST" action="{{ route('presupuestos.estado.update', $presupuesto) }}" class="presupuesto-estado-menu-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="dropdown-item" type="submit" name="estado" value="pendiente">Pendiente</button>
                                                    <button class="dropdown-item" type="submit" name="estado" value="aceptado">Aceptado</button>
                                                    <button class="dropdown-item" type="submit" name="estado" value="rechazado">Rechazado</button>
                                                    <button class="dropdown-item" type="submit" name="estado" value="pendiente pedido">Pendiente pedido</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="presupuestos-empty-state">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                        <h4>No hay presupuestos para mostrar</h4>
                                        <p>Prueba a cambiar la búsqueda o crea un nuevo presupuesto para empezar.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="presupuestos-pagination">
                {{ $presupuestos->links() }}
            </div>
        </div>
    </div>
@endsection