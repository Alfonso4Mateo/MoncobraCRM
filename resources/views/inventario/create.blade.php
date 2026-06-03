@extends('adminlte::page')

@section('title', 'Nueva Entrada de Stock - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-create.css'])
@endsection

@section('content')
    @php
        $hoy = now()->format('d / m / Y');
        $stockBaseInicial = old('stock_base_preview', $stockBase ?? 0);
    @endphp

    <section class="inventory-entry-page">
        <header class="inventory-entry-topbar">
            <nav class="inventory-entry-breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('inventario.index') }}">Inventario</a>
                <span><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                <strong>Nueva Entrada de Stock</strong>
            </nav>

            <div class="inventory-entry-actions">
                <a href="{{ route('inventario.index') }}" class="btn-entry-cancel">Cancelar</a>
                <button type="submit" form="inventory-entry-form" class="btn-entry-save">
                    <i class="fas fa-save" aria-hidden="true"></i>
                    Guardar Registro
                </button>
            </div>
        </header>

        <section class="inventory-entry-head">
            <h1>Nueva Entrada de Stock</h1>
            <p>Registrar mercancia recibida en el almacen central.</p>
        </section>

        @if ($errors->any())
            <div class="inventory-entry-alert" role="alert">
                <strong>No se pudo guardar el registro.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="inventory-entry-form" action="{{ route('inventario.entrada.store') }}" method="POST" novalidate>
            @csrf

            <div class="inventory-entry-layout">
                <div class="inventory-entry-main">
                    <article class="entry-card">
                        <header>
                            <h2><i class="far fa-copy" aria-hidden="true"></i> Detalles del producto</h2>
                        </header>

                        <div class="entry-grid entry-grid-producto">
                            <div class="field-group field-wide">
                                <label for="producto_busqueda">Producto</label>
                                <textarea
                                    id="producto_busqueda"
                                    name="producto_busqueda"
                                    rows="1"
                                    maxlength="1000"
                                    placeholder="Buscar por nombre o SKU..."
                                    class="input-auto-grow @error('producto_busqueda') is-invalid @enderror"
                                    required
                                >{{ old('producto_busqueda') }}</textarea>
                                <small>Se mostraran resultados coincidentes del catalogo activo.</small>
                            </div>

                            <div class="field-group field-wide">
                                <label>Nuevo item</label>
                                <a href="{{ route('inventario.item.create') }}" class="btn-entry-new-item">
                                    <i class="fas fa-plus"></i>
                                    Crear nuevo item
                                </a>
                                <small>Si el producto no existe en catalogo, registralo primero desde esta opcion.</small>
                            </div>
                        </div>

                        <div class="entry-grid entry-grid-two">
                            <div class="field-group">
                                <label for="stock_actual">Cantidad</label>
                                <div class="input-chip-wrap">
                                    <input
                                        id="stock_actual"
                                        name="stock_actual"
                                        type="number"
                                        min="0"
                                        step="1"
                                        value="{{ old('stock_actual', 1) }}"
                                        class="@error('stock_actual') is-invalid @enderror"
                                        required
                                    >
                                    <span>UNIDADES</span>
                                </div>
                            </div>

                            <div class="field-group">
                                <label for="almacen">Almacen destino</label>
                                <input
                                    id="almacen"
                                    name="almacen"
                                    type="text"
                                    value="{{ old('almacen') }}"
                                    placeholder="Ej: Almacen Central - Nave A"
                                    class="@error('almacen') is-invalid @enderror"
                                >
                            </div>

                            <div class="field-group">
                                <label for="ubicacion">Zona</label>
                                <input
                                    id="ubicacion"
                                    name="ubicacion"
                                    type="text"
                                    value="{{ old('ubicacion') }}"
                                    placeholder="Pasillo 3"
                                    class="@error('ubicacion') is-invalid @enderror"
                                >
                            </div>

                            <div class="field-group">
                                <label for="clase">Nivel</label>
                                <input
                                    id="clase"
                                    name="clase"
                                    type="text"
                                    value="{{ old('clase') }}"
                                    placeholder="Estanteria 12"
                                    class="@error('clase') is-invalid @enderror"
                                >
                            </div>
                        </div>

                    </article>

                    <article class="entry-card">
                        <header>
                            <h2><i class="far fa-file-alt" aria-hidden="true"></i> Notas y observaciones</h2>
                        </header>

                        <div class="field-group full">
                            <textarea id="notas" rows="4" class="input-auto-grow input-auto-grow--notes" placeholder="Indicar cualquier incidencia, estado del embalaje o instrucciones especiales..."></textarea>
                        </div>
                    </article>
                </div>

                <aside class="inventory-entry-side">
                    <article class="entry-card">
                        <header>
                            <h2><i class="fas fa-truck-loading" aria-hidden="true"></i> Logistica y origen</h2>
                        </header>

                        <div class="entry-grid single">
                            <div class="field-group">
                                <label for="fecha_preview">Fecha de entrada</label>
                                <input id="fecha_preview" type="text" value="{{ $hoy }}" readonly>
                            </div>

                            <div class="field-group">
                                <label for="ot">Orden de trabajo (OT)</label>
                                <input
                                    id="ot"
                                    name="ot"
                                    type="text"
                                    value="{{ old('ot') }}"
                                    placeholder="Buscar OT activa..."
                                    class="@error('ot') is-invalid @enderror"
                                >
                            </div>

                            <div class="field-group">
                                <label for="solicitante">Solicitante</label>
                                <input
                                    id="solicitante"
                                    name="solicitante"
                                    type="text"
                                    value="{{ old('solicitante', auth()->user()->name ?? '') }}"
                                    placeholder="Nombre del solicitante"
                                    class="@error('solicitante') is-invalid @enderror"
                                >
                            </div>

                            <div class="field-group">
                                <label for="referencia_proveedor">Proveedor asociado</label>
                                <select id="referencia_proveedor" name="referencia_proveedor" class="@error('referencia_proveedor') is-invalid @enderror">
                                    <option value="">Seleccionar proveedor</option>
                                    @foreach ($proveedores as $proveedor)
                                        <option value="{{ $proveedor }}" @selected(old('referencia_proveedor') === $proveedor)>
                                            {{ $proveedor }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field-group">
                                <label for="albaran_preview">N Albaran proveedor</label>
                                <input id="albaran_preview" type="text" value="{{ old('codigo', 'ALB-2025-XXXX') }}" readonly>
                            </div>
                        </div>
                    </article>

                    <article class="entry-note-box">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <p>Asegurese de que el numero de albaran sea legible. Al guardar, se generara una etiqueta de ubicacion automaticamente.</p>
                    </article>
                </aside>
            </div>

            <datalist id="inventario-catalogo">
                @foreach ($catalogo as $producto)
                    <option value="{{ $producto->descripcion }}" data-codigo="{{ $producto->codigo }}"></option>
                    <option value="{{ $producto->codigo }}" data-codigo="{{ $producto->codigo }}"></option>
                @endforeach
            </datalist>
            <input type="hidden" name="codigo" id="codigo" value="{{ old('codigo') }}">
            <input type="hidden" name="stock_base_preview" id="stock_base_preview" value="{{ (int) $stockBaseInicial }}">
        </form>
    </section>
@endsection

@section('js')
    @php
        $catalogoJs = $catalogo
            ->map(function ($item) {
                return [
                    'codigo' => (string) $item->codigo,
                    'descripcion' => (string) $item->descripcion,
                    'almacen' => (string) ($item->almacen ?? ''),
                    'ubicacion' => (string) ($item->ubicacion ?? ''),
                    'stock_actual' => (int) ($item->stock_actual ?? 0),
                ];
            })
            ->values();
    @endphp

    <script>
        (function () {
            const catalogo = @json($catalogoJs);

            const descripcionInput = document.getElementById('producto_busqueda');
            const codigoInput = document.getElementById('codigo');
            const stockInput = document.getElementById('stock_actual');
            const almacenInput = document.getElementById('almacen');
            const ubicacionInput = document.getElementById('ubicacion');
            const stockBaseEl = document.getElementById('stock-base');
            const stockFinalEl = document.getElementById('stock-final');
            const stockBaseHidden = document.getElementById('stock_base_preview');
            const albaranPreview = document.getElementById('albaran_preview');

            const normalize = (value) => String(value || '').trim().toLowerCase();

            const findProducto = () => {
                const descripcionValue = normalize(descripcionInput.value);
                const codigoValue = normalize(codigoInput.value);

                if (!descripcionValue && !codigoValue) {
                    return null;
                }

                return catalogo.find((item) => {
                    return normalize(item.codigo) === codigoValue
                        || normalize(item.descripcion) === descripcionValue
                        || normalize(item.codigo) === descripcionValue;
                }) || null;
            };

            const refreshSummary = () => {
                const base = parseInt(stockBaseHidden.value || '0', 10) || 0;
                const entrada = parseInt(stockInput.value || '0', 10) || 0;
                stockBaseEl.textContent = base;
                stockFinalEl.textContent = base + Math.max(0, entrada);
            };

            const hydrateFromCatalogo = () => {
                const producto = findProducto();

                if (!producto) {
                    codigoInput.value = '';
                    refreshSummary();
                    return;
                }

                codigoInput.value = producto.codigo;

                if (!almacenInput.value) {
                    almacenInput.value = producto.almacen;
                }

                if (!ubicacionInput.value) {
                    ubicacionInput.value = producto.ubicacion;
                }

                stockBaseHidden.value = String(producto.stock_actual);
                albaranPreview.value = codigoInput.value || producto.codigo || 'ALB-2025-XXXX';
                refreshSummary();
            };

            descripcionInput.addEventListener('change', hydrateFromCatalogo);
            codigoInput.addEventListener('change', hydrateFromCatalogo);
            stockInput.addEventListener('input', refreshSummary);
            codigoInput.addEventListener('input', function () {
                albaranPreview.value = this.value || 'ALB-2025-XXXX';
            });

            const autoGrowAreas = document.querySelectorAll('.input-auto-grow');
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

            refreshSummary();
        })();
    </script>
@endsection
