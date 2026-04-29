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
            <p>Actualización de datos, especificaciones tecnicas y parametros del item registrado en el sistema central de logistica.</p>
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

                        <div class="field-group field-full">
                            <label for="descripcion">Descripcion del item</label>
                            <input id="descripcion" name="descripcion" type="text" value="{{ old('descripcion', $inventario->descripcion) }}" placeholder="Nombre descriptivo completo del material o pieza industrial" class="@error('descripcion') is-invalid @enderror" required>
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
