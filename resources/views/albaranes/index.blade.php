@extends('adminlte::page')

@section('title', 'Albaranes Clientes - MoncobraCRM')

@section('header-title')
    <i class="fas fa-file-alt"></i> Albaranes Clientes
@endsection

@section('content')
    @php
        $textoVariacionMensual = ($variacionMensual >= 0 ? '+' : '') . number_format($variacionMensual, 1, ',', '.') . '% vs mes ant.';
        $textoVariacionEntregados = ($variacionEntregadosHoy >= 0 ? '+' : '') . number_format($variacionEntregadosHoy, 1, ',', '.') . '% hoy';
    @endphp

    <section class="albaranes-clientes-ui">
        <section class="albaranes-page-head">
            <h1>Albaranes Clientes</h1>
        </section>

        @if (session('error'))
            <div class="albaranes-alert-error">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="albaranes-alert-success">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                {{ session('success') }}
            </div>
        @endif

        <header class="albaranes-toolbar">
            <div class="albaranes-toolbar-actions">
                @if(auth()->check() && in_array(auth()->user()->role, ['admin','superadmin'], true))
                    <a href="{{ route('albaranes.correlativo.edit') }}" class="toolbar-main-btn toolbar-main-btn--muted" title="Ajustar correlativo de albaranes">
                        Ajustar correlativo
                        <i class="fas fa-hashtag"></i>
                    </a>
                @endif
                <a href="{{ route('albaranes.create') }}" class="toolbar-main-btn">
                    Crear Albarán
                    <i class="fas fa-plus"></i>
                </a>
            </div>
        </header>

        <div class="albaranes-kpis-grid">
            <article class="kpi-card">
                <div class="kpi-head">
                    <span class="kpi-icon kpi-blue"><i class="far fa-file-alt"></i></span>
                    <span class="kpi-badge">{{ $textoVariacionMensual }}</span>
                </div>
                <p class="kpi-title">Albaranes Totales</p>
                <p class="kpi-value">{{ number_format($totalAlbaranes, 0, ',', '.') }}</p>
            </article>

            <article class="kpi-card">
                <div class="kpi-head">
                    <span class="kpi-icon kpi-amber"><i class="far fa-clock"></i></span>
                </div>
                <p class="kpi-title">Pendientes de Entrega</p>
                <p class="kpi-value">{{ number_format($pendientesEntrega, 0, ',', '.') }}</p>
            </article>

            <article class="kpi-card">
                <div class="kpi-head">
                    <span class="kpi-icon kpi-green"><i class="far fa-check-circle"></i></span>
                    <span class="kpi-badge kpi-badge-green">{{ $textoVariacionEntregados }}</span>
                </div>
                <p class="kpi-title">Entregados Hoy</p>
                <p class="kpi-value">{{ number_format($entregadosHoy, 0, ',', '.') }}</p>
            </article>
        </div>

        <article class="albaranes-card">
            <form method="GET" action="{{ route('albaranes.index') }}" class="filters-row">
                <div class="ot-filter-box">
                    <i class="fas fa-filter" aria-hidden="true"></i>
                    <input
                        type="text"
                        name="buscar"
                        value="{{ $buscar }}"
                        placeholder="Buscar por Nº albarán, documento, OT, fecha, cliente, título, pedido, total o estado..."
                        aria-label="Buscar albaranes"
                    >
                </div>

                <label class="date-label" for="desde">Desde:</label>
                <input type="date" id="desde" name="desde" value="{{ $desde }}" class="date-input">

                <label class="date-label" for="hasta">Hasta:</label>
                <input type="date" id="hasta" name="hasta" value="{{ $hasta }}" class="date-input">

                <button type="submit" class="filter-btn">
                    <i class="fas fa-search"></i>
                    Filtrar
                </button>

                <a href="{{ route('albaranes.index') }}" class="clear-btn">Limpiar</a>
            </form>

            <div class="table-responsive table-wrapper">
                <table class="table albaranes-table">
                    <thead>
                        <tr>
                            <th>Nº Albarán</th>
                            <th>Nº Presupuesto</th>
                            <th>OT Asociada</th>
                            <th>Fecha Entrega</th>
                            <th>Cliente</th>
                            <th>Título</th>
                            <th>Nº Pedido</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($albaranes as $albaran)
                            @php
                                $estado = in_array((string) $albaran->estado, ['pendiente', 'recibido', 'entregado'], true)
                                    ? (string) $albaran->estado
                                    : 'pendiente';
                                $pedidoNumero = trim((string) ($albaran->pedido_cliente ?? ''));
                                $total = (float) ($albaran->ui_total ?? 0);
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('albaranes.pdf', $albaran) }}" class="code-link">
                                        {{ $albaran->numero }}
                                    </a>
                                </td>
                                <td>
                                    @if (!empty($albaran->ui_presupuesto_id) && !empty($albaran->ui_presupuesto_numero))
                                        <a href="{{ route('presupuestos.show', $albaran->ui_presupuesto_id) }}" class="code-link">
                                            {{ $albaran->ui_presupuesto_numero }}
                                        </a>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="ot-pill">{{ $albaran->ot ?: 'Sin OT' }}</span>
                                </td>
                                <td>{{ optional($albaran->fecha)->format('d/m/Y') ?: '-' }}</td>
                                <td>{{ $albaran->cliente?->empresa_nombre ?: 'Sin cliente' }}</td>
                                <td>{{ $albaran->titulo ?: '-' }}</td>
                                <td>
                                    @if ($albaran->ui_pedido_id)
                                        <a href="{{ route('pedidos-clientes.show', $albaran->ui_pedido_id) }}" class="code-link">
                                            {{ $pedidoNumero }}
                                        </a>
                                    @elseif ($pedidoNumero !== '')
                                        <a href="{{ route('pedidos.show', $pedidoNumero) }}" class="code-link">
                                            {{ $pedidoNumero }}
                                        </a>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>
                                <td>{{ number_format($total, 2, ',', '.') }}€</td>
                                <td>
                                    <span class="estado-chip estado-{{ $estado }}">{{ strtoupper($estado) }}</span>
                                </td>
                                <td>
                                    <div class="presupuesto-action-group">
                                        <a href="{{ route('albaranes.preview', $albaran) }}" class="presupuesto-action-btn presupuesto-action-btn--view" aria-label="Previsualizar albarán" title="Previsualizar albarán">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('albaranes.pdf', $albaran) }}" class="presupuesto-action-btn presupuesto-action-btn--view" aria-label="Abrir PDF del albarán" title="Abrir PDF del albarán">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <a href="{{ route('albaranes.edit', $albaran) }}" class="presupuesto-action-btn presupuesto-action-btn--edit" aria-label="Editar albarán" title="Editar albarán">
                                            <i class="fas fa-pen"></i>
                                        </a>

                                        @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin'], true))
                                            <button
                                                type="button"
                                                class="presupuesto-action-btn presupuesto-action-btn--danger"
                                                data-delete-albaran
                                                data-delete-url="{{ route('albaranes.destroy', $albaran) }}"
                                                data-albaran-numero="{{ $albaran->numero }}"
                                                data-pedido-numero="{{ $albaran->ui_pedido_numero ?? '' }}"
                                                data-pedido-id="{{ $albaran->ui_pedido_id ?? '' }}"
                                                data-pedido-albaranes-count="{{ (int) ($albaran->ui_pedido_albaranes_count ?? 0) }}"
                                                aria-label="Eliminar albarán"
                                                title="Eliminar albarán"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @endif

                                        @php
                                            $dropdownId = 'albaran-estado-dropdown-' . $albaran->id;
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
                                                title="Cambiar estado"
                                            >
                                                <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="{{ $dropdownId }}">
                                                <h6 class="dropdown-header">Cambiar estado</h6>
                                                <form method="POST" action="{{ route('albaranes.estado.update', $albaran) }}" class="estado-menu-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="dropdown-item" type="submit" name="estado" value="pendiente">Pendiente</button>
                                                    <button class="dropdown-item" type="submit" name="estado" value="recibido">Recibido</button>
                                                    <button class="dropdown-item" type="submit" name="estado" value="entregado">Entregado</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="empty-row">No se encontraron albaranes para el filtro indicado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="table-footer">
                <p>
                    Mostrando {{ $albaranes->firstItem() ?? 0 }} a {{ $albaranes->lastItem() ?? 0 }} de {{ number_format($albaranes->total(), 0, ',', '.') }} albaranes
                </p>
                @if ($albaranes->hasPages())
                    <div class="table-pagination">
                        {{ $albaranes->onEachSide(1)->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </footer>
        </article>

        <form id="albaran-delete-form" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>

        <div class="modal fade albaran-delete-modal" id="albaranDeleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered albaran-delete-modal__dialog" role="document">
                <div class="modal-content albaran-delete-modal__content">
                    <div class="modal-header albaran-delete-modal__header">
                        <div class="albaran-delete-modal__title-wrap">
                            <span class="albaran-delete-modal__icon albaran-delete-modal__icon--danger">
                                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h5 class="modal-title">Eliminar albarán</h5>
                                <p class="albaran-delete-modal__subtitle">Revisa el impacto antes de confirmar.</p>
                            </div>
                        </div>
                        <button type="button" class="close albaran-delete-modal__close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body albaran-delete-modal__body">
                        <p id="albaranDeleteConfirmMessage" class="albaran-delete-modal__message mb-0"></p>
                    </div>
                    <div class="modal-footer albaran-delete-modal__footer">
                        <button type="button" class="btn albaran-delete-modal__btn albaran-delete-modal__btn--ghost" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn albaran-delete-modal__btn albaran-delete-modal__btn--danger" id="albaranDeleteConfirmButton">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('css')
    @vite(['resources/css/albaranes-clientes-index.css'])
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForm = document.getElementById('albaran-delete-form');
            const confirmModal = document.getElementById('albaranDeleteConfirmModal');
            const confirmMessage = document.getElementById('albaranDeleteConfirmMessage');
            const confirmButton = document.getElementById('albaranDeleteConfirmButton');

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

            document.querySelectorAll('[data-delete-albaran]').forEach((button) => {
                button.addEventListener('click', function () {
                    currentDeleteUrl = this.getAttribute('data-delete-url') || '';
                    const albaranNumero = this.getAttribute('data-albaran-numero') || '';
                    const pedidoNumero = this.getAttribute('data-pedido-numero') || '';
                    const pedidoId = this.getAttribute('data-pedido-id') || '';
                    const pedidoAlbaranesCount = Number.parseInt(this.getAttribute('data-pedido-albaranes-count') || '0', 10);

                    if (pedidoId !== '' && pedidoNumero !== '') {
                        const remainingAfterDelete = Math.max(0, pedidoAlbaranesCount - 1);
                        const nextState = remainingAfterDelete > 0 ? 'facturado parcial' : 'pendiente';
                        const detail = remainingAfterDelete > 0
                            ? `Si lo borras, el pedido ${pedidoNumero} seguirá teniendo ${remainingAfterDelete} albarán/es y pasará a estado ${nextState}.`
                            : `Si lo borras, el pedido ${pedidoNumero} pasará a estado ${nextState}.`;

                        confirmMessage.textContent = `¿Estás seguro de que quieres borrar el albarán ${albaranNumero}? ${detail}`;
                    } else {
                        confirmMessage.textContent = `¿Estás seguro de que quieres borrar el albarán ${albaranNumero}? Esta acción no se puede deshacer.`;
                    }

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