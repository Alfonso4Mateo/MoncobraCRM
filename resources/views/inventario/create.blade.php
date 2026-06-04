@extends('adminlte::page')

@section('title', 'Nueva Entrada de Stock - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-item-create.css'])
    <style>
        .btn-entry-new-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #173e67; 
            border: none;
            padding: 0.65rem 1.2rem;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.85rem;
            color: #ffffff; 
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 3px 6px rgba(23, 62, 103, 0.2);
        }
        .btn-entry-new-item:hover {
            background: #0f2747; 
            color: #ffffff;
            transform: translateY(-1px);
        }
        .sidebar-save-btn {
            width: 100%;
            margin-top: 1.5rem;
            background: #173e67;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.9rem;
            font-weight: 800;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .sidebar-save-btn:hover {
            background: #0f2747;
        }
        .variants-grid-table input[type="number"] {
            width: 100px;
            height: 36px;
            border: 1px solid #d5dfec;
            border-radius: 8px;
            padding: 0 0.5rem;
            font-weight: 700;
            color: #173e67;
        }
    </style>
@endsection

@section('content')
    @php
        $hoy = now()->format('d / m / Y');
    @endphp

    <section class="inventory-item-page">
        <header class="inventory-item-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="module-tag">MÓDULO DE INVENTARIO</span>
                <h1 style="margin-bottom: 0.2rem;">Nueva Entrada Múltiple de Stock</h1>
                <p style="margin-bottom: 0;">Registrar la entrada de uno o varios artículos simultáneamente.</p>
            </div>
            <div>
                <a href="{{ route('inventario.item.create') }}" class="btn-entry-new-item">
                    <i class="fas fa-plus"></i>
                    Crear ítem nuevo en el catálogo
                </a>
            </div>
        </header>

        @if ($errors->any())
            <div class="item-alert" role="alert">
                <strong>No se pudo registrar la entrada.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="inventory-item-layout">
            <form id="inventory-entry-form" action="{{ route('inventario.entrada.store') }}" method="POST" class="inventory-item-form" novalidate>
                @csrf

                {{-- SECCIÓN 1: LOGÍSTICA GENERAL (Simplificada) --}}
                <fieldset class="item-section">
                    <div class="item-section-label">
                        <h3><i class="fas fa-truck-loading" aria-hidden="true"></i> Datos de la Entrada</h3>
                        <p>Información general aplicable a todos los artículos de esta recepción.</p>
                    </div>
                    <div class="item-section-content">
                        <div class="item-section-fields fields-3">
                            <div class="field-group">
                                <label>Fecha de entrada</label>
                                <input type="text" value="{{ $hoy }}" readonly style="background: #f8fafc; color: #64748b; font-weight: 700;">
                            </div>

                            <div class="field-group">
                                <label for="almacen_global">Almacén a ingresar</label>
                                <select id="almacen_global" name="almacen_global" required style="border: 1px solid #d5dfec; border-radius: 8px; padding: 0.5rem; width: 100%; color: #173e67; font-weight: 600;">
                                    <option value="" disabled selected>Selecciona un almacén...</option>
                                    @isset($almacenes)
                                        @foreach($almacenes as $almacen)
                                            {{-- Usamos el nombre del almacén como value porque la tabla inventario lo guarda como texto --}}
                                            <option value="{{ $almacen->nombre }}" @selected(old('almacen_global') === $almacen->nombre)>
                                                {{ $almacen->nombre }}
                                            </option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>

                            <div class="field-group">
                                <label for="solicitante">Personal / Usuario</label>
                                <input id="solicitante" name="solicitante" type="text" value="{{ old('solicitante', auth()->user()->name ?? '') }}" placeholder="Nombre del receptor" required>
                            </div>
                        </div>
                    </div>
                </fieldset>

                {{-- SECCIÓN 2: AÑADIR ARTÍCULOS --}}
                <fieldset class="item-section">
                    <div class="item-section-label">
                        <h3><i class="fas fa-boxes" aria-hidden="true"></i> Artículos a Ingresar</h3>
                        <p>Busca en el catálogo y añade las cantidades recibidas a la lista.</p>
                    </div>
                    <div class="item-section-content">
                        
                        {{-- Buscador y botón añadir --}}
                        <div class="item-section-fields fields-1">
                            <div class="field-group" style="display: flex; gap: 1rem; align-items: flex-end;">
                                <div style="flex: 1;">
                                    <label for="producto_busqueda">Buscar producto o variante</label>
                                    <input
                                        type="text"
                                        list="inventario-catalogo"
                                        id="producto_busqueda"
                                        placeholder="Escribe el nombre, código o atributo (ej: Talla M)..."
                                        autocomplete="off"
                                    >
                                </div>
                                <button type="button" id="btn-add-item" class="variants-grid-toolbar__btn variants-grid-toolbar__btn--primary">
                                    Añadir a la lista
                                </button>
                            </div>
                        </div>

                        {{-- Tabla de artículos añadidos --}}
                        <div class="variants-grid-shell" style="margin-top: 2rem;">
                            <table class="variants-grid-table">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Descripción del Artículo</th>
                                        <th style="width: 140px;">Uds. a ingresar</th>
                                        <th style="width: 80px; text-align: center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="items-table-body">
                                    <tr id="empty-row">
                                        <td colspan="4" class="text-center" style="color: #64748b; font-weight: 600; padding: 2rem !important;">
                                            No hay artículos en la lista. Busca y añade un producto arriba.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </fieldset>
            </form>

            {{-- SIDEBAR LATERAL --}}
            <aside class="inventory-sidebar">
                <div class="inventory-sidebar-card" style="background: #f8fbff; border-color: #dce6f4;">
                    <div class="inventory-sidebar-title" style="color: #173e67;">
                        <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                        Resumen de Entrada
                    </div>
                    
                    <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.8rem; border-bottom: 1px dashed #d5dfec;">
                        <span style="font-size: 0.75rem; font-weight: 800; color: #7a8ca4;">PRODUCTOS DISTINTOS</span>
                        <strong id="summary-items" style="font-size: 1.15rem; color: #0f2747;">0</strong>
                    </div>
                    <div style="margin-top: 0.8rem; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.75rem; font-weight: 800; color: #7a8ca4;">TOTAL UNIDADES</span>
                        <strong id="summary-units" style="font-size: 1.4rem; color: #1e8a58;">0</strong>
                    </div>

                    <button type="submit" form="inventory-entry-form" class="sidebar-save-btn" id="btn-submit">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                        Confirmar Entrada Múltiple
                    </button>
                    <div style="text-align: center; margin-top: 0.8rem;">
                        <a href="{{ route('inventario.index') }}" style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-decoration: underline;">Cancelar y volver</a>
                    </div>
                </div>
            </aside>
        </div>

        {{-- DATALIST PARA BÚSQUEDA --}}
        <datalist id="inventario-catalogo">
            @foreach ($catalogo as $producto)
                <option value="{{ $producto->descripcion }}" data-codigo="{{ $producto->codigo }}"></option>
            @endforeach
        </datalist>
    </section>
@endsection

@section('js')
    @php
        $catalogoJs = $catalogo->map(function ($item) {
            return [
                'id' => $item->id,
                'codigo' => (string) $item->codigo,
                'descripcion' => (string) $item->descripcion,
            ];
        })->values();
    @endphp

    <script>
        (function () {
            const catalogo = @json($catalogoJs);
            let addedItems = {}; 

            const searchInput = document.getElementById('producto_busqueda');
            const btnAdd = document.getElementById('btn-add-item');
            const tbody = document.getElementById('items-table-body');
            const emptyRow = document.getElementById('empty-row');
            const summaryItems = document.getElementById('summary-items');
            const summaryUnits = document.getElementById('summary-units');
            const btnSubmit = document.getElementById('btn-submit');

            const normalize = (value) => String(value || '').trim().toLowerCase();

            const findProducto = () => {
                const searchVal = normalize(searchInput.value);
                if (!searchVal) return null;
                return catalogo.find((item) => normalize(item.descripcion) === searchVal || normalize(item.codigo) === searchVal) || null;
            };

            const updateSummary = () => {
                const rows = tbody.querySelectorAll('tr.item-row');
                let totalUnits = 0;
                
                rows.forEach(row => {
                    const qtyInput = row.querySelector('.qty-input');
                    if (qtyInput) totalUnits += parseInt(qtyInput.value || 0, 10);
                });

                summaryItems.textContent = Object.keys(addedItems).length;
                summaryUnits.textContent = totalUnits;
                
                if (Object.keys(addedItems).length > 0) {
                    emptyRow.style.display = 'none';
                    btnSubmit.disabled = false;
                    btnSubmit.style.opacity = '1';
                } else {
                    emptyRow.style.display = 'table-row';
                    btnSubmit.disabled = true;
                    btnSubmit.style.opacity = '0.5';
                }
            };

            const renderRow = (producto) => {
                const tr = document.createElement('tr');
                tr.className = 'item-row';
                tr.dataset.id = producto.id;

                tr.innerHTML = `
                    <td>
                        <span style="font-weight: 800; color: #173e67;">${producto.codigo}</span>
                        <input type="hidden" name="items[${producto.id}][inventario_id]" value="${producto.id}">
                        <input type="hidden" name="items[${producto.id}][codigo]" value="${producto.codigo}">
                    </td>
                    <td>
                        <strong style="color: #213a57;">${producto.descripcion}</strong>
                        <input type="hidden" name="items[${producto.id}][descripcion]" value="${producto.descripcion}">
                    </td>
                    <td>
                        <input type="number" name="items[${producto.id}][cantidad]" class="qty-input" value="1" min="1" required>
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="variant-mini-btn btn-remove" title="Quitar de la lista">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;

                // Eventos de la fila
                tr.querySelector('.btn-remove').addEventListener('click', () => {
                    tr.remove();
                    delete addedItems[producto.id];
                    updateSummary();
                });

                tr.querySelector('.qty-input').addEventListener('input', updateSummary);

                tbody.appendChild(tr);
            };

            btnAdd.addEventListener('click', () => {
                const producto = findProducto();
                if (!producto) {
                    alert('Producto no encontrado. Por favor, selecciona uno de la lista desplegable.');
                    return;
                }

                if (addedItems[producto.id]) {
                    // Si ya existe, le sumamos 1
                    const existingRow = tbody.querySelector(`tr[data-id="${producto.id}"]`);
                    const input = existingRow.querySelector('.qty-input');
                    input.value = parseInt(input.value, 10) + 1;
                } else {
                    // Si es nuevo, creamos la fila
                    addedItems[producto.id] = true;
                    renderRow(producto);
                }

                searchInput.value = ''; // Limpiar buscador
                updateSummary();
            });

            // Prevenir enviar formulario al pulsar Enter en el buscador
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btnAdd.click();
                }
            });

            // Init
            updateSummary();
        })();
    </script>
@endsection