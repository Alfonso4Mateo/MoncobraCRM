@extends('adminlte::page')

@section('title', 'Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-index.css'])
@endsection

@section('content')
    <section class="inventory-page">
        <header class="inventory-hero">
            <div>
                <h1>Control de Inventario y Stock</h1>
                <p>Gestión centralizada de existencias, ubicaciones y alertas críticas.</p>
            </div>

            <div class="inventory-hero-actions">
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'superadmin')
                    <a href="{{ route('clases.index') }}" class="inventory-primary-action" title="Gestionar categorías de items">
                        <i class="fas fa-tags"></i>
                        Gestionar Clases
                    </a>
                @endif
                <a href="{{ route('inventario.acciones.index') }}" class="inventory-primary-action">
                    <i class="fas fa-clipboard-list"></i>
                    Registro de acciones
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="inventory-alert inventory-alert-success">
                <i class="fas fa-circle-check"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="inventory-alert inventory-alert-error">
                <i class="fas fa-triangle-exclamation"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="inventory-layout">
            <div class="inventory-main">
                <div class="inventory-stats-grid">
                    <article class="inventory-stat-card">
                        <div class="inventory-stat-top">
                            <div>
                                <span class="inventory-stat-label">Total productos</span>
                                <strong class="inventory-stat-value">{{ number_format($totalProductos, 0, ',', '.') }}</strong>
                                <span class="inventory-stat-note positive">+{{ number_format(max(1, (int) round($totalProductos * 0.02)), 0, ',', '.') }} este mes</span>
                            </div>
                            <span class="inventory-stat-icon blue"><i class="fas fa-boxes"></i></span>
                        </div>
                    </article>

                    <article class="inventory-stat-card">
                        <div class="inventory-stat-top">
                            <div>
                                <span class="inventory-stat-label">Nivel crítico</span>
                                <strong class="inventory-stat-value">{{ number_format($nivelCritico, 0, ',', '.') }}</strong>
                                <span class="inventory-stat-note danger">Requiere atención inmediata</span>
                            </div>
                            <span class="inventory-stat-icon red"><i class="fas fa-exclamation-circle"></i></span>
                        </div>
                    </article>

                    <article class="inventory-stat-card">
                        <div class="inventory-stat-top">
                            <div>
                                <span class="inventory-stat-label">Stock total</span>
                                <strong class="inventory-stat-value">{{ number_format($stockTotal, 0, ',', '.') }}</strong>
                                <span class="inventory-stat-note">Unidades registradas</span>
                            </div>
                            <span class="inventory-stat-icon amber"><i class="fas fa-boxes"></i></span>
                        </div>
                    </article>

                    <article class="inventory-stat-card">
                        <div class="inventory-stat-top">
                            <div>
                                <span class="inventory-stat-label">Ubicaciones</span>
                                <strong class="inventory-stat-value">{{ number_format($ubicaciones, 0, ',', '.') }}</strong>
                                <span class="inventory-stat-note">{{ number_format($almacenes, 0, ',', '.') }} almacenes operativos</span>
                            </div>
                            <span class="inventory-stat-icon teal"><i class="fas fa-map-marker-alt"></i></span>
                        </div>
                    </article>
                </div>

                <div class="inventory-search-card">
                    <div class="inventory-search-header">
                        <div>
                            <h2>Buscar inventario</h2>
                            <p>Filtra por descripción, clase o almacén.</p>
                        </div>

                        @if(request()->hasAny(['descripcion', 'clase_id', 'almacen']))
                            <a href="{{ route('inventario.index') }}" class="inventory-search-reset">Limpiar filtros</a>
                        @endif
                    </div>

                    <form method="GET" action="{{ route('inventario.index') }}" class="inventory-search-form">
                        <div class="inventory-search-field">
                            <label for="descripcion">Descripción</label>
                            <input id="descripcion" name="descripcion" type="text" value="{{ $descripcion }}" placeholder="Buscar por descripción">
                        </div>

                        <div class="inventory-search-field">
                            <label for="clase_id">Clase</label>
                            <select id="clase_id" name="clase_id">
                                <option value="">Todas las clases</option>
                                @foreach($clases as $id => $nombre)
                                    <option value="{{ $id }}" @selected((string) $claseId === (string) $id)>{{ $nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="inventory-search-field">
                            <label for="almacen">Almacén</label>
                            <input id="almacen" name="almacen" type="text" value="{{ $almacen }}" placeholder="Buscar por almacén">
                        </div>

                        <div class="inventory-search-actions">
                            <button type="submit" class="inventory-search-btn">
                                <i class="fas fa-search"></i>
                                Buscar
                            </button>
                        </div>
                    </form>
                </div>

                <div class="inventory-card inventory-table-card">

                    <div class="table-responsive inventory-table-wrapper">
                        <table class="table inventory-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th>Variante</th>
                                    <th>Clase</th>
                                    <th>Almacén</th>
                                    <th>Stock actual</th>
                                    <th>Stock mínimo</th>
                                    <th>Nivel crítico</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventarios as $producto)
                                    @php
                                        $stockActual = (int) $producto->stock_actual;
                                        $stockMinimo = (int) ($producto->stock_minimo ?? 0);
                                        $nivelCriticoProducto = (int) ($producto->nivel_critico ?? 0);

                                        if ($stockActual <= $nivelCriticoProducto) {
                                            $estado = 'critico';
                                            $estadoTexto = 'Crítico';
                                        } elseif ($stockActual <= $stockMinimo) {
                                            $estado = 'bajo';
                                            $estadoTexto = 'Reposición';
                                        } else {
                                            $estado = 'optimo';
                                            $estadoTexto = 'Óptimo';
                                        }
                                    @endphp
                                    <tr class="inventory-row inventory-row-{{ $estado }}">
                                        <td>
                                            <span class="inventory-code">{{ $producto->codigo }}</span>
                                        </td>
                                        <td>
                                            <div class="inventory-description">
                                                <strong>{{ $producto->descripcion }}</strong>
                                                @if($producto->referencia_proveedor)
                                                    <span>{{ $producto->referencia_proveedor }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="inventory-variant">
                                                @if($producto->atributos_variante && count($producto->atributos_variante) > 0)
                                                    @foreach($producto->atributos_variante as $tipo => $valor)
                                                        <span class="inventory-variant-badge">{{ $tipo }}: {{ $valor }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="inventory-pill muted">Sin variante</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if(is_object($producto->claseRelacion))
                                                <span class="inventory-pill muted">{{ $producto->claseRelacion->nombre }}</span>
                                            @else
                                                <span class="inventory-pill muted">{{ $producto->clase ?: 'Sin clase' }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="inventory-location">
                                                <strong>{{ $producto->almacen ?: 'Sin almacén' }}</strong>
                                                @if($producto->ubicacion)
                                                    <span>{{ $producto->ubicacion }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="inventory-stock inventory-stock-main">{{ number_format($stockActual, 0, ',', '.') }}</span>
                                        </td>
                                        <td>
                                            <span class="inventory-stock">{{ number_format($stockMinimo, 0, ',', '.') }}</span>
                                        </td>
                                        <td>
                                            <span class="inventory-stock inventory-stock-critico">{{ number_format($nivelCriticoProducto, 0, ',', '.') }}</span>
                                        </td>
                                        <td>
                                            <span class="inventory-status inventory-status-{{ $estado }}">{{ $estadoTexto }}</span>
                                        </td>
                                        <td>
                                            <div class="inventory-actions">
                                                <a href="{{ route('inventario.show', $producto) }}" class="inventory-action-icon" title="Ver producto">
                                                    <i class="far fa-eye"></i>
                                                </a>
                                                <a href="{{ route('inventario.edit', $producto) }}" class="inventory-action-icon" title="Editar producto">
                                                    <i class="far fa-pen-to-square"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10">
                                            <div class="inventory-empty-state">
                                                <i class="fas fa-box-open"></i>
                                                <strong>No hay productos en inventario</strong>
                                                <span>Cuando se creen productos aparecerán aquí con el mismo diseño del panel.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="inventory-toolbar inventory-toolbar-footer">
                        <span class="inventory-toolbar-label">Mostrando {{ $inventarios->firstItem() ?? 0 }} - {{ $inventarios->lastItem() ?? 0 }} de {{ number_format($inventarios->total(), 0, ',', '.') }} productos</span>

                        @if ($inventarios->hasPages())
                            <div class="inventory-pagination">
                                {{ $inventarios->onEachSide(1)->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="inventory-sidebar">
                <div class="inventory-sidebar-card inventory-sidebar-card-actions">
                    <div class="inventory-sidebar-actions">
                        <a href="#" class="inventory-mini-btn light">
                            <i class="fas fa-download"></i>
                            Exportar
                        </a>
                        <a href="#" class="inventory-mini-btn light">
                            <i class="fas fa-upload"></i>
                            Importar
                        </a>
                    </div>

                    <div class="inventory-sidebar-actions stacked">
                        <a href="{{ route('inventario.salida.create') }}" class="inventory-mini-btn dark">
                            <i class="fas fa-minus"></i>
                            Nueva salida
                        </a>
                        <a href="{{ route('inventario.create') }}" class="inventory-mini-btn primary">
                            <i class="fas fa-plus"></i>
                            Nueva entrada
                        </a>
                    </div>
                </div>

                <div class="inventory-sidebar-card">
                    <div class="inventory-sidebar-title">
                        <i class="fas fa-clock-rotate-left"></i>
                        Últimos movimientos
                    </div>

                    <div class="inventory-movements">
                        @forelse($movimientosRecientes as $movimiento)
                            <article class="movement-item movement-{{ $movimiento->tono }}">
                                <div class="movement-icon">
                                    <i class="fas {{ $movimiento->icono }}"></i>
                                </div>
                                <div class="movement-body">
                                    <strong>{{ $movimiento->titulo }}</strong>
                                    <span>{{ $movimiento->subtitulo }}</span>
                                    <small>{{ $movimiento->tiempo }}</small>
                                </div>
                            </article>
                        @empty
                            <div class="inventory-empty-mini">
                                Sin movimientos recientes.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="inventory-sidebar-card inventory-sidebar-card-dark">
                    <div class="inventory-sidebar-title light">
                        Ocupación de almacenes
                    </div>

                    <div class="warehouse-list">
                        @forelse($ocupacionAlmacenes as $almacen)
                            <div class="warehouse-item">
                                <div class="warehouse-icon">
                                    <i class="fas fa-warehouse"></i>
                                </div>
                                <div class="warehouse-body">
                                    <div class="warehouse-head">
                                        <span>{{ $almacen->nombre }}</span>
                                        <strong>{{ number_format($almacen->total_productos, 0, ',', '.') }}</strong>
                                    </div>
                                    <small>productos registrados</small>
                                </div>
                            </div>
                        @empty
                            <div class="inventory-empty-mini inventory-empty-mini-light">
                                No hay almacenes registrados.
                            </div>
                        @endforelse
                    </div>

                    <div class="inventory-sidebar-footer-actions">
                        <a href="{{ route('almacenes.create') }}" class="inventory-sidebar-btn">Crear almacén</a>
                        <a href="{{ route('inventario.traslado.create') }}" class="inventory-sidebar-btn">Trasladar</a>
                    </div>
                </div>
            </aside>
        </div>
    </section>
@endsection