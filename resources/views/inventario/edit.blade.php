@extends('adminlte::page')

@section('title', 'Editar Item de Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-item-create.css'])
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

                <section class="item-section">
                    <aside class="item-section-label">
                        <span>SECCION 1</span>
                        <h2>Datos Basicos</h2>
                        <p>Identificacion fundamental del producto y vinculacion con proveedor oficial.</p>
                    </aside>

                    <div class="item-section-fields fields-2">
                        <div class="field-group">
                            <label for="codigo">Codigo del producto</label>
                            <input id="codigo" name="codigo" type="text" value="{{ old('codigo', $inventario->codigo) }}" placeholder="Ejem: PRD-2024-X1" class="@error('codigo') is-invalid @enderror" required>
                        </div>

                        <div class="field-group">
                            <label for="referencia_proveedor">Referencia proveedor</label>
                            <input id="referencia_proveedor" name="referencia_proveedor" type="text" value="{{ old('referencia_proveedor', $inventario->referencia_proveedor) }}" placeholder="REF-8829-00" class="@error('referencia_proveedor') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="nombre">Nombre del producto</label>
                            <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $inventario->nombre ?? $inventario->descripcion) }}" placeholder="Nombre corto del producto" class="@error('nombre') is-invalid @enderror" required>
                        </div>

                        <div class="field-group field-full">
                            <label for="descripcion">Descripcion del item</label>
                            <input id="descripcion" name="descripcion" type="text" value="{{ old('descripcion', $inventario->descripcion) }}" placeholder="Descripcion larga o detalles técnicos" class="@error('descripcion') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="clase_id">Clase del producto</label>
                            <select id="clase_id" name="clase_id" class="@error('clase_id') is-invalid @enderror">
                                <option value="">-- Seleccionar una clase --</option>
                                @foreach($clases as $id => $nombre)
                                    <option value="{{ $id }}" @selected(old('clase_id', $inventario->clase_id) == $id)>{{ $nombre }}</option>
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
                        <p>Define los tipos de variantes (Talla, Color, Material, etc.) y añade tantos valores como necesites por cada tipo.</p>
                    </aside>

                    <div class="item-section-fields fields-1">
                        <div class="field-group field-full">
                            <label for="tipos_atributos">Tipos de variantes</label>
                            <input id="tipos_atributos" name="tipos_atributos" type="text" value="{{ old('tipos_atributos', implode(', ', $inventario->variante?->tipos_atributos ?? [])) }}" placeholder="Ejem: Talla, Color, Material (separados por coma)" class="@error('tipos_atributos') is-invalid @enderror">
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
                        <h2>Control de Existencias</h2>
                        <p>Parametros de stock y ubicacion fisica dentro de los almacenes operativos.</p>
                    </aside>

                    <div class="item-section-fields fields-3">
                        <div class="field-group field-tight">
                            <label for="stock_actual">Stock actual</label>
                            <input id="stock_actual" name="stock_actual" type="number" min="0" step="1" value="{{ old('stock_actual', $inventario->stock_actual) }}" class="@error('stock_actual') is-invalid @enderror" required>
                        </div>

                        <div class="field-group field-tight">
                            <label for="stock_minimo">Minimo stock (alerta)</label>
                            <input id="stock_minimo" name="stock_minimo" type="number" min="0" step="1" value="{{ old('stock_minimo', $inventario->stock_minimo) }}" class="@error('stock_minimo') is-invalid @enderror">
                        </div>

                        <div class="field-group field-tight">
                            <label for="nivel_critico">Stock critico</label>
                            <input id="nivel_critico" name="nivel_critico" type="number" min="0" step="1" value="{{ old('nivel_critico', $inventario->nivel_critico) }}" class="@error('nivel_critico') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="almacen">Almacen</label>
                            <input id="almacen" name="almacen" type="text" value="{{ old('almacen', $inventario->almacen) }}" placeholder="Almacen Central" class="@error('almacen') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="ubicacion">Ubicacion</label>
                            <input id="ubicacion" name="ubicacion" type="text" value="{{ old('ubicacion', $inventario->ubicacion) }}" placeholder="Pasillo 3 / Estanteria 12" class="@error('ubicacion') is-invalid @enderror">
                        </div>
                    </div>
                </section>

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
            const valoresIniciales = @json(old('atributos_variante', $inventario->atributos_variante ?? []));

            // Generar campos dinámicos para variantes
            const tiposAtributosInput = document.getElementById('tipos_atributos');
            const atributosContainer = document.getElementById('atributos-container');

            const parseTipos = (value) => {
                return String(value || '')
                    .split(',')
                    .map((item) => item.trim())
                    .filter((item) => item.length > 0);
            };

            const collectCurrentValues = () => {
                const valores = {};

                atributosContainer.querySelectorAll('[data-variant-type]').forEach((group) => {
                    const tipo = group.dataset.variantType || '';
                    const inputs = group.querySelectorAll('input[data-variant-value]');
                    const values = Array.from(inputs)
                        .map((input) => input.value.trim())
                        .filter((input) => input.length > 0);

                    if (tipo && values.length > 0) {
                        valores[tipo] = values;
                    }
                });

                return valores;
            };

            const createValueRow = (tipo, value = '') => {
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
                removeButton.innerHTML = '<i class="fas fa-trash"></i> Eliminar';
                removeButton.style.border = '1px solid #d0d7de';
                removeButton.style.background = '#fff';
                removeButton.style.borderRadius = '8px';
                removeButton.style.padding = '0.55rem 0.85rem';

                removeButton.addEventListener('click', () => {
                    row.remove();

                    const list = row.parentElement;
                    if (list && list.children.length === 0) {
                        list.appendChild(createValueRow(tipo));
                    }
                });

                row.appendChild(input);
                row.appendChild(removeButton);

                return row;
            };

            const createVariantGroup = (tipo, values = []) => {
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
                label.textContent = `Valor - ${tipo}`;
                label.style.margin = '0';
                label.style.fontWeight = '700';

                const addButton = document.createElement('button');
                addButton.type = 'button';
                addButton.innerHTML = '<i class="fas fa-plus"></i> Añadir valor';
                addButton.style.border = '1px solid #0f172a';
                addButton.style.background = '#0f172a';
                addButton.style.color = '#fff';
                addButton.style.borderRadius = '8px';
                addButton.style.padding = '0.55rem 0.85rem';

                const list = document.createElement('div');

                addButton.addEventListener('click', () => {
                    list.appendChild(createValueRow(tipo));
                });

                const initialValues = Array.isArray(values) && values.length > 0 ? values : [''];
                initialValues.forEach((value) => {
                    list.appendChild(createValueRow(tipo, value));
                });

                header.appendChild(label);
                header.appendChild(addButton);
                group.appendChild(header);
                group.appendChild(list);

                return group;
            };

            const generarCamposAtributos = () => {
                const valor = tiposAtributosInput.value.trim();
                const valoresActuales = collectCurrentValues();
                atributosContainer.innerHTML = '';

                if (!valor) return;

                const tipos = parseTipos(valor);

                if (tipos.length === 0) return;

                tipos.forEach(tipo => {
                    const valoresTipo = valoresActuales[tipo] || valoresIniciales[tipo] || [];
                    atributosContainer.appendChild(createVariantGroup(tipo, valoresTipo));
                });
            };

            // Generar campos iniciales
            generarCamposAtributos();

            // Regenerar cuando cambie el input de tipos
            tiposAtributosInput.addEventListener('input', generarCamposAtributos);
        })();
    </script>
@endsection

