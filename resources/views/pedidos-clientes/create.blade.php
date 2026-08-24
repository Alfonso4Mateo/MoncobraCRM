@extends('adminlte::page')

@section('title', 'Crear Nuevo Pedido - MoncobraCRM')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @vite(['resources/css/pedidos-clientes-create.css'])
@endsection

@section('content_header')
    <div class="pedido-create-hero">
        <div class="pedido-create-hero__breadcrumbs">PEDIDOS CLIENTES > NUEVO PEDIDO</div>
        <div class="pedido-create-hero__title-wrap">
            <div>
                <h1>Crear Nuevo Pedido</h1>
                <p>Prepara el pedido a partir del presupuesto vinculado y completa las líneas antes de confirmar.</p>
            </div>
            <a href="{{ route('pedidos-clientes.index') }}" class="pedido-create-back-btn">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Volver al listado
            </a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $clienteSeleccionadoId = (string) old('id_cliente', $clienteSeleccionadoId ?? '');
        $presupuestoSeleccionadoId = (string) old('presupuesto_id', $presupuestoSeleccionadoId ?? '');
        $estadoActual = (string) old('estado', 'pendiente');
        $pedidoBolsa = (bool) old('bolsa', false);
        $numeroPedido = old('numero_pedido', '');
        $fechaPedido = old('fecha_pedido', $fechaPedido ?? now()->toDateString());
        $otPedido = old('ot', $presupuestoSeleccionado?->ot ?? null);
        $referenciaManual = old('referencia_manual');
        $bolsaTexto = old('bolsa_texto', '');
        $lineasJson = old('lista_articulos', json_encode($lineasIniciales ?? [], JSON_UNESCAPED_UNICODE));
        $lineasInicialesJs = json_decode($lineasJson, true);
        $lineasInicialesJs = is_array($lineasInicialesJs) ? $lineasInicialesJs : ($lineasIniciales ?? []);
        $presupuestosParaPedidoJs = $presupuestosParaPedido ?? [];
        $baseImponible = (float) ($baseImponible ?? 0);
        $totalPedido = (float) ($totalPedido ?? 0);
        $totalPedidoManual = old('total', $pedidoBolsa ? '' : number_format($totalPedido, 2, '.', ''));
    @endphp

    <form id="pedido-cliente-form" action="{{ route('pedidos-clientes.store') }}" method="POST" class="pedido-create-layout" novalidate>
        @csrf
        <input type="hidden" name="bolsa" value="0">
        <input type="hidden" name="estado" id="pedido_estado" value="{{ $estadoActual }}">
        <input type="hidden" name="total" id="pedido_total" value="{{ $pedidoBolsa ? $totalPedidoManual : number_format($totalPedido, 2, '.', '') }}">
        <input type="hidden" name="lista_articulos" id="pedido_lista_articulos" value="{{ $lineasJson }}">

        <section class="pedido-create-main">
            @if ($errors->any())
                <div class="pedido-alert pedido-alert--danger" role="alert">
                    <strong>No se pudo crear el pedido.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <article class="pedido-card pedido-card--trace">
                <div class="pedido-card__head">
                    <div>
                        <span class="pedido-card__eyebrow">Trazabilidad documental</span>
                        <h2>Documento vinculado</h2>
                    </div>
                    <span class="pedido-pill pedido-pill--soft">Alta en curso</span>
                </div>

                <div class="pedido-trace-grid">
                    <div class="pedido-trace-box pedido-trace-box--accent">
                        <span>Presupuesto origen</span>
                        <strong>
                            @if ($presupuestoSeleccionado)
                                {{ $presupuestoSeleccionado->numero ?? ('PR-' . $presupuestoSeleccionado->id) }}
                            @else
                                Sin presupuesto vinculado
                            @endif
                        </strong>
                        <small>
                            @if ($presupuestoSeleccionado)
                                {{ $presupuestoSeleccionado->titulo ?? 'Vinculado automáticamente desde la oferta' }}
                            @else
                                Selecciona un presupuesto si quieres heredar datos y líneas.
                            @endif
                        </small>
                    </div>

                    <div class="pedido-trace-box">
                        <label for="presupuesto_id">Cambiar presupuesto</label>
                        <select id="presupuesto_id" name="presupuesto_id" class="pedido-select pedido-select--search">
                            <option value="">Sin presupuesto</option>
                            @foreach ($presupuestos as $presupuesto)
                                <option value="{{ $presupuesto->id }}" {{ $presupuestoSeleccionadoId === (string) $presupuesto->id ? 'selected' : '' }}>
                                    {{ $presupuesto->numero ?? ('PR-' . $presupuesto->id) }} - {{ $presupuesto->cliente?->empresa_nombre ?? 'Sin cliente' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pedido-trace-box">
                        <label for="referencia_manual">Pedido-Cliente</label>
                        <input type="text" id="referencia_manual" name="referencia_manual" class="pedido-input" value="{{ $referenciaManual }}" placeholder="Introduce el Código del pedido de cliente">
                    </div>
                </div>
            </article>

            <article class="pedido-card">
                <div class="pedido-card__head">
                    <div>
                        <span class="pedido-card__eyebrow">Datos del pedido</span>
                        <h2>Cliente, número y fecha</h2>
                    </div>
                    <span class="pedido-pill">Manual</span>
                </div>

                <div class="pedido-form-grid pedido-form-grid--four">
                    <div class="pedido-field pedido-field--wide">
                        <label for="id_cliente">Cliente</label>
                        <select id="id_cliente" name="id_cliente" class="pedido-select" required {{ $presupuestoSeleccionadoId !== '' ? 'disabled' : '' }}>
                               <option value="">Selecciona un cliente</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ $clienteSeleccionadoId === (string) $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->empresa_nombre }}
                                </option>
                            @endforeach
                        </select>
                            <input type="hidden" id="id_cliente_locked" name="id_cliente" value="{{ $clienteSeleccionadoId }}" {{ $presupuestoSeleccionadoId !== '' ? '' : 'disabled' }}>
                    </div>

                    <div class="pedido-field">
                        <label for="numero_pedido">Número de pedido</label>
                        <input type="text" id="numero_pedido" name="numero_pedido" class="pedido-input" value="{{ $numeroPedido }}" required>
                        <small class="pedido-help">Rellénalo manualmente con el número de referencia interna que quieras guardar.</small>
                    </div>

                    <div class="pedido-field">
                        <label for="fecha_pedido">Fecha</label>
                        <input type="date" id="fecha_pedido" name="fecha_pedido" class="pedido-input" value="{{ $fechaPedido }}" required>
                    </div>

                    <div class="pedido-field">
                        <label for="ot">OT</label>
                        <input type="text" id="ot" name="ot" class="pedido-input" value="{{ $otPedido }}" placeholder="Orden de trabajo">
                    </div>

                    <div class="pedido-field">
                        <label for="pedido_bolsa">Pedido bolsa</label>
                        <label class="pedido-checkbox-inline" style="display:flex; align-items:center; gap:10px; margin-top:10px;">
                            <input type="checkbox" id="pedido_bolsa" name="bolsa" value="1" {{ $pedidoBolsa ? 'checked' : '' }}>
                            <span>Activar modo bolsa</span>
                        </label>
                        <small class="pedido-help">En modo bolsa no se usan líneas y solo se introduce el total del pedido.</small>
                    </div>
                </div>
            </article>

            <article class="pedido-card">
                <div class="pedido-card__head pedido-card__head--stacked">
                    <div>
                        <span class="pedido-card__eyebrow">Líneas del pedido</span>
                        <h2>Introduce los artículos</h2>
                    </div>
                    <div class="pedido-line-actions">
                        <button type="button" class="pedido-chip-btn pedido-chip-btn--ghost" id="pedido-add-line">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            Añadir línea
                        </button>
                    </div>
                </div>

                <div class="pedido-line-editor">
                    <div class="pedido-form-grid pedido-form-grid--line">
                        <!-- Campo 'Código referencia' eliminado según petición: se mostrará Línea en la tabla -->
                        <div class="pedido-field pedido-field--wide">
                            <label for="line_descripcion">Descripción</label>
                            <textarea id="line_descripcion" class="pedido-input pedido-textarea" rows="2" placeholder="Descripción del artículo o servicio"></textarea>
                        </div>
                        <div class="pedido-field pedido-field--compact">
                            <label for="line_cantidad">Cantidad</label>
                            <input type="number" step="0.01" min="0" max="1000000" id="line_cantidad" class="pedido-input" value="1">
                        </div>
                        <div class="pedido-field pedido-field--compact">
                            <label for="line_medida">Medida</label>
                            <input type="text" id="line_medida" value="und" class="pedido-input" placeholder="u, kg, m...">
                        </div>
                        <div class="pedido-field pedido-field--compact">
                            <label for="line_precio">P. unitario</label>
                                <input type="number" step="0.01" min="0" max="10000000" id="line_precio" class="pedido-input" value="0">
                        </div>
                        <div class="pedido-field pedido-field--compact">
                            <label for="line_margen">Margen</label>
                            <input type="number" step="0.01" min="0" max="1000" id="line_margen" class="pedido-input" value="0">
                        </div>
                    </div>

                    <div class="pedido-field pedido-field--wide" id="pedido_bolsa_total_wrap" {{ $pedidoBolsa ? '' : 'hidden' }}>
                        <label for="pedido_bolsa_total">Total del pedido</label>
                            <input type="number" step="0.01" min="0" max="10000000" id="pedido_bolsa_total" class="pedido-input" value="{{ $totalPedidoManual }}" placeholder="Importe total del pedido">
                        <small class="pedido-help">Rellena este importe solo si el pedido es bolsa.</small>
                    </div>
                    <div class="pedido-field pedido-field--wide" id="pedido_bolsa_texto_wrap" {{ $pedidoBolsa ? '' : 'hidden' }}>
                        <label for="pedido_bolsa_texto">Texto del pedido bolsa</label>
                        <textarea id="pedido_bolsa_texto" name="bolsa_texto" class="pedido-input pedido-textarea" rows="3" placeholder="Describe el pedido bolsa">{{ $bolsaTexto }}</textarea>
                        <small class="pedido-help">Se mostrara como descripcion si no hay lineas en el PDF.</small>
                    </div>
                </div>

                <div class="pedido-table-wrap">
                    <table class="pedido-table">
                        <thead>
                            <tr>
                                <th style="width: 6%">Línea</th>
                                <th>Descripción</th>
                                <th style="width: 10%">Cant.</th>
                                <th style="width: 10%">Medida</th>
                                <th style="width: 14%">P. unitario</th>
                                <th style="width: 10%">Margen</th>
                                <th style="width: 14%">Total</th>
                                <th style="width: 6%"></th>
                            </tr>
                        </thead>
                        <tbody id="pedido-lines-body"></tbody>
                    </table>
                </div>
            </article>
        </section>

        <aside class="pedido-create-aside">
            <article class="pedido-summary-card">
                <div class="pedido-summary-card__head">
                    <span class="pedido-card__eyebrow">Resumen</span>
                    <h2>Importes</h2>
                </div>

                <div class="pedido-summary-list">
                    <div class="pedido-summary-row">
                        <span>Base imponible</span>
                        <strong id="summary-base">{{ number_format($baseImponible, 2, ',', '.') }} €</strong>
                    </div>
                    <div class="pedido-summary-row pedido-summary-row--total">
                        <span>Total pedido</span>
                        <strong id="summary-total">{{ number_format($totalPedido, 2, ',', '.') }} €</strong>
                    </div>
                </div>

                <div class="pedido-summary-actions">
                    <button type="submit" class="pedido-action-btn pedido-action-btn--secondary" data-estado="pendiente">
                        Guardar como borrador
                    </button>
                    <button type="submit" class="pedido-action-btn pedido-action-btn--primary" data-estado="pendiente">
                        Confirmar y crear pedido
                    </button>
                </div>
            </article>

            <article class="pedido-tip-card">
                <span class="pedido-card__eyebrow">Sugerencia</span>
                <h3>Revisa la trazabilidad antes de confirmar</h3>
                <p>Si vienes desde un presupuesto, heredarás las líneas y el importe base. Después puedes ajustar cantidades, precios y observaciones sin perder el vínculo.</p>
            </article>
        </aside>
    </form>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('pedido-cliente-form');
            const body = document.getElementById('pedido-lines-body');
            const addLineButton = document.getElementById('pedido-add-line');
            const bolsaCheckbox = document.getElementById('pedido_bolsa');
            const bolsaTotalWrap = document.getElementById('pedido_bolsa_total_wrap');
            const bolsaTotalInput = document.getElementById('pedido_bolsa_total');
            const bolsaTextoWrap = document.getElementById('pedido_bolsa_texto_wrap');
            const bolsaTextoInput = document.getElementById('pedido_bolsa_texto');
            const descripcionInput = document.getElementById('line_descripcion');
            const cantidadInput = document.getElementById('line_cantidad');
            const medidaInput = document.getElementById('line_medida');
            const precioInput = document.getElementById('line_precio');
            const margenInput = document.getElementById('line_margen');
            const numeroPedidoInput = document.getElementById('numero_pedido');
            const presupuestoSelect = document.getElementById('presupuesto_id');
            const clienteSelect = document.getElementById('id_cliente');
            const clienteLockedInput = document.getElementById('id_cliente_locked');
            const otInput = document.getElementById('ot');
            const hiddenLines = document.getElementById('pedido_lista_articulos');
            const hiddenState = document.getElementById('pedido_estado');
            const hiddenTotal = document.getElementById('pedido_total');
            const summaryBase = document.getElementById('summary-base');
            const summaryTotal = document.getElementById('summary-total');
            const lineFormGrid = document.querySelector('.pedido-form-grid--line');
            const lineTableWrap = document.querySelector('.pedido-table-wrap');
            const initialLines = @json($lineasInicialesJs ?? []);
            const presupuestos = @json($presupuestosParaPedidoJs ?? []);
            let bolsaMode = bolsaCheckbox.checked;

            const moneyFormatter = new Intl.NumberFormat('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const parseValue = (value) => {
                const numeric = Number.parseFloat(String(value).replace(',', '.'));
                return Number.isFinite(numeric) ? numeric : 0;
            };

            const autoResizeDescripcion = () => {
                descripcionInput.style.height = 'auto';
                descripcionInput.style.height = `${descripcionInput.scrollHeight}px`;
            };

            const formatMoney = (value) => `${moneyFormatter.format(value)} €`;

            const computeTotal = (cantidad, precioUnitario, margen) => {
                const qty = Math.max(0, parseValue(cantidad));
                const unit = Math.max(0, parseValue(precioUnitario));
                const pct = Math.max(0, parseValue(margen));
                return Number((qty * unit * (1 + (pct / 100))).toFixed(2));
            };

            const normalizeLines = (lines) => Array.isArray(lines)
                ? lines
                    .filter((line) => line && typeof line === 'object')
                    .map((line) => {
                        const cantidad = Number(parseValue(line.cantidad).toFixed(2));
                        const precioUnitario = Number(parseValue(line.precio_unitario ?? line.precio).toFixed(2));
                        const margen = Number(parseValue(line.margen).toFixed(2));

                        return {
                            articulo: String(line.articulo ?? '').trim(),
                            descripcion: String(line.descripcion ?? '').trim(),
                            cantidad,
                            medida: String(line.medida ?? line.unidad ?? '').trim(),
                            precio_unitario: precioUnitario,
                            margen,
                            total: computeTotal(cantidad, precioUnitario, margen),
                        };
                    })
                    .filter((line) => line.descripcion !== '')
                : [];

            const findPresupuesto = (presupuestoId) => presupuestos.find((presupuesto) => String(presupuesto.id) === String(presupuestoId));

            const syncClienteHidden = (clienteId) => {
                const value = String(clienteId ?? '').trim();
                clienteLockedInput.value = value;
            };

            const lockCliente = (clienteId) => {
                syncClienteHidden(clienteId);
                clienteLockedInput.disabled = false;
                clienteSelect.disabled = true;
            };

            const unlockCliente = () => {
                clienteSelect.disabled = false;
                clienteLockedInput.disabled = true;
                clienteLockedInput.value = '';
            };

            const syncClienteFromSelect = () => {
                if (!clienteSelect.disabled) {
                    syncClienteHidden(clienteSelect.value);
                }
            };

            const syncBolsaUi = () => {
                bolsaMode = bolsaCheckbox.checked;
                bolsaTotalWrap.hidden = !bolsaMode;
                bolsaTextoWrap.hidden = !bolsaMode;
                lineFormGrid.hidden = bolsaMode;
                lineTableWrap.hidden = bolsaMode;
                bolsaTotalInput.required = bolsaMode;
                addLineButton.disabled = bolsaMode || descripcionInput.value.trim().length === 0;

                if (bolsaMode) {
                    items.splice(0, items.length);
                    syncHidden();
                }

                renderRows();
            };

            if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
                window.jQuery(presupuestoSelect).select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: 'Buscar presupuesto',
                    allowClear: true,
                });
            }

            const items = normalizeLines(initialLines);

            const syncHidden = () => {
                hiddenLines.value = JSON.stringify(items);
            };

            const renderTotals = () => {
                const base = items.reduce((carry, item) => carry + parseValue(item.total), 0);
                const total = bolsaMode ? parseValue(bolsaTotalInput.value) : base;

                hiddenTotal.value = total.toFixed(2);
                summaryBase.textContent = formatMoney(bolsaMode ? 0 : base);
                summaryTotal.textContent = formatMoney(total);
            };

            const renderRows = () => {
                if (bolsaMode) {
                    body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Modo bolsa activado. No se usan líneas.</td></tr>';
                    syncHidden();
                    renderTotals();
                    return;
                }

                if (items.length === 0) {
                    body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No hay items agregados.</td></tr>';
                    syncHidden();
                    renderTotals();
                    return;
                }

                body.innerHTML = items.map((item, index) => `
                    <tr>
                        <td>${String(index + 1).padStart(2, '0')}</td>
                        <td>${item.descripcion || '<span class="text-muted">Sin descripción</span>'}</td>
                        <td>${moneyFormatter.format(parseValue(item.cantidad))}</td>
                        <td>${item.medida ? item.medida : '<span class="text-muted">-</span>'}</td>
                        <td>${moneyFormatter.format(parseValue(item.precio_unitario))} €</td>
                        <td>${moneyFormatter.format(parseValue(item.margen))} %</td>
                        <td>${moneyFormatter.format(parseValue(item.total))} €</td>
                        <td>
                            <button type="button" class="pedido-line-remove" data-index="${index}" aria-label="Eliminar línea">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                syncHidden();
                renderTotals();
            };

            const resetLineForm = () => {
                descripcionInput.value = '';
                cantidadInput.value = '1';
                medidaInput.value = 'und';
                precioInput.value = '0';
                margenInput.value = '0';
                autoResizeDescripcion();
                descripcionInput.focus();
            };

            const applyPresupuesto = (presupuestoId) => {
                if (!presupuestoId) {
                    bolsaCheckbox.disabled = false;
                    unlockCliente();
                    syncClienteFromSelect();
                    syncBolsaUi();
                    return;
                }

                const presupuesto = findPresupuesto(presupuestoId);
                if (!presupuesto) {
                    bolsaCheckbox.disabled = false;
                    unlockCliente();
                    syncClienteFromSelect();
                    syncBolsaUi();
                    return;
                }

                syncBolsaUi();

                if (presupuesto.cliente_id) {
                    clienteSelect.value = String(presupuesto.cliente_id);
                    lockCliente(presupuesto.cliente_id);
                } else {
                    unlockCliente();
                }

                if (presupuesto.ot !== null && presupuesto.ot !== undefined) {
                    otInput.value = String(presupuesto.ot);
                }

                syncClienteHidden(clienteSelect.value);

                if (!Array.isArray(presupuesto.lineas) || presupuesto.lineas.length === 0) {
                    return;
                }

                items.splice(0, items.length, ...normalizeLines(presupuesto.lineas));
                renderRows();
                resetLineForm();
            };

            const addLine = () => {
                if (bolsaMode) {
                    return;
                }

                const descripcion = descripcionInput.value.trim();
                const cantidad = Math.max(0, parseValue(cantidadInput.value));
                const medida = medidaInput.value.trim() || 'und';
                const precioUnitario = Math.max(0, parseValue(precioInput.value));
                const margen = Math.max(0, parseValue(margenInput.value));

                if (!descripcion) {
                    window.alert('Completa la descripción antes de añadir la línea.');
                    descripcionInput.focus();
                    return;
                }

                const total = computeTotal(cantidad, precioUnitario, margen);

                items.push({
                    articulo: '',
                    descripcion,
                    cantidad: Number(cantidad.toFixed(2)),
                    medida,
                    precio_unitario: Number(precioUnitario.toFixed(2)),
                    margen: Number(margen.toFixed(2)),
                    total,
                });

                renderRows();
                resetLineForm();
            };

            const refreshFromBudgetSelection = () => {
                applyPresupuesto(presupuestoSelect.value);
            };

            const refreshFromBudgetSelectionDeferred = () => {
                window.requestAnimationFrame(refreshFromBudgetSelection);
            };

            descripcionInput.addEventListener('input', () => {
                addLineButton.disabled = bolsaMode || descripcionInput.value.trim().length === 0;
                autoResizeDescripcion();
            });

            descripcionInput.addEventListener('keydown', (event) => {
                if (event.key !== 'Tab') {
                    return;
                }

                event.preventDefault();
                const start = descripcionInput.selectionStart ?? 0;
                const end = descripcionInput.selectionEnd ?? 0;
                const insertion = '    ';

                descripcionInput.value = `${descripcionInput.value.slice(0, start)}${insertion}${descripcionInput.value.slice(end)}`;
                descripcionInput.selectionStart = descripcionInput.selectionEnd = start + insertion.length;
                descripcionInput.dispatchEvent(new Event('input', { bubbles: true }));
            });

            [cantidadInput, precioInput, margenInput].forEach((input) => {
                input.addEventListener('input', () => {
                    addLineButton.disabled = bolsaMode || descripcionInput.value.trim().length === 0;
                });
            });

            bolsaCheckbox.addEventListener('change', () => {
                syncBolsaUi();
            });

            bolsaTotalInput.addEventListener('input', () => {
                renderTotals();
            });

            clienteSelect.addEventListener('change', syncClienteFromSelect);

            presupuestoSelect.addEventListener('change', refreshFromBudgetSelectionDeferred);
            presupuestoSelect.addEventListener('input', refreshFromBudgetSelectionDeferred);

            if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
                window.jQuery(presupuestoSelect).on('select2:select select2:clear change', refreshFromBudgetSelectionDeferred);
            }

            addLineButton.addEventListener('click', addLine);

            body.addEventListener('click', (event) => {
                const target = event.target instanceof HTMLElement ? event.target : null;
                const removeButton = target?.closest('.pedido-line-remove');

                if (!(removeButton instanceof HTMLElement)) {
                    return;
                }

                const index = Number.parseInt(removeButton.dataset.index || '-1', 10);
                if (index < 0 || index >= items.length) {
                    return;
                }

                items.splice(index, 1);
                renderRows();
            });

            renderRows();
            syncBolsaUi();
            addLineButton.disabled = descripcionInput.value.trim().length === 0;
            autoResizeDescripcion();

            if (presupuestoSelect.value) {
                refreshFromBudgetSelectionDeferred();
            } else {
                unlockCliente();
                syncClienteFromSelect();
            }

            form.addEventListener('submit', () => {
                const button = document.activeElement;
                if (button && button.dataset && button.dataset.estado) {
                    hiddenState.value = button.dataset.estado;
                }

                if (bolsaCheckbox.checked) {
                    hiddenTotal.value = parseValue(bolsaTotalInput.value).toFixed(2);
                    hiddenLines.value = '[]';
                } else if (bolsaTextoInput) {
                    bolsaTextoInput.value = '';
                }

                if (clienteSelect.disabled) {
                    clienteLockedInput.disabled = false;
                    syncClienteHidden(clienteSelect.value);
                } else {
                    clienteLockedInput.disabled = true;
                    syncClienteFromSelect();
                }

                syncHidden();
                renderTotals();
            });
        });
    </script>
@endsection
