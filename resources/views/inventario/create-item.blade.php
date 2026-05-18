@extends('adminlte::page')

@section('title', 'Crear Nuevo Item de Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-item-create.css'])
    <style>
        #variantes-container {
            display: grid;
            grid-template-columns: 5fr;
            row-gap: 2rem;
            column-gap: 15rem;
        }

        @media (min-width: 640px) {
            #variantes-container {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 960px) {
            #variantes-container {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            #variantes-container {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1600px) {
            #variantes-container {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        #variantes-container > div {
            min-width: 200px;
        }

        #variantes-container input[data-stock-input] {
            width: 100%;
            min-width: 150px;
            box-sizing: border-box;
        }

        .variant-stock-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 10px;
            flex-wrap: wrap;
        }

        .variant-stock-row label {
            min-width: 10px;
            margin: 0;
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

                <section class="item-section">
                    <aside class="item-section-label">
                        <span>SECCION 1</span>
                        <h2>Datos Basicos</h2>
                        <p>Identificacion fundamental del producto y vinculacion con proveedor oficial.</p>
                    </aside>

                    <div class="item-section-fields fields-2">
                        <div class="field-group">
                            <label for="codigo">Codigo del producto</label>
                            <input id="codigo" name="codigo" type="text" value="{{ old('codigo', $varianteBase->codigo ?? '') }}" placeholder="Ejem: PRD-2024-X1" class="@error('codigo') is-invalid @enderror" required>
                        </div>

                        <div class="field-group">
                            <label for="referencia_proveedor">Referencia proveedor</label>
                            <input id="referencia_proveedor" name="referencia_proveedor" type="text" value="{{ old('referencia_proveedor', $varianteBase->referencia_proveedor ?? '') }}" placeholder="REF-8829-00" class="@error('referencia_proveedor') is-invalid @enderror">
                        </div>

                        <div class="field-group field-full">
                            <label for="descripcion">Descripcion del item</label>
                            <textarea id="descripcion" name="descripcion" rows="1" maxlength="1000" placeholder="Nombre descriptivo completo del material o pieza industrial" class="input-auto-grow @error('descripcion') is-invalid @enderror" required>{{ old('descripcion', $varianteBase->descripcion ?? '') }}</textarea>
                        </div>
                        <div class="field-group">
                            <label for="clase_id">Clase del producto</label>
                            <select id="clase_id" name="clase_id" class="@error('clase_id') is-invalid @enderror">
                                <option value="">-- Seleccionar una clase --</option>
                                @foreach($clases as $id => $nombre)
                                    <option value="{{ $id }}" @selected(old('clase_id', $varianteBase->clase_id ?? null) == $id)>{{ $nombre }}</option>
                                @endforeach
                            </select>
                            @error('clase_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="item-section">
                    <aside class="item-section-label">
                        <span>SECCION 1.5</span>
                        <h2>Variantes Dinámicas del Producto</h2>
                        <p>Define los tipos de variantes, añade varios valores por tipo y el sistema generará todas las combinaciones posibles.</p>
                    </aside>

                    <div class="item-section-fields fields-1">
                        <div class="field-group field-full">
                            <label for="tipos_atributos">Tipos de variantes</label>
                            <input id="tipos_atributos" name="tipos_atributos" type="text" value="{{ old('tipos_atributos', implode(', ', $varianteBase?->tipos_atributos ?? array_keys($valoresIniciales ?? []))) }}" placeholder="Ejem: Talla, Color, Material (separados por coma)" class="@error('tipos_atributos') is-invalid @enderror">
                            <small style="color: #666; display: block; margin-top: 0.5rem;">Especifica los tipos de variantes que tendrá este producto, separados por coma.</small>
                        </div>

                        <div id="atributos-container" class="field-group field-full" style="margin-top: 1rem;">
                            <!-- Los campos de atributos se generarán dinámicamente con JavaScript -->
                        </div>
                    </div>
                </section>

                <section class="item-section">
                    <aside class="item-section-label">
                        <span>SECCION 2</span>
                        <h2>Datos comunes de stock</h2>
                        <p>Estos valores se aplicarán a todas las combinaciones generadas.</p>
                    </aside>

                    <div class="item-section-fields fields-2">
                        <div class="field-group field-tight">
                            <label for="stock_minimo">Minimo stock (alerta)</label>
                            <input id="stock_minimo" name="stock_minimo" type="number" min="0" step="1" value="{{ old('stock_minimo', $varianteBase->stock_minimo ?? 10) }}" class="@error('stock_minimo') is-invalid @enderror">
                        </div>

                        <div class="field-group field-tight">
                            <label for="nivel_critico">Stock critico</label>
                            <input id="nivel_critico" name="nivel_critico" type="number" min="0" step="1" value="{{ old('nivel_critico', $varianteBase->nivel_critico ?? 5) }}" class="@error('nivel_critico') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="almacen">Almacen</label>
                            <input id="almacen" name="almacen" type="text" value="{{ old('almacen', $varianteBase->almacen ?? '') }}" placeholder="Almacen Central" class="@error('almacen') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="ubicacion">Ubicacion</label>
                            <input id="ubicacion" name="ubicacion" type="text" value="{{ old('ubicacion', $varianteBase->ubicacion ?? '') }}" placeholder="Pasillo 3 / Estanteria 12" class="@error('ubicacion') is-invalid @enderror">
                        </div>
                    </div>
                </section>

                <section class="item-section">
                    <aside class="item-section-label">
                        <span>SECCION 2.5</span>
                        <h2>Combinaciones y stock individual</h2>
                        <p>Cada combinación tendrá su propio stock. Ajusta aquí las unidades por talla, color o cualquier otro tipo de variante.</p>
                    </aside>

                    <div class="item-section-fields fields-1">
                        <div id="variantes-container" class="field-group field-full" style="margin-top: 1rem;"></div>
                        <div class="field-group field-full" style="margin-top: 1rem;">
                            <strong>Total stock combinado: <span id="stock-total">0</span></strong>
                        </div>
                    </div>
                </section>
                
                <footer class="item-form-footer">
                    <p>* Todos los campos son obligatorios para el registro inicial.</p>
                    <div class="item-footer-actions">
                            <a href="{{ route('inventario.index') }}" class="btn-footer-cancel">Cancelar</a>
                        <button type="submit" class="btn-footer-save">
                            <i class="fas fa-save"></i>
                            Guardar Producto
                        </button>
                    </div>
                </footer>
            </form>

            <aside class="inventory-item-side">
                <article class="side-card with-accent">
                    <h3>Ultima accion</h3>
                    @if ($ultimaAccion)
                        <p>{{ $ultimaAccion->codigo }} - {{ $ultimaAccion->descripcion }}</p>
                    @else
                        <p>No hay registros previos en esta sesion.</p>
                    @endif
                </article>

                <article class="side-card">
                    <h3>Estado de conexion</h3>
                    <p><i class="fas fa-circle"></i> Base de Datos: Sincronizada</p>
                </article>
            </aside>
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
            const stockTotal = document.getElementById('stock-total');

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

            const createVariantGroup = (tipo, values = [], onChange = null) => {
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
                label.textContent = `Valores - ${tipo}`;
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

            const generateCombinations = (valuesByType) => {
                const types = Object.keys(valuesByType);

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
                    .reduce((sum, input) => sum + (parseInt(input.value || '0', 10) || 0), 0);

                stockTotal.textContent = String(total);
            };

            const renderCombinations = () => {
                const valuesByType = collectTypeValues();
                const types = Object.keys(valuesByType);
                const preservedStocks = collectCombinationStocks();
                variantesContainer.innerHTML = '';

                if (types.length === 0) {
                    variantesContainer.innerHTML = '<div class="inventory-empty-mini">Define al menos un tipo de variante para generar combinaciones.</div>';
                    stockTotal.textContent = '0';
                    return;
                }

                const combinations = generateCombinations(valuesByType);

                if (combinations.length === 0) {
                    variantesContainer.innerHTML = '<div class="inventory-empty-mini">Añade al menos un valor en cada tipo para generar combinaciones.</div>';
                    stockTotal.textContent = '0';
                    return;
                }

                combinations.forEach((atributos, index) => {
                    const combinationKey = normalizeKey(atributos);
                    const card = document.createElement('div');
                    card.dataset.combinationKey = combinationKey;
                    card.style.border = '1px solid #e5e7eb';
                    card.style.borderRadius = '12px';
                    card.style.padding = '1rem';
                    card.style.background = '#fff';

                    const header = document.createElement('div');
                    header.style.display = 'flex';
                    header.style.justifyContent = 'space-between';
                    header.style.alignItems = 'center';
                    header.style.gap = '1rem';
                    header.style.marginBottom = '0.85rem';

                    const title = document.createElement('strong');
                    title.textContent = Object.entries(atributos)
                        .map(([tipo, valor]) => `${tipo}: ${valor}`)
                        .join(' / ');

                    const suffix = document.createElement('span');
                    suffix.style.fontSize = '0.85rem';
                    suffix.style.color = '#6b7280';
                    suffix.textContent = `Combinación ${index + 1}`;

                    header.appendChild(title);
                    header.appendChild(suffix);
                    card.appendChild(header);

                    Object.entries(atributos).forEach(([tipo, valor]) => {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = `variantes[${index}][atributos][${tipo}]`;
                        hidden.value = valor;
                        card.appendChild(hidden);
                    });

                    const stockWrap = document.createElement('div');
                    stockWrap.className = 'variant-stock-row';

                    const stockLabel = document.createElement('label');
                    stockLabel.textContent = 'Stock individual';
                    stockLabel.style.margin = '0';
                    stockLabel.style.minWidth = '140px';

                    const stockInput = document.createElement('input');
                    stockInput.type = 'number';
                    stockInput.min = '0';
                    stockInput.step = '1';
                    stockInput.name = `variantes[${index}][stock_actual]`;
                    stockInput.dataset.stockInput = '1';
                    stockInput.value = preservedStocks[combinationKey] ?? initialCombinationStocks[combinationKey] ?? '0';
                    stockInput.style.maxWidth = '100%';

                    stockInput.addEventListener('input', updateTotalStock);

                    stockWrap.appendChild(stockLabel);
                    stockWrap.appendChild(stockInput);
                    card.appendChild(stockWrap);

                    variantesContainer.appendChild(card);
                });

                updateTotalStock();
            };

            const renderVariantTypes = () => {
                const currentValues = collectTypeValues();
                const tipos = parseTipos(tiposAtributosInput.value);

                atributosContainer.innerHTML = '';

                if (tipos.length === 0) {
                    variantesContainer.innerHTML = '<div class="inventory-empty-mini">Escribe tipos de variantes para empezar a generar combinaciones.</div>';
                    stockTotal.textContent = '0';
                    return;
                }

                tipos.forEach((tipo) => {
                    const values = currentValues[tipo] || valoresIniciales[tipo] || [''];
                    atributosContainer.appendChild(createVariantGroup(tipo, values, renderCombinations));
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
            renderVariantTypes();
        })();
    </script>
@endsection
