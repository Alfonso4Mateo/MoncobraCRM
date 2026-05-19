@extends('adminlte::page')

@section('title', 'Crear Nuevo Pedido - MoncobraCRM')

@section('css')
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
        $numeroPedido = old('numero_pedido', $numeroPedidoAuto ?? '');
        $fechaPedido = old('fecha_pedido', $fechaPedido ?? now()->toDateString());
        $otPedido = old('ot', $presupuestoSeleccionado?->ot ?? null);
        $referenciaManual = old('referencia_manual');
        $lineasJson = old('lista_articulos', json_encode($lineasIniciales ?? [], JSON_UNESCAPED_UNICODE));
        $lineasInicialesJs = json_decode($lineasJson, true);
        $lineasInicialesJs = is_array($lineasInicialesJs) ? $lineasInicialesJs : ($lineasIniciales ?? []);
        $presupuestosParaPedidoJs = $presupuestosParaPedido ?? [];
        $baseImponible = (float) ($baseImponible ?? 0);
        $totalPedido = (float) ($totalPedido ?? 0);
    @endphp

    <form id="pedido-cliente-form" action="{{ route('pedidos-clientes.store') }}" method="POST" class="pedido-create-layout" novalidate>
        @csrf
        <input type="hidden" name="estado" id="pedido_estado" value="{{ $estadoActual }}">
        <input type="hidden" name="total" id="pedido_total" value="{{ number_format($totalPedido, 2, '.', '') }}">
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
                        <select id="presupuesto_id" name="presupuesto_id" class="pedido-select">
                            <option value="">Sin presupuesto</option>
                            @foreach ($presupuestos as $presupuesto)
                                <option value="{{ $presupuesto->id }}" {{ $presupuestoSeleccionadoId === (string) $presupuesto->id ? 'selected' : '' }}>
                                    {{ $presupuesto->numero ?? ('PR-' . $presupuesto->id) }} - {{ $presupuesto->cliente?->empresa_nombre ?? 'Sin cliente' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pedido-trace-box">
                        <label for="referencia_manual">Referencia manual</label>
                        <input type="text" id="referencia_manual" name="referencia_manual" class="pedido-input" value="{{ $referenciaManual }}" placeholder="Introduce una referencia interna">
                    </div>
                </div>
            </article>

            <article class="pedido-card">
                <div class="pedido-card__head">
                    <div>
                        <span class="pedido-card__eyebrow">Datos del pedido</span>
                        <h2>Cliente, número y fecha</h2>
                    </div>
                    <span class="pedido-pill">Autogenerado</span>
                </div>

                <div class="pedido-form-grid pedido-form-grid--four">
                    <div class="pedido-field pedido-field--wide">
                        <label for="id_cliente">Cliente</label>
                        <select id="id_cliente" name="id_cliente" class="pedido-select" required>
                            <option value="">Selecciona un cliente</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ $clienteSeleccionadoId === (string) $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->empresa_nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pedido-field">
                        <label for="numero_pedido">Número de pedido</label>
                        <input type="text" id="numero_pedido" name="numero_pedido" class="pedido-input" value="{{ $numeroPedido }}" required>
                        <small class="pedido-help">Se propone automáticamente.</small>
                    </div>

                    <div class="pedido-field">
                        <label for="fecha_pedido">Fecha</label>
                        <input type="date" id="fecha_pedido" name="fecha_pedido" class="pedido-input" value="{{ $fechaPedido }}" required>
                    </div>

                    <div class="pedido-field">
                        <label for="ot">OT</label>
                        <input type="text" id="ot" name="ot" class="pedido-input" value="{{ $otPedido }}" placeholder="Orden de trabajo">
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
                        <div class="pedido-field">
                            <label for="line_articulo">Código referencia</label>
                            <input type="text" id="line_articulo" class="pedido-input" placeholder="Código o referencia">
                        </div>
                        <div class="pedido-field pedido-field--wide">
                            <label for="line_descripcion">Descripción</label>
                            <input type="text" id="line_descripcion" class="pedido-input" placeholder="Descripción del artículo o servicio">
                        </div>
                        <div class="pedido-field pedido-field--compact">
                            <label for="line_cantidad">Cantidad</label>
                            <input type="number" step="0.01" min="0" id="line_cantidad" class="pedido-input" value="1">
                        </div>
                        <div class="pedido-field pedido-field--compact">
                            <label for="line_medida">Medida</label>
                            <input type="text" id="line_medida" class="pedido-input" placeholder="u, kg, m...">
                        </div>
                        <div class="pedido-field pedido-field--compact">
                            <label for="line_precio">P. unitario</label>
                            <input type="number" step="0.01" min="0" id="line_precio" class="pedido-input" value="0">
                        </div>
                        <div class="pedido-field pedido-field--compact">
                            <label for="line_margen">Margen</label>
                            <input type="number" step="0.01" min="0" id="line_margen" class="pedido-input" value="0">
                        </div>
                    </div>
                </div>

                <div class="pedido-table-wrap">
                    <table class="pedido-table">
                        <thead>
                            <tr>
                                <th style="width: 14%">Código ref.</th>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('pedido-cliente-form');
            const body = document.getElementById('pedido-lines-body');
            const addLineButton = document.getElementById('pedido-add-line');
            const articuloInput = document.getElementById('line_articulo');
            const descripcionInput = document.getElementById('line_descripcion');
            const cantidadInput = document.getElementById('line_cantidad');
            const medidaInput = document.getElementById('line_medida');
            const precioInput = document.getElementById('line_precio');
            const margenInput = document.getElementById('line_margen');
            const numeroPedidoInput = document.getElementById('numero_pedido');
            const presupuestoSelect = document.getElementById('presupuesto_id');
            const clienteSelect = document.getElementById('id_cliente');
            const hiddenLines = document.getElementById('pedido_lista_articulos');
            const hiddenState = document.getElementById('pedido_estado');
            const hiddenTotal = document.getElementById('pedido_total');
            const summaryBase = document.getElementById('summary-base');
            const summaryTotal = document.getElementById('summary-total');
            const initialLines = @json($lineasInicialesJs ?? []);
            const presupuestos = @json($presupuestosParaPedidoJs ?? []);

            const moneyFormatter = new Intl.NumberFormat('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const parseValue = (value) => {
                const numeric = Number.parseFloat(String(value).replace(',', '.'));
                return Number.isFinite(numeric) ? numeric : 0;
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

            const items = normalizeLines(initialLines);

            const syncHidden = () => {
                hiddenLines.value = JSON.stringify(items);
            };

            const renderTotals = () => {
                const base = items.reduce((carry, item) => carry + parseValue(item.total), 0);
                const total = base;

                hiddenTotal.value = total.toFixed(2);
                summaryBase.textContent = formatMoney(base);
                summaryTotal.textContent = formatMoney(total);
            };

            const renderRows = () => {
                if (items.length === 0) {
                    body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No hay items agregados.</td></tr>';
                    syncHidden();
                    renderTotals();
                    return;
                }

                body.innerHTML = items.map((item, index) => `
                    <tr>
                        <td>
                            ${item.articulo ? `<strong>${item.articulo}</strong>` : '<span class="text-muted">-</span>'}
                        </td>
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
                articuloInput.value = '';
                descripcionInput.value = '';
                cantidadInput.value = '1';
                medidaInput.value = '';
                precioInput.value = '0';
                margenInput.value = '0';
                articuloInput.focus();
            };

            const applyPresupuesto = (presupuestoId) => {
                const presupuesto = findPresupuesto(presupuestoId);
                if (!presupuesto) {
                    return;
                }

                if (presupuesto.cliente_id) {
                    clienteSelect.value = String(presupuesto.cliente_id);
                }

                // If the presupuesto contains a predefined pedido number, copy it
                if (presupuesto.numero_pedido) {
                    numeroPedidoInput.value = presupuesto.numero_pedido;
                    numeroPedidoInput.readOnly = true;
                    numeroPedidoInput.title = 'Bloqueado: proviene del presupuesto (doble clic para desbloquear)';
                } else {
                    // ensure unlocked if presupuesto has no numero_pedido
                    numeroPedidoInput.readOnly = false;
                    numeroPedidoInput.removeAttribute('title');
                }

                if (!Array.isArray(presupuesto.lineas) || presupuesto.lineas.length === 0) {
                    return;
                }

                items.splice(0, items.length, ...normalizeLines(presupuesto.lineas));
                renderRows();
                resetLineForm();
            };

            const addLine = () => {
                const articulo = articuloInput.value.trim();
                const descripcion = descripcionInput.value.trim();
                const cantidad = Math.max(0, parseValue(cantidadInput.value));
                const medida = medidaInput.value.trim();
                const precioUnitario = Math.max(0, parseValue(precioInput.value));
                const margen = Math.max(0, parseValue(margenInput.value));

                if (!articulo) {
                    window.alert('Completa el código referencia antes de añadir la línea.');
                    articuloInput.focus();
                    return;
                }

                if (!descripcion) {
                    window.alert('Completa la descripción antes de añadir la línea.');
                    descripcionInput.focus();
                    return;
                }

                const total = computeTotal(cantidad, precioUnitario, margen);

                items.push({
                    articulo,
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

            articuloInput.addEventListener('input', () => {
                addLineButton.disabled = articuloInput.value.trim().length === 0;
            });

            [cantidadInput, precioInput, margenInput].forEach((input) => {
                input.addEventListener('input', () => {
                    addLineButton.disabled = articuloInput.value.trim().length === 0;
                });
            });

            presupuestoSelect.addEventListener('change', refreshFromBudgetSelection);

            // When presupuesto selection changes to none, unlock the pedido number
            presupuestoSelect.addEventListener('change', () => {
                if (!presupuestoSelect.value) {
                    numeroPedidoInput.readOnly = false;
                    numeroPedidoInput.removeAttribute('title');
                } else {
                    const p = findPresupuesto(presupuestoSelect.value);
                    if (!p || !p.numero_pedido) {
                        numeroPedidoInput.readOnly = false;
                        numeroPedidoInput.removeAttribute('title');
                    }
                }
            });

            // Allow user to unlock the field with a double click
            numeroPedidoInput.addEventListener('dblclick', () => {
                numeroPedidoInput.readOnly = false;
                numeroPedidoInput.removeAttribute('title');
            });

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
            addLineButton.disabled = articuloInput.value.trim().length === 0;

            if (presupuestoSelect.value) {
                applyPresupuesto(presupuestoSelect.value);
            }

            form.addEventListener('submit', () => {
                const button = document.activeElement;
                if (button && button.dataset && button.dataset.estado) {
                    hiddenState.value = button.dataset.estado;
                }

                syncHidden();
                renderTotals();
            });
        });
    </script>
@endsection
