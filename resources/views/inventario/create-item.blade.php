@extends('adminlte::page')

@section('title', 'Crear Nuevo Item de Inventario - MoncobraCRM')

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
            <h1>Crear Nuevo Item de Inventario</h1>
            <p>Registro de nuevas existencias con variantes (tallas, colores), especificaciones tecnicas y parametros economicos para el sistema central de logistica.</p>
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

                <section class="item-section">
                    <aside class="item-section-label">
                        <span>SECCION 1</span>
                        <h2>Datos Basicos</h2>
                        <p>Identificacion fundamental del producto y vinculacion con proveedor oficial.</p>
                    </aside>

                    <div class="item-section-fields fields-2">
                        <div class="field-group">
                            <label for="codigo">Codigo del producto</label>
                            <input id="codigo" name="codigo" type="text" value="{{ old('codigo') }}" placeholder="Ejem: PRD-2024-X1" class="@error('codigo') is-invalid @enderror" required>
                        </div>

                        <div class="field-group">
                            <label for="referencia_proveedor">Referencia proveedor</label>
                            <input id="referencia_proveedor" name="referencia_proveedor" type="text" value="{{ old('referencia_proveedor') }}" placeholder="REF-8829-00" class="@error('referencia_proveedor') is-invalid @enderror">
                        </div>

                        <div class="field-group field-full">
                            <label for="descripcion">Descripcion del item</label>
                            <textarea id="descripcion" name="descripcion" rows="1" maxlength="1000" placeholder="Nombre descriptivo completo del material o pieza industrial" class="input-auto-grow @error('descripcion') is-invalid @enderror" required>{{ old('descripcion') }}</textarea>
                        </div>
                        <div class="field-group">
                            <label for="clase_id">Clase del producto</label>
                            <select id="clase_id" name="clase_id" class="@error('clase_id') is-invalid @enderror">
                                <option value="">-- Seleccionar una clase --</option>
                                @foreach($clases as $id => $nombre)
                                    <option value="{{ $id }}" @selected(old('clase_id') == $id)>{{ $nombre }}</option>
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
                        <p>Define los tipos de variantes (Talla, Color, Material, etc.) y sus valores específicos.</p>
                    </aside>

                    <div class="item-section-fields fields-1">
                        <div class="field-group field-full">
                            <label for="tipos_atributos">Tipos de variantes</label>
                            <input id="tipos_atributos" name="tipos_atributos" type="text" value="{{ old('tipos_atributos') }}" placeholder="Ejem: Talla, Color, Material (separados por coma)" class="@error('tipos_atributos') is-invalid @enderror">
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
                            <label for="stock_actual">Stock inicial</label>
                            <input id="stock_actual" name="stock_actual" type="number" min="0" step="1" value="{{ old('stock_actual', 0) }}" class="@error('stock_actual') is-invalid @enderror" required>
                        </div>

                        <div class="field-group field-tight">
                            <label for="stock_minimo">Minimo stock (alerta)</label>
                            <input id="stock_minimo" name="stock_minimo" type="number" min="0" step="1" value="{{ old('stock_minimo', 10) }}" class="@error('stock_minimo') is-invalid @enderror">
                        </div>

                        <div class="field-group field-tight">
                            <label for="nivel_critico">Stock critico</label>
                            <input id="nivel_critico" name="nivel_critico" type="number" min="0" step="1" value="{{ old('nivel_critico', 5) }}" class="@error('nivel_critico') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="almacen">Almacen</label>
                            <input id="almacen" name="almacen" type="text" value="{{ old('almacen') }}" placeholder="Almacen Central" class="@error('almacen') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="ubicacion">Ubicacion</label>
                            <input id="ubicacion" name="ubicacion" type="text" value="{{ old('ubicacion') }}" placeholder="Pasillo 3 / Estanteria 12" class="@error('ubicacion') is-invalid @enderror">
                        </div>
                    </div>
                </section>

                <section class="item-section">
                    <aside class="item-section-label">
                        <span>SECCION 3</span>
                        <h2>Informacion Economica</h2>
                        <p>Valoracion de activos y politica de margenes comerciales aplicados.</p>
                    </aside>

                    <div class="item-section-fields fields-1">
                        <div class="field-group field-tight">
                            <label for="precio_coste_preview">Precio de coste</label>
                            <div class="currency-preview">
                                <span>EUR</span>
                                <input id="precio_coste_preview" type="text" value="0.00" readonly>
                            </div>
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
            // Auto-grow para textarea
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

            // Generar campos dinámicos para variantes
            const tiposAtributosInput = document.getElementById('tipos_atributos');
            const atributosContainer = document.getElementById('atributos-container');

            const generarCamposAtributos = () => {
                const valor = tiposAtributosInput.value.trim();
                atributosContainer.innerHTML = '';

                if (!valor) return;

                // Parsear tipos de atributos (separados por coma)
                const tipos = valor.split(',')
                    .map(t => t.trim())
                    .filter(t => t.length > 0);

                if (tipos.length === 0) return;

                // Crear campos para cada tipo
                tipos.forEach(tipo => {
                    const fieldGroup = document.createElement('div');
                    fieldGroup.className = 'field-group';
                    fieldGroup.innerHTML = `
                        <label for="atributo_${tipo}">Valor - ${tipo}</label>
                        <input id="atributo_${tipo}" name="atributos_variante[${tipo}]" type="text" 
                            placeholder="Ejem: ${tipo === 'Talla' ? 'M, L, XL' : tipo === 'Color' ? 'Rojo, Azul' : 'especifica un valor'}"
                            value="{{ old('atributos_variante.${tipo}', '') }}">
                    `;
                    atributosContainer.appendChild(fieldGroup);
                });
            };

            // Generar campos iniciales si hay valores en old()
            generarCamposAtributos();

            // Regenerar cuando cambie el input de tipos
            tiposAtributosInput.addEventListener('input', generarCamposAtributos);
        })();
    </script>
@endsection
