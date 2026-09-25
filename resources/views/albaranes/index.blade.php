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
                @can('albaranes.manage')
                    <a href="{{ route('albaranes.correlativo.edit') }}" class="toolbar-main-btn toolbar-main-btn--muted" title="Ajustar correlativo de albaranes">
                        Ajustar correlativo
                        <i class="fas fa-hashtag"></i>
                    </a>
                    <a href="{{ route('albaranes.create') }}" class="toolbar-main-btn">
                        Crear Albarán
                        <i class="fas fa-plus"></i>
                    </a>
                @endcan
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
            <form method="GET" action="{{ route('albaranes.index') }}" class="filters-row" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <!-- 1. Buscador -->
                <div class="ot-filter-box" style="flex-grow: 1; min-width: 250px; margin: 0;">
                    <i class="fas fa-filter" aria-hidden="true"></i>
                    <input
                        type="text"
                        name="buscar"
                        value="{{ $buscar }}"
                        placeholder="Buscar por Nº albarán, documento, CC, fecha, cliente..."
                        aria-label="Buscar albaranes"
                        style="width: 100%;"
                    >
                </div>

                <!-- 2. Fechas -->
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label class="date-label" for="desde" style="margin: 0;">Desde:</label>
                    <input type="date" id="desde" name="desde" value="{{ $desde }}" class="date-input">
                </div>

                <div style="display: flex; align-items: center; gap: 6px;">
                    <label class="date-label" for="hasta" style="margin: 0;">Hasta:</label>
                    <input type="date" id="hasta" name="hasta" value="{{ $hasta }}" class="date-input">
                </div>

                <!-- 3. NUEVO: Selector de Estado Nativo -->
                <div style="display: flex; align-items: center;">
                    <select name="estado" class="date-input" style="min-width: 150px; cursor: pointer;">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" @selected(request('estado') === 'pendiente')>Pendiente</option>
                        <option value="recibido" @selected(request('estado') === 'recibido')>Recibido</option>
                        <option value="facturado" @selected(request('estado') === 'facturado')>Facturado</option>
                        <option value="facturado_parcial" @selected(request('estado') === 'facturado_parcial')>Facturado Parcial</option>
                        <option value="cancelado" @selected(request('estado') === 'cancelado')>Cancelado</option>
                    </select>
                </div>

                <!-- 4. Botones de Acción -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-search"></i> Filtrar
                    </button>

                    <!-- Botón Solo Bolsa -->
                    <a href="{{ route('albaranes.index', array_merge(request()->query(), ['bolsa' => isset($bolsaActual) && $bolsaActual ? 0 : 1])) }}" 
                       class="filter-btn" 
                       style="{{ isset($bolsaActual) && $bolsaActual ? 'background-color: #1e40af; color: white; border-color: #1e40af;' : 'background-color: #f1f5f9; color: #475569; border-color: #cbd5e1;' }} width: auto; white-space: nowrap;" 
                       title="Ver únicamente albaranes vinculados a pedidos bolsa">
                        <i class="fas fa-box{{ isset($bolsaActual) && $bolsaActual ? '-open' : '' }}"></i>
                        {{ isset($bolsaActual) && $bolsaActual ? 'Viendo Solo Bolsa' : 'Solo Bolsa' }}
                    </a>

                    <a href="{{ route('albaranes.index') }}" class="clear-btn" style="width: auto; white-space: nowrap;">
                        Limpiar
                    </a>
                </div>
            </form>
    
            <div class="table-responsive table-wrapper">
                <table class="table albaranes-table">
                    <thead>
                        <tr>
                            <th>Nº Albarán</th>
                            <th>Nº Presupuesto</th>
                            <th>CC Asociada</th>
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
                                $estadoValido = strtolower((string) $albaran->estado);
                                $estado = in_array($estadoValido, ['pendiente', 'recibido', 'facturado', 'facturado_parcial', 'cancelado'], true)
                                    ? $estadoValido
                                    : 'pendiente';
                                $pedidoNumero = trim((string) ($albaran->pedido_cliente ?? ''));
                                $total = (float) ($albaran->ui_total ?? 0);
                                
                                // Detectamos si el albarán está vinculado a alguna bolsa
                                $esBolsa = isset($albaran->pedidosClientes) && $albaran->pedidosClientes->where('bolsa', true)->isNotEmpty();
                            @endphp
                            
                            <!-- Aplicamos el fondo azulado condicionalmente -->
                            <tr @if($esBolsa) style="background-color: #f0f6ff;" @endif>
                                <td>
                                    @can('albaranes.download')
                                        <a href="{{ route('albaranes.pdf', $albaran) }}" class="code-link">
                                            {{ $albaran->numero }}
                                        </a>
                                    @else
                                        <span class="code-link" style="color: #64748b; cursor: not-allowed; text-decoration: none;">{{ $albaran->numero }}</span>
                                    @endcan
                                </td>
                                <td>
                                    @if (!empty($albaran->ui_presupuesto_id) && !empty($albaran->ui_presupuesto_numero))
                                        @can('presupuestos.view')
                                            <a href="{{ route('presupuestos.show', $albaran->ui_presupuesto_id) }}" class="code-link">
                                                {{ $albaran->ui_presupuesto_numero }}
                                            </a>
                                        @else
                                            <span class="muted">{{ $albaran->ui_presupuesto_numero }}</span>
                                        @endcan
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="ot-pill">{{ $albaran->ot ?: 'Sin CC' }}</span>
                                </td>
                                <td>{{ optional($albaran->fecha)->format('d/m/Y') ?: '-' }}</td>
                                <td>{{ $albaran->cliente?->empresa_nombre ?: 'Sin cliente' }}</td>
                                <td>{{ $albaran->titulo ?: '-' }}</td>
                                <td>
                                    @if(isset($albaran->ui_pedidos_vinculados) && $albaran->ui_pedidos_vinculados->isNotEmpty())
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            @foreach($albaran->ui_pedidos_vinculados as $pedidoVinculado)
                                                <div style="border: 1px solid #e2e8f0; border-radius: 4px; padding: 2px 6px; background: #f8fafc; display: inline-block; font-size: 0.85em; white-space: nowrap;">
                                                    @can('pedidos.view')
                                                        <a href="{{ route('pedidos-clientes.show', $pedidoVinculado['id']) }}" style="font-weight: bold; color: #1f74dd; text-decoration: none;">
                                                            {{ $pedidoVinculado['numero'] }}
                                                        </a>
                                                    @else
                                                        <span style="font-weight: bold; color: #64748b;">{{ $pedidoVinculado['numero'] }}</span>
                                                    @endcan
                                                    
                                                    @if($pedidoVinculado['imputado'] > 0)
                                                        <span style="color: #64748b; margin-left: 4px;">
                                                            ({{ number_format($pedidoVinculado['imputado'], 2, ',', '.') }}€)
                                                        </span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif ($pedidoNumero !== '')
                                        <!-- Fallback para registros antiguos o escritos manualmente que no tienen tabla pivote -->
                                        @can('pedidos.view')
                                           <span style="font-weight: bold; color: #64748b;">{{ $pedidoNumero }}</span>
                                        @else
                                            <span class="muted">{{ $pedidoNumero }}</span>
                                        @endcan
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight: 500;">{{ number_format($total, 2, ',', '.') }}€</div>
                                    @if(isset($albaran->ui_excedente) && $albaran->ui_excedente > 0)
                                        <div style="margin-top: 4px;">
                                            <span style="font-size: 0.82em; color: #b45309; background: #fef3c7; border: 1px solid #fde68a; padding: 2px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: bold;" title="Saldo flotante disponible para absorber en futuras bolsas">
                                                <i class="fas fa-coins" aria-hidden="true"></i> {{ number_format($albaran->ui_excedente, 2, ',', '.') }}€ libres
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="estado-chip estado-{{ $estado }}">{{ strtoupper(str_replace('_', ' ', $estado)) }}</span>
                                </td>
                                <td>
                                    <div class="presupuesto-action-group">
                                        @can('albaranes.download')
                                            <a href="{{ route('albaranes.preview', $albaran) }}" class="presupuesto-action-btn presupuesto-action-btn--view" aria-label="Previsualizar albarán" title="Previsualizar albarán">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('albaranes.pdf', $albaran) }}" class="presupuesto-action-btn presupuesto-action-btn--view" aria-label="Abrir PDF del albarán" title="Abrir PDF del albarán">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        @endcan

                                        @can('albaranes.manage')
                                            @if($estado !== 'facturado')
                                                <!-- BOTÓN DIRECTO: Pasar a Facturado -->
                                                <form action="{{ route('albaranes.estado.update', $albaran) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Marcar este albarán como FACTURADO? Se bloqueará y el pedido restará este importe automáticamente.');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="estado" value="facturado">
                                                    <button type="submit" class="presupuesto-action-btn" style="color: #28a745; border-color: #28a745; background-color: transparent;" aria-label="Pasar a Facturado" title="Pasar a Facturado">
                                                        <i class="fas fa-check-double"></i>
                                                    </button>
                                                </form>

                                                <a href="{{ route('albaranes.edit', $albaran) }}" class="presupuesto-action-btn presupuesto-action-btn--edit" aria-label="Editar albarán" title="Editar albarán">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                            @endif
                                        @endcan

                                        @can('albaranes.delete')
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
                                        @endcan

                                        @can('albaranes.manage')
                                            @php
                                                $dropdownId = 'albaran-estado-dropdown-' . $albaran->id;
                                            @endphp
                                            <div class="dropdown presupuesto-dropdown">
                                                <button
                                                    type="button"
                                                    class="presupuesto-action-btn--state dropdown-toggle"
                                                    id="{{ $dropdownId }}"
                                                    data-toggle="dropdown"
                                                    data-boundary="window" 
                                                    aria-haspopup="true"
                                                    aria-expanded="false"
                                                    aria-label="Cambiar estado"
                                                    title="Cambiar estado"
                                                >
                                                    <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="{{ $dropdownId }}">
                                                    <h6 class="dropdown-header">Cambiar estado</h6>
                                                    
                                                    <!-- Opción Pendiente -->
                                                    <form method="POST" action="{{ route('albaranes.estado.update', $albaran) }}" style="margin: 0;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="pendiente">
                                                        <button class="dropdown-item" type="submit">Pendiente</button>
                                                    </form>

                                                    <!-- Opción Recibido -->
                                                    <form method="POST" action="{{ route('albaranes.estado.update', $albaran) }}" style="margin: 0;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="recibido">
                                                        <button class="dropdown-item" type="submit">Recibido</button>
                                                    </form>

                                                    <!-- Opción Facturado Parcial -->
                                                    <form method="POST" action="{{ route('albaranes.estado.update', $albaran) }}" style="margin: 0;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="facturado_parcial">
                                                        <button class="dropdown-item" type="submit">Facturado Parcial</button>
                                                    </form>

                                                    <!-- Opción Facturado -->
                                                    <form method="POST" action="{{ route('albaranes.estado.update', $albaran) }}" style="margin: 0;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="facturado">
                                                        <button class="dropdown-item" type="submit" onclick="return confirm('¿Seguro que quieres pasar el albarán a FACTURADO?');">Facturado</button>
                                                    </form>

                                                    <!-- Opción Cancelado -->
                                                    <form method="POST" action="{{ route('albaranes.estado.update', $albaran) }}" style="margin: 0;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="cancelado">
                                                        <button class="dropdown-item" type="submit" onclick="return confirm('¿Seguro que quieres CANCELAR este albarán?');">Cancelado</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endcan
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

        <!-- Modales de borrado (igual que antes) -->
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