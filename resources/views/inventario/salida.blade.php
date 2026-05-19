@extends('adminlte::page')

@section('title', 'Nueva Salida de Stock - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-salida.css'])
    <style>
        .row-error { outline: 2px solid #f87171; background: #fff6f6; }
        #client-errors { display: none; }
        #client-errors.is-visible { display: block; }
    </style>
@endsection

@section('content')
    <section class="stock-out-page">
        <header class="stock-out-head">
            <nav class="stock-out-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('inventario.index') }}">Inventario</a>
                <span><i class="fas fa-chevron-right"></i></span>
                <strong>Nueva Salida de Stock</strong>
            </nav>

            <div class="stock-out-datetime">
                <i class="far fa-calendar-alt"></i>
                {{ now()->format('d M Y, H:i A') }}
            </div>
        </header>

        <section class="stock-out-title">
            <h1>Registro de Salida</h1>
            <p>Control de egresos de materiales y componentes industriales.</p>
        </section>

        <div id="client-errors" class="stock-out-alert" role="alert" aria-hidden="true"></div>

        <style>
            .stock-out-alert { display:none; background:#fff4f4; border:1px solid #f5c6cb; padding:0.75rem; border-radius:4px; margin-bottom:0.75rem; }
            .stock-out-alert.is-visible { display:block; }
            .item-row.row-error { background:#fff6f6; border-left:4px solid #e3342f; }
            .item-row .is-invalid { border-color:#e3342f; }
        </style>

        @if ($errors->any())
            <div class="stock-out-alert" role="alert">
                <strong>No se pudo registrar la salida.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="stock-out-form" action="{{ route('inventario.salida.store') }}" method="POST" novalidate>
            @csrf

            <div class="stock-out-layout">
                <div class="stock-out-main">
                    <article class="out-card">
                        <header><h2><i class="far fa-copy"></i> Identificacion del producto</h2></header>

                        <div class="out-grid out-grid-top">
                            <div class="field-group field-wide">
                                <label for="producto_busqueda">Producto</label>
                                <div class="autocomplete-wrapper">
                                    <textarea
                                        id="producto_busqueda"
                                        name="producto_busqueda"
                                        rows="1"
                                        maxlength="1000"
                                        placeholder="Escribe SKU o nombre..."
                                        data-sync="producto_busqueda"
                                        class="input-auto-grow @error('producto_busqueda') is-invalid @enderror"
                                        required
                                    >{{ old('producto_busqueda') }}</textarea>
                                    <div id="producto-suggestions" class="autocomplete-dropdown" role="listbox" aria-label="Sugerencias de productos"></div>
                                </div>
                            </div>

                            <div class="field-group field-tight">
                                <label for="cantidad_retirar">Cantidad a retirar</label>
                                <input
                                    id="cantidad_retirar"
                                    name="cantidad_retirar"
                                    type="number"
                                    min="1"
                                    step="1"
                                    value="{{ old('cantidad_retirar', 1) }}"
                                    data-sync="cantidad_retirar"
                                    class="@error('cantidad_retirar') is-invalid @enderror"
                                    required
                                >
                            </div>
                        </div>

                        <article class="selected-product-card">
                            <div class="selected-head">
                                <span>Seleccionado</span>
                                <span class="state-ok">En stock</span>
                            </div>

                            <div class="selected-body">
                                <div class="selected-icon"><i class="fas fa-cart-arrow-down"></i></div>
                                <div>
                                    <h3 id="selected-product-name">{{ old('producto_busqueda', 'Selecciona un item del inventario') }}</h3>
                                    <small id="selected-product-code">SKU: -</small>
                                </div>
                                <div class="selected-stock">
                                    <small>Stock Actual</small>
                                    <strong><span id="selected-stock-value">0</span> unid</strong>
                                </div>
                            </div>
                        </article>

                        <div id="items-section-top" style="margin-top:0.75rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
                                <small>Agrega las filas que quieras; se enviarán como <strong>items[]</strong></small>
                                <button type="button" id="add-item-btn" class="btn-pdf btn-pdf--small" title="Añadir producto"> <i class="fas fa-plus"></i> Añadir producto</button>
                            </div>

                            <div id="items-container" style="margin-top:0.75rem;"></div>
                        </div>
                    </article>

                    <article class="out-card">
                        <header><h2><i class="far fa-clipboard"></i> Trazabilidad y asignacion</h2></header>

                        <div class="out-grid out-grid-bottom">
                            <div class="field-group">
                                <label for="ot">Orden de trabajo (OT)</label>
                                <input id="ot" name="ot" type="text" value="{{ old('ot') }}" placeholder="Buscar OT activa..." class="@error('ot') is-invalid @enderror">
                            </div>

                            <div class="field-group">
                                <label for="solicitante">Solicitante</label>
                                <input id="solicitante" name="solicitante" type="text" value="{{ old('solicitante', auth()->user()->name ?? '') }}" placeholder="Nombre del solicitante" list="solicitantes-list" autocomplete="off" class="@error('solicitante') is-invalid @enderror" data-sync="solicitante">
                            </div>
                        </div>

                        <footer class="out-actions">
                            <button type="submit" class="btn-confirm-out">
                                <i class="far fa-check-circle"></i>
                                Confirmar salida de stock
                            </button>
                            <a href="{{ route('inventario.index') }}" class="btn-cancel-out">Cancelar</a>
                        </footer>
                    </article>
                </div>

                <aside class="stock-out-side">
                    <article class="side-summary-card">
                        <h2>Resumen de operación</h2>

                        <dl class="summary-list">
                            <div>
                                <dt>Operador</dt>
                                <dd>{{ auth()->user()->name ?? 'Operador' }}</dd>
                            </div>
                            <div>
                                <dt>Cantidad de productos</dt>
                                <dd id="qty-preview">-{{ (int) old('cantidad_retirar', 1) }}</dd>
                            </div>
                            <div>
                                <dt>Criticidad</dt>
                                <dd><span class="crit-level">Media</span></dd>
                            </div>
                        </dl>

                        <div class="security-note">
                            <small>NOTA DE SEGURIDAD</small>
                            <p>Asegurese de verificar fisicamente el numero de serie antes de completar el retiro del almacen central.</p>
                        </div>

                        <div class="pdf-action-stack">
                            <button type="button" class="btn-pdf" data-pdf-open aria-controls="salida-pdf-modal" onclick="openSalidaPdf && openSalidaPdf()">Ver PDF</button>
                            <button type="button" class="btn-pdf btn-pdf--print" data-pdf-print>Imprimir</button>
                            <button type="submit" class="btn-pdf btn-pdf--save" name="guardar_documento" value="1">Guardar documento</button>
                        </div>
                    </article>

                    <article class="side-history-card">
                        <h3>Salidas recientes</h3>
                        <div class="history-list">
                            @forelse($salidasRecientes as $item)
                                <article>
                                    <div class="history-icon"><i class="far fa-minus-square"></i></div>
                                    <div>
                                        <strong>{{ $item->descripcion }}</strong>
                                        <small>{{ $item->updated_at?->format('d-m-Y H:i') }} · Hace {{ $item->updated_at?->diffForHumans() }}</small>
                                    </div>
                                    <span>-{{ max(1, (int) round($item->stock_actual * 0.05)) }}</span>
                                </article>
                            @empty
                                <p class="history-empty">No hay salidas recientes registradas.</p>
                            @endforelse
                        </div>
                        <button type="button" class="btn-history">Ver todo el historial</button>
                    </article>
                </aside>
            </div>

            <datalist id="inventario-catalogo-salida">
                @foreach ($catalogo as $producto)
                    <option value="{{ $producto->descripcion }}" data-codigo="{{ $producto->codigo }}"></option>
                    <option value="{{ $producto->codigo }}" data-codigo="{{ $producto->codigo }}"></option>
                @endforeach
            </datalist>
            <datalist id="solicitantes-list">
                @foreach (($solicitantes ?? []) as $solicitanteNombre)
                    <option value="{{ $solicitanteNombre }}"></option>
                @endforeach
            </datalist>
            <input type="hidden" name="codigo" id="codigo" value="{{ old('codigo') }}">

            <div class="pdf-modal" id="salida-pdf-modal" aria-hidden="true" role="dialog" aria-modal="true">
                <div class="pdf-modal__backdrop" data-pdf-close></div>
                <div class="pdf-modal__panel" role="document">
                    <header class="pdf-modal__header">
                        <h2>Vista previa del documento</h2>
                        <div class="pdf-modal__actions">
                            <button type="button" class="pdf-print-btn" data-pdf-print>
                                <i class="fas fa-print"></i>
                                Imprimir
                            </button>
                            <button type="button" class="pdf-close-btn" data-pdf-close aria-label="Cerrar vista previa">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </header>

                    <div class="pdf-modal__body">
                        <article class="pdf-sheet">
                            <div class="pdf-sheet__top">
                                <div class="pdf-logo">
                                    <img src="{{ asset('images/logo_h100.png') }}" alt="Moncobra">
                                </div>
                                <div class="pdf-title">
                                    <span>EQUIPO DE PROTECCION PERSONAL (EPI'S)</span>
                                    <span>(CONTROL DE ENTREGA/REPOSICION)</span>
                                </div>
                                <div class="pdf-service">SERVICIO DE PREVENCION</div>
                            </div>

                            <div class="pdf-meta-row">
                                <div class="pdf-field pdf-field--wide">
                                    <label for="pdf_delegacion">DELEGACION:</label>
                                    <input id="pdf_delegacion" name="pdf_delegacion" type="text" value="{{ old('pdf_delegacion') }}" placeholder="Delegacion">
                                </div>
                                <div class="pdf-field">
                                    <label for="pdf_fecha">FECHA:</label>
                                    <input id="pdf_fecha" name="pdf_fecha" type="text" value="{{ old('pdf_fecha', now()->format('d/m/Y')) }}">
                                </div>
                            </div>

                            <div class="pdf-meta-row">
                                <div class="pdf-field pdf-field--wide">
                                    <label for="pdf_trabajador">TRABAJADOR (NOMBRE Y APELLIDOS):</label>
                                    <input id="pdf_trabajador" name="pdf_trabajador" type="text" data-sync="solicitante" list="solicitantes-list" autocomplete="off" value="{{ old('solicitante', auth()->user()->name ?? '') }}" placeholder="Nombre del trabajador">
                                </div>
                                <div class="pdf-field">
                                    <label for="pdf_ficha">FICHA N:</label>
                                    <input id="pdf_ficha" name="pdf_ficha" type="text" value="{{ old('pdf_ficha') }}">
                                </div>
                            </div>

                            <div class="pdf-table-wrap">
                                <table class="pdf-table">
                                    <thead>
                                        <tr>
                                            <th>ARTICULO</th>
                                            <th>REPOSICIONES / NUEVAS NECESIDADES</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <textarea
                                                    data-pdf-line="1"
                                                    class="pdf-cell-input pdf-auto-grow"
                                                    placeholder="Nombre del producto"
                                                    readonly
                                                ></textarea>
                                            </td>
                                            <td>
                                                <textarea data-pdf-line="1-qty" class="pdf-cell-input pdf-cell-input--center pdf-auto-grow" placeholder="Cantidad" readonly></textarea>
                                            </td>
                                        </tr>
                                        @for ($i = 2; $i <= 10; $i++)
                                            <tr>
                                                <td><textarea data-pdf-line="{{ $i }}" class="pdf-cell-input pdf-auto-grow" placeholder="" readonly></textarea></td>
                                                <td><textarea data-pdf-line="{{ $i }}-qty" class="pdf-cell-input pdf-cell-input--center pdf-auto-grow" placeholder="" readonly></textarea></td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>

                            <div class="pdf-observaciones">
                                <label for="pdf_observaciones">OBSERVACIONES:</label>
                                <textarea id="pdf_observaciones" name="pdf_observaciones" class="pdf-auto-grow" placeholder="Escriba cualquier nota o comentario adicional...">{{ old('pdf_observaciones') }}</textarea>
                            </div>

                            <div class="pdf-legal">
                                De conformidad con la normativa vigente, este registro documenta la entrega y reposicion de equipos de proteccion.
                            </div>

                            <div class="pdf-signatures">
                                <div class="pdf-sign">Firma del Responsable</div>
                                <div class="pdf-sign">Firma del Trabajador</div>
                            </div>
                        </article>
                    </div>

                    <footer class="pdf-modal__footer">
                        <button type="button" class="btn-pdf-close" data-pdf-close>Cerrar vista</button>
                    </footer>
                </div>
            </div>
        </form>
    </section>
@endsection

@section('js')
    @php
        $catalogoSalidaJs = $catalogo
            ->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'codigo' => (string) $item->codigo,
                    'nombre' => (string) ($item->nombre ?? ''),
                    'descripcion' => (string) $item->descripcion,
                    'stock_actual' => (int) ($item->stock_actual ?? 0),
                ];
            })
            ->values();
    @endphp

    <script>
        (function () {
            const catalogo = @json($catalogoSalidaJs);
            const productoInput = document.getElementById('producto_busqueda');
            const suggestionsDropdown = document.getElementById('producto-suggestions');
            const cantidadInput = document.getElementById('cantidad_retirar');
            const qtyPreview = document.getElementById('qty-preview');
            const selectedName = document.getElementById('selected-product-name');
            const selectedCode = document.getElementById('selected-product-code');
            const selectedStock = document.getElementById('selected-stock-value');
            const pdfOpenButton = document.querySelector('[data-pdf-open]');
            const pdfModal = document.getElementById('salida-pdf-modal');
            const pdfCloseButtons = pdfModal ? pdfModal.querySelectorAll('[data-pdf-close]') : [];
            const pdfPrintButtons = document.querySelectorAll('[data-pdf-print]');
            const syncInputs = document.querySelectorAll('[data-sync]');
            const autoGrowAreas = document.querySelectorAll('.pdf-auto-grow');
            const formAutoGrowAreas = document.querySelectorAll('.input-auto-grow');

            const showClientErrors = (errors) => {
                const container = document.getElementById('client-errors');
                if (!container) return;
                container.innerHTML = `<strong>Errores:</strong><ul>${errors.map(e => `<li>${e}</li>`).join('')}</ul>`;
                container.classList.add('is-visible');
                // clear previous highlights
                document.querySelectorAll('.item-row.row-error').forEach(r => r.classList.remove('row-error'));
                // highlight rows where qty > maxStock
                const rows = document.querySelectorAll('.item-row');
                rows.forEach(row => {
                    const qtyEl = row.querySelector('input[data-item-qty]');
                    if (!qtyEl) return;
                    const maxStock = parseInt(qtyEl.dataset.maxStock || qtyEl.max || '0', 10) || 0;
                    const qty = parseInt(qtyEl.value || '0', 10) || 0;
                    if (maxStock > 0 && qty > maxStock) row.classList.add('row-error');
                });
                container.scrollIntoView({behavior: 'smooth', block: 'center'});
            };

            const clearClientErrors = () => {
                const container = document.getElementById('client-errors');
                if (!container) return;
                container.innerHTML = '';
                container.classList.remove('is-visible');
                document.querySelectorAll('.item-row.row-error').forEach(r => r.classList.remove('row-error'));
            };

            const normalize = (value) => String(value || '').trim().toLowerCase();

            const findProducto = () => {
                const search = normalize(productoInput.value);
                if (!search) {
                    return null;
                }

                return catalogo.find((item) => {
                    return normalize(item.codigo) === search
                        || normalize(item.descripcion) === search
                        || normalize(item.nombre) === search;
                }) || null;
            };

            const findSimilarProductos = (query) => {
                if (!query) return [];
                const normalized = normalize(query);
                return catalogo.filter((item) => {
                    return normalize(item.codigo).includes(normalized)
                        || normalize(item.descripcion).includes(normalized)
                        || normalize(item.nombre).includes(normalized);
                }).slice(0, 8);
            };

            const renderSuggestions = () => {
                const query = productoInput.value.trim();
                if (!query) {
                    suggestionsDropdown.classList.remove('is-open');
                    suggestionsDropdown.innerHTML = '';
                    return;
                }

                const similares = findSimilarProductos(query);
                if (similares.length === 0) {
                    suggestionsDropdown.classList.remove('is-open');
                    suggestionsDropdown.innerHTML = '';
                    return;
                }

                suggestionsDropdown.innerHTML = similares
                    .map((item, index) => `
                        <div class="autocomplete-item" data-index="${index}" role="option">
                            <strong>${item.codigo}</strong>
                            <small>${item.descripcion}</small>
                        </div>
                    `)
                    .join('');

                suggestionsDropdown.classList.add('is-open');

                suggestionsDropdown.querySelectorAll('.autocomplete-item').forEach((item) => {
                    item.addEventListener('click', () => {
                        const index = parseInt(item.dataset.index, 10);
                        const selected = similares[index];
                        productoInput.value = selected.descripcion;
                        hydrateProduct();
                        suggestionsDropdown.classList.remove('is-open');
                        suggestionsDropdown.innerHTML = '';
                    });
                });
            };

            const updateSummary = () => {
                const qty = parseInt(cantidadInput.value || '0', 10) || 0;
                qtyPreview.textContent = `-${Math.max(1, qty)}`;
            };

            const hydrateProduct = () => {
                const producto = findProducto();

                if (!producto) {
                    selectedName.textContent = productoInput.value || 'Selecciona un item del inventario';
                    selectedName.textContent = productoInput.value || 'Selecciona un item del inventario';
                    selectedCode.textContent = 'SKU: -';
                    selectedStock.textContent = '0';
                    // clear limits
                    if (cantidadInput) { cantidadInput.removeAttribute('max'); }
                    return;
                }
                selectedName.textContent = producto.descripcion;
                selectedCode.textContent = `SKU: ${producto.codigo}`;
                selectedStock.textContent = producto.stock_actual;
                // set limits on single-quantity input
                if (cantidadInput) {
                    cantidadInput.max = producto.stock_actual;
                    if (parseInt(cantidadInput.value || '0', 10) > producto.stock_actual) {
                        cantidadInput.value = producto.stock_actual;
                    }
                }
            };

            productoInput.addEventListener('change', hydrateProduct);
            productoInput.addEventListener('input', () => {
                clearClientErrors();
                renderSuggestions();
                hydrateProduct();
            });
            cantidadInput.addEventListener('input', updateSummary);

            // Cerrar dropdown cuando se hace click fuera
            document.addEventListener('click', (event) => {
                if (!event.target.closest('.autocomplete-wrapper')) {
                    suggestionsDropdown.classList.remove('is-open');
                }
            });

            if (pdfOpenButton && pdfModal) {
                const openModal = () => {
                    populatePdfPreview();
                    pdfModal.classList.add('is-open');
                    pdfModal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('pdf-modal-open');
                };

                const populatePdfPreview = () => {
                    const lines = Array.from(pdfModal.querySelectorAll('textarea[data-pdf-line]'));
                    lines.forEach(t => t.value = '');
                    const itemsContainerLocal = document.getElementById('items-container');
                    const rows = itemsContainerLocal ? itemsContainerLocal.querySelectorAll('.item-row') : [];

                    const errors = [];

                    if (rows.length > 0) {
                        rows.forEach((row, rIdx) => {
                            const qtyEl = row.querySelector('input[type="number"]');
                            const qty = parseInt(qtyEl?.value || '0', 10) || 0;
                            let maxStock = 0;
                            if (qtyEl) {
                                maxStock = parseInt(qtyEl.dataset.maxStock || qtyEl.max || '0', 10) || 0;
                            }
                            // try to resolve by hidden inventario_id if needed
                            if (maxStock === 0) {
                                const codeEl = row.querySelector('input[type="hidden"][name$="[inventario_id]"]');
                                if (codeEl && codeEl.value) {
                                    const found = catalogo.find(i => String(i.id) === String(codeEl.value));
                                    if (found) maxStock = found.stock_actual || 0;
                                }
                            }
                            if (maxStock > 0 && qty > maxStock) {
                                errors.push(`Stock insuficiente en fila ${rIdx + 1}: solicitado ${qty}, disponible ${maxStock}`);
                            }
                        });
                    } else {
                        const prod = findProducto();
                        const singleQty = parseInt(cantidadInput.value || '0', 10) || 0;
                        if (prod && singleQty > (prod.stock_actual || 0)) {
                            errors.push(`Stock insuficiente para ${prod.descripcion} (máx ${prod.stock_actual || 0}).`);
                        }
                    }

                    if (errors.length > 0) {
                        showClientErrors(errors);
                        return false;
                    }

                    // if validation passed, populate fields
                    if (rows.length > 0) {
                        let idx = 1;
                        rows.forEach(row => {
                            const prodText = (row.querySelector('textarea') || {}).value || '';
                            const qty = (row.querySelector('input[type="number"]') || {}).value || '';
                            const lineEl = pdfModal.querySelector(`textarea[data-pdf-line="${idx}"]`);
                            const qtyEl = pdfModal.querySelector(`textarea[data-pdf-line="${idx}-qty"]`);
                            if (lineEl) lineEl.value = prodText;
                            if (qtyEl) qtyEl.value = qty;
                            idx++;
                        });
                    } else {
                        const prod = findProducto();
                        const prodName = prod ? prod.descripcion : (productoInput.value || '');
                        const qty = cantidadInput.value || '';
                        const lineEl = pdfModal.querySelector('textarea[data-pdf-line="1"]');
                        const qtyEl = pdfModal.querySelector('textarea[data-pdf-line="1-qty"]');
                        if (lineEl) lineEl.value = prodName;
                        if (qtyEl) qtyEl.value = qty;
                    }

                    return true;
                };

                const closeModal = () => {
                    pdfModal.classList.remove('is-open');
                    pdfModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('pdf-modal-open');
                };

                const triggerPrint = () => {
                    if (!pdfModal.classList.contains('is-open')) {
                        // validate and open
                        if (!populatePdfPreview()) return;
                        openModal();
                    }
                    setTimeout(() => window.print(), 0);
                };

                // Expose a safe global opener as a fallback for the button
                window.openSalidaPdf = () => {
                    try {
                        if (!populatePdfPreview()) return;
                        openModal();
                    } catch (err) {
                        console.warn('No se pudo abrir la vista previa del PDF', err);
                    }
                };

                pdfOpenButton.addEventListener('click', openModal);
                pdfCloseButtons.forEach((button) => button.addEventListener('click', closeModal));
                pdfPrintButtons.forEach((button) => button.addEventListener('click', triggerPrint));
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && pdfModal.classList.contains('is-open')) {
                        closeModal();
                    }
                });
            }

            if (syncInputs.length) {
                const syncGroups = new Map();
                const syncState = { active: false };

                syncInputs.forEach((input) => {
                    const key = input.dataset.sync || '';
                    if (!key) {
                        return;
                    }
                    if (!syncGroups.has(key)) {
                        syncGroups.set(key, []);
                    }
                    syncGroups.get(key).push(input);
                });

                const syncHandler = (event) => {
                    if (syncState.active) {
                        return;
                    }
                    const source = event.target;
                    const key = source.dataset.sync || '';
                    const group = syncGroups.get(key);
                    if (!group) {
                        return;
                    }
                    syncState.active = true;
                    group.forEach((input) => {
                        if (input === source) {
                            return;
                        }
                        if (input.value !== source.value) {
                            input.value = source.value;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                    syncState.active = false;
                };

                syncGroups.forEach((group) => {
                    group.forEach((input) => input.addEventListener('input', syncHandler));
                });
            }

            if (autoGrowAreas.length) {
                const resizeArea = (el) => {
                    el.style.height = 'auto';
                    el.style.height = `${el.scrollHeight}px`;
                };

                autoGrowAreas.forEach((area) => {
                    resizeArea(area);
                    area.addEventListener('input', () => resizeArea(area));
                });
            }

            if (formAutoGrowAreas.length) {
                const resizeArea = (el) => {
                    el.style.height = 'auto';
                    el.style.height = `${el.scrollHeight}px`;
                };

                formAutoGrowAreas.forEach((area) => {
                    resizeArea(area);
                    area.addEventListener('input', () => resizeArea(area));
                });
            }

            hydrateProduct();
            updateSummary();

            // Dynamic items list (items[])
            (function () {
                const itemsContainer = document.getElementById('items-container');
                const addItemBtn = document.getElementById('add-item-btn');
                let nextIndex = 0;

                const recalcQtyPreview = () => {
                    const qtyEls = itemsContainer.querySelectorAll('input[data-item-qty]');
                    let total = 0;
                    qtyEls.forEach((el) => { total += parseInt(el.value || '0', 10) || 0; });
                    if (total === 0) {
                        // fallback to single quantity
                        const single = parseInt(cantidadInput.value || '0', 10) || 0;
                        qtyPreview.textContent = `-${Math.max(1, single)}`;
                    } else {
                        qtyPreview.textContent = `-${total}`;
                    }
                };

                const createRow = (index, product = '', qty = 1) => {
                    const row = document.createElement('div');
                    row.className = 'item-row';
                    row.style.display = 'flex';
                    row.style.gap = '0.5rem';
                    row.style.alignItems = 'center';
                    row.style.marginTop = '0.5rem';

                    const prod = document.createElement('textarea');
                    prod.name = `items[${index}][producto_busqueda]`;
                    prod.placeholder = 'SKU o descripción';
                    prod.rows = 1;
                    prod.className = 'input-auto-grow';
                    prod.style.flex = '1';
                    // if product is an object (selected), fill and lock the textarea and add hidden codigo
                    if (product && typeof product === 'object') {
                        prod.value = product.descripcion || product.nombre || '';
                        prod.readOnly = true;
                    } else {
                        prod.value = product || '';
                    }

                    const qtyInput = document.createElement('input');
                    qtyInput.type = 'number';
                    qtyInput.min = '1';
                    qtyInput.step = '1';
                    qtyInput.value = qty || 1;
                    qtyInput.name = `items[${index}][cantidad]`;
                    qtyInput.dataset.itemQty = '1';
                    qtyInput.style.width = '5.5rem';

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    removeBtn.className = 'btn-mini btn-danger';

                    const suggestions = document.createElement('div');
                    suggestions.className = 'autocomplete-dropdown';
                    suggestions.style.position = 'relative';

                    prod.addEventListener('input', () => {
                        const query = prod.value.trim();
                        if (!query) { suggestions.classList.remove('is-open'); suggestions.innerHTML = ''; recalcQtyPreview(); return; }
                        const similares = findSimilarProductos(query);
                        if (similares.length === 0) { suggestions.classList.remove('is-open'); suggestions.innerHTML = ''; return; }
                        suggestions.innerHTML = similares.map((item, idx) => `
                            <div class="autocomplete-item" data-index="${idx}" role="option">
                                <strong>${item.codigo}</strong>
                                <small>${item.descripcion}</small>
                            </div>
                        `).join('');
                        suggestions.classList.add('is-open');
                        suggestions.querySelectorAll('.autocomplete-item').forEach((it) => {
                            it.addEventListener('click', () => {
                                const si = parseInt(it.dataset.index, 10);
                                const selected = similares[si];
                                prod.value = selected.descripcion;
                                // attach a hidden input to store codigo for server-side resolution
                                let hidden = row.querySelector('input[type="hidden"][name="items[' + index + '][inventario_id]"]');
                                if (!hidden) {
                                    hidden = document.createElement('input');
                                    hidden.type = 'hidden';
                                    hidden.name = `items[${index}][inventario_id]`;
                                    row.appendChild(hidden);
                                }
                                hidden.value = selected.id;

                                // set max allowed on qty input according to stock
                                if (qtyInput) {
                                    qtyInput.max = selected.stock_actual;
                                    qtyInput.dataset.maxStock = selected.stock_actual;
                                    if (parseInt(qtyInput.value || '0', 10) > selected.stock_actual) {
                                        qtyInput.value = selected.stock_actual;
                                    }
                                }

                                suggestions.classList.remove('is-open');
                                suggestions.innerHTML = '';
                                recalcQtyPreview();
                            });
                        });
                    });

                    qtyInput.addEventListener('input', () => {
                        const maxStock = parseInt(qtyInput.dataset.maxStock || qtyInput.max || '0', 10) || 0;
                        if (maxStock > 0 && parseInt(qtyInput.value || '0', 10) > maxStock) {
                            qtyInput.classList.add('is-invalid');
                        } else {
                            qtyInput.classList.remove('is-invalid');
                        }
                        recalcQtyPreview();
                    });
                    removeBtn.addEventListener('click', () => { row.remove(); recalcQtyPreview(); });

                    row.appendChild(prod);
                    row.appendChild(qtyInput);
                    row.appendChild(removeBtn);
                    row.appendChild(suggestions);

                    // if product is object, also add hidden codigo input and set qty max
                    if (product && typeof product === 'object') {
                        let hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = `items[${index}][inventario_id]`;
                        hidden.value = product.id || '';
                        row.appendChild(hidden);

                        if (qtyInput) {
                            qtyInput.max = product.stock_actual || ''; 
                            qtyInput.dataset.maxStock = product.stock_actual || 0;
                        }
                    }

                    return row;
                };

                // Load old items if present
                const oldItems = @json(old('items', []));
                if (Array.isArray(oldItems) && oldItems.length > 0) {
                    oldItems.forEach((it) => {
                        let prod = it.producto_busqueda ?? '';
                        if (it.inventario_id) {
                            const found = catalogo.find(i => String(i.id) === String(it.inventario_id));
                            if (found) prod = found;
                        }
                        const r = createRow(nextIndex++, prod, it.cantidad ?? 1);
                        itemsContainer.appendChild(r);
                    });
                }

                addItemBtn.addEventListener('click', () => {
                    clearClientErrors();
                    const prod = findProducto();
                    const qty = parseInt(cantidadInput.value || '0', 10) || 0;
                    const clientErrors = [];
                    if (!prod) {
                        clientErrors.push('Selecciona un producto válido arriba antes de añadir.');
                    }
                    if (qty < 1) {
                        clientErrors.push('Introduce una cantidad válida antes de añadir.');
                    }
                    if (clientErrors.length) {
                        showClientErrors(clientErrors);
                        return;
                    }

                    itemsContainer.appendChild(createRow(nextIndex++, prod, qty));
                    recalcQtyPreview();
                });

                // Form submit client-side validation: ensure no requested qty exceeds available stock
                const stockForm = document.getElementById('stock-out-form');
                stockForm.addEventListener('submit', (e) => {
                    const rows = itemsContainer.querySelectorAll('.item-row');
                    const errors = [];

                    rows.forEach((row) => {
                        const qtyEl = row.querySelector('input[data-item-qty]');
                        const codeEl = row.querySelector('input[type="hidden"][name$="[inventario_id]"]');
                        const prodText = row.querySelector('textarea');
                        const qty = parseInt(qtyEl?.value || '0', 10) || 0;
                        if (qty < 1) {
                            errors.push('Cantidad inválida en una de las filas.');
                            return;
                        }

                        let maxStock = 0;
                        if (codeEl && codeEl.value) {
                            const found = catalogo.find(i => String(i.id) === String(codeEl.value) || i.codigo === codeEl.value);
                            if (found) maxStock = found.stock_actual || 0;
                        } else {
                            const found = catalogo.find(i => normalize(i.descripcion) === normalize(prodText.value) || normalize(i.codigo) === normalize(prodText.value) || normalize(i.nombre) === normalize(prodText.value));
                            if (found) maxStock = found.stock_actual || 0;
                        }

                        if (maxStock > 0 && qty > maxStock) {
                            errors.push(`Stock insuficiente para ${prodText.value} (máx ${maxStock}).`);
                        }
                    });

                    // Single product check if no items[] used
                    if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('textarea').value.trim() === '')) {
                        const singleQty = parseInt(cantidadInput.value || '0', 10) || 0;
                        const prod = findProducto();
                        if (prod && singleQty > (prod.stock_actual || 0)) {
                            errors.push(`Stock insuficiente para ${prod.descripcion} (máx ${(prod.stock_actual || 0)}).`);
                        }
                    }

                    if (errors.length > 0) {
                        e.preventDefault();
                        showClientErrors(errors);
                        return false;
                    }
                });

                // Recalculate when single quantity changes too
                cantidadInput.addEventListener('input', recalcQtyPreview);
                recalcQtyPreview();
            })();
        })();
    </script>
@endsection
