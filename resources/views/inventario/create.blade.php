@extends('adminlte::page')

@section('title', 'Nueva Entrada de Stock - MoncobraCRM')

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
        .btn-erp-navy { display: inline-flex; align-items: center; gap: 0.5rem; background: #173e67; border: 1px solid #173e67; color: #fff !important; font-weight: 800; font-size: 0.85rem; padding: 0.6rem 1.15rem; border-radius: 10px; text-decoration: none !important; cursor: pointer; transition: all 0.15s; box-shadow: 0 3px 6px rgba(23, 62, 103, 0.18); }
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
        .erp-input[readonly] { background: #f8fafc; color: #64748b; font-weight: 700; }

        /* Barra de selección de artículos sin solapamientos */
        .picker-bar { display: grid; grid-template-columns: minmax(0, 1fr) 150px 140px; gap: 0.85rem; align-items: end; background: #f8fbff; border: 1px solid #dce6f4; border-radius: 12px; padding: 1.1rem; margin-bottom: 1.25rem; }
        @media (max-width: 768px) { .picker-bar { grid-template-columns: 1fr; } }

        .kardex-table-wrap { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .kardex-table { width: 100%; border-collapse: collapse; margin: 0; }
        .kardex-table th { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0.85rem 1.1rem; font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; text-align: left; }
        .kardex-table td { padding: 0.85rem 1.1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 0.9rem; color: #1e293b; }
        .qty-input { width: 110px; height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 0.6rem; font-weight: 800; color: #0f2747; text-align: center; }
        .btn-delete-row { background: #fff5f5; color: #dc2626; border: 1px solid #fecaca; width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
        .btn-delete-row:hover { background: #fee2e2; color: #b91c1c; }

        /* Resumen lateral fijo */
        .kardex-summary-card { background: #f8fbff; border: 1px solid #dce6f4; border-radius: 16px; padding: 1.35rem; position: sticky; top: 1rem; }
        .kardex-summary-title { font-size: 1rem; font-weight: 800; color: #0f2747; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.1rem; }
        .kardex-summary-row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px dashed #cbd5e1; }
        .kardex-summary-row span { font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; }
        .kardex-summary-row strong { font-size: 1.25rem; font-weight: 800; color: #0f2747; }
        .btn-confirm-kardex { width: 100%; margin-top: 1.25rem; background: #173e67; color: #fff; border: none; border-radius: 11px; padding: 0.9rem 1rem; font-weight: 800; font-size: 0.92rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; transition: all 0.15s; box-shadow: 0 4px 12px rgba(23, 62, 103, 0.2); }
        .btn-confirm-kardex:hover:not(:disabled) { background: #0f2747; }
        .btn-confirm-kardex:disabled { background: #94a3b8; cursor: not-allowed; box-shadow: none; opacity: 0.65; }
    </style>
@endsection

@section('content')
    @php
        $hoy = now()->format('d / m / Y');
    @endphp

    <section class="container-fluid kardex-page">
        <header class="kardex-head">
            <div>
                <span class="kardex-tag">MÓDULO DE INVENTARIO </span>
                <h1 class="kardex-title">Nueva Entrada Múltiple de Stock</h1>
                <p class="kardex-subtitle">Registrar la recepción de artículos o albaranes directamente en el almacén de destino.</p>
            </div>
            <div class="d-flex align-items-center" style="gap: 0.65rem;">
                <a href="{{ route('inventario.index') }}" class="btn-erp-back">
                    <i class="fas fa-arrow-left"></i> Volver al Inventario
                </a>
                <a href="{{ route('inventario.item.create') }}" class="btn-erp-navy">
                    <i class="fas fa-plus"></i> Crear ítem nuevo en el catálogo
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
                <strong>No se pudo registrar la entrada:</strong>
                <ul class="mb-0 mt-1 font-weight-bold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="kardex-grid">
            <form id="inventory-entry-form" action="{{ route('inventario.kardex.entrada.store') }}" method="POST" novalidate>
                @csrf

                {{-- SECCIÓN 1: DATOS DE LA RECEPCIÓN --}}
                <div class="erp-card">
                    <div class="erp-card-header">
                        <h2 class="erp-card-title">
                            <i class="fas fa-truck-loading text-primary"></i> 1. Datos de la Recepción
                        </h2>
                    </div>
                    <div class="erp-card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="erp-label">Fecha de entrada</label>
                                <input type="text" class="erp-input" value="{{ $hoy }}" readonly>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label class="erp-label" for="almacen_destino_id">Almacén de Destino <span class="text-danger">*</span></label>
                                <select id="almacen_destino_id" name="almacen_destino_id" class="erp-select" required>
                                    <option value="" disabled @selected(!old('almacen_destino_id'))>Selecciona un almacén...</option>
                                    @foreach($almacenes as $almacen)
                                        <option value="{{ $almacen->id }}" @selected(old('almacen_destino_id') == $almacen->id)>
                                            {{ $almacen->nombre }} {{ $almacen->codigo ? "({$almacen->codigo})" : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="erp-label" for="documento_referencia">Albarán / Ref. Documento</label>
                                <input id="documento_referencia" name="documento_referencia" type="text" class="erp-input" value="{{ old('documento_referencia') }}" placeholder="Ej: ALB-2026-089">
                            </div>

                            <div class="col-12">
                                <label class="erp-label" for="observaciones">Observaciones / Receptor</label>
                                <input id="observaciones" name="observaciones" type="text" class="erp-input" value="{{ old('observaciones', 'Recepcionado por: ' . (auth()->user()->name ?? '')) }}" placeholder="Notas de recepción, proveedor o estado del pedido...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECCIÓN 2: ARTÍCULOS A INGRESAR --}}
                <div class="erp-card mb-0">
                    <div class="erp-card-header">
                        <h2 class="erp-card-title">
                            <i class="fas fa-boxes text-primary"></i> 2. Artículos a Ingresar
                        </h2>
                    </div>
                    <div class="erp-card-body">
                        <div class="picker-bar">
                            <div>
                                <label class="erp-label" for="select_variante_picker">Seleccionar producto o variante del catálogo</label>
                                <select id="select_variante_picker" class="erp-select">
                                    <option value="">-- Busca y selecciona un artículo del catálogo --</option>
                                    @foreach($variantes as $v)
                                        @php
                                            $sku = $v->sku ?? $v->codigo;
                                            $desc = $v->nombre ?: ($v->articulo?->nombre ?: $v->articulo?->descripcion);
                                            $clase = $v->articulo?->clase?->nombre ?? 'General';
                                        @endphp
                                        <option value="{{ $v->id }}" data-codigo="{{ $sku }}" data-descripcion="{{ $desc }}">
                                            [{{ $sku }}] {{ $desc }} ({{ $clase }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="erp-label" for="input_cantidad_picker">Uds. a ingresar</label>
                                <input id="input_cantidad_picker" type="number" min="1" value="1" class="erp-input text-center font-weight-bold">
                            </div>

                            <div>
                                <button type="button" id="btn-add-item" class="btn-erp-navy w-100 justify-content-center" style="height: 42px;">
                                    <i class="fas fa-plus"></i> Añadir línea
                                </button>
                            </div>
                        </div>

                        <div class="kardex-table-wrap table-responsive">
                            <table class="kardex-table">
                                <thead>
                                    <tr>
                                        <th style="width: 22%;">SKU / Código</th>
                                        <th style="width: 48%;">Descripción de la Variante</th>
                                        <th style="width: 18%; text-align: center;">Uds. a ingresar</th>
                                        <th style="width: 12%; text-align: center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="items-table-body">
                                    <tr id="empty-row">
                                        <td colspan="4" class="text-center py-4 text-muted font-weight-bold">
                                            No hay artículos en la lista. Selecciona un producto arriba y pulsa "Añadir línea".
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>

            {{-- RESUMEN LATERAL --}}
            <aside class="kardex-summary-card">
                <div class="kardex-summary-title">
                    <i class="fas fa-clipboard-check text-primary"></i> Resumen de Entrada
                </div>

                <div class="kardex-summary-row">
                    <span>Referencias</span>
                    <strong id="summary-items">0</strong>
                </div>

                <div class="kardex-summary-row" style="border-bottom: none;">
                    <span>Total unidades</span>
                    <strong id="summary-units" style="color: #15803d; font-size: 1.45rem;">0</strong>
                </div>

                <button type="submit" form="inventory-entry-form" class="btn-confirm-kardex" id="btn-submit" disabled>
                    <i class="fas fa-check-circle"></i> Confirmar Entrada
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
    <script>
        (function () {
            const picker = document.getElementById('select_variante_picker');
            const btnAdd = document.getElementById('btn-add-item');
            const quantityPicker = document.getElementById('input_cantidad_picker');
            const tbody = document.getElementById('items-table-body');
            const emptyRow = document.getElementById('empty-row');
            const summaryItems = document.getElementById('summary-items');
            const summaryUnits = document.getElementById('summary-units');
            const btnSubmit = document.getElementById('btn-submit');

            let lineas = [];

            const renderTable = () => {
                if (lineas.length === 0) {
                    tbody.innerHTML = '';
                    tbody.appendChild(emptyRow);
                    summaryItems.textContent = '0';
                    summaryUnits.textContent = '0';
                    btnSubmit.disabled = true;
                    return;
                }

                let totalUnits = 0;
                tbody.innerHTML = lineas.map((item, idx) => {
                    totalUnits += item.cantidad;
                    return `
                        <tr>
                            <td>
                                <strong style="color: #173e67; font-weight: 800;">${item.codigo}</strong>
                                <input type="hidden" name="lineas[${idx}][variante_id]" value="${item.variante_id}">
                            </td>
                            <td>
                                <strong style="color: #1e293b;">${item.descripcion}</strong>
                            </td>
                            <td class="text-center">
                                <input type="number" name="lineas[${idx}][cantidad]" class="qty-input" data-idx="${idx}" value="${item.cantidad}" min="1" max="99999" required>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-delete-row btn-remove" data-idx="${idx}" title="Quitar de la lista">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');

                summaryItems.textContent = String(lineas.length);
                summaryUnits.textContent = String(totalUnits);
                btnSubmit.disabled = false;
            };

            btnAdd.addEventListener('click', () => {
                const varianteId = parseInt(picker.value, 10);
                if (!varianteId) {
                    alert('Selecciona un artículo de la lista desplegable.');
                    return;
                }

                const opt = picker.options[picker.selectedIndex];
                const pickerQuantity = Math.max(1, parseInt(quantityPicker?.value || '1', 10));
                const existente = lineas.find((l) => l.variante_id === varianteId);

                if (existente) {
                    existente.cantidad += pickerQuantity;
                } else {
                    lineas.push({
                        variante_id: varianteId,
                        codigo: opt.dataset.codigo,
                        descripcion: opt.dataset.descripcion,
                        cantidad: pickerQuantity
                    });
                }

                picker.value = '';
                if (quantityPicker) quantityPicker.value = '1';
                renderTable();
            });

            tbody.addEventListener('input', (e) => {
                if (e.target.classList.contains('qty-input')) {
                    const idx = parseInt(e.target.dataset.idx, 10);
                    const val = Math.max(1, parseInt(e.target.value || 1, 10));
                    lineas[idx].cantidad = val;
                    summaryUnits.textContent = String(lineas.reduce((acc, l) => acc + l.cantidad, 0));
                }
            });

            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-remove');
                if (btn) {
                    const idx = parseInt(btn.dataset.idx, 10);
                    lineas.splice(idx, 1);
                    renderTable();
                }
            });
        })();
    </script>
@endsection