@extends('adminlte::page')

@section('title', 'Crear Nuevo Pedido - MoncobraCRM')

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
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
        $clienteSeleccionadoId = (string) old('id_cliente',$clienteSeleccionadoId ?? '');
        $presupuestoSeleccionadoId = (string) old('presupuesto_id',$presupuestoSeleccionadoId ?? '');
        // Defensa: un "0" nunca debe interpretarse como presupuesto seleccionado.
        if ($presupuestoSeleccionadoId === '0') { $presupuestoSeleccionadoId = ''; }
        if ($clienteSeleccionadoId === '0') { $clienteSeleccionadoId = ''; }
        $estadoActual = (string) old('estado', 'pendiente');
        $pedidoBolsa = (bool) old('bolsa', $bolsaPreseleccionada ?? false);
        $bolsaSeleccionadaId = (string) old('bolsa_id', $bolsaIdPreseleccionada ?? '');;$numeroPedido = old('numero_pedido', '');
        $fechaPedido = old('fecha_pedido',$fechaPedido ?? now()->toDateString());
        $otPedido = old('ot',$presupuestoSeleccionado?->ot ?? null);
        $referenciaManual = old('referencia_manual');$bolsaTexto = old('bolsa_texto', '');
        $lineasJson = old('lista_articulos', json_encode($lineasIniciales ?? [], JSON_UNESCAPED_UNICODE));
        $lineasInicialesJs = json_decode($lineasJson, true);
        $lineasInicialesJs = is_array($lineasInicialesJs) ? $lineasInicialesJs : ($lineasIniciales ?? []);
        $presupuestosParaPedidoJs =$presupuestosParaPedido ?? [];
        $baseImponible = (float) ($baseImponible ?? 0);
        $totalPedido = (float) ($totalPedido ?? 0);
        $totalPedidoManual = old('total', $pedidoBolsa ? '' : number_format($totalPedido, 2, '.', ''));
    @endphp

    <form id="pedido-cliente-form" action="{{ route('pedidos-clientes.store') }}" method="POST" enctype="multipart/form-data" class="pedido-create-layout" novalidate>
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
                                <option value="{{ $presupuesto->id }}" {{ $presupuestoSeleccionadoId === (string)$presupuesto->id ? 'selected' : '' }}>
                                    {{ $presupuesto->numero ?? ('PR-' . $presupuesto->id) }} - {{$presupuesto->cliente?->empresa_nombre ?? 'Sin cliente' }}
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
                                <option value="{{ $cliente->id }}" {{ $clienteSeleccionadoId === (string)$cliente->id ? 'selected' : '' }}>
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
                        <label for="ot">CC (Centro de Coste)</label>
                        <input type="text" id="ot" name="ot" class="pedido-input" value="{{ $otPedido }}" placeholder="Orden de trabajo" list="cc_frecuentes" autocomplete="off">
                        <datalist id="cc_frecuentes">
                            @foreach($centrosCoste as $cc)
                                <option value="{{ $cc->etiqueta_completa }}">
                            @endforeach
                        </datalist>
                    </div>

                    <div class="pedido-field">
                        <label for="pedido_bolsa">Pedido bolsa</label>
                        <label class="pedido-checkbox-inline" style="display:flex; align-items:center; gap:10px; margin-top:10px;">
                            <input type="checkbox" id="pedido_bolsa" name="bolsa" value="1" {{ $pedidoBolsa ? 'checked' : '' }}>
                            <span>Activar modo bolsa</span>
                        </label>
                        <small class="pedido-help">La bolsa permite a los albaranes consumir saldos de las líneas de forma fraccionada.</small>
                    </div>

                   <div class="pedido-field pedido-field--wide">
                        <label>Documento del cliente</label>
                        @include('pedidos-clientes.partials.archivo-adjunto')
                    </div>
                </div>
            </article>

            <!-- INTERRUPTOR Y CAMPO DE DESCRIPCIÓN GENERAL -->
            <div class="pedido-card mt-3 mb-3">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="switchDescripcionGeneral" {{ old('descripcion_general', $pedidoCliente->descripcion_general ?? '') ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" style="color: var(--pedido-primary); cursor: pointer;" for="switchDescripcionGeneral">
                        <i class="fas fa-align-left mr-1"></i> Añadir Descripción General al documento
                    </label>
                </div>
                
                <div class="pedido-field pedido-field--wide mt-3" id="contenedorDescripcionGeneral" style="{{ old('descripcion_general', $pedidoCliente->descripcion_general ?? '') ? 'display: block;' : 'display: none;' }}">
                    <label for="descripcion_general">Descripción / Condiciones del pedido</label>
                    <textarea name="descripcion_general" id="descripcion_general" class="pedido-textarea form-control" rows="3" placeholder="Escribe aquí la descripción general. Este texto ocupará todo el ancho de la hoja en el PDF generado...">{{ old('descripcion_general', $pedidoCliente->descripcion_general ?? '') }}</textarea>
                    <span class="pedido-help text-muted"><i class="fas fa-info-circle"></i> Puedes usar saltos de línea. El texto se adaptará automáticamente en el documento final.</span>
                </div>
            </div>

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
                        <div class="pedido-field pedido-field--compact" id="line_modo_consumo_wrap" {{ $pedidoBolsa ? '' : 'hidden' }}>
                            <label for="line_modo_consumo">Consumo</label>
                            <select id="line_modo_consumo" class="pedido-select">
                                <option value="cantidad">Por unidades</option>
                                <option value="importe">Por importe (límite)</option>
                            </select>
                        </div>
                    </div>

                    <!-- CAMPO MANUAL OCULTO (Mantenido en el DOM por compatibilidad con selectores, pero oculto al usuario) -->
                    <div class="pedido-field pedido-field--wide" id="pedido_bolsa_total_wrap" style="display: none;">
                        <label for="pedido_bolsa_total">Total del pedido manual</label>
                            <input type="number" step="0.01" min="0" max="10000000" id="pedido_bolsa_total" class="pedido-input" value="{{ $totalPedidoManual }}" placeholder="Importe total">
                    </div>

                    <div class="pedido-field pedido-field--wide" id="pedido_bolsa_global_wrap" {{ $pedidoBolsa ? '' : 'hidden' }}>
                        <label for="pedido_bolsa_id">Bolsa global</label>
                        <select id="pedido_bolsa_id" name="bolsa_id" class="pedido-select">
                            <option value="">Bolsa suelta (sin bolsa global)</option>
                            @foreach ($bolsasParaPedido ?? [] as $bolsaOption)
                                <option value="{{ $bolsaOption['id'] }}"
                                        data-cliente-id="{{ $bolsaOption['id_cliente'] }}"
                                        {{ $bolsaSeleccionadaId === (string) $bolsaOption['id'] ? 'selected' : '' }}>
                                    {{ $bolsaOption['numero'] ?: ('Bolsa #' . $bolsaOption['id']) }} ({{ $bolsaOption['tipo_bolsa'] }}) — {{ $bolsaOption['saldo_texto'] ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="pedido-help">
                            Solo se muestran las bolsas del cliente seleccionado. Si no ves la que buscas, créala primero desde <a href="{{ route('bolsas.create') }}" target="_blank">Bolsas</a>.
                        </small>
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
                                <th style="width: 10%" id="th-modo-consumo" {{ $pedidoBolsa ? '' : 'hidden' }}>Consumo</th>
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

                <div id="bolsa-limit-warning" class="pedido-alert pedido-alert--danger" style="display: none; margin: 15px 20px; font-size: 0.85rem;">
                    <i class="fas fa-exclamation-triangle"></i> El total supera el saldo de la bolsa global seleccionada. (Máximo: <strong id="bolsa-limit-amount"></strong>)
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
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c]));

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('pedido-cliente-form');
            const body = document.getElementById('pedido-lines-body');
            const addLineButton = document.getElementById('pedido-add-line');
            const bolsaCheckbox = document.getElementById('pedido_bolsa');
            const bolsaTotalWrap = document.getElementById('pedido_bolsa_total_wrap');
            const bolsaTotalInput = document.getElementById('pedido_bolsa_total');
            const bolsaGlobalWrap = document.getElementById('pedido_bolsa_global_wrap');
            const bolsaIdSelect = document.getElementById('pedido_bolsa_id');
            const modoConsumoWrap = document.getElementById('line_modo_consumo_wrap');
            const modoConsumoSelect = document.getElementById('line_modo_consumo');
            const thModoConsumo = document.getElementById('th-modo-consumo');
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
            
            // --- NUEVOS ELEMENTOS DESCRIPCIÓN GENERAL ---
            const switchDesc = document.getElementById('switchDescripcionGeneral');
            const containerDesc = document.getElementById('contenedorDescripcionGeneral');
            const inputDesc = document.getElementById('descripcion_general');

            const initialLines = @json($lineasInicialesJs ?? []);
            const presupuestos = @json($presupuestosParaPedidoJs ?? []);
            const bolsasData = @json($bolsasParaPedido ?? []);
            let bolsaMode = bolsaCheckbox.checked;

            const moneyFormatter = new Intl.NumberFormat('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
            let editingLineUid = null;
            let editingLineArticulo = '';

            const parseValue = (value) => {
                const numeric = Number.parseFloat(String(value).replace(',', '.'));
                return Number.isFinite(numeric) ? numeric : 0;
            };

            const autoResizeDescripcion = () => {
                descripcionInput.style.height = 'auto';
                descripcionInput.style.height = `${descripcionInput.scrollHeight}px`;
            };

            const formatMoney = (value) => `${moneyFormatter.format(value)} €`;

            const syncModoConsumoUi = () => {
                if (!modoConsumoSelect) {
                    return;
                }

                const esImporte = bolsaMode && modoConsumoSelect.value === 'importe';

                if (esImporte) {
                    cantidadInput.value = '1';
                    cantidadInput.disabled = true;
                } else {
                    cantidadInput.disabled = false;
                }
            };

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
                        const modoConsumo = line.modo_consumo === 'importe' ? 'importe' : 'cantidad';
                        const cantidad = modoConsumo === 'importe' ? 1 : Number(parseValue(line.cantidad).toFixed(2));
                        const precioUnitario = Number(parseValue(line.precio_unitario ?? line.precio).toFixed(2));
                        const margen = Number(parseValue(line.margen).toFixed(2));

                        return {
                            articulo: String(line.articulo ?? '').trim(),
                            descripcion: String(line.descripcion ?? '').trim(),
                            cantidad,
                            medida: String(line.medida ?? line.unidad ?? '').trim(),
                            precio_unitario: precioUnitario,
                            margen,
                            modo_consumo: modoConsumo,
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

            const clienteTs = () => clienteSelect.tomselect;

            const lockCliente = (clienteId) => {
                const value = String(clienteId ?? '');
                if (clienteTs()) {
                    clienteTs().setValue(value, true);
                } else {
                    clienteSelect.value = value;
                }
                syncClienteHidden(value);
                clienteLockedInput.disabled = false;
                if (clienteTs()) {
                    clienteTs().disable();
                } else {
                    clienteSelect.disabled = true;
                }
            };

            const unlockCliente = () => {
                if (clienteTs()) {
                    clienteTs().enable();
                } else {
                    clienteSelect.disabled = false;
                }
                clienteLockedInput.disabled = true;
                clienteLockedInput.value = '';
            };

            const syncClienteFromSelect = () => {
                if (!clienteSelect.disabled) {
                    syncClienteHidden(clienteSelect.value);
                }
            };

            const filterBolsaIdOptions = () => {
                if (!bolsaIdSelect) {
                    return;
                }

                const clienteId = String(clienteLockedInput.value || clienteSelect.value || '').trim();

                Array.from(bolsaIdSelect.options).forEach((option) => {
                    if (option.value === '') {
                        option.hidden = false;
                        return;
                    }
                    const perteneceAlCliente = String(option.dataset.clienteId || '') === clienteId;
                    option.hidden = !perteneceAlCliente;
                });

                const seleccionActual = bolsaIdSelect.selectedOptions[0];
                if (seleccionActual && seleccionActual.hidden) {
                    bolsaIdSelect.value = '';
                }
            };

            const syncBolsaUi = () => {
                bolsaMode = bolsaCheckbox.checked;
                
                bolsaTotalWrap.hidden = true; 
                lineFormGrid.hidden = false; 
                lineTableWrap.hidden = false; 
                bolsaTotalInput.required = false;

                if (bolsaGlobalWrap) {
                    bolsaGlobalWrap.hidden = !bolsaMode;
                }
                if (modoConsumoWrap) {
                    modoConsumoWrap.hidden = !bolsaMode;
                }
                if (thModoConsumo) {
                    thModoConsumo.hidden = !bolsaMode;
                }
                if (!bolsaMode && bolsaIdSelect) {
                    bolsaIdSelect.value = '';
                }
                if (!bolsaMode && modoConsumoSelect) {
                    modoConsumoSelect.value = 'cantidad';
                }

                filterBolsaIdOptions();
                syncModoConsumoUi();
                
                addLineButton.disabled = descripcionInput.value.trim().length === 0;

                renderRows();
            };

            document.querySelectorAll('.pedido-select:not(#pedido_bolsa_id), .select2:not(#pedido_bolsa_id)').forEach((select) => {
                if (!select.tomselect) {
                    new TomSelect(select, {
                        create: false,
                        ...(select === presupuestoSelect ? {
                            placeholder: 'Buscar presupuesto',
                            allowEmptyOption: true,
                        } : {}),
                    });
                }
            });

            const items = normalizeLines(initialLines);

            const syncHidden = () => {
                hiddenLines.value = JSON.stringify(items);
            };

            const renderTotals = () => {
                const total = items.reduce((carry, item) => carry + parseValue(item.total), 0);
                hiddenTotal.value = total.toFixed(2);
                summaryBase.textContent = formatMoney(total);
                summaryTotal.textContent = formatMoney(total);
                
                const warningEl = document.getElementById('bolsa-limit-warning');
                const amountSpan = document.getElementById('bolsa-limit-amount');
                const submitBtns = document.querySelectorAll('.pedido-summary-actions button[type="submit"]');
                
                let exceedsLimit = false;

                if (bolsaMode && bolsaIdSelect && bolsaIdSelect.value) {
                    const bolsa = bolsasData.find(b => String(b.id) === bolsaIdSelect.value);
                    
                    if (bolsa && !bolsa.es_abierta && total > bolsa.saldo_disponible) {
                        exceedsLimit = true;
                        if(amountSpan) amountSpan.textContent = formatMoney(bolsa.saldo_disponible);
                    }
                }

                if (exceedsLimit) {
                    if(warningEl) warningEl.style.display = 'block';
                    submitBtns.forEach(btn => btn.disabled = true);
                } else {
                    if(warningEl) warningEl.style.display = 'none';
                    submitBtns.forEach(btn => btn.disabled = false);
                }
            };

            const renderRows = () => {
                if (items.length === 0) {
                    body.innerHTML = `<tr><td colspan="${bolsaMode ? 9 : 8}" class="text-center text-muted py-4">No hay items agregados.</td></tr>`;
                    syncHidden();
                    renderTotals();
                    return;
                }

                body.innerHTML = items.map((item, index) => `
                    <tr>
                        <td>${String(index + 1).padStart(2, '0')}</td>
                        <td style="white-space: pre-wrap">${esc(item.descripcion) || '<span class="text-muted">Sin descripción</span>'}</td>
                        <td>${moneyFormatter.format(parseValue(item.cantidad))}</td>
                        <td>${item.medida ? esc(item.medida) : '<span class="text-muted">-</span>'}</td>
                        <td>${moneyFormatter.format(parseValue(item.precio_unitario))} €</td>
                        <td>${moneyFormatter.format(parseValue(item.margen))} %</td>
                        ${bolsaMode ? `<td>${item.modo_consumo === 'importe' ? 'Importe' : 'Unidades'}</td>` : ''}
                        <td>${moneyFormatter.format(parseValue(item.total))} €</td>
                        <td style="white-space: nowrap;">
                            <button type="button" class="pedido-line-edit mr-1" data-index="${index}" aria-label="Editar línea" style="background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;">
                                <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="pedido-line-remove" data-index="${index}" aria-label="Eliminar línea" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 4px;">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                syncHidden();
                renderTotals();
            };

            const resetLineForm = () => {
                editingLineUid = null;
                editingLineArticulo = '';
                descripcionInput.value = '';
                cantidadInput.value = '1';
                cantidadInput.disabled = false;
                medidaInput.value = 'und';
                precioInput.value = '0';
                margenInput.value = '0';
                if (modoConsumoSelect) {
                    modoConsumoSelect.value = 'cantidad';
                }
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
                const descripcion = descripcionInput.value.trim();
                const modoConsumo = (bolsaMode && modoConsumoSelect && modoConsumoSelect.value === 'importe') ? 'importe' : 'cantidad';
                const cantidad = modoConsumo === 'importe' ? 1 : Math.max(0, parseValue(cantidadInput.value));
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
                    uid: editingLineUid || (typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : String(Date.now())),
                    articulo: editingLineArticulo,
                    descripcion,
                    cantidad: Number(cantidad.toFixed(2)),
                    medida,
                    precio_unitario: Number(precioUnitario.toFixed(2)),
                    margen: Number(margen.toFixed(2)),
                    modo_consumo: modoConsumo,
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
                addLineButton.disabled = descripcionInput.value.trim().length === 0;
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
                    addLineButton.disabled = descripcionInput.value.trim().length === 0;
                });
            });

            bolsaCheckbox.addEventListener('change', () => {
                syncBolsaUi();
            });

            if (modoConsumoSelect) {
                modoConsumoSelect.addEventListener('change', syncModoConsumoUi);
            }

            clienteSelect.addEventListener('change', () => {
                syncClienteFromSelect();
                filterBolsaIdOptions();
            });
            
            if (bolsaIdSelect) {
                bolsaIdSelect.addEventListener('change', renderTotals);
            }

            presupuestoSelect.addEventListener('change', refreshFromBudgetSelectionDeferred);
            presupuestoSelect.addEventListener('input', refreshFromBudgetSelectionDeferred);

            addLineButton.addEventListener('click', addLine);
            
            // --- EVENTO INTERRUPTOR DESCRIPCIÓN GENERAL ---
            if (switchDesc && containerDesc && inputDesc) {
                switchDesc.addEventListener('change', function () {
                    if (this.checked) {
                        containerDesc.style.opacity = '0';
                        containerDesc.style.display = 'block';
                        setTimeout(() => {
                            containerDesc.style.transition = 'opacity 0.3s ease';
                            containerDesc.style.opacity = '1';
                            inputDesc.focus();
                        }, 10);
                    } else {
                        containerDesc.style.opacity = '0';
                        setTimeout(() => {
                            containerDesc.style.display = 'none';
                        }, 300);
                    }
                });
            }

            body.addEventListener('click', (event) => {
                const target = event.target instanceof HTMLElement ? event.target : null;
                
                // --- LÓGICA DE BORRADO ---
                const removeButton = target?.closest('.pedido-line-remove');
                if (removeButton instanceof HTMLElement) {
                    const index = Number.parseInt(removeButton.dataset.index || '-1', 10);
                    if (index >= 0 && index < items.length) {
                        items.splice(index, 1);
                        renderRows();
                    }
                    return;
                }

                // --- LÓGICA DE EDICIÓN ---
                const editButton = target?.closest('.pedido-line-edit');
                if (editButton instanceof HTMLElement) {
                    const index = Number.parseInt(editButton.dataset.index || '-1', 10);
                    if (index >= 0 && index < items.length) {
                        const itemToEdit = items[index];
                        editingLineUid = itemToEdit.uid || null;
                        editingLineArticulo = itemToEdit.articulo || '';
                        
                        descripcionInput.value = itemToEdit.descripcion;
                        cantidadInput.value = itemToEdit.cantidad;
                        medidaInput.value = itemToEdit.medida;
                        precioInput.value = itemToEdit.precio_unitario;
                        margenInput.value = itemToEdit.margen;
                        
                        if (modoConsumoSelect && itemToEdit.modo_consumo) {
                            modoConsumoSelect.value = itemToEdit.modo_consumo;
                            syncModoConsumoUi(); 
                        }
                        
                        autoResizeDescripcion();
                        
                        items.splice(index, 1);
                        renderRows();
                        
                        descripcionInput.focus();
                        descripcionInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }
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
                if (!form.checkValidity()) {
                    return;
                }

                const button = document.activeElement;
                if (button && button.dataset && button.dataset.estado) {
                    hiddenState.value = button.dataset.estado;
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

                setTimeout(() => {
                    const btns = form.querySelectorAll('button[type="submit"]');
                    btns.forEach(btn => {
                        btn.disabled = true;
                        btn.style.pointerEvents = 'none';
                        const anchoOriginal = btn.offsetWidth;
                        if(anchoOriginal > 0) btn.style.width = anchoOriginal + 'px';
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando';
                    });
                }, 50);
            });
        });
    </script>
@endsection