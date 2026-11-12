@extends('adminlte::page')

@section('title', 'Ver Ítem de Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-item-create.css'])
    <style>
        .inventory-item-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .btn-erp-back { display: inline-flex; align-items: center; gap: 0.5rem; background: #fff; border: 1px solid #cbd5e1; color: #173e67; font-weight: 800; font-size: 0.85rem; padding: 0.6rem 1.15rem; border-radius: 10px; text-decoration: none !important; transition: all 0.15s ease; }
        .btn-erp-back:hover { background: #f8fafc; border-color: #94a3b8; color: #0f2747; }
    </style>
@endsection

@section('content')
    <section class="inventory-item-page">
        <header class="inventory-item-head">
            <div>
                <span class="module-tag">MÓDULO DE INVENTARIO</span>
                <h1 style="margin: 0.25rem 0;">Ver Ítem de Inventario</h1>
                <p style="margin: 0;">Detalle completo del registro seleccionado — solo lectura.</p>
            </div>
            <div>
                <a href="{{ route('inventario.index') }}" class="btn-erp-back">
                    <i class="fas fa-arrow-left"></i> Volver al Inventario
                </a>
            </div>
        </header>

        <div class="inventory-item-layout">
            <div class="inventory-item-form">
                <section class="item-section">
                    <aside class="item-section-label">
                        <span>SECCIÓN 1</span>
                        <h2>Datos Básicos</h2>
                        <p>Identificación fundamental del producto y vinculación con proveedor oficial.</p>
                    </aside>

                    <div class="item-section-fields fields-2">
                        <div class="field-group">
                            <label>Código del producto</label>
                            <div class="field-read">{{ $inventario->codigo ?: '—' }}</div>
                        </div>

                        <div class="field-group">
                            <label>Referencia proveedor</label>
                            <div class="field-read">{{ $inventario->referencia_proveedor ?: '—' }}</div>
                        </div>

                        <div class="field-group">
                            <label>Nombre proveedor</label>
                            <div class="field-read">{{ $inventario->nombre_proveedor ?: '—' }}</div>
                        </div>

                        <div class="field-group">
                            <label>Nombre</label>
                            <div class="field-read">{{ $inventario->nombre ?? $inventario->descripcion ?? '—' }}</div>
                        </div>

                        <div class="field-group">
                            <label>Clase del producto</label>
                            <div class="field-read">
                                @if(isset($inventario->claseRelacion) && is_object($inventario->claseRelacion))
                                    {{ $inventario->claseRelacion->nombre }}
                                @else
                                    {{ $inventario->clase ?? 'Sin clase' }}
                                @endif
                            </div>
                        </div>

                        <div class="field-group field-full">
                            <label>Descripción del ítem</label>
                            <div class="field-read">{{ $inventario->descripcion ?: '—' }}</div>
                        </div>
                    </div>
                </section>

                <section class="item-section">
                    <aside class="item-section-label">
                        <span>SECCIÓN 1.5</span>
                        <h2>Variantes del Producto</h2>
                        <p>Información de atributos dinámicos asociados.</p>
                    </aside>

                    <div class="item-section-fields fields-1">
                        @php
                            $atributosVar = (array) ($inventario->atributos_variante ?? []);
                        @endphp

                        <div class="field-group">
                            <label>Tipos de variantes</label>
                            <div class="field-read">
                                @if(!empty($atributosVar))
                                    {{ implode(', ', array_keys($atributosVar)) }}
                                @elseif(!empty($inventario->variante?->tipos_atributos))
                                    {{ implode(', ', (array) $inventario->variante->tipos_atributos) }}
                                @else
                                    Sin variantes definidas
                                @endif
                            </div>
                        </div>

                        @if(!empty($atributosVar))
                            @foreach($atributosVar as $tipo => $valor)
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
                        <span>SECCIÓN 2</span>
                        <h2>Control de Existencias</h2>
                        <p>Parámetros de stock y ubicación física dentro de los almacenes operativos.</p>
                    </aside>

                    <div class="item-section-fields fields-3">
                        <div class="field-group field-tight">
                            <label>Stock actual</label>
                            <div class="field-read">{{ $inventario->stock_actual ?? 0 }}</div>
                        </div>

                        <div class="field-group field-tight">
                            <label>Mínimo stock (alerta)</label>
                            <div class="field-read">{{ $inventario->stock_minimo ?? 0 }}</div>
                        </div>

                        <div class="field-group field-tight">
                            <label>Stock crítico</label>
                            <div class="field-read">{{ $inventario->nivel_critico ?? 0 }}</div>
                        </div>

                        <div class="field-group field-full">
                            <label>Almacenes con existencias</label>
                            <div class="field-read">{{ $inventario->almacen ?: 'Sin almacén asignado' }}</div>
                        </div>
                    </div>
                </section>

                <footer class="item-form-footer">
                    <p>* Vista de solo lectura.</p>
                    <div class="item-footer-actions">
                        <a href="{{ route('inventario.index') }}" class="btn-footer-cancel">Volver</a>
                        <a href="{{ route('inventario.item.create', ['variante_id' => $inventario->inventario_variante_id ?? $inventario->id]) }}" class="btn-footer-save" style="background:#0f172a;color:#fff;">
                            <i class="fas fa-plus"></i>
                            Añadir variante
                        </a>
                        <a href="{{ route('inventario.edit', $inventario->id) }}" class="btn-footer-save">
                            <i class="fas fa-edit"></i>
                            Editar
                        </a>
                        <form action="{{ route('inventario.destroy', $inventario->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Confirmas dar de baja este ítem?');">
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
                    <p>ID: {{ $inventario->id }} · Creado: {{ optional($inventario->created_at)->format('d/m/Y') ?: '—' }}</p>
                </article>

                <article class="side-card">
                    <h3>Proveedor</h3>
                    <p>
                        <strong>{{ $inventario->nombre_proveedor ?: 'Sin nombre asignado' }}</strong>
                        <span class="d-block text-muted" style="font-size: 0.8rem;">
                            Ref: {{ $inventario->referencia_proveedor ?: 'No especificada' }}
                        </span>
                    </p>
                </article>
            </aside>
        </div>
    </section>
@endsection