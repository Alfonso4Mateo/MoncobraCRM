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

@section('content_header')
    <div class="presupuestos-header">
        <div class="presupuestos-header__copy">
            <h1>Seguimiento de Presupuestos</h1>
            <p>Visualiza, filtra y gestiona las ofertas comerciales del proyecto activo.</p>
        </div>

        <div class="presupuestos-actions">
            @can('presupuestos.manage')
                <!-- Correlativo está vinculado a la gestión -->
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
            @endcan
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
                    <h2>Encuentra presupuestos por numero, cliente, OT, fecha o estado</h2>
                    <p>Usa texto libre y combina rango de fechas con estado para acotar resultados.</p>
                </div>

                <div class="presupuestos-search-grid">
                    <div class="presupuestos-input-group">
                        <i class="fas fa-search"></i>
                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Buscar por numero, cliente, OT"
                            autocomplete="off"
                        >
                    </div>

                    <div class="presupuestos-filter-field">
                        <label for="fecha_desde">Desde</label>
                        <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $fechaDesde ?? '' }}">
                    </div>

                    <div class="presupuestos-filter-field">
                        <label for="fecha_hasta">Hasta</label>
                        <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $fechaHasta ?? '' }}">
                    </div>

                    <div class="presupuestos-filter-field">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="todos" {{ ($estado ?? 'todos') === 'todos' ? 'selected' : '' }}>Todos</option>
                            <option value="pendiente" {{ ($estado ?? '') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="aceptado" {{ ($estado ?? '') === 'aceptado' ? 'selected' : '' }}>Aceptado</option>
                            <option value="rechazado" {{ ($estado ?? '') === 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                            <option value="pendiente pedido" {{ ($estado ?? '') === 'pendiente pedido' ? 'selected' : '' }}>Pendiente pedido</option>
                        </select>
                    </div>

                    <div class="presupuestos-actions">
                        <button type="submit" class="presupuestos-search-btn">
                            Buscar
                        </button>

                        @if($search !== '' || !empty($fechaDesde) || !empty($fechaHasta) || (($estado ?? 'todos') !== 'todos'))
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
                                        
                                        <!-- Ver Detalle -->
                                        <a href="{{ route('presupuestos.show', $presupuesto) }}" class="presupuesto-action-btn presupuesto-action-btn--view" aria-label="Ver presupuesto" title="Ver presupuesto">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Editar -->
                                        @can('presupuestos.manage')
                                            <a href="{{ route('presupuestos.edit', $presupuesto) }}" class="presupuesto-action-btn presupuesto-action-btn--edit" aria-label="Editar presupuesto" title="Editar presupuesto">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                        @endcan

                                        <!-- Eliminar -->
                                        @can('presupuestos.delete')
                                            <button
                                                type="button"
                                                class="presupuesto-action-btn presupuesto-action-btn--danger"
                                                data-delete-presupuesto
                                                data-delete-url="{{ route('presupuestos.destroy', $presupuesto) }}"
                                                data-pedido-numero="{{ $presupuesto->pedidoCliente?->numero_pedido ?? '' }}"
                                                aria-label="Eliminar presupuesto"
                                                title="Eliminar presupuesto"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @endcan

                                        <!-- Crear Pedido (OJO: En el futuro requerirá permiso de pedidos.manage) -->
                                        @if(in_array($estado, ['pendiente', 'pendiente pedido'], true))
                                            <a href="{{ route('pedidos-clientes.create', ['presupuesto_id' => $presupuesto->id]) }}" class="presupuesto-action-btn presupuesto-action-btn--order" aria-label="Crear pedido" title="Crear pedido">
                                                <i class="fas fa-cart-plus"></i>
                                            </a>
                                        @else
                                            <button type="button" class="presupuesto-action-btn presupuesto-action-btn--order" disabled title="No es posible crear pedido desde un presupuesto {{ $estado }}">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        @endif

                                        <!-- Cambiar Estado (Requiere manage) -->
                                        @can('presupuestos.manage')
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
                                        @endcan
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

    <!-- Modales para borrado (igual que antes) -->
    <form id="presupuesto-delete-form" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    <div class="modal fade presupuesto-delete-modal presupuesto-delete-modal--blocked" id="presupuestoDeleteBlockedModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered presupuesto-delete-modal__dialog" role="document">
            <div class="modal-content presupuesto-delete-modal__content">
                <div class="modal-header presupuesto-delete-modal__header">
                    <div class="presupuesto-delete-modal__title-wrap">
                        <span class="presupuesto-delete-modal__icon presupuesto-delete-modal__icon--blocked">
                            <i class="fas fa-ban" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h5 class="modal-title">No se puede borrar el presupuesto</h5>
                            <p class="presupuesto-delete-modal__subtitle">El presupuesto tiene un pedido asociado.</p>
                        </div>
                    </div>
                    <button type="button" class="close presupuesto-delete-modal__close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body presupuesto-delete-modal__body">
                    <p class="presupuesto-delete-modal__message mb-0" id="presupuestoDeleteBlockedMessage"></p>
                </div>
                <div class="modal-footer presupuesto-delete-modal__footer">
                    <button type="button" class="btn presupuesto-delete-modal__btn presupuesto-delete-modal__btn--primary" data-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade presupuesto-delete-modal presupuesto-delete-modal--confirm" id="presupuestoDeleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered presupuesto-delete-modal__dialog" role="document">
            <div class="modal-content presupuesto-delete-modal__content">
                <div class="modal-header presupuesto-delete-modal__header">
                    <div class="presupuesto-delete-modal__title-wrap">
                        <span class="presupuesto-delete-modal__icon presupuesto-delete-modal__icon--danger">
                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h5 class="modal-title">Eliminar presupuesto</h5>
                            <p class="presupuesto-delete-modal__subtitle">Accion irreversible.</p>
                        </div>
                    </div>
                    <button type="button" class="close presupuesto-delete-modal__close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body presupuesto-delete-modal__body">
                    <p id="presupuestoDeleteConfirmMessage" class="presupuesto-delete-modal__message mb-0"></p>
                </div>
                <div class="modal-footer presupuesto-delete-modal__footer">
                    <button type="button" class="btn presupuesto-delete-modal__btn presupuesto-delete-modal__btn--ghost" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn presupuesto-delete-modal__btn presupuesto-delete-modal__btn--danger" id="presupuestoDeleteConfirmButton">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForm = document.getElementById('presupuesto-delete-form');
            const confirmModal = document.getElementById('presupuestoDeleteConfirmModal');
            const blockedModal = document.getElementById('presupuestoDeleteBlockedModal');
            const confirmMessage = document.getElementById('presupuestoDeleteConfirmMessage');
            const blockedMessage = document.getElementById('presupuestoDeleteBlockedMessage');
            const confirmButton = document.getElementById('presupuestoDeleteConfirmButton');

            let currentDeleteUrl = '';

            const showModal = (modalElement) => {
                if (!modalElement) {
                    return;
                }

                if (window.$ && typeof window.$(modalElement).modal === 'function') {
                    window.$(modalElement).modal('show');
                    return;
                }

                modalElement.classList.add('show');
                modalElement.style.display = 'block';
                modalElement.setAttribute('aria-modal', 'true');
            };

            document.querySelectorAll('[data-delete-presupuesto]').forEach((button) => {
                button.addEventListener('click', function () {
                    const deleteUrl = this.getAttribute('data-delete-url') || '';
                    const pedidoNumero = this.getAttribute('data-pedido-numero') || '';

                    currentDeleteUrl = deleteUrl;

                    if (pedidoNumero !== '') {
                        blockedMessage.textContent = `No se puede borrar el presupuesto porque tiene el pedido asociado: ${pedidoNumero}.`;
                        showModal(blockedModal);
                        return;
                    }

                    confirmMessage.textContent = 'Quieres borrar este presupuesto?';
                    showModal(confirmModal);
                });
            });

            if (confirmButton) {
                confirmButton.addEventListener('click', function () {
                    if (!currentDeleteUrl || !deleteForm) {
                        return;
                    }

                    deleteForm.setAttribute('action', currentDeleteUrl);
                    deleteForm.submit();
                });
            }
        });
    </script>
@endsection