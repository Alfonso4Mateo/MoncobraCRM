@extends('adminlte::page')

@section('title', 'Editar Item de Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-item-create.css'])
    <style>
        .item-section--stacked {
            display: block;
        }

        .item-section--stacked .item-section-label {
            margin-bottom: 1rem;
        }

        .item-section--stacked .item-section-fields {
            width: 100%;
            grid-template-columns: minmax(0, 1fr);
        }

        .item-section .section-content {
            width: 100%;
            max-width: 40rem;
        }

        .variants-grid-fields {
            grid-template-columns: minmax(0, 1fr);
            width: 100%;
            min-width: 0;
        }

        .variants-grid-fields > * {
            min-width: 0;
        }

        .variants-grid-fields .section-content {
            width: 100%;
        }
    </style>
@endsection

@section('content')
    <section class="inventory-item-page">
        <header class="inventory-item-head">
            <span class="module-tag">MODULO DE INVENTARIO</span>
            <h1>Editar Item de Inventario</h1>
            <p>Actualización de datos, especificaciones tecnicas y variantes (talla, color) del item registrado en el sistema central de logistica.</p>
        </header>

        @if ($errors->any())
            <div class="item-alert" role="alert">
                <strong>No se pudo actualizar el item.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="inventory-item-layout">
            <form action="{{ route('inventario.update', $inventario->id) }}" method="POST" class="inventory-item-form" novalidate>
                @csrf
                @method('PUT')

                @include('inventario.create-item.partials.section-basics')

                @include('inventario.create-item.partials.section-variant-types')

                @php($showStockActual = true)
                @include('inventario.create-item.partials.section-stock')

                @include('inventario.create-item.partials.section-variant-grid')

                <footer class="item-form-footer">
                    <p>* Todos los campos son obligatorios para el registro.</p>
                    <div class="item-footer-actions">
                        <a href="{{ route('inventario.index') }}" class="btn-footer-cancel">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn-footer-save">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </footer>
            </form>
        </div>
    </section>
@endsection

@section('js')
    <script>
        (function () {
            const valoresIniciales = @json(old('atributos_variante', $valoresIniciales ?? []));
            const variantesIniciales = @json(old('variantes', $variantesIniciales ?? []));

            const tiposAtributosInput = document.getElementById('tipos_atributos');
            const atributosContainer = document.getElementById('atributos-container');
            const variantesContainer = document.getElementById('variantes-container');
            const variantesHead = document.getElementById('variantes-head');
            const stockTotal = document.getElementById('stock-total');
            const variantOrderPreview = document.getElementById('variant-order-preview');
            const variantsSummary = document.getElementById('variants-summary');
            const bulkStockValue = document.getElementById('bulk-stock-value');
            const bulkApplyStock = document.getElementById('bulk-apply-stock');
            const bulkSetZero = document.getElementById('bulk-set-zero');
            const bulkActivateAll = document.getElementById('bulk-activate-all');
            const bulkDeactivateAll = document.getElementById('bulk-deactivate-all');
            const stockMinimoInput = document.getElementById('stock_minimo');
            const nivelCriticoInput = document.getElementById('nivel_critico');
            let tiposOrdenados = [];

            const parseTipos = (value) => String(value || '')
                .split(',')
                .map((item) => item.trim())
                .filter((item) => item.length > 0);

            const normalizeKey = (atributos) => Object.entries(atributos || {})
                .map(([tipo, valor]) => `${tipo}:${valor}`)
                .join('|');

            const collectTypeValues = () => {
                const valuesByType = {};

                atributosContainer.querySelectorAll('[data-variant-type]').forEach((group) => {
                    const tipo = group.dataset.variantType || '';
                    const values = Array.from(group.querySelectorAll('input[data-variant-value]'))
                        .map((input) => input.value.trim())
                        .filter((value) => value.length > 0);

                    if (tipo && values.length > 0) {
                        valuesByType[tipo] = values;
                    }
                });

                return valuesByType;
            };

            const collectCombinationStocks = () => {
                const stocks = {};

                variantesContainer.querySelectorAll('[data-combination-key]').forEach((row) => {
                    const key = row.dataset.combinationKey || '';
                    const input = row.querySelector('input[data-stock-input]');
                    if (key && input) {
                        stocks[key] = input.value;
                    }
                });

                return stocks;
            };

            const buildInitialCombinationStocks = () => {
                const stocks = {};

                (Array.isArray(variantesIniciales) ? variantesIniciales : []).forEach((variant) => {
                    const atributos = variant && typeof variant === 'object' ? (variant.atributos || {}) : {};
                    const normalizedAttributes = {};

                    Object.entries(atributos).forEach(([tipo, valor]) => {
                        const firstValue = Array.isArray(valor) ? valor[0] : valor;
                        if (firstValue !== undefined && firstValue !== null && String(firstValue).trim() !== '') {
                            normalizedAttributes[tipo] = String(firstValue).trim();
                        }
                    });

                    const key = normalizeKey(normalizedAttributes);
                    if (key) {
                        stocks[key] = String(variant.stock_actual ?? '0');
                    }
                });

                return stocks;
            };

            const buildInitialCombinationIds = () => {
                const ids = {};

                (Array.isArray(variantesIniciales) ? variantesIniciales : []).forEach((variant) => {
                    const atributos = variant && typeof variant === 'object' ? (variant.atributos || {}) : {};
                    const normalizedAttributes = {};

                    Object.entries(atributos).forEach(([tipo, valor]) => {
                        const firstValue = Array.isArray(valor) ? valor[0] : valor;
                        if (firstValue !== undefined && firstValue !== null && String(firstValue).trim() !== '') {
                            normalizedAttributes[tipo] = String(firstValue).trim();
                        }
                    });

                    const key = normalizeKey(normalizedAttributes);
                    if (key && variant.id) {
                        ids[key] = String(variant.id);
                    }
                });

                return ids;
            };

            const initialCombinationStocks = buildInitialCombinationStocks();
            const initialCombinationIds = buildInitialCombinationIds();

            const createValueRow = (tipo, value = '', onChange = null) => {
                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.gap = '0.75rem';
                row.style.alignItems = 'center';
                row.style.marginTop = '0.5rem';

                const input = document.createElement('input');
                input.type = 'text';
                input.name = `atributos_variante[${tipo}][]`;
                input.placeholder = `Valor para ${tipo}`;
                input.value = value;
                input.dataset.variantValue = '1';
                input.style.flex = '1';

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.textContent = 'Eliminar';
                removeButton.style.border = '1px solid #d0d7de';
                removeButton.style.background = '#fff';
                removeButton.style.borderRadius = '8px';
                removeButton.style.padding = '0.55rem 0.85rem';

                const handleChange = () => {
                    if (typeof onChange === 'function') {
                        onChange();
                    }
                };

                input.addEventListener('input', handleChange);

                removeButton.addEventListener('click', () => {
                    row.remove();

                    const list = row.parentElement;
                    if (list && list.children.length === 0) {
                        list.appendChild(createValueRow(tipo, '', onChange));
                    }

                    handleChange();
                });

                row.appendChild(input);
                row.appendChild(removeButton);

                return row;
            };

            const createVariantGroup = (tipo, values = [], levelIndex = 0, onChange = null) => {
                const group = document.createElement('div');
                group.className = 'field-group field-full';
                group.dataset.variantType = tipo;
                group.style.padding = '1rem';
                group.style.border = '1px solid #e5e7eb';
                group.style.borderRadius = '12px';
                group.style.background = '#fff';

                const header = document.createElement('div');
                header.style.display = 'flex';
                header.style.justifyContent = 'space-between';
                header.style.alignItems = 'center';
                header.style.gap = '1rem';

                const label = document.createElement('label');
                label.textContent = `Nivel ${levelIndex + 1} - ${tipo}`;
                label.style.margin = '0';
                label.style.fontWeight = '700';

                const addButton = document.createElement('button');
                addButton.type = 'button';
                addButton.textContent = 'Añadir valor';
                addButton.style.border = '1px solid #0f172a';
                addButton.style.background = '#0f172a';
                addButton.style.color = '#fff';
                addButton.style.borderRadius = '8px';
                addButton.style.padding = '0.55rem 0.85rem';

                const list = document.createElement('div');
                const initialValues = Array.isArray(values) && values.length > 0 ? values : [''];

                addButton.addEventListener('click', () => {
                    list.appendChild(createValueRow(tipo, '', onChange));
                    if (typeof onChange === 'function') {
                        onChange();
                    }
                });

                initialValues.forEach((value) => {
                    list.appendChild(createValueRow(tipo, value, onChange));
                });

                header.appendChild(label);
                header.appendChild(addButton);
                group.appendChild(header);
                group.appendChild(list);

                return group;
            };

            const renderTableHead = (types) => {
                if (!variantesHead) {
                    return;
                }

                variantesHead.innerHTML = '';

                const row = document.createElement('tr');

                types.forEach((tipo, index) => {
                    const th = document.createElement('th');
                    th.textContent = `Nivel ${index + 1} · ${tipo}`;
                    row.appendChild(th);
                });

                ['Stock', 'Estado', 'Activo', 'Acciones'].forEach((label) => {
                    const th = document.createElement('th');
                    th.textContent = label;
                    row.appendChild(th);
                });

                variantesHead.appendChild(row);
            };

            const getVariantThresholds = () => ({
                minimum: parseInt(stockMinimoInput?.value || '0', 10) || 0,
                critical: parseInt(nivelCriticoInput?.value || '0', 10) || 0,
            });

            const resolveVariantState = (stockValue) => {
                const { minimum, critical } = getVariantThresholds();

                if (stockValue <= critical) {
                    return 'critico';
                }

                if (stockValue <= minimum) {
                    return 'bajo';
                }

                return 'optimo';
            };

            const updateVariantsSummary = () => {
                if (!variantsSummary) {
                    return;
                }

                const rows = Array.from(variantesContainer.querySelectorAll('tr[data-combination-key]'));
                const activeRows = rows.filter((row) => {
                    const activeInput = row.querySelector('input[data-row-active]');
                    return activeInput ? activeInput.value === '1' : true;
                });

                variantsSummary.textContent = `${activeRows.length} combinaciones activas · ${rows.length} combinaciones totales`;
            };

            const updateRowState = (row) => {
                const stockInput = row.querySelector('input[data-stock-input]');
                const stateChip = row.querySelector('[data-state-chip]');
                const activeInput = row.querySelector('input[data-row-active]');
                const isActive = activeInput ? activeInput.value === '1' : true;
                const stockValue = parseInt(stockInput?.value || '0', 10) || 0;
                const state = resolveVariantState(stockValue);

                row.dataset.variantState = state;
                row.classList.remove('variant-row-optimo', 'variant-row-bajo', 'variant-row-critico', 'variant-row-inactive');
                row.classList.add(`variant-row-${state}`);

                if (!isActive) {
                    row.classList.add('variant-row-inactive');
                }

                if (stateChip) {
                    stateChip.className = `variant-state-chip variant-state-chip--${state}`;
                    stateChip.textContent = state === 'critico'
                        ? 'Crítico'
                        : (state === 'bajo' ? 'Reposición' : 'Óptimo');
                }

                if (stockInput) {
                    stockInput.classList.remove('variant-stock-input--critico', 'variant-stock-input--bajo');
                    if (state === 'critico') {
                        stockInput.classList.add('variant-stock-input--critico');
                    } else if (state === 'bajo') {
                        stockInput.classList.add('variant-stock-input--bajo');
                    }
                }
            };

            const setRowActiveState = (row, active) => {
                const activeInput = row.querySelector('input[data-row-active]');
                const fieldInputs = row.querySelectorAll('[data-row-field]');
                const toggleInput = row.querySelector('input[data-row-toggle]');

                if (activeInput) {
                    activeInput.value = active ? '1' : '0';
                }

                if (toggleInput) {
                    toggleInput.checked = active;
                }

                fieldInputs.forEach((input) => {
                    input.disabled = !active;
                });

                row.classList.toggle('variant-row-inactive', !active);
                updateRowState(row);
                updateVariantsSummary();
            };

            const setAllRowsActive = (active) => {
                variantesContainer.querySelectorAll('tr[data-combination-key]').forEach((row) => {
                    setRowActiveState(row, active);
                });
                updateTotalStock();
            };

            const applyBulkStock = (value) => {
                const parsedValue = Math.max(0, parseInt(value || '0', 10) || 0);

                variantesContainer.querySelectorAll('tr[data-combination-key]').forEach((row) => {
                    const activeInput = row.querySelector('input[data-row-active]');
                    const stockInput = row.querySelector('input[data-stock-input]');

                    if (activeInput && activeInput.value !== '1') {
                        return;
                    }

                    if (stockInput && !stockInput.disabled) {
                        stockInput.value = String(parsedValue);
                        stockInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });

                updateTotalStock();
            };

            const generateCombinations = (valuesByType, orderedTypes = []) => {
                const types = orderedTypes.length > 0 ? orderedTypes : Object.keys(valuesByType);

                if (types.length === 0) {
                    return [];
                }

                return types.reduce((accumulator, type) => {
                    const values = valuesByType[type] || [];

                    if (values.length === 0) {
                        return [];
                    }

                    const combinations = [];

                    accumulator.forEach((existing) => {
                        values.forEach((value) => {
                            combinations.push({
                                ...existing,
                                [type]: value,
                            });
                        });
                    });

                    return combinations;
                }, [{}]);
            };

            const updateTotalStock = () => {
                const total = Array.from(variantesContainer.querySelectorAll('input[data-stock-input]'))
                    .filter((input) => !input.disabled)
                    .reduce((sum, input) => sum + (parseInt(input.value || '0', 10) || 0), 0);

                stockTotal.textContent = String(total);
            };

            const renderCombinations = () => {
                const valuesByType = collectTypeValues();
                const types = tiposOrdenados.length > 0 ? tiposOrdenados : Object.keys(valuesByType);
                const preservedStocks = collectCombinationStocks();
                variantesContainer.innerHTML = '';
                const emptyColspan = types.length + 4;

                if (types.length === 0) {
                    variantesContainer.innerHTML = `<tr><td colspan="${emptyColspan}" class="variants-grid-empty">Define al menos un tipo de variante para generar combinaciones.</td></tr>`;
                    stockTotal.textContent = '0';
                    updateVariantsSummary();
                    return;
                }

                const combinations = generateCombinations(valuesByType, types);

                if (combinations.length === 0) {
                    variantesContainer.innerHTML = `<tr><td colspan="${emptyColspan}" class="variants-grid-empty">Añade al menos un valor en cada tipo para generar combinaciones.</td></tr>`;
                    stockTotal.textContent = '0';
                    updateVariantsSummary();
                    return;
                }

                combinations.forEach((atributos, index) => {
                    const combinationKey = normalizeKey(atributos);
                    const stockValue = preservedStocks[combinationKey] ?? initialCombinationStocks[combinationKey] ?? '0';
                    const rowId = initialCombinationIds[combinationKey] ?? '';
                    const row = document.createElement('tr');
                    row.dataset.combinationKey = combinationKey;
                    row.className = 'variant-row variant-row-optimo';

                    types.forEach((tipo) => {
                        const td = document.createElement('td');
                        const cell = document.createElement('div');
                        cell.className = 'variant-attribute-cell';

                        const name = document.createElement('span');
                        name.className = 'variant-attribute-name';
                        name.textContent = tipo;

                        const value = document.createElement('span');
                        value.className = 'variant-attribute-value';
                        value.textContent = atributos[tipo] ?? '—';

                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = `variantes[${index}][atributos][${tipo}]`;
                        hidden.value = atributos[tipo] ?? '';
                        hidden.dataset.rowField = '1';

                        cell.appendChild(name);
                        cell.appendChild(value);
                        cell.appendChild(hidden);
                        td.appendChild(cell);
                        row.appendChild(td);
                    });

                    const stockTd = document.createElement('td');
                    if (rowId) {
                        const hiddenId = document.createElement('input');
                        hiddenId.type = 'hidden';
                        hiddenId.name = `variantes[${index}][id]`;
                        hiddenId.value = rowId;
                        hiddenId.dataset.rowField = '1';
                        stockTd.appendChild(hiddenId);
                    }

                    const stockInput = document.createElement('input');
                    stockInput.type = 'number';
                    stockInput.min = '0';
                    stockInput.max = '999999';
                    stockInput.step = '1';
                    stockInput.name = `variantes[${index}][stock_actual]`;
                    stockInput.dataset.stockInput = '1';
                    stockInput.dataset.rowField = '1';
                    stockInput.value = stockValue;
                    stockInput.className = 'variant-stock-input';

                    stockInput.addEventListener('input', function() {
                        if (this.value.length > 5) {
                            this.value = this.value.slice(0, 5);
                        }
                    });

                    stockTd.appendChild(stockInput);
                    row.appendChild(stockTd);

                    const stateTd = document.createElement('td');
                    const stateChip = document.createElement('span');
                    stateChip.dataset.stateChip = '1';
                    stateTd.appendChild(stateChip);
                    row.appendChild(stateTd);

                    const activeTd = document.createElement('td');
                    const activeWrapper = document.createElement('label');
                    activeWrapper.className = 'variant-switch';

                    const activeToggle = document.createElement('input');
                    activeToggle.type = 'checkbox';
                    activeToggle.checked = true;
                    activeToggle.dataset.rowToggle = '1';

                    const activeTrack = document.createElement('span');
                    activeTrack.className = 'variant-switch__track';

                    const activeThumb = document.createElement('span');
                    activeThumb.className = 'variant-switch__thumb';

                    activeToggle.addEventListener('change', () => {
                        setRowActiveState(row, activeToggle.checked);
                        updateTotalStock();
                    });

                    activeWrapper.appendChild(activeToggle);
                    activeWrapper.appendChild(activeTrack);
                    activeWrapper.appendChild(activeThumb);
                    activeTd.appendChild(activeWrapper);

                    const activeHidden = document.createElement('input');
                    activeHidden.type = 'hidden';
                    activeHidden.name = `variantes[${index}][activo]`;
                    activeHidden.value = '1';
                    activeHidden.dataset.rowActive = '1';
                    activeTd.appendChild(activeHidden);
                    row.appendChild(activeTd);

                    const actionsTd = document.createElement('td');
                    const actions = document.createElement('div');
                    actions.className = 'variant-action-group';

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'variant-mini-btn';
                    removeButton.innerHTML = '<i class="fas fa-trash"></i>';
                    removeButton.title = 'Eliminar combinación';
                    removeButton.addEventListener('click', () => {
                        row.remove();
                        updateTotalStock();
                        updateVariantsSummary();
                    });

                    actions.appendChild(removeButton);
                    actionsTd.appendChild(actions);
                    row.appendChild(actionsTd);

                    updateRowState(row);
                    variantesContainer.appendChild(row);
                });

                updateTotalStock();
                updateVariantsSummary();
            };

            const renderVariantTypes = () => {
                const currentValues = collectTypeValues();
                const tipos = parseTipos(tiposAtributosInput.value);
                tiposOrdenados = tipos;

                atributosContainer.innerHTML = '';

                if (variantOrderPreview) {
                    const previewText = tipos.length > 0 ? tipos.join(' → ') : '—';
                    const previewSpan = variantOrderPreview.querySelector('span');
                    if (previewSpan) {
                        previewSpan.textContent = previewText;
                    }
                }

                renderTableHead(tipos);

                if (tipos.length === 0) {
                    variantesContainer.innerHTML = `<tr><td colspan="${tipos.length + 4}" class="variants-grid-empty">Escribe tipos de variantes para empezar a generar combinaciones.</td></tr>`;
                    stockTotal.textContent = '0';
                    updateVariantsSummary();
                    return;
                }

                tipos.forEach((tipo, index) => {
                    const values = currentValues[tipo] || valoresIniciales[tipo] || [''];
                    atributosContainer.appendChild(createVariantGroup(tipo, values, index, renderCombinations));
                });

                renderCombinations();
            };

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

            tiposAtributosInput.addEventListener('input', renderVariantTypes);
            if (bulkApplyStock) {
                bulkApplyStock.addEventListener('click', () => applyBulkStock(bulkStockValue ? bulkStockValue.value : '0'));
            }
            if (bulkSetZero) {
                bulkSetZero.addEventListener('click', () => {
                    if (bulkStockValue) {
                        bulkStockValue.value = '0';
                    }
                    applyBulkStock('0');
                });
            }
            if (bulkActivateAll) {
                bulkActivateAll.addEventListener('click', () => setAllRowsActive(true));
            }
            if (bulkDeactivateAll) {
                bulkDeactivateAll.addEventListener('click', () => setAllRowsActive(false));
            }
            renderVariantTypes();
        })();
    </script>
@endsection

