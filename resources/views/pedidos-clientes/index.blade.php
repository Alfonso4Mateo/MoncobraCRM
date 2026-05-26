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

        <div class="pedidos-clientes-header__actions">
            @if(auth()->check() && in_array(auth()->user()->role, ['admin','superadmin'], true))
                <a href="{{ route('pedidos-clientes.correlativo.edit') }}" class="pedidos-clientes-create-btn pedidos-clientes-create-btn--muted">
                    <i class="fas fa-hashtag" aria-hidden="true"></i>
                    Ajustar correlativo
                </a>
            @endif

            <a href="{{ route('pedidos-clientes.create') }}" class="pedidos-clientes-create-btn">
                <i class="fas fa-plus" aria-hidden="true"></i>
                Nuevo Pedido
            </a>
        </div>
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

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-triangle-exclamation"></i>
                {{ session('error') }}
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
                            <th>Acciones</th>
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
                                    @php
                                        $albaranesCount = (int) ($pedido->ui_albaranes_count ?? 0);
                                    @endphp

                                    <a href="{{ route('pedidos-clientes.albaranes', $pedido) }}" class="pedido-albaran-btn pedido-albaran-btn--view" title="Ver albarán/es" aria-label="Ver albarán/es">
                                        <i class="fas fa-file-invoice" aria-hidden="true"></i>
                                        Ver albarán/es
                                    </a>
                                </td>
                                <td data-label="Total" class="text-right">
                                    <strong class="pedido-total">€{{ number_format((float) ($pedido->ui_total ?? 0), 2, ',', '.') }}</strong>
                                </td>
                                <td data-label="Acciones">
                                    <div class="presupuesto-action-group">
                                        <a href="{{ route('pedidos-clientes.show', $pedido) }}" class="presupuesto-action-btn presupuesto-action-btn--view" aria-label="Ver pedido" title="Ver pedido">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('pedidos-clientes.preview', $pedido) }}" class="presupuesto-action-btn presupuesto-action-btn--view" aria-label="Previsualizar PDF del pedido" title="Previsualizar PDF del pedido">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>

                                        @php
                                            $dropdownId = 'pedido-estado-dropdown-' . $pedido->id;
                                        @endphp
                                        <div class="dropdown presupuesto-dropdown">
                                            <button
                                                type="button"
                                                class="presupuesto-action-btn--state dropdown-toggle"
                                                id="{{ $dropdownId }}"
                                                data-toggle="dropdown"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                aria-label="Cambiar estado"
                                                title="Cambiar estado de facturación"
                                            >
                                                <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="{{ $dropdownId }}">
                                                <h6 class="dropdown-header">Cambiar estado de facturación</h6>
                                                <form method="POST" action="{{ route('pedidos-clientes.estado.update', $pedido) }}" class="estado-menu-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="dropdown-item" type="submit" name="estado" value="pendiente">Pendiente</button>
                                                    <button class="dropdown-item" type="submit" name="estado" value="facturado_parcial">Facturado Parcial</button>
                                                    <button class="dropdown-item" type="submit" name="estado" value="facturado">Facturado</button>
                                                </form>
                                            </div>
                                        </div>

                                        @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin'], true))
                                            <button
                                                type="button"
                                                class="presupuesto-action-btn presupuesto-action-btn--danger"
                                                data-delete-pedido
                                                data-delete-url="{{ route('pedidos-clientes.destroy', $pedido) }}"
                                                data-pedido-numero="{{ $pedido->numero_pedido }}"
                                                data-presupuesto-numero="{{ $pedido->ui_presupuesto_numero ?? 'Sin presupuesto' }}"
                                                data-albaranes-count="{{ $albaranesCount }}"
                                                aria-label="Eliminar pedido"
                                                title="Eliminar pedido"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
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

        <form id="pedido-delete-form" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>

        <div class="modal fade pedido-delete-modal pedido-delete-modal--blocked" id="pedidoDeleteBlockedModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered pedido-delete-modal__dialog" role="document">
                <div class="modal-content pedido-delete-modal__content">
                    <div class="modal-header pedido-delete-modal__header">
                        <div class="pedido-delete-modal__title-wrap">
                            <span class="pedido-delete-modal__icon pedido-delete-modal__icon--blocked">
                                <i class="fas fa-ban" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h5 class="modal-title">No se puede borrar el pedido</h5>
                                <p class="pedido-delete-modal__subtitle">El pedido tiene albaranes asociados.</p>
                            </div>
                        </div>
                        <button type="button" class="close pedido-delete-modal__close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body pedido-delete-modal__body">
                        <p class="pedido-delete-modal__message mb-0" id="pedidoDeleteBlockedMessage"></p>
                    </div>
                    <div class="modal-footer pedido-delete-modal__footer">
                        <button type="button" class="btn pedido-delete-modal__btn pedido-delete-modal__btn--primary" data-dismiss="modal">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade pedido-delete-modal pedido-delete-modal--confirm" id="pedidoDeleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered pedido-delete-modal__dialog" role="document">
                <div class="modal-content pedido-delete-modal__content">
                    <div class="modal-header pedido-delete-modal__header">
                        <div class="pedido-delete-modal__title-wrap">
                            <span class="pedido-delete-modal__icon pedido-delete-modal__icon--danger">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h5 class="modal-title">Eliminar pedido</h5>
                                <p class="pedido-delete-modal__subtitle">Acción irreversible con reactivación del presupuesto.</p>
                            </div>
                        </div>
                        <button type="button" class="close pedido-delete-modal__close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body pedido-delete-modal__body">
                        <p id="pedidoDeleteConfirmMessage" class="pedido-delete-modal__message mb-0"></p>
                    </div>
                    <div class="modal-footer pedido-delete-modal__footer">
                        <button type="button" class="btn pedido-delete-modal__btn pedido-delete-modal__btn--ghost" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn pedido-delete-modal__btn pedido-delete-modal__btn--danger" id="pedidoDeleteConfirmButton">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForm = document.getElementById('pedido-delete-form');
            const confirmModal = document.getElementById('pedidoDeleteConfirmModal');
            const blockedModal = document.getElementById('pedidoDeleteBlockedModal');
            const confirmMessage = document.getElementById('pedidoDeleteConfirmMessage');
            const blockedMessage = document.getElementById('pedidoDeleteBlockedMessage');
            const confirmButton = document.getElementById('pedidoDeleteConfirmButton');

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

            document.querySelectorAll('[data-delete-pedido]').forEach((button) => {
                button.addEventListener('click', function () {
                    const deleteUrl = this.getAttribute('data-delete-url') || '';
                    const pedidoNumero = this.getAttribute('data-pedido-numero') || '';
                    const presupuestoNumero = this.getAttribute('data-presupuesto-numero') || 'Sin presupuesto';
                    const albaranesCount = Number.parseInt(this.getAttribute('data-albaranes-count') || '0', 10);

                    currentDeleteUrl = deleteUrl;

                    if (albaranesCount > 0) {
                        blockedMessage.textContent = `Este pedido tiene ${albaranesCount} albarán/es asignado/s y no podrá ser borrado.`;
                        showModal(blockedModal);
                        return;
                    }

                    confirmMessage.textContent = `¿Seguro que quieres borrar este pedido? El presupuesto ${presupuestoNumero} pasará a estado pendiente.`;
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
