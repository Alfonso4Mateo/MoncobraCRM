@extends('adminlte::page')

@section('title', 'Nuevo Presupuesto - MoncobraCRM')

@section('content')
    @php
        $isCarga = $modo === 'carga';
        $volverUrl = $volverACliente && $clienteSeleccionadoId
            ? route('clientes.show', $clienteSeleccionadoId)
            : route('presupuestos.index');
    @endphp

    <section class="presupuesto-create-ui">

        @if ($errors->any())
            <div class="alert alert-danger presupuesto-errors" role="alert">
                <strong>No se pudo guardar el presupuesto.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <article class="presupuesto-card">
            <header>
                <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>
                <h2>Presupuesto cliente</h2>
            </header>

            <form action="{{ route('presupuestos.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="modo" value="{{ $modo }}">

                @if ($volverACliente && $clienteSeleccionadoId)
                    <input type="hidden" name="redirect_cliente_id" value="{{ $clienteSeleccionadoId }}">
                @endif

                <div class="presupuesto-grid">
                    <div class="field-group">
                        <label>Documento</label>
                        <div class="documento-display"><strong>Presupuesto</strong></div>
                        <input type="hidden" id="documento" name="documento" value="PRESUPUESTO">
                    </div>

                    <div class="field-group">
                        <label for="numero">Numero</label>
                        <input type="text" id="numero" name="numero" value="{{ old('numero', $siguienteNumero ?? '') }}" placeholder="{{ $siguienteNumero ?? '' }}" class="@error('numero') is-invalid @enderror" maxlength="50" required>
                    </div>

                    <div class="field-group">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" class="@error('fecha') is-invalid @enderror" required>
                    </div>

                    <div class="field-group">
                        <label for="cliente_id">Cliente</label>
                        <select id="cliente_id" name="cliente_id" class="@error('cliente_id') is-invalid @enderror" required>
                            <option value="">Seleccione un cliente...</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ (string) old('cliente_id', $clienteSeleccionadoId ?: '') === (string) $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->empresa_nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="titulo">Titulo del presupuesto</label>
                        <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}" placeholder="Ej: Renovacion flota logistica trimestral" class="@error('titulo') is-invalid @enderror" maxlength="255">
                    </div>

                    <div class="field-group">
                        <label for="ot">OT (Orden de trabajo)</label>
                        <input type="text" id="ot" name="ot" value="{{ old('ot') }}" placeholder="Referencia OT" class="@error('ot') is-invalid @enderror" maxlength="255">
                    </div>

                    <div class="field-group">
                        <label for="validez_oferta">Validez oferta</label>
                        <input type="text" id="validez_oferta" name="validez_oferta" value="{{ old('validez_oferta', '30 días') }}" placeholder="Ej: 30 días" class="@error('validez_oferta') is-invalid @enderror" maxlength="255">
                    </div>

                    <div class="field-group field-full">
                        <label for="exclusiones">Exclusiones</label>
                        <textarea id="exclusiones" name="exclusiones" rows="3" placeholder="Describa exclusiones relevantes" class="@error('exclusiones') is-invalid @enderror">{{ old('exclusiones', 'Cualquier concepto no descrito en la oferta') }}</textarea>
                    </div>

                    <div class="field-group field-full">
                        <label for="archivo_pdf">Archivo PDF {{ $isCarga ? '(obligatorio)' : '(opcional)' }}</label>
                        <input type="file" id="archivo_pdf" name="archivo_pdf" accept="application/pdf" class="@error('archivo_pdf') is-invalid @enderror" {{ $isCarga ? 'required' : '' }}>
                        <small class="pdf-help">Formato permitido: PDF. Tamano maximo: 10 MB.</small>
                    </div>
                </div>

                <section class="items-builder" aria-labelledby="items-builder-title">
                    <header class="items-builder-head">
                        <h3 id="items-builder-title">Datos del item</h3>
                        <button type="button" id="btn_agregar_item" class="btn-agregar-item">
                            Agregar
                        </button>
                    </header>

                    <div class="items-form-grid">
                        <div class="field-group">
                            <label for="item_articulo">Código referencia</label>
                            <input type="text" id="item_articulo" placeholder="Codigo o referencia">
                        </div>
                        <div class="field-group field-span-3">
                            <label for="item_descripcion">Descripcion</label>
                            <textarea id="item_descripcion" rows="3" placeholder="Descripcion detallada del material o servicio"></textarea>
                        </div>
                        <div class="field-group">
                            <label for="item_cantidad">Cantidad</label>
                            <input type="number" id="item_cantidad" min="0" step="0.01" value="1">
                        </div>
                        <div class="field-group">
                            <label for="item_medida">Medida</label>
                            <input type="text" id="item_medida" placeholder="u, kg, m...">
                        </div>
                        <div class="field-group">
                            <label for="item_precio_unitario">Precio unitario</label>
                            <input type="number" id="item_precio_unitario" min="0" step="0.01" value="0">
                        </div>
                        <div class="field-group field-group-margen">
                            <label for="item_margen">Margen (%)</label>
                            <input type="number" id="item_margen" min="0" step="0.01" value="0">
                        </div>
                    </div>

                    <input type="hidden" id="lista_articulos" name="lista_articulos" value='{{ old('lista_articulos', '[]') }}'>

                    <div class="items-table-wrap">
                        <table class="items-table" aria-label="Listado de items del presupuesto">
                            <thead>
                                <tr>
                                    <th>Línea</th>
                                    <th>Código ref.</th>
                                    <th>Descripción</th>
                                    <th>Cantidad</th>
                                    <th>Medida</th>
                                    <th>P. unitario</th>
                                    <th>Margen</th>
                                    <th>Total</th>
                                    <th class="actions-col"></th>
                                </tr>
                            </thead>
                            <tbody id="items_tbody">
                                <tr class="items-empty-row">
                                    <td colspan="9">No hay items agregados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <footer class="presupuesto-actions">
                    <div class="presupuesto-actions-left">
                        <button type="button" id="btn_eliminar_item" class="btn-neutral btn-eliminar" disabled>
                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            Eliminar
                        </button>
                        <button type="button" id="btn_editar_item" class="btn-neutral btn-editar" disabled>
                            <i class="fas fa-pen" aria-hidden="true"></i>
                            Editar
                        </button>
                        <a href="{{ $volverUrl }}" class="btn-neutral btn-salir">
                            <i class="fas fa-times" aria-hidden="true"></i>
                            Salir
                        </a>
                        <button type="submit" class="btn-guardar">
                        <i class="fas fa-save" aria-hidden="true"></i>
                            Guardar
                        </button>
                    </div>

                    <div class="presupuesto-totals-box" aria-live="polite">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <strong id="items_subtotal">0,00 EUR</strong>
                        </div>
                        <div class="total-row total-final">
                            <span>Total presupuesto</span>
                            <strong id="items_total">0,00 EUR</strong>
                        </div>
                    </div>
                </footer>
            </form>
        </article>
    </section>
@endsection

@section('css')
    @vite(['resources/css/presupuestos-create.css'])
@endsection

@section('js')
    <script>
        (function () {
            const hiddenInput = document.getElementById('lista_articulos');
            const tbody = document.getElementById('items_tbody');
            const subtotalEl = document.getElementById('items_subtotal');
            const totalEl = document.getElementById('items_total');

            const articuloInput = document.getElementById('item_articulo');
            const descripcionInput = document.getElementById('item_descripcion');
            const cantidadInput = document.getElementById('item_cantidad');
            const medidaInput = document.getElementById('item_medida');
            const precioInput = document.getElementById('item_precio_unitario');
            const margenInput = document.getElementById('item_margen');
            const addButton = document.getElementById('btn_agregar_item');
            const editButton = document.getElementById('btn_editar_item');
            const deleteButton = document.getElementById('btn_eliminar_item');

            const eur = new Intl.NumberFormat('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const qtyFmt = new Intl.NumberFormat('es-ES', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

            const safeNumber = (value) => {
                const numeric = Number.parseFloat(String(value).replace(',', '.'));
                return Number.isFinite(numeric) ? numeric : 0;
            };

            const parseItems = () => {
                try {
                    const parsed = JSON.parse(hiddenInput.value || '[]');
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };

            const normalizeItem = (item) => {
                const cantidad = Math.max(0, safeNumber(item?.cantidad));
                const precioUnitario = Math.max(0, safeNumber(item?.precio_unitario));
                const margen = Math.max(0, safeNumber(item?.margen));
                const total = cantidad * precioUnitario * (1 + (margen / 100));

                return {
                    articulo: String(item?.articulo ?? '').trim(),
                    descripcion: String(item?.descripcion ?? '').trim(),
                    cantidad: Number(cantidad.toFixed(2)),
                    medida: String(item?.medida ?? item?.unidad ?? '').trim(),
                    precio_unitario: Number(precioUnitario.toFixed(2)),
                    margen: Number(margen.toFixed(2)),
                    total: Number(total.toFixed(2)),
                };
            };

            const items = parseItems()
                .filter((item) => item && typeof item === 'object')
                .map(normalizeItem)
                .filter((item) => item.descripcion !== '');
            let selectedIndex = -1;
            let editingIndex = null;

            const resetItemForm = () => {
                articuloInput.value = '';
                descripcionInput.value = '';
                cantidadInput.value = '1';
                medidaInput.value = '';
                precioInput.value = '0';
                margenInput.value = '0';
                editingIndex = null;
                addButton.textContent = 'Agregar';
            };

            const setButtonsState = () => {
                const hasSelection = selectedIndex >= 0 && selectedIndex < items.length;
                editButton.disabled = !hasSelection;
                deleteButton.disabled = !hasSelection;
            };

            const fillFormFromItem = (index) => {
                const item = items[index];
                if (!item) {
                    return;
                }

                articuloInput.value = item.articulo || '';
                descripcionInput.value = item.descripcion || '';
                cantidadInput.value = String(item.cantidad ?? 1);
                medidaInput.value = String(item.medida ?? item.unidad ?? '');
                precioInput.value = String(item.precio_unitario ?? 0);
                margenInput.value = String(item.margen ?? 0);
                editingIndex = index;
                addButton.textContent = 'Actualizar';
                descripcionInput.focus();
            };

            const deleteItemAt = (index) => {
                if (index < 0 || index >= items.length) {
                    return;
                }

                items.splice(index, 1);

                if (editingIndex === index) {
                    resetItemForm();
                } else if (editingIndex !== null && editingIndex > index) {
                    editingIndex -= 1;
                }

                if (selectedIndex === index) {
                    selectedIndex = -1;
                } else if (selectedIndex > index) {
                    selectedIndex -= 1;
                }

                renderRows();
            };

            const syncHidden = () => {
                hiddenInput.value = JSON.stringify(items);
            };

            const renderTotals = () => {
                const subtotal = items.reduce((acc, item) => acc + safeNumber(item.total), 0);
                const total = subtotal;

                subtotalEl.textContent = `${eur.format(subtotal)} EUR`;
                totalEl.textContent = `${eur.format(total)} EUR`;
            };

            const renderRows = () => {
                if (items.length === 0) {
                    tbody.innerHTML = '<tr class="items-empty-row"><td colspan="9">No hay items agregados.</td></tr>';
                    renderTotals();
                    syncHidden();
                    setButtonsState();
                    return;
                }

                tbody.innerHTML = items.map((item, index) => `
                    <tr class="${selectedIndex === index ? 'item-selected' : ''}" data-index="${index}">
                        <td>${String(index + 1).padStart(2, '0')}</td>
                        <td>${item.articulo ? `<strong>${item.articulo}</strong>` : '<span class="text-muted">-</span>'}</td>
                        <td>${item.descripcion}</td>
                        <td style="text-align:right">${eur.format(safeNumber(item.cantidad))}</td>
                        <td>${item.medida ? item.medida : '<span class="text-muted">-</span>'}</td>
                        <td>${eur.format(safeNumber(item.precio_unitario))} EUR</td>
                        <td>${eur.format(safeNumber(item.margen))} %</td>
                        <td>${eur.format(safeNumber(item.total))} EUR</td>
                        <td class="actions-col">
                            <div class="row-actions">
                                <button type="button" class="btn-row-action btn-edit-item" data-index="${index}" aria-label="Editar item" title="Editar item">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" class="btn-row-action btn-remove-item" data-index="${index}" aria-label="Eliminar item" title="Eliminar item">×</button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                renderTotals();
                syncHidden();
                setButtonsState();
            };

            addButton.addEventListener('click', function () {
                const descripcion = descripcionInput.value.trim();
                const articulo = articuloInput.value.trim();
                const cantidad = Math.max(0, safeNumber(cantidadInput.value));
                const medida = String(medidaInput.value || '').trim();
                const precioUnitario = Math.max(0, safeNumber(precioInput.value));
                const margen = Math.max(0, safeNumber(margenInput.value));

                if (!descripcion || cantidad <= 0) {
                    window.alert('Complete al menos la descripción y una cantidad mayor que cero.');
                    return;
                }

                const total = cantidad * precioUnitario * (1 + (margen / 100));

                const payload = {
                    articulo,
                    descripcion,
                    cantidad: Number(cantidad.toFixed(2)),
                    medida,
                    precio_unitario: Number(precioUnitario.toFixed(2)),
                    margen: Number(margen.toFixed(2)),
                    total: Number(total.toFixed(2)),
                };

                if (editingIndex !== null && editingIndex >= 0 && editingIndex < items.length) {
                    items[editingIndex] = payload;
                    selectedIndex = editingIndex;
                } else {
                    items.push(payload);
                    selectedIndex = items.length - 1;
                }

                editingIndex = null;
                addButton.textContent = 'Agregar';
                articuloInput.value = '';
                descripcionInput.value = '';
                cantidadInput.value = '1';
                medidaInput.value = '';
                precioInput.value = '0';
                margenInput.value = '0';

                renderRows();
            });

            editButton.addEventListener('click', function () {
                if (selectedIndex < 0 || selectedIndex >= items.length) {
                    return;
                }

                fillFormFromItem(selectedIndex);
                renderRows();
            });

            deleteButton.addEventListener('click', function () {
                if (selectedIndex < 0 || selectedIndex >= items.length) {
                    return;
                }

                deleteItemAt(selectedIndex);
            });

            tbody.addEventListener('click', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const removeButton = target.closest('.btn-remove-item');
                if (removeButton instanceof HTMLElement) {
                    const index = Number.parseInt(removeButton.dataset.index || '-1', 10);
                    deleteItemAt(index);
                    return;
                }

                const rowEditButton = target.closest('.btn-edit-item');
                if (rowEditButton instanceof HTMLElement) {
                    const index = Number.parseInt(rowEditButton.dataset.index || '-1', 10);
                    if (index >= 0 && index < items.length) {
                        selectedIndex = index;
                        fillFormFromItem(index);
                        renderRows();
                    }
                    return;
                }

                const row = target.closest('tr[data-index]');
                if (row instanceof HTMLElement) {
                    const index = Number.parseInt(row.dataset.index || '-1', 10);
                    if (index >= 0 && index < items.length) {
                        selectedIndex = index;
                        renderRows();
                    }
                }
            });

            renderRows();
        })();
    </script>
@endsection
