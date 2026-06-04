@extends('adminlte::page')

@section('title', 'Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-index.css'])
    <style>
        .inventory-parent-row {
            font-weight: 600;
        }

        .inventory-child-row[hidden] {
            display: none !important;
        }

        .inventory-child-row td {
            background: #f8fafc;
        }

        .inventory-child-row.inventory-row-critico td {
            background: #ffecec !important;
        }

        .inventory-child-row.inventory-row-bajo td {
            background: #fff4e4 !important;
        }

        .inventory-child-row.inventory-row-optimo td {
            background: #f8fafc !important;
        }

        .inventory-child-marker {
            color: #94a3b8;
            font-weight: 700;
            margin-right: 0.5rem;
        }

        .inventory-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: 1px solid #dbe4f0;
            border-radius: 999px;
            background: #fff;
            color: #0f172a;
            cursor: pointer;
        }

        .inventory-toggle-btn i {
            transition: transform 0.15s ease;
        }

        .inventory-toggle-btn[aria-expanded="true"] i {
            transform: rotate(90deg);
        }

        .inventory-table {
            table-layout: fixed;
        }

        .inventory-table th:nth-child(1),
        .inventory-table td:nth-child(1) {
            width: 7%;
        }

        .inventory-table th:nth-child(2),
        .inventory-table td:nth-child(2) {
            width: 8%;
        }

        .inventory-table th:nth-child(3),
        .inventory-table td:nth-child(3) {
            width: 30%;
        }

        .inventory-table th:nth-child(4),
        .inventory-table td:nth-child(4) {
            width: 12%;
        }

        .inventory-table th:nth-child(5),
        .inventory-table td:nth-child(5) {
            width: 15%;
        }

        .inventory-table th:nth-child(6),
        .inventory-table td:nth-child(6) {
            width: 10%;
        }

        .inventory-table th:nth-child(7),
        .inventory-table td:nth-child(7) {
            width: 9%;
        }

        .inventory-table th:nth-child(8),
        .inventory-table td:nth-child(8) {
            width: 9%;
        }

        .inventory-table td:nth-child(3),
        .inventory-table td:nth-child(4),
        .inventory-table td:nth-child(5) {
            word-break: break-word;
        }

        .inventory-parent-alerts {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-top: 0.35rem;
            flex-wrap: wrap;
        }

        .inventory-parent-alert {
            display: inline-flex;
            align-items: center;
            gap: 0.28rem;
            padding: 0.2rem 0.45rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .inventory-parent-alert--warning {
            background: #fff4e4;
            color: #c27b00;
        }

        .inventory-parent-alert--danger {
            background: #ffecec;
            color: #c0392b;
        }
    </style>
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
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Clase</th>
                                    <th>Almacén</th>
                                    <th>Stock actual</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventarios as $grupo)
                                    <tr class="inventory-row inventory-row-{{ $grupo->estadoGrupo }} inventory-parent-row" data-parent-group="group-{{ $loop->index }}">
                                        <td><span class="inventory-code">{{ $grupo->codigoPadre }}</span></td>
                                        <td><div class="inventory-name"><strong>{{ $grupo->nombrePadre }}</strong></div></td>
                                        <td>
                                            <div class="inventory-description">
                                                <strong>{{ $grupo->descripcionPadre }}</strong>
                                                @if($grupo->hijosReposicion > 0 || $grupo->hijosCriticos > 0)
                                                    <div class="inventory-parent-alerts" aria-label="Alertas de variantes hijas">
                                                        @if($grupo->hijosReposicion > 0)
                                                            <span class="inventory-parent-alert inventory-parent-alert--warning" title="Variantes en reposición">
                                                                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                                                                {{ $grupo->hijosReposicion }}
                                                            </span>
                                                        @endif
                                                        @if($grupo->hijosCriticos > 0)
                                                            <span class="inventory-parent-alert inventory-parent-alert--danger" title="Variantes críticas">
                                                                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                                                                {{ $grupo->hijosCriticos }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                                @if($grupo->referenciaPadre)
                                                    <span>{{ $grupo->referenciaPadre }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td><span class="inventory-pill muted">{{ $grupo->clasePadre }}</span></td>
                                        <td><span class="inventory-stock inventory-stock-main">{{ number_format($grupo->stockGrupo, 0, ',', '.') }}</span></td>
                                        <td><span class="inventory-status inventory-status-{{ $grupo->estadoGrupo }}">{{ $grupo->estadoTextoGrupo }}</span></td>
                                        <td>
                                            <div class="inventory-actions">
                                                @if($grupo->idPadre)
                                                    <a href="{{ route('inventario.show', $grupo->idPadre) }}" class="inventory-action-icon" title="Ver producto padre">
                                                        <i class="far fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('inventario.edit', $grupo->idPadre) }}" class="inventory-action-icon" title="Editar item">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                @else
                                                    <span class="inventory-action-icon" title="Ver producto padre"><i class="far fa-eye"></i></span>
                                                    <span class="inventory-action-icon" title="Editar item"><i class="fas fa-pen"></i></span>
                                                @endif
                                                @if($grupo->variantePadreId)
                                                    <a href="{{ route('inventario.item.create', ['variante_id' => $grupo->variantePadreId]) }}" class="inventory-action-icon" title="Añadir variante hija">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                @endif
                                                @if($grupo->tieneHijos)
                                                    <button type="button" class="inventory-toggle-btn" data-toggle-children data-target-group="group-{{ $loop->index }}" aria-expanded="false" aria-label="Mostrar variantes">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    @foreach($grupo->hijos as $producto)
                                        <tr class="inventory-row inventory-row-{{ $producto->estado }} inventory-child-row" data-child-group="group-{{ $loop->parent->index }}" hidden>
                                            <td><span class="inventory-code">{{ $producto->codigo }}</span></td>
                                            <td><div class="inventory-name" style="padding-left:1.25rem;"><strong>{{ $producto->nombre ?? '' }}</strong></div></td>
                                            <td>
                                                <div class="inventory-description">
                                                    <strong style="padding-left:1.25rem; position:relative;">
                                                        <span class="inventory-child-marker">└</span>
                                                        {{ $producto->descripcion }}
                                                    </strong>
                                                    @if($producto->referencia_proveedor)
                                                        <span>{{ $producto->referencia_proveedor }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td><span class="inventory-pill muted">{{ data_get($producto->clase_relacion, 'nombre', $grupo->clasePadre) }}</span></td>
                                            <td><span class="inventory-stock inventory-stock-{{ $producto->estado === 'bajo' ? 'bajo' : ($producto->estado === 'critico' ? 'critico' : 'main') }}">{{ number_format($producto->stock_actual, 0, ',', '.') }}</span></td>
                                            <td><span class="inventory-status inventory-status-{{ $producto->estado }}">{{ $producto->estado_texto }}</span></td>
                                            <td>
                                                <div class="inventory-actions">
                                                    @if($producto->id)
                                                        <a href="{{ route('inventario.show', $producto->id) }}" class="inventory-action-icon" title="Ver variante">
                                                            <i class="far fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('inventario.edit', $producto->id) }}" class="inventory-action-icon" title="Editar variante">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                    @else
                                                        <span class="inventory-action-icon" title="Ver variante"><i class="far fa-eye"></i></span>
                                                        <span class="inventory-action-icon" title="Editar variante"><i class="fas fa-pen"></i></span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="8">
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

@section('js')
    <script>
        (function () {
            const toggleButtons = document.querySelectorAll('[data-toggle-children]');

            toggleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const groupId = button.dataset.targetGroup;
                    const expanded = button.getAttribute('aria-expanded') === 'true';
                    const childRows = document.querySelectorAll(`[data-child-group="${groupId}"]`);

                    childRows.forEach((row) => {
                        row.hidden = expanded;
                    });

                    button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    button.setAttribute('aria-label', expanded ? 'Mostrar variantes' : 'Ocultar variantes');
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.className = expanded ? 'fas fa-chevron-right' : 'fas fa-chevron-down';
                    }
                });
            });
        })();
    </script>
@endsection
