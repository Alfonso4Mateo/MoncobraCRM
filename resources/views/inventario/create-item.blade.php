@extends('adminlte::page')

@section('title', 'Crear Nuevo Item de Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-item-create.css'])
    <style>
        .variants-grid-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
            margin-top: 1rem;
            padding: 1rem 1.1rem;
            border: 1px solid #d7e1ee;
            border-radius: 14px;
            background: #f8fbff;
        }

        .variants-grid-toolbar__group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            min-width: 220px;
        }

        .variants-grid-toolbar__label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #73839a;
        }

        .variants-grid-toolbar__inline {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .variants-grid-toolbar__inline input {
            width: 110px;
            height: 40px;
            border: 1px solid #d5dfec;
            border-radius: 10px;
            padding: 0 0.7rem;
            font-weight: 700;
            color: #0f2747;
            background: #fff;
        }

        .variants-grid-toolbar__btn {
            height: 40px;
            padding: 0 0.9rem;
            border-radius: 10px;
            border: 1px solid #d5dfec;
            background: #ffffff;
            color: #173e67;
            font-weight: 800;
            font-size: 0.82rem;
            cursor: pointer;
        }

        .variants-grid-toolbar__btn--primary {
            background: #173e67;
            border-color: #173e67;
            color: #fff;
        }

        .variants-grid-toolbar__summary {
            margin-left: auto;
            padding: 0.8rem 0.95rem;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #dce6f4;
            color: #173e67;
            font-weight: 800;
            min-width: 220px;
        }

        .variants-grid-shell {
            margin-top: 1rem;
            border: 1px solid #dce6f4;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
        }

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

        /* Constrain non-variant sections so text components don't stretch full width */
        .item-section .section-content {
            width: 100%;
            max-width: 25rem;
        }

        .item-section--stacked .field-group.field-full,
        .item-section--stacked .variants-grid-toolbar,
        .item-section--stacked .variants-grid-shell,
        .item-section--stacked #atributos-container {
            width: 100%;
        }

        .item-section--stacked .variants-grid-table {
            min-width: 100%;
        }

        .item-section-fields.fields-1.variants-grid-fields {
            grid-template-columns: minmax(0, 1fr);
            width: 100%;
            min-width: 0;
        }

        .variants-grid-fields {
            grid-template-columns: minmax(0, 1fr);
            width: 100%;
            min-width: 0;
        }

        .item-section-fields.fields-1.variants-grid-fields > * {
            min-width: 0;
        }

        .variants-grid-fields > * {
            min-width: 0;
        }

        .item-section-fields.fields-1.variants-grid-fields .variants-grid-toolbar,
        .item-section-fields.fields-1.variants-grid-fields .variants-grid-shell {
            width: 100%;
        }

        .variants-grid-fields .variants-grid-toolbar,
        .variants-grid-fields .variants-grid-shell,
        .variants-grid-fields #atributos-container {
            width: 100%;
        }

        #variantes-container {
            width: 100%;
        }

        .variants-grid-table {
            width: 100%;
            min-width: 980px;
            margin: 0;
            table-layout: auto;
            border-collapse: separate;
            border-spacing: 0;
        }

        .variants-grid-table thead th {
            background: #f7f9fc;
            border-bottom: 1px solid #dce6f4 !important;
            color: #7a8ca4;
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0.8rem 0.6rem !important;
            white-space: nowrap;
        }

        .variants-grid-table thead th:nth-child(-n + 3),
        .variants-grid-table tbody td:nth-child(-n + 3) {
            min-width: 10.5rem;
        }

        .variants-grid-table thead th:nth-last-child(-n + 4),
        .variants-grid-table tbody td:nth-last-child(-n + 4) {
            min-width: 7.5rem;
        }

        .variants-grid-table tbody td {
            border-top: 1px solid #eef3f8 !important;
            padding: 0.75rem 0.6rem !important;
            vertical-align: middle !important;
            color: #213a57;
            font-size: 0.84rem;
            white-space: normal;
        }

        .variant-row-critico td {
            background: #ffecec;
        }

        .variant-row-bajo td {
            background: #fff4e4;
        }

        .variant-row-optimo td {
            background: #ffffff;
        }

        .variant-row-inactive {
            opacity: 0.58;
        }

        .variant-attribute-cell {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 0;
        }

        .variant-attribute-name {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.7rem;
            font-weight: 800;
            color: #7a8ca4;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .variant-attribute-value {
            font-weight: 800;
            color: #173e67;
            word-break: break-word;
        }

        .variant-stock-input {
            width: 100%;
            min-width: 90px;
            height: 38px;
            border: 1px solid #d5dfec;
            border-radius: 10px;
            padding: 0 0.75rem;
            font-weight: 800;
            color: #173e67;
            background: #fff;
            box-sizing: border-box;
        }

        .variant-stock-input--critico {
            color: #a13c3c;
        }

        .variant-stock-input--bajo {
            color: #b77b17;
        }

        .variant-state-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 5.25rem;
            padding: 0.28rem 0.62rem;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .variant-state-chip--optimo {
            background: #e7f9f0;
            color: #1e8a58;
        }

        .variant-state-chip--bajo {
            background: #fff3dd;
            color: #b77b17;
        }

        .variant-state-chip--critico {
            background: #ffe7e7;
            color: #c43d3d;
        }

        .variant-action-group {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            justify-content: flex-end;
        }

        .variant-switch {
            position: relative;
            width: 46px;
            height: 26px;
            display: inline-flex;
            align-items: center;
        }

        .variant-switch input {
            position: absolute;
            inset: 0;
            opacity: 0;
            margin: 0;
            cursor: pointer;
        }

        .variant-switch__track {
            width: 100%;
            height: 100%;
            border-radius: 999px;
            background: #d6deea;
            transition: background 0.18s ease;
        }

        .variant-switch__thumb {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 3px 8px rgba(15, 23, 42, 0.15);
            transition: transform 0.18s ease;
        }

        .variant-switch input:checked + .variant-switch__track {
            background: #173e67;
        }

        .variant-switch input:checked + .variant-switch__track + .variant-switch__thumb {
            transform: translateX(20px);
        }

        .variant-row-inactive .variant-switch__track {
            background: #f1b5b5;
        }

        .variant-mini-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid #d5dfec;
            background: #fff;
            color: #8b97a8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .variant-mini-btn:hover {
            color: #b42318;
            border-color: #f0c9c9;
            background: #fff5f5;
        }

        .variants-grid-empty {
            padding: 1rem;
            text-align: center;
            color: #6b7280;
            font-weight: 700;
        }
    </style>
@endsection

@section('content')
    <section class="inventory-item-page">
        <header class="inventory-item-head">
            <span class="module-tag">MODULO DE INVENTARIO</span>
            <h1>{{ $varianteBase ? 'Añadir variantes a un item existente' : 'Crear Nuevo Item de Inventario' }}</h1>
            <p>{{ $varianteBase ? 'Se generarán nuevas combinaciones y cada una tendrá su propio stock.' : 'Registro de nuevas existencias con variantes (tallas, colores), especificaciones tecnicas y parametros economicos para el sistema central de logistica.' }}</p>
        </header>

        @if ($errors->any())
            <div class="item-alert" role="alert">
                <strong>No se pudo crear el item.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="inventory-item-layout">
            <form action="{{ route('inventario.store') }}" method="POST" class="inventory-item-form" novalidate>
                @csrf
                @if(!empty($varianteBase))
                    <input type="hidden" name="inventario_variante_id" value="{{ $varianteBase->id }}">
                @endif

                @include('inventario.create-item.partials.section-basics')
                @include('inventario.create-item.partials.section-variant-types')
                @include('inventario.create-item.partials.section-stock')
                @include('inventario.create-item.partials.section-variant-grid')
                @include('inventario.create-item.partials.form-footer')
            </form>

            @include('inventario.create-item.partials.sidebar')
        </div>
    </section>
@endsection

@section('js')
    <script>
        (function () {
            const valoresIniciales = @json(old('atributos_variante', $valoresIniciales ?? []));
            const variantesIniciales = @json(old('variantes', []));

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

                variantesContainer.querySelectorAll('[data-combination-key]').forEach((card) => {
                    const key = card.dataset.combinationKey || '';
                    const input = card.querySelector('input[data-stock-input]');
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

            const initialCombinationStocks = buildInitialCombinationStocks();

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
