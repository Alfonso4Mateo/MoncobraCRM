@extends('adminlte::page')

@section('title', 'Registrar Salida de Stock - MoncobraCRM')

@section('plugins.Select2', true)

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .kardex-page { font-family: 'Manrope', sans-serif; padding: 1.25rem 0.5rem; }
        .kardex-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .kardex-tag { display: inline-block; background: #e0f2fe; color: #0369a1; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.25rem 0.65rem; border-radius: 999px; margin-bottom: 0.35rem; }
        .kardex-title { font-size: 1.7rem; font-weight: 800; color: #0f2747; margin: 0; }
        .kardex-subtitle { color: #64748b; font-size: 0.9rem; margin: 0.2rem 0 0 0; }

        .btn-erp-back { display: inline-flex; align-items: center; gap: 0.5rem; background: #fff; border: 1px solid #cbd5e1; color: #173e67; font-weight: 800; font-size: 0.85rem; padding: 0.6rem 1.15rem; border-radius: 10px; text-decoration: none !important; transition: all 0.15s; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
        .btn-erp-back:hover { background: #f8fafc; border-color: #94a3b8; color: #0f2747; }
        .btn-erp-navy { display: inline-flex; align-items: center; gap: 0.5rem; background: #173e67; border: 1px solid #173e67; color: #fff !important; font-weight: 800; font-size: 0.85rem; padding: 0.6rem 1.15rem; border-radius: 10px; cursor: pointer; transition: all 0.15s; }
        .btn-erp-navy:hover { background: #0f2747; color: #fff !important; }

        .kardex-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 1.25rem; align-items: start; }
        @media (max-width: 1100px) { .kardex-grid { grid-template-columns: 1fr; } }

        .erp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 15px rgba(15,23,42,0.03); margin-bottom: 1.25rem; overflow: hidden; }
        .erp-card-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fcfdff; }
        .erp-card-title { margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f2747; display: flex; align-items: center; gap: 0.55rem; }
        .erp-card-body { padding: 1.4rem 1.5rem; }

        .erp-label { display: block; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem; }
        .erp-input, .erp-select { width: 100%; height: 42px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 0.85rem; font-weight: 600; font-size: 0.9rem; color: #0f172a; background: #fff; transition: all 0.15s; }
        .erp-input:focus, .erp-select:focus { outline: none; border-color: #173e67; box-shadow: 0 0 0 3px rgba(23, 62, 103, 0.12); }

        /* Adaptación visual de Select2 al diseño ERP */
        .select2-container--bootstrap4 .select2-selection {
            border-radius: 10px !important;
            border: 1px solid #cbd5e1 !important;
            height: 42px !important;
            padding: 0.35rem 0.5rem !important;
            font-size: 0.9rem !important;
            font-weight: 600 !important;
        }
        .select2-container--bootstrap4.select2-container--focus .select2-selection {
            border-color: #173e67 !important;
            box-shadow: 0 0 0 3px rgba(23, 62, 103, 0.12) !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            color: #0f172a !important;
        }
        .select2-dropdown {
            border-radius: 10px !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12) !important;
            font-family: 'Manrope', sans-serif !important;
            font-size: 0.88rem !important;
        }
        .select2-results__option--highlighted[aria-selected] {
            background-color: #6dabee !important;
        }

        .tallas-box { background: #f8fbff; border: 1px solid #dce6f4; border-radius: 12px; padding: 0.85rem 1rem; margin-top: 0.5rem; display: none; }
        .tallas-badge { display: inline-flex; align-items: center; gap: 4px; background: #fff; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 7px; font-size: 0.78rem; font-weight: 700; color: #173e67; margin: 3px; }

        .picker-bar { display: grid; grid-template-columns: minmax(0, 1fr) 150px 140px; gap: 0.85rem; align-items: end; background: #f8fbff; border: 1px solid #dce6f4; border-radius: 12px; padding: 1.1rem; margin-bottom: 1.25rem; }
        @media (max-width: 768px) { .picker-bar { grid-template-columns: 1fr; } }

        .kardex-table-wrap { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .kardex-table { width: 100%; border-collapse: collapse; margin: 0; }
        .kardex-table th { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0.85rem 1.1rem; font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; text-align: left; }
        .kardex-table td { padding: 0.85rem 1.1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 0.9rem; color: #1e293b; }
        .qty-input { width: 105px; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 0.6rem; font-weight: 800; color: #0f2747; text-align: center; }
        .btn-delete-row { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
        .btn-delete-row:hover { background: #fee2e2; color: #b91c1c; }

        .kardex-summary-card { background: #f8fbff; border: 1px solid #dce6f4; border-radius: 16px; padding: 1.35rem; position: sticky; top: 1rem; }
        .kardex-summary-title { font-size: 1rem; font-weight: 800; color: #0f2747; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.1rem; }
        .kardex-summary-row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px dashed #cbd5e1; }
        .kardex-summary-row span { font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; }
        .kardex-summary-row strong { font-size: 1.25rem; font-weight: 800; color: #0f2747; }
        .epi-doc-notice { margin-top: 1rem; padding: 0.75rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; font-size: 0.78rem; font-weight: 700; color: #1e40af; display: none; }
        .btn-confirm-kardex { width: 100%; margin-top: 1.25rem; background: #173e67; color: #fff; border: none; border-radius: 11px; padding: 0.9rem 1rem; font-weight: 800; font-size: 0.92rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; transition: all 0.15s; box-shadow: 0 4px 12px rgba(23, 62, 103, 0.2); }
        .btn-confirm-kardex:hover:not(:disabled) { background: #0f2747; }
        .btn-confirm-kardex:disabled { background: #94a3b8; cursor: not-allowed; box-shadow: none; opacity: 0.65; }
    </style>
@endsection

@section('content')
<section class="container-fluid kardex-page">
    <header class="kardex-head">
        <div>
            <span class="kardex-tag">MÓDULO DE INVENTARIO</span>
            <h1 class="kardex-title">Registrar Salida de Material / EPI</h1>
            <p class="kardex-subtitle">Descarga existencias del almacén, asigna material a operarios y genera el acta de entrega EPI.</p>
        </div>
        <div>
            <a href="{{ route('inventario.index') }}" class="btn-erp-back">
                <i class="fas fa-arrow-left"></i> Volver al Inventario
            </a>
        </div>
    </header>

    @if(session('error'))
        <div class="alert alert-danger" style="border-radius: 12px; font-weight: 700;">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" style="border-radius: 12px;">
            <ul class="mb-0 font-weight-bold">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="kardex-grid">
        <form action="{{ route('inventario.kardex.salida.store') }}" method="POST" id="form-salida" novalidate>
            @csrf

            {{-- SECCIÓN 1: ORIGEN Y DESTINATARIO --}}
            <div class="erp-card">
                <div class="erp-card-header">
                    <h2 class="erp-card-title">
                        <i class="fas fa-warehouse text-primary"></i> 1. Origen y Destinatario
                    </h2>
                </div>
                <div class="erp-card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="erp-label" for="almacen_origen_id">Almacén de Origen <span class="text-danger">*</span></label>
                            <select name="almacen_origen_id" id="almacen_origen_id" class="erp-select" required>
                                <option value="">-- Selecciona el almacén --</option>
                                @foreach($almacenes as $alm)
                                    <option value="{{ $alm->id }}" @selected(old('almacen_origen_id') == $alm->id)>
                                        {{ $alm->nombre }} ({{ $alm->codigo ?? 'ALM-' . $alm->id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="erp-label" for="personal_id">Trabajador / Receptor (Opcional)</label>
                            <select name="personal_id" id="personal_id" class="erp-select">
                                <option value="">-- Ninguno (Salida general / Proyecto) --</option>
                                @foreach($trabajadores as $t)
                                    <option value="{{ $t->id }}" @selected(old('personal_id', $personalId ?? 0) == $t->id)>
                                        {{ $t->name }} {{ $t->apellido }} ({{ $t->dni_nie ?: 'ID: ' . $t->id_rrhh }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="erp-label" for="ot">Orden de Trabajo (OT) / Proyecto</label>
                            <input type="text" id="ot" name="ot" class="erp-input" placeholder="Ej. OT-2026-042" value="{{ old('ot') }}">
                        </div>

                        <div class="col-md-5 mb-2">
                            <label class="erp-label" for="documento_referencia">Documento de Referencia / Albarán</label>
                            <input type="text" id="documento_referencia" name="documento_referencia" class="erp-input" placeholder="Ej. ALB-78945" value="{{ old('documento_referencia') }}">
                        </div>

                        <div class="col-md-7 mb-2">
                            <label class="erp-label" for="observaciones">Observaciones de la entrega</label>
                            <input type="text" id="observaciones" name="observaciones" class="erp-input" placeholder="Detalles de la entrega o motivo de reposición..." value="{{ old('observaciones') }}">
                        </div>
                    </div>

                    <div id="tallas-trabajador-box" class="tallas-box">
                        <span style="font-size: 0.74rem; font-weight: 800; color: #173e67; text-transform: uppercase; display: block; margin-bottom: 4px;">
                            <i class="fas fa-user-check mr-1"></i> Tallas registradas del operario:
                        </span>
                        <div id="tallas-badges-container"></div>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 2: ARTÍCULOS A RETIRAR --}}
            <div class="erp-card mb-0">
                <div class="erp-card-header">
                    <h2 class="erp-card-title">
                        <i class="fas fa-boxes text-primary"></i> 2. Artículos a Retirar
                    </h2>
                </div>
                <div class="erp-card-body">
                    <div class="picker-bar">
                        <div>
                            <label class="erp-label" for="select-producto-picker">Buscar Producto con Stock en el Almacén</label>
                            <select id="select-producto-picker" class="form-control">
                                <option value="">-- Primero selecciona el almacén de origen --</option>
                            </select>
                        </div>
                        <div>
                            <label class="erp-label" for="input-cantidad-picker">Uds. a retirar</label>
                            <input type="number" id="input-cantidad-picker" class="erp-input text-center font-weight-bold" min="1" value="1">
                        </div>
                        <div>
                            <button type="button" class="btn-erp-navy w-100 justify-content-center" id="btn-add-linea" style="height: 42px;">
                                <i class="fas fa-plus"></i> Añadir línea
                            </button>
                        </div>
                    </div>

                    <div class="kardex-table-wrap table-responsive">
                        <table class="kardex-table" id="tabla-lineas">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">SKU / Código</th>
                                    <th style="width: 42%;">Artículo / Variante</th>
                                    <th style="width: 14%; text-align: center;">Stock Disp.</th>
                                    <th style="width: 14%; text-align: center;">Cantidad</th>
                                    <th style="width: 10%; text-align: center;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="lineas-tbody">
                                <tr id="empty-row">
                                    <td colspan="5" class="text-center py-4 text-muted font-weight-bold">
                                        No hay artículos añadidos a la salida todavía.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        {{-- RESUMEN LATERAL UNIFICADO --}}
        <aside class="kardex-summary-card">
            <div class="kardex-summary-title">
                <i class="fas fa-clipboard-list text-primary"></i> Resumen de Salida
            </div>

            <div class="kardex-summary-row">
                <span>Referencias</span>
                <strong id="summary-salida-items">0</strong>
            </div>

            <div class="kardex-summary-row" style="border-bottom: none;">
                <span>Total a retirar</span>
                <strong id="summary-salida-units" style="color: #dc2626; font-size: 1.45rem;">0</strong>
            </div>

            <div id="epi-doc-notice" class="epi-doc-notice">
                <i class="fas fa-print mr-1"></i> Al confirmar, se abrirá automáticamente el <strong>Documento de Entrega EPI</strong> listo para imprimir y firmar.
            </div>

            <button type="submit" form="form-salida" class="btn-confirm-kardex" id="btn-submit-form" disabled>
                <i class="fas fa-check-circle"></i> Confirmar Salida
            </button>

            <div class="text-center mt-3">
                <a href="{{ route('inventario.index') }}" style="font-size: 0.82rem; color: #64748b; font-weight: 700; text-decoration: underline;">
                    Cancelar y volver
                </a>
            </div>
        </aside>
    </div>
</section>
@endsection

@section('js')
    @php
        $stocksJs = $variantesConStock->map(function ($s) {
            $articulo = $s->variante?->articulo;
            $clase = $articulo?->clase?->nombre ?? '';
            return [
                'almacen_id' => (int) $s->almacen_id,
                'variante_id' => (int) ($s->variante_id ?? $s->inventario_variante_id),
                'sku' => (string) ($s->variante?->sku ?? $s->variante?->codigo ?? 'S/C'),
                'nombre' => (string) ($s->variante?->nombre ?: $articulo?->nombre ?: $articulo?->descripcion ?: 'Artículo'),
                'clase' => (string) $clase,
                'stock' => (int) $s->cantidad,
            ];
        })->values();

        $trabajadoresJs = $trabajadores->mapWithKeys(function ($t) {
            return [
                $t->id => [
                    'nombre' => trim("{$t->name} {$t->apellido}"),
                    'tallas' => array_filter([
                        'Camiseta' => $t->camiseta,
                        'Pantalón' => $t->pantalon,
                        'Calzado' => $t->calzado,
                        'Chaqueta' => $t->chaqueta,
                        'Sudadera' => $t->sudadera,
                        'Casco' => $t->casco,
                        'Guantes' => $t->guantes,
                        'Gafas' => $t->gafas,
                    ]),
                ]
            ];
        });
    @endphp

    <script>
        $(document).ready(function () {
            const stocksDisponibles = @json($stocksJs);
            const trabajadoresData = @json($trabajadoresJs);

            const $selectAlmacen = $('#almacen_origen_id');
            const $selectPersonal = $('#personal_id');
            const $pickerProducto = $('#select-producto-picker');
            const $pickerCantidad = $('#input-cantidad-picker');
            const $btnAdd = $('#btn-add-linea');
            const tbody = document.getElementById('lineas-tbody');
            const emptyRow = document.getElementById('empty-row');
            const boxTallas = document.getElementById('tallas-trabajador-box');
            const containerTallas = document.getElementById('tallas-badges-container');
            const summaryItems = document.getElementById('summary-salida-items');
            const summaryUnits = document.getElementById('summary-salida-units');
            const btnSubmit = document.getElementById('btn-submit-form');
            const epiNotice = document.getElementById('epi-doc-notice');

            let lineasCargadas = [];

            // 1. Inicializar Select2 en Trabajador para búsqueda por nombre/DNI
            $selectPersonal.select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: '-- Ninguno (Salida general / Proyecto) --',
                allowClear: true
            });

            // Función para normalizar texto (sin acentos ni mayúsculas) para búsqueda predictiva
            function normalizar(texto) {
                return (texto || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }

            // 2. Inicializar Select2 en Buscador de Productos con matcher multicriterio
            function initProductSelect2() {
                $pickerProducto.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: 'Escribe SKU, nombre o atributo (ej: ptr-3012, guantes, verde)...',
                    allowClear: true,
                    matcher: function (params, data) {
                        if ($.trim(params.term) === '') {
                            return data;
                        }

                        // Términos ingresados separados por espacios
                        const queryTerms = normalizar(params.term).split(' ').filter(t => t.length > 0);
                        const itemText = normalizar(data.text);

                        // Si todas las palabras coinciden en el texto, se muestra
                        const matchesAll = queryTerms.every(term => itemText.includes(term));
                        return matchesAll ? data : null;
                    }
                });
            }

            initProductSelect2();

            // Al elegir producto en el selector, enfocar directamente la cantidad para agilidad
            $pickerProducto.on('select2:select', function () {
                $pickerCantidad.focus().select();
            });

            // Permitir añadir pulsando Enter en el campo de cantidad
            $pickerCantidad.on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $btnAdd.trigger('click');
                }
            });

            // Manejo de cambio de trabajador y badge de tallas
            $selectPersonal.on('change', function () {
                const val = $(this).val();
                const hasWorker = Boolean(val);
                if (epiNotice) {
                    epiNotice.style.display = hasWorker ? 'block' : 'none';
                }

                const trabajador = trabajadoresData[val];
                if (!trabajador || Object.keys(trabajador.tallas).length === 0) {
                    boxTallas.style.display = 'none';
                    return;
                }

                containerTallas.innerHTML = Object.entries(trabajador.tallas)
                    .map(([prenda, talla]) => `<span class="tallas-badge"><strong>${prenda}:</strong> ${talla}</span>`)
                    .join('');
                boxTallas.style.display = 'block';
            });
            if ($selectPersonal.val()) $selectPersonal.trigger('change');

            // Manejo de cambio de Almacén de Origen
            $selectAlmacen.on('change', function () {
                const almId = parseInt($(this).val(), 10);

                if (lineasCargadas.length > 0) {
                    if (confirm('Al cambiar de almacén se limpiarán los artículos ya agregados. ¿Deseas continuar?')) {
                        lineasCargadas = [];
                        renderLineas();
                    } else {
                        return;
                    }
                }

                // Destruir instancia anterior de Select2 para limpiar el DOM
                if ($pickerProducto.hasClass('select2-hidden-accessible')) {
                    $pickerProducto.select2('destroy');
                }
                $pickerProducto.empty();

                if (!almId) {
                    $pickerProducto.html('<option value="">-- Primero selecciona el almacén de origen --</option>');
                    initProductSelect2();
                    return;
                }

                const filtrados = stocksDisponibles.filter((s) => s.almacen_id === almId);

                if (filtrados.length === 0) {
                    $pickerProducto.html('<option value="">No hay productos con existencias en este almacén</option>');
                    initProductSelect2();
                    return;
                }

                $pickerProducto.append('<option value=""></option>');
                filtrados.forEach((p) => {
                    const tagClase = p.clase ? `[${p.clase}] ` : '';
                    const label = `${tagClase}[${p.sku}] ${p.nombre} — (Stock: ${p.stock} uds.)`;
                    const $opt = $('<option>', {
                        value: p.variante_id,
                        text: label
                    });
                    $opt.attr('data-sku', p.sku);
                    $opt.attr('data-nombre', p.nombre);
                    $opt.attr('data-stock', p.stock);
                    $pickerProducto.append($opt);
                });

                initProductSelect2();
                $pickerProducto.val('').trigger('change');
            });
            if ($selectAlmacen.val()) $selectAlmacen.trigger('change');

            // Añadir línea a la tabla
            $btnAdd.on('click', function () {
                const varianteId = parseInt($pickerProducto.val(), 10);
                const $opt = $pickerProducto.find('option:selected');
                const cantidad = parseInt($pickerCantidad.val(), 10);

                if (!varianteId) {
                    alert('Selecciona un artículo de la lista.');
                    $pickerProducto.select2('open');
                    return;
                }

                const maxStock = parseInt($opt.attr('data-stock'), 10);
                if (cantidad < 1 || isNaN(cantidad)) {
                    alert('Introduce una cantidad válida.');
                    $pickerCantidad.focus().select();
                    return;
                }

                const existente = lineasCargadas.find((l) => l.variante_id === varianteId);
                const cantTotal = (existente ? existente.cantidad : 0) + cantidad;

                if (cantTotal > maxStock) {
                    alert(`No puedes retirar ${cantTotal} uds. El stock disponible en este almacén es de ${maxStock} uds.`);
                    $pickerCantidad.focus().select();
                    return;
                }

                if (existente) {
                    existente.cantidad = cantTotal;
                } else {
                    lineasCargadas.push({
                        variante_id: varianteId,
                        sku: $opt.attr('data-sku'),
                        nombre: $opt.attr('data-nombre'),
                        stock_disp: maxStock,
                        cantidad: cantidad
                    });
                }

                $pickerCantidad.val(1);
                $pickerProducto.val('').trigger('change');
                renderLineas();

                // Reabrir buscador de productos para añadir el siguiente artículo rápidamente
                $pickerProducto.select2('open');
            });

            function updateSummaryTotals() {
                summaryItems.textContent = String(lineasCargadas.length);
                const total = lineasCargadas.reduce((acc, l) => acc + l.cantidad, 0);
                summaryUnits.textContent = String(total);
                btnSubmit.disabled = lineasCargadas.length === 0;
            }

            function renderLineas() {
                if (lineasCargadas.length === 0) {
                    tbody.innerHTML = '';
                    tbody.appendChild(emptyRow);
                    updateSummaryTotals();
                    return;
                }

                tbody.innerHTML = lineasCargadas.map((l, idx) => `
                    <tr>
                        <td><strong style="color: #173e67; font-weight: 800;">${l.sku}</strong></td>
                        <td><strong style="color: #1e293b;">${l.nombre}</strong></td>
                        <td class="text-center"><span class="badge badge-info" style="font-size: 0.82rem; padding: 0.35rem 0.6rem;">${l.stock_disp} uds.</span></td>
                        <td class="text-center">
                            <input type="number"
                                   name="lineas[${idx}][cantidad]"
                                   class="qty-input input-row-qty"
                                   data-idx="${idx}"
                                   min="1"
                                   max="${l.stock_disp}"
                                   value="${l.cantidad}"
                                   required>
                            <input type="hidden" name="lineas[${idx}][variante_id]" value="${l.variante_id}">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn-delete-row js-btn-delete" data-idx="${idx}" title="Quitar fila">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                updateSummaryTotals();
            }

            tbody.addEventListener('input', function (e) {
                if (e.target.classList.contains('input-row-qty')) {
                    const idx = parseInt(e.target.dataset.idx, 10);
                    let val = parseInt(e.target.value, 10);
                    const max = lineasCargadas[idx].stock_disp;

                    if (val > max) {
                        alert(`La cantidad no puede superar el stock disponible (${max} uds.).`);
                        val = max;
                    }
                    if (val < 1 || isNaN(val)) val = 1;

                    lineasCargadas[idx].cantidad = val;
                    e.target.value = val;
                    updateSummaryTotals();
                }
            });

            tbody.addEventListener('click', function (e) {
                const btn = e.target.closest('.js-btn-delete');
                if (btn) {
                    const idx = parseInt(btn.dataset.idx, 10);
                    lineasCargadas.splice(idx, 1);
                    renderLineas();
                }
            });
        });
    </script>
@endsection