@extends('adminlte::page')

@section('title', 'Editar Presupuesto - MoncobraCRM')

@section('css')
    @vite(['resources/css/presupuestos-detail.css', 'resources/css/presupuestos-create.css'])
@endsection

@section('content_header')
    <div class="presupuesto-detail-header">
        <div class="presupuesto-detail-header__copy">
            <h1>Editar Artículos del Presupuesto</h1>
            <p>Modifica los productos, cantidades, unidades de medida y márgenes del presupuesto.</p>
        </div>
        <a href="{{ route('presupuestos.show', $presupuesto) }}" class="presupuesto-detail-btn presupuesto-detail-btn--ghost">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Volver al detalle
        </a>
    </div>
@endsection

@section('content')
    <form action="{{ route('presupuestos.update', $presupuesto) }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        <section class="presupuesto-detail-shell">
            
            @if ($errors->any())
                <div class="alert alert-danger presupuesto-detail-alert" role="alert" style="margin-bottom: 20px;">
                    <strong>No se pudo actualizar el presupuesto.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <article class="presupuesto-detail-card">
                <header class="presupuesto-detail-card__head">
                    <h2>Información del presupuesto</h2>
                </header>
                <div class="presupuesto-detail-card__body">
                        <div class="items-form-grid">
                            <div class="field-group">
                                <label for="documento">Documento</label>
                                <input type="text" id="documento" name="documento" value="{{ old('documento', $presupuesto->documento) }}" required>
                            </div>
                            <div class="field-group">
                                <label for="numero">Número</label>
                                <input type="text" id="numero" name="numero" value="{{ old('numero', $presupuesto->numero) }}">
                            </div>
                            <div class="field-group">
                                <label for="fecha">Fecha</label>
                                <input type="date" id="fecha" name="fecha" value="{{ old('fecha', optional($presupuesto->fecha)->format('Y-m-d')) }}" required>
                            </div>
                            <div class="field-group">
                                <label for="estado_display">Estado</label>
                                <input type="text" id="estado_display" value="{{ ucfirst($presupuesto->estado ?? 'pendiente') }}" disabled title="El estado se actualiza desde las acciones">
                            </div>
                            <div class="field-group field-span-3">
                                <label for="cliente_id">Cliente</label>
                                <select id="cliente_id" name="cliente_id" required>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" {{ old('cliente_id', $presupuesto->cliente_id) == $cliente->id ? 'selected' : '' }}>
                                            {{ $cliente->empresa_nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field-group field-span-3">
                                <label for="titulo">Título</label>
                                <input type="text" id="titulo" name="titulo" value="{{ old('titulo', $presupuesto->titulo) }}">
                            </div>
                            <div class="field-group">
                                <label for="ot">OT</label>
                                <input type="text" id="ot" name="ot" value="{{ old('ot', $presupuesto->ot) }}">
                            </div>
                            <div class="field-group">
                                <label for="solicitante">Solicitante</label>
                                <input type="text" name="solicitante" id="solicitante" class="form-control" value="{{ old('solicitante', $presupuesto->solicitante ?? '') }}">
                            </div>
                            <div class="field-group">
                                <label for="destinatario">Destinatario</label>
                                <input type="text" name="destinatario" id="destinatario" class="form-control" value="{{ old('destinatario', $presupuesto->destinatario ?? '') }}">
                            </div>
                        </div>
                        
                </div>
            </article>

            <article class="presupuesto-detail-card">
                <header class="presupuesto-detail-card__head">
                    <h2>Editar artículos</h2>
                </header>

                <div class="presupuesto-detail-card__body">
                    <section class="items-builder" aria-labelledby="items-builder-title">
                        <header class="items-builder-head">
                            <h3 id="items-builder-title">Datos del item</h3>
                            <button type="button" id="btn_agregar_item" class="btn-agregar-item">
                                Agregar
                            </button>
                        </header>

                        <div class="items-form-grid">
                            <div class="field-group field-span-3">
                                <label for="item_descripcion">Descripcion</label>
                                <textarea id="item_descripcion" rows="3" placeholder="Descripcion detallada del material o servicio"></textarea>
                            </div>
                            <div class="field-group">
                                <label for="item_cantidad">Cantidad</label>
                                <input type="number" id="item_cantidad" min="1" max="1000000" step="1" value="1">
                            </div>
                            <div class="field-group">
                                <label for="item_unidad">Unidades de medida</label>
                                <input type="text" id="item_unidad" placeholder="u, kg, m, etc.">
                            </div>
                            <div class="field-group">
                                <label for="item_precio_unitario">Precio unitario</label>
                                <input type="number" id="item_precio_unitario" min="0" max="10000000" step="0.01" value="0">
                            </div>
                            <div class="field-group field-group-margen">
                                <label for="item_margen">Margen (%)</label>
                                <input type="number" id="item_margen" min="0" max="1000" step="0.01" value="0">
                            </div>
                        </div>

                        <input type="hidden" id="lista_articulos" name="lista_articulos" value='{{ old('lista_articulos', json_encode($presupuesto->lista_articulos ?? [])) }}'>

                        <div class="items-table-wrap">
                            <table class="items-table" aria-label="Listado de items del presupuesto">
                                <thead>
                                    <tr>
                                        <th>Pos.</th>
                                        <th>Descripcion</th>
                                        <th>Cantidad</th>
                                        <th>P. Unitario</th>
                                        <th>Total</th>
                                        <th class="actions-col"></th>
                                    </tr>
                                </thead>
                                <tbody id="items_tbody">
                                    <tr class="items-empty-row">
                                        <td colspan="6">No hay items agregados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="presupuesto-extra-fields" style="margin-top:12px;">
                        <div class="field-group">
                            <label for="validez_oferta">Validez oferta</label>
                            <input type="text" id="validez_oferta" name="validez_oferta" value="{{ old('validez_oferta', $presupuesto->validez_oferta ?? '30 días') }}" placeholder="Ej: 30 días" class="@error('validez_oferta') is-invalid @enderror" maxlength="255">
                        </div>

                        <div class="field-group field-full">
                            <label for="exclusiones">Exclusiones</label>
                            <textarea id="exclusiones" name="exclusiones" rows="3" placeholder="Describa exclusiones relevantes" class="@error('exclusiones') is-invalid @enderror">{{ old('exclusiones', $presupuesto->exclusiones ?? 'Cualquier concepto no descrito en la oferta') }}</textarea>
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
                            <a href="{{ route('presupuestos.show', $presupuesto) }}" class="btn-neutral btn-salir">
                                <i class="fas fa-times" aria-hidden="true"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn-guardar">
                            <i class="fas fa-save" aria-hidden="true"></i>
                                Guardar cambios
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
                </div>
            </article>
        </section>
    </form>
@endsection

@section('js')
    <script>
        (function () {
            const hiddenInput = document.getElementById('lista_articulos');
            const tbody = document.getElementById('items_tbody');
            const subtotalEl = document.getElementById('items_subtotal');
            const totalEl = document.getElementById('items_total');

            const descripcionInput = document.getElementById('item_descripcion');
            const cantidadInput = document.getElementById('item_cantidad');
            const unidadInput = document.getElementById('item_unidad');
            const precioInput = document.getElementById('item_precio_unitario');
            const margenInput = document.getElementById('item_margen');
            const addButton = document.getElementById('btn_agregar_item');
            const editButton = document.getElementById('btn_editar_item');
            const deleteButton = document.getElementById('btn_eliminar_item');

            const eur = new Intl.NumberFormat('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const MAX_CANTIDAD = 1000000;
            const MAX_PRECIO = 10000000;
            const MAX_MARGEN = 1000;

            const qtyFmt = new Intl.NumberFormat('es-ES', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

            const safeNumber = (value) => {
                const numeric = Number.parseFloat(String(value).replace(',', '.'));
                return Number.isFinite(numeric) ? numeric : 0;
            };

            const validateLineValues = (cantidad, precioUnitario, margen) => {
                if (cantidad > MAX_CANTIDAD) {
                    window.alert(`La cantidad no puede superar ${MAX_CANTIDAD}.`);
                    return false;
                }
                if (precioUnitario > MAX_PRECIO) {
                    window.alert(`El precio unitario no puede superar ${MAX_PRECIO}.`);
                    return false;
                }
                if (margen > MAX_MARGEN) {
                    window.alert(`El margen no puede superar ${MAX_MARGEN}%.`);
                    return false;
                }
                return true;
            };

            const parseItems = () => {
                try {
                    const parsed = JSON.parse(hiddenInput.value || '[]');
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };

            const items = parseItems();
            let selectedIndex = -1;
            let editingIndex = null;

            const resetItemForm = () => {
                descripcionInput.value = '';
                cantidadInput.value = '1';
                unidadInput.value = '';
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

                descripcionInput.value = item.descripcion || '';
                cantidadInput.value = String(item.cantidad ?? 1);
                unidadInput.value = String(item.unidad ?? '');
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
                    tbody.innerHTML = '<tr class="items-empty-row"><td colspan="6">No hay items agregados.</td></tr>';
                    renderTotals();
                    syncHidden();
                    setButtonsState();
                    return;
                }

                tbody.innerHTML = items.map((item, index) => `
                    <tr class="${selectedIndex === index ? 'item-selected' : ''}" data-index="${index}">
                        <td>${String(index + 1).padStart(2, '0')}</td>
                        <td>${item.descripcion}</td>
                        <td style="text-align:right">${qtyFmt.format(Number(item.cantidad))}${item.unidad ? ' ' + item.unidad : ''}</td>
                        <td>${eur.format(safeNumber(item.precio_con_margen || item.precio_unitario))} EUR</td>
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
                const cantidadRaw = safeNumber(cantidadInput.value);
                const cantidad = Math.max(1, Math.round(cantidadRaw));
                const unidad = String(unidadInput.value || '').trim();
                const precioUnitario = Math.max(0, safeNumber(precioInput.value));
                const margen = Math.max(0, safeNumber(margenInput.value));

                if (!descripcion || cantidad <= 0) {
                    window.alert('Complete al menos la descripcion y una cantidad entera mayor que cero.');
                    return;
                }

                const precioConMargen = precioUnitario * (1 + (margen / 100));
                const precioConMargenRounded = Number(precioConMargen.toFixed(2));
                const total = cantidad * precioConMargenRounded;

                const payload = {
                    descripcion,
                    cantidad: Number(cantidad),
                    unidad: unidad,
                    precio_unitario: Number(precioUnitario.toFixed(2)),
                    margen: Number(margen.toFixed(2)),
                    precio_con_margen: precioConMargenRounded,
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
                descripcionInput.value = '';
                cantidadInput.value = '1';
                unidadInput.value = '';
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
                        // Solo realizamos cambios si seleccionamos una fila diferente
                        if (selectedIndex !== index) {
                            selectedIndex = index;
                            
                            // 1. Buscamos si hay una fila previamente seleccionada y le quitamos la clase
                            const filaSeleccionadaPrevia = tbody.querySelector('.item-selected');
                            if (filaSeleccionadaPrevia) {
                                filaSeleccionadaPrevia.classList.remove('item-selected');
                            }
                            
                            // 2. Le añadimos la clase de selección a la fila donde acabamos de hacer clic
                            row.classList.add('item-selected');
                            
                            // 3. Actualizamos los botones de "Editar" y "Eliminar" para que se activen
                            setButtonsState();
                        }
                    }
                }
            });

            renderRows();
        })();
    </script>
@endsection