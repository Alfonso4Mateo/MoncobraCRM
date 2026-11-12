@extends('adminlte::page')

@section('title', 'Traslado de Inventario - MoncobraCRM')

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
        .btn-erp-navy { display: inline-flex; align-items: center; gap: 0.5rem; background: #173e67; border: 1px solid #173e67; color: #fff !important; font-weight: 800; font-size: 0.85rem; padding: 0.6rem 1.15rem; border-radius: 10px; cursor: pointer; transition: all 0.15s; box-shadow: 0 3px 6px rgba(23, 62, 103, 0.18); }
        .btn-erp-navy:hover { background: #0f2747; color: #fff !important; }

        .kardex-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 1.25rem; align-items: start; }
        @media (max-width: 1100px) { .kardex-grid { grid-template-columns: 1fr; } }

        .erp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 15px rgba(15,23,42,0.03); margin-bottom: 1.25rem; overflow: hidden; }
        .erp-card-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fcfdff; }
        .erp-card-title { margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f2747; display: flex; align-items: center; gap: 0.55rem; }
        .erp-ref-badge { font-size: 0.75rem; font-weight: 800; color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 0.28rem 0.65rem; border-radius: 8px; }
        .erp-card-body { padding: 1.4rem 1.5rem; }

        .erp-label { display: block; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem; }
        .erp-input, .erp-select { width: 100%; height: 42px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 0.85rem; font-weight: 600; font-size: 0.9rem; color: #0f172a; background: #fff; transition: all 0.15s; }
        .erp-input:focus, .erp-select:focus { outline: none; border-color: #173e67; box-shadow: 0 0 0 3px rgba(23, 62, 103, 0.12); }

        .picker-bar { display: grid; grid-template-columns: minmax(0, 1fr) 150px 140px; gap: 0.85rem; align-items: end; background: #f8fbff; border: 1px solid #dce6f4; border-radius: 12px; padding: 1.1rem; margin-bottom: 1.25rem; }
        @media (max-width: 768px) { .picker-bar { grid-template-columns: 1fr; } }

        .kardex-table-wrap { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .kardex-table { width: 100%; border-collapse: collapse; margin: 0; }
        .kardex-table th { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0.85rem 1.1rem; font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; text-align: left; }
        .kardex-table td { padding: 0.85rem 1.1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 0.9rem; color: #1e293b; }
        .qty-input { width: 105px; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 0.6rem; font-weight: 800; color: #0f2747; text-align: center; }
        .btn-delete-row { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
        .btn-delete-row:hover { background: #fee2e2; color: #b91c1c; }

        /* Resumen lateral fijo */
        .kardex-summary-card { background: #f8fbff; border: 1px solid #dce6f4; border-radius: 16px; padding: 1.35rem; position: sticky; top: 1rem; }
        .kardex-summary-title { font-size: 1rem; font-weight: 800; color: #0f2747; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
        .transfer-route-box { background: #ffffff; border: 1px solid #dbe4f0; border-radius: 11px; padding: 0.75rem 0.85rem; margin-bottom: 0.85rem; font-size: 0.8rem; font-weight: 700; color: #334155; display: flex; flex-direction: column; gap: 0.35rem; }
        .transfer-route-step { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
        .transfer-route-step small { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; color: #64748b; }
        .transfer-route-step strong { color: #0f2747; font-weight: 800; text-align: right; }
        .kardex-summary-row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px dashed #cbd5e1; }
        .kardex-summary-row span { font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; }
        .kardex-summary-row strong { font-size: 1.25rem; font-weight: 800; color: #0f2747; }
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
                <h1 class="kardex-title">Traslado entre Almacenes</h1>
                <p class="kardex-subtitle">Mueve existencias de un almacén de origen a otro de destino con trazabilidad completa.</p>
            </div>
            <div>
                <a href="{{ route('inventario.index') }}" class="btn-erp-back">
                    <i class="fas fa-arrow-left"></i> Volver al Inventario
                </a>
            </div>
        </header>

        @if(session('error'))
            <div class="alert alert-danger" style="border-radius: 12px; font-weight: 700;">
                <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 12px;">
                <strong>No se pudo completar el traslado:</strong>
                <ul class="mb-0 mt-1 font-weight-bold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="kardex-grid">
            <form id="inv-transfer-form" action="{{ route('inventario.kardex.traslado.store') }}" method="POST" novalidate>
                @csrf

                {{-- SECCIÓN 1: CONFIGURACIÓN DEL TRASLADO --}}
                <div class="erp-card">
                    <div class="erp-card-header">
                        <h2 class="erp-card-title">
                            <i class="fas fa-exchange-alt text-primary"></i> 1. Configuración del Traslado
                        </h2>
                        <span class="erp-ref-badge">REF: {{ $transaccionId }}</span>
                    </div>
                    <div class="erp-card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="erp-label" for="almacen_origen_id">Almacén de Origen <span class="text-danger">*</span></label>
                                <select id="almacen_origen_id" name="almacen_origen_id" class="erp-select" required>
                                    <option value="">-- Seleccione origen --</option>
                                    @foreach ($almacenes as $alm)
                                        <option value="{{ $alm->id }}" data-nombre="{{ $alm->nombre }}" @selected(old('almacen_origen_id') == $alm->id)>
                                            {{ $alm->nombre }} {{ $alm->codigo ? "({$alm->codigo})" : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="erp-label" for="almacen_destino_id">Almacén de Destino <span class="text-danger">*</span></label>
                                <select id="almacen_destino_id" name="almacen_destino_id" class="erp-select" required>
                                    <option value="">-- Seleccione destino --</option>
                                    @foreach ($almacenes as $alm)
                                        <option value="{{ $alm->id }}" data-nombre="{{ $alm->nombre }}" @selected(old('almacen_destino_id') == $alm->id)>
                                            {{ $alm->nombre }} {{ $alm->codigo ? "({$alm->codigo})" : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="erp-label" for="documento_referencia">Documento / Referencia</label>
                                <input id="documento_referencia" name="documento_referencia" type="text" class="erp-input" value="{{ old('documento_referencia', $transaccionId) }}" placeholder="TRF-XXXX">
                            </div>

                            <div class="col-12">
                                <label class="erp-label" for="observaciones">Motivo / Solicitante</label>
                                <input id="observaciones" name="observaciones" type="text" class="erp-input" value="{{ old('observaciones', 'Solicitado por: ' . (auth()->user()->name ?? '')) }}" placeholder="Observaciones del traslado o responsable del transporte...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECCIÓN 2: ARTÍCULOS A TRASLADAR --}}
                <div class="erp-card mb-0">
                    <div class="erp-card-header">
                        <h2 class="erp-card-title">
                            <i class="fas fa-boxes text-primary"></i> 2. Artículos a Trasladar
                        </h2>
                    </div>
                    <div class="erp-card-body">
                        <div class="picker-bar">
                            <div>
                                <label class="erp-label" for="picker_producto_origen">Añadir artículo disponible en el Almacén de Origen</label>
                                <select id="picker_producto_origen" class="erp-select">
                                    <option value="">-- Primero selecciona el almacén de origen --</option>
                                </select>
                            </div>

                            <div>
                                <label class="erp-label" for="picker_cantidad_origen">Uds. a trasladar</label>
                                <input id="picker_cantidad_origen" type="number" min="1" value="1" class="erp-input text-center font-weight-bold">
                            </div>

                            <div>
                                <button type="button" id="btn_add_transfer_row" class="btn-erp-navy w-100 justify-content-center" style="height: 42px;">
                                    <i class="fas fa-plus"></i> Añadir línea
                                </button>
                            </div>
                        </div>

                        <div class="kardex-table-wrap table-responsive">
                            <table class="kardex-table" aria-label="Detalle de productos para traslado">
                                <thead>
                                    <tr>
                                        <th style="width: 18%;">SKU / Código</th>
                                        <th style="width: 34%;">Descripción</th>
                                        <th style="width: 16%;">Almacén Origen</th>
                                        <th style="width: 12%; text-align: center;">Stock Disp.</th>
                                        <th style="width: 12%; text-align: center;">Uds. a Mover</th>
                                        <th style="width: 8%; text-align: center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="transfer_rows">
                                    <tr id="empty_transfer_row">
                                        <td colspan="6" class="text-center py-4 text-muted font-weight-bold">
                                            Selecciona un almacén de origen y añade los productos a trasladar.
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
                    <i class="fas fa-random text-primary"></i> Resumen de Traslado
                </div>

                <div class="transfer-route-box">
                    <div class="transfer-route-step">
                        <small>Origen:</small>
                        <strong id="summary-origen-name">Sin seleccionar</strong>
                    </div>
                    <div class="transfer-route-step" style="border-top: 1px dashed #e2e8f0; padding-top: 0.35rem;">
                        <small>Destino:</small>
                        <strong id="summary-destino-name" style="color: #2563eb;">Sin seleccionar</strong>
                    </div>
                </div>

                <div class="kardex-summary-row">
                    <span>Referencias</span>
                    <strong id="summary-transfer-items">0</strong>
                </div>

                <div class="kardex-summary-row" style="border-bottom: none;">
                    <span>Total unidades</span>
                    <strong id="summary-transfer-units" style="color: #2563eb; font-size: 1.45rem;">0</strong>
                </div>

                <button type="submit" form="inv-transfer-form" class="btn-confirm-kardex" id="btn_submit_transfer" disabled>
                    <i class="fas fa-check-circle"></i> Confirmar Traslado
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
        $stocksTrasladoJs = $stocks->map(function ($s) {
            $articulo = $s->variante?->articulo;
            return [
                'almacen_id' => (int) $s->almacen_id,
                'almacen_nombre' => (string) ($s->almacen?->nombre ?? 'Almacén'),
                'variante_id' => (int) ($s->variante_id ?? $s->inventario_variante_id),
                'codigo' => (string) ($s->variante?->sku ?? $s->variante?->codigo ?? 'S/C'),
                'descripcion' => (string) ($s->variante?->nombre ?: $articulo?->nombre ?: $articulo?->descripcion ?: 'Artículo'),
                'referencia_proveedor' => (string) ($articulo?->referencia_proveedor ?? ''),
                'stock_actual' => (int) $s->cantidad,
            ];
        })->values();
    @endphp

    <script>
        (function () {
            const stocks = @json($stocksTrasladoJs);
            const selectOrigen = document.getElementById('almacen_origen_id');
            const selectDestino = document.getElementById('almacen_destino_id');
            const picker = document.getElementById('picker_producto_origen');
            const btnAdd = document.getElementById('btn_add_transfer_row');
            const quantityPicker = document.getElementById('picker_cantidad_origen');
            const rowsBody = document.getElementById('transfer_rows');
            const emptyRow = document.getElementById('empty_transfer_row');
            const form = document.getElementById('inv-transfer-form');

            const summaryOrigenName = document.getElementById('summary-origen-name');
            const summaryDestinoName = document.getElementById('summary-destino-name');
            const summaryItems = document.getElementById('summary-transfer-items');
            const summaryUnits = document.getElementById('summary-transfer-units');
            const btnSubmit = document.getElementById('btn_submit_transfer');

            let lineas = [];

            const updateSummary = () => {
                const optOrigen = selectOrigen.options[selectOrigen.selectedIndex];
                const optDestino = selectDestino.options[selectDestino.selectedIndex];

                summaryOrigenName.textContent = (selectOrigen.value && optOrigen) ? (optOrigen.dataset.nombre || optOrigen.text) : 'Sin seleccionar';
                summaryDestinoName.textContent = (selectDestino.value && optDestino) ? (optDestino.dataset.nombre || optDestino.text) : 'Sin seleccionar';

                summaryItems.textContent = String(lineas.length);
                const totalUds = lineas.reduce((acc, item) => acc + item.cantidad, 0);
                summaryUnits.textContent = String(totalUds);

                const validWarehouses = Boolean(selectOrigen.value && selectDestino.value && selectOrigen.value !== selectDestino.value);
                btnSubmit.disabled = !(lineas.length > 0 && validWarehouses);
            };

            const syncDestinationOptions = () => {
                const origenVal = selectOrigen.value;
                Array.from(selectDestino.options).forEach((opt) => {
                    if (!opt.value) return;
                    opt.disabled = (opt.value === origenVal);
                });
                if (selectDestino.value && selectDestino.value === origenVal) {
                    selectDestino.value = '';
                }
                updateSummary();
            };

            const renderRows = () => {
                if (lineas.length === 0) {
                    rowsBody.innerHTML = '';
                    rowsBody.appendChild(emptyRow);
                    updateSummary();
                    return;
                }

                rowsBody.innerHTML = lineas.map((item, idx) => `
                    <tr>
                        <td><strong style="color: #173e67; font-weight: 800;">${item.codigo}</strong></td>
                        <td>
                            <strong style="color: #1e293b;">${item.descripcion}</strong>
                            ${item.referencia_proveedor ? `<small class="d-block text-muted">Ref: ${item.referencia_proveedor}</small>` : ''}
                        </td>
                        <td style="font-weight: 600; color: #475569;">${item.almacen_nombre}</td>
                        <td class="text-center">
                            <span class="badge badge-info" style="font-size: 0.82rem; padding: 0.35rem 0.6rem;">${item.stock_actual} uds.</span>
                        </td>
                        <td class="text-center">
                            <input type="hidden" name="lineas[${idx}][variante_id]" value="${item.variante_id}">
                            <input type="number" name="lineas[${idx}][cantidad]" min="1" max="${item.stock_actual}" value="${item.cantidad}" data-idx="${idx}" class="qty-input input-qty" required>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn-delete-row btn-remove-row" data-idx="${idx}" title="Quitar línea">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                updateSummary();
            };

            selectOrigen.addEventListener('change', function () {
                const origenId = parseInt(this.value, 10);
                if (lineas.length > 0) {
                    if (confirm('Al cambiar el almacén de origen se vaciarán las líneas actuales. ¿Continuar?')) {
                        lineas = [];
                        renderRows();
                    }
                }

                syncDestinationOptions();

                if (!origenId) {
                    picker.innerHTML = '<option value="">-- Primero selecciona el almacén de origen --</option>';
                    return;
                }

                const disponibles = stocks.filter((s) => s.almacen_id === origenId);
                if (disponibles.length === 0) {
                    picker.innerHTML = '<option value="">No hay artículos con stock en este almacén</option>';
                    return;
                }

                picker.innerHTML = '<option value="">-- Selecciona un artículo para trasladar --</option>' +
                    disponibles.map((item) => `<option value="${item.variante_id}">[${item.codigo}] ${item.descripcion} (Disponible: ${item.stock_actual} uds.)</option>`).join('');
            });

            selectDestino.addEventListener('change', updateSummary);

            btnAdd.addEventListener('click', function () {
                const origenId = parseInt(selectOrigen.value, 10);
                const varianteId = parseInt(picker.value, 10);

                if (!origenId || !varianteId) {
                    alert('Selecciona el almacén de origen y un artículo disponible.');
                    return;
                }

                const found = stocks.find((s) => s.almacen_id === origenId && s.variante_id === varianteId);
                if (!found) return;

                const requested = Math.max(1, parseInt(quantityPicker?.value || '1', 10));
                const existente = lineas.find((l) => l.variante_id === varianteId);

                if (existente) {
                    if (existente.cantidad + requested <= found.stock_actual) {
                        existente.cantidad += requested;
                    } else {
                        alert(`No puedes superar el stock disponible en este almacén (${found.stock_actual} uds.).`);
                    }
                } else {
                    if (requested > found.stock_actual) {
                        alert(`No puedes superar el stock disponible en este almacén (${found.stock_actual} uds.).`);
                        return;
                    }
                    lineas.push({
                        ...found,
                        cantidad: requested
                    });
                }

                picker.value = '';
                if (quantityPicker) quantityPicker.value = '1';
                renderRows();
            });

            rowsBody.addEventListener('input', function (e) {
                if (e.target.classList.contains('input-qty')) {
                    const idx = parseInt(e.target.dataset.idx, 10);
                    let val = parseInt(e.target.value, 10);
                    const max = lineas[idx].stock_actual;
                    if (val > max) {
                        alert(`No puedes superar el stock disponible (${max} uds.).`);
                        val = max;
                    }
                    if (val < 1 || isNaN(val)) val = 1;
                    lineas[idx].cantidad = val;
                    e.target.value = val;
                    updateSummary();
                }
            });

            rowsBody.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-remove-row');
                if (btn) {
                    const idx = parseInt(btn.dataset.idx, 10);
                    lineas.splice(idx, 1);
                    renderRows();
                }
            });

            form.addEventListener('submit', function (e) {
                if (selectOrigen.value && selectOrigen.value === selectDestino.value) {
                    e.preventDefault();
                    alert('El almacén de destino debe ser distinto al almacén de origen.');
                    return;
                }
                if (lineas.length === 0) {
                    e.preventDefault();
                    alert('Añade al menos un producto para realizar el traslado.');
                }
            });

            if (selectOrigen.value) selectOrigen.dispatchEvent(new Event('change'));
            updateSummary();
        })();
    </script>
@endsection