@extends('adminlte::page')

@section('title', 'Perfil del Trabajador')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/personal-show.css'])
    <style>
        .profile-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            align-items: flex-end;
        }

        .profile-filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .profile-filter-group label {
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #8a98ab;
        }

        .profile-filter-group input,
        .profile-filter-group select {
            padding: 9px 11px;
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            font-family: var(--profile-font);
            font-size: .9rem;
            color: var(--profile-ink);
            background: #fff;
        }

        .profile-filter-group input:focus,
        .profile-filter-group select:focus {
            outline: none;
            border-color: var(--profile-primary);
            box-shadow: 0 0 0 3px rgba(23, 62, 103, .08);
        }

        .profile-filter-btn {
            padding: 9px 16px;
            background: var(--profile-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 800;
            font-size: .9rem;
            cursor: pointer;
            transition: all .18s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .profile-filter-btn:hover {
            background: var(--profile-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(23, 62, 103, .2);
        }

        .profile-filter-reset {
            padding: 9px 12px;
            background: transparent;
            color: var(--profile-muted);
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            font-weight: 600;
            font-size: .85rem;
            cursor: pointer;
            transition: all .18s ease;
        }

        .profile-filter-reset:hover {
            color: var(--profile-ink);
            border-color: var(--profile-ink);
        }

        @media (max-width: 768px) {
            .profile-filters {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <section class="profile-page">
        <header class="profile-hero">
            <div>
                <div class="profile-crumbs">GESTIÓN DE PERSONAL <span>•</span> PERFIL TRABAJADOR</div>
                <h1>Perfil del Trabajador</h1>
            </div>

            <div class="profile-hero-actions">
                <a href="{{ route('inventario.salida.create') }}" class="profile-action profile-action--soft">
                    <i class="fas fa-arrow-up-from-box"></i>
                    Registrar Salida
                </a>
                <a href="#" class="profile-action profile-action--soft">
                    <i class="fas fa-file-export"></i>
                    Exportar Ficha
                </a>
                @can('manage-users')
                    <a href="{{ route('personal.edit', $personal->id) }}" class="profile-action profile-action--primary">
                        <i class="fas fa-pen"></i>
                        Editar Perfil
                    </a>
                @endcan
            </div>
        </header>

        <div class="profile-grid">
            <section class="profile-main">
                <article class="profile-card profile-card--main-sidebar">
                    <div class="profile-main-row">
                        <div class="profile-main-left">
                            <div class="profile-status">{{ $personal->activo ? 'ACTIVO' : 'INACTIVO' }}</div>

                            <div class="profile-avatar-wrap">
                                <div class="profile-avatar">
                                    <i class="fas fa-hard-hat"></i>
                                </div>
                            </div>

                            <div class="profile-name-block">
                                <h2>{{ $personal->name }} {{ $personal->apellido }}</h2>
                                <p>{{ strtoupper($personal->departamento ?: 'Sin departamento') }}</p>
                            </div>

                            <div class="profile-metadata">
                                <div>
                                    <span>ID EMPLEADO</span>
                                    <strong>AL-{{ str_pad((string) $personal->id, 3, '0', STR_PAD_LEFT) }}</strong>
                                </div>
                                <div>
                                    <span>DEPARTAMENTO</span>
                                    <strong>{{ $personal->departamento ?: '—' }}</strong>
                                </div>
                                <div>
                                    <span>ANTIGÜEDAD</span>
                                    <strong>{{ optional($personal->created_at)->format('d M Y') }}</strong>
                                </div>
                            </div>
                        </div>

                        <article class="profile-card profile-card--tallas">
                            <h3><i class="fas fa-ruler-combined"></i> Tallas y EPIs</h3>

                            <div class="profile-size-list">
                                @foreach ($tallas as $talla)
                                    <div class="profile-size-row">
                                        <div class="profile-size-label">
                                            <i class="fas {{ $talla['icon'] }}"></i>
                                            <span>{{ $talla['label'] }}</span>
                                        </div>
                                        <strong>{{ $talla['value'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    </div>
                </article>

                <!-- Panel de Observaciones -->
                @if($personal->descripcion)
                    <article class="profile-card">
                        <div class="profile-card__header">
                            <div>
                                <h3><i class="fas fa-clipboard-list"></i> Observaciones</h3>
                                <p>Notas y comentarios adicionales sobre el trabajador</p>
                            </div>
                        </div>

                        <div class="profile-card__body" style="padding: 20px;">
                            <div style="color: var(--profile-ink); line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;">
                                {{ $personal->descripcion }}
                            </div>
                        </div>
                    </article>
                @endif

                <article class="profile-card profile-card--table">
                    <div class="profile-card__header">
                        <div>
                            <h3>Histórico de Salidas de Inventario</h3>
                            <p>Registro detallado de material y EPIs entregados</p>
                        </div>
                    </div>

                    <!-- Panel de Filtros -->
                    <div style="padding: 20px; border-bottom: 1px solid var(--profile-line); background: #fafbfc;">
                        <form id="filters-form" method="GET" style="display: contents;">
                            <div class="profile-filters">
                                <div class="profile-filter-group">
                                    <label for="fecha_desde">Desde</label>
                                    <input type="date" id="fecha_desde" name="fecha_desde" value="{{ request('fecha_desde') }}">
                                </div>

                                <div class="profile-filter-group">
                                    <label for="fecha_hasta">Hasta</label>
                                    <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                                </div>

                                <div class="profile-filter-group">
                                    <label for="articulo">Buscar Artículo</label>
                                    <input type="text" id="articulo" name="articulo" placeholder="Nombre del artículo..." value="{{ request('articulo') }}">
                                </div>

                                <div style="display: flex; gap: 8px;">
                                    <button type="submit" class="profile-filter-btn">
                                        <i class="fas fa-magnifying-glass"></i>
                                        Buscar
                                    </button>
                                    @if(request('fecha_desde') || request('fecha_hasta') || request('articulo'))
                                        <a href="{{ route('personal.show', $personal->id) }}" class="profile-filter-reset" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-times"></i>
                                            Limpiar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive profile-table-wrap">
                        <table class="table profile-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Artículo</th>
                                    <th>Cantidad</th>
                                    <th>OT relacionada</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($historicoSalidas as $registro)
                                    <tr>
                                        <td data-label="Fecha"><span class="profile-date">{{ $registro->fecha }}</span></td>
                                        <td data-label="Artículo">
                                            <div class="profile-article">
                                                <span class="profile-article__icon"><i class="fas fa-box"></i></span>
                                                <strong>{{ $registro->articulo }}</strong>
                                            </div>
                                        </td>
                                        <td data-label="Cantidad"><strong class="profile-quantity">{{ $registro->cantidad }}</strong></td>
                                        <td data-label="OT relacionada"><span class="profile-chip profile-chip--muted">{{ $registro->ot }}</span></td>
                                        <td data-label="Estado"><span class="{{ $registro->estado_clase }}">{{ $registro->estado }}</span></td>
                                        <td data-label="Acción"><a href="#" class="profile-icon-link" title="Ver detalle"><i class="far fa-eye"></i></a></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="profile-empty-state">
                                                <i class="fas fa-box-open"></i>
                                                <strong>No hay registros para mostrar</strong>
                                                <span>Cuando existan salidas de inventario aparecerán aquí.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="profile-table-footer">
                        <span>MOSTRANDO 1 - {{ count($historicoSalidas) }} DE {{ count($historicoSalidas) }} REGISTROS</span>
                        <div class="profile-pagination">
                            <button type="button" disabled>&lsaquo;</button>
                            <button type="button" class="is-active" disabled>1</button>
                            <button type="button" disabled>2</button>
                            <button type="button" disabled>&rsaquo;</button>
                        </div>
                    </div>
                </article>
            </section>
        </div>

        <a href="{{ route('personal.index') }}" class="profile-fab" title="Volver al listado">
            <i class="fas fa-arrow-left"></i>
        </a>
    </section>
@endsection
