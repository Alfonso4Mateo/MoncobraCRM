@extends('adminlte::page')

@section('title', 'Ver Item de Inventario - MoncobraCRM')

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
            <h1>Ver Item de Inventario</h1>
            <p>Detalle completo del registro seleccionado — solo lectura.</p>
        </header>

        <div class="inventory-item-layout">
            <div class="inventory-item-form">
                <section class="item-section">
                    <aside class="item-section-label">
                        <span>SECCION 1</span>
                        <h2>Datos Basicos</h2>
                        <p>Identificacion fundamental del producto y vinculacion con proveedor oficial.</p>
                    </aside>

                    <div class="item-section-fields fields-2">
                        <div class="field-group">
                            <label>Codigo del producto</label>
                            <div class="field-read">{{ $inventario->codigo }}</div>
                        </div>

                        <div class="field-group">
                            <label>Referencia proveedor</label>
                            <div class="field-read">{{ $inventario->referencia_proveedor }}</div>
                        </div>

                        <div class="field-group field-full">
                            <label>Descripcion del item</label>
                            <div class="field-read">{{ $inventario->descripcion }}</div>
                        </div>

                        <div class="field-group">
                            <label>Clase del producto</label>
                            <div class="field-read">
                                @if(is_object($inventario->claseRelacion))
                                    {{ $inventario->claseRelacion->nombre }}
                                @else
                                    {{ $inventario->clase ?: 'Sin clase' }}
                                @endif
                            </div>
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
                            <label>Stock actual</label>
                            <div class="field-read">{{ $inventario->stock_actual }}</div>
                        </div>

                        <div class="field-group field-tight">
                            <label>Minimo stock (alerta)</label>
                            <div class="field-read">{{ $inventario->stock_minimo }}</div>
                        </div>

                        <div class="field-group field-tight">
                            <label>Stock critico</label>
                            <div class="field-read">{{ $inventario->nivel_critico }}</div>
                        </div>

                        <div class="field-group">
                            <label>Almacen</label>
                            <div class="field-read">{{ $inventario->almacen }}</div>
                        </div>

                        <div class="field-group">
                            <label>Ubicacion</label>
                            <div class="field-read">{{ $inventario->ubicacion }}</div>
                        </div>
                    </div>
                </section>

                <footer class="item-form-footer">
                    <p>* Vista de solo lectura.</p>
                    <div class="item-footer-actions">
                        <a href="{{ route('inventario.index') }}" class="btn-footer-cancel">Cancelar</a>
                        <a href="{{ route('inventario.edit', $inventario->id) }}" class="btn-footer-save">
                            <i class="fas fa-edit"></i>
                            Editar
                        </a>
                        <form action="{{ route('inventario.destroy', $inventario->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Confirmas eliminar este item?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-footer-cancel" style="background:#fff5f5;color:#9c2f2f;border-color:#fecaca;">Eliminar</button>
                        </form>
                    </div>
                </footer>
            </div>

            <aside class="inventory-item-side">
                <article class="side-card with-accent">
                    <h3>Meta</h3>
                    <p>ID: {{ $inventario->id }} · Creado: {{ $inventario->created_at?->format('Y-m-d') }}</p>
                </article>

                <article class="side-card">
                    <h3>Proveedor</h3>
                    <p>{{ $inventario->referencia_proveedor ?: 'No especificado' }}</p>
                </article>
            </aside>
        </div>
    </section>
@endsection
