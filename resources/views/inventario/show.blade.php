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

                        <div class="field-group">
                            <label>Nombre</label>
                            <div class="field-read">{{ $inventario->nombre ?? $inventario->descripcion }}</div>
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
                        <span>SECCION 1.5</span>
                        <h2>Variantes del Producto</h2>
                        <p>Información de variantes dinámicas.</p>
                    </aside>

                    <div class="item-section-fields fields-1">
                        <div class="field-group">
                            <label>Tipos de variantes</label>
                            <div class="field-read">
                                @if($inventario->variante && $inventario->variante->tipos_atributos)
                                    {{ implode(', ', $inventario->variante->tipos_atributos) }}
                                @else
                                    Sin variantes definidas
                                @endif
                            </div>
                        </div>

                        @if($inventario->atributos_variante && count($inventario->atributos_variante) > 0)
                            @foreach($inventario->atributos_variante as $tipo => $valor)
                                <div class="field-group">
                                    <label>{{ $tipo }}</label>
                                    @php
                                        $valores = is_array($valor) ? array_filter($valor, fn ($item) => $item !== null && $item !== '') : [$valor];
                                    @endphp
                                    <div class="field-read">{{ implode(', ', $valores) }}</div>
                                </div>
                            @endforeach
                        @else
                            <div class="field-group">
                                <label>Valores</label>
                                <div class="field-read">Sin valores especificados</div>
                            </div>
                        @endif
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
                            <label>Almacén</label>
                            <div class="field-read">{{ $inventario->almacen ?: 'Sin almacén' }}</div>
                        </div>

                    </div>
                </section>

                <footer class="item-form-footer">
                    <p>* Vista de solo lectura.</p>
                    <div class="item-footer-actions">
                        <a href="{{ route('inventario.index') }}" class="btn-footer-cancel">Cancelar</a>
                        @if($inventario->inventario_variante_id)
                            <a href="{{ route('inventario.item.create', ['variante_id' => $inventario->inventario_variante_id]) }}" class="btn-footer-save" style="background:#0f172a;color:#fff;">
                                <i class="fas fa-plus"></i>
                                Añadir variante
                            </a>
                        @endif
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
