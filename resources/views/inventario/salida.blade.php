@extends('adminlte::page')

@section('title', 'Nueva Salida de Stock - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-salida.css'])
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
                                <input id="solicitante" name="solicitante" type="text" value="{{ old('solicitante', auth()->user()->name ?? '') }}" placeholder="Nombre del solicitante" class="@error('solicitante') is-invalid @enderror" data-sync="solicitante">
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
                            <button type="button" class="btn-pdf" data-pdf-open aria-controls="salida-pdf-modal">Ver PDF</button>
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
                                    <input id="pdf_trabajador" name="pdf_trabajador" type="text" data-sync="solicitante" value="{{ old('solicitante', auth()->user()->name ?? '') }}" placeholder="Nombre del trabajador">
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
                                                    name="pdf_articulo_1"
                                                    data-sync="producto_busqueda"
                                                    class="pdf-cell-input pdf-auto-grow"
                                                    placeholder="Nombre del producto"
                                                >{{ old('producto_busqueda') }}</textarea>
                                            </td>
                                            <td>
                                                <textarea
                                                    name="pdf_cantidad_1"
                                                    data-sync="cantidad_retirar"
                                                    class="pdf-cell-input pdf-cell-input--center pdf-auto-grow"
                                                    placeholder="Cantidad"
                                                >{{ old('cantidad_retirar', 1) }}</textarea>
                                            </td>
                                        </tr>
                                        @for ($i = 2; $i <= 10; $i++)
                                            <tr>
                                                <td><textarea name="pdf_articulo_{{ $i }}" class="pdf-cell-input pdf-auto-grow" placeholder=""></textarea></td>
                                                <td><textarea name="pdf_cantidad_{{ $i }}" class="pdf-cell-input pdf-cell-input--center pdf-auto-grow" placeholder=""></textarea></td>
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
                    'codigo' => (string) $item->codigo,
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
            const codigoInput = document.getElementById('codigo');
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

            const normalize = (value) => String(value || '').trim().toLowerCase();

            const findProducto = () => {
                const search = normalize(productoInput.value);
                if (!search) {
                    return null;
                }

                return catalogo.find((item) => {
                    return normalize(item.codigo) === search || normalize(item.descripcion) === search;
                }) || null;
            };

            const updateSummary = () => {
                const qty = parseInt(cantidadInput.value || '0', 10) || 0;
                qtyPreview.textContent = `-${Math.max(1, qty)}`;
            };

            const hydrateProduct = () => {
                const producto = findProducto();

                if (!producto) {
                    codigoInput.value = '';
                    selectedName.textContent = productoInput.value || 'Selecciona un item del inventario';
                    selectedCode.textContent = 'SKU: -';
                    selectedStock.textContent = '0';
                    return;
                }

                codigoInput.value = producto.codigo;
                selectedName.textContent = producto.descripcion;
                selectedCode.textContent = `SKU: ${producto.codigo}`;
                selectedStock.textContent = producto.stock_actual;
            };

            productoInput.addEventListener('change', hydrateProduct);
            productoInput.addEventListener('input', hydrateProduct);
            cantidadInput.addEventListener('input', updateSummary);

            if (pdfOpenButton && pdfModal) {
                const openModal = () => {
                    pdfModal.classList.add('is-open');
                    pdfModal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('pdf-modal-open');
                };

                const closeModal = () => {
                    pdfModal.classList.remove('is-open');
                    pdfModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('pdf-modal-open');
                };

                const triggerPrint = () => {
                    if (!pdfModal.classList.contains('is-open')) {
                        openModal();
                    }
                    setTimeout(() => window.print(), 0);
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
        })();
    </script>
@endsection
