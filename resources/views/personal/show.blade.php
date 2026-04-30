@extends('adminlte::page')

@section('title', 'Perfil del Trabajador')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/personal-show.css'])
@endsection

@section('content')
    <section class="profile-page">
        <header class="profile-hero">
            <div>
                <div class="profile-crumbs">GESTIÓN DE PERSONAL <span>•</span> PERFIL TRABAJADOR</div>
                <h1>Perfil del Trabajador</h1>
            </div>

            <div class="profile-hero-actions">
                <a href="#" class="profile-action profile-action--soft">
                    <i class="fas fa-file-export"></i>
                    Exportar Ficha
                </a>
                @can('edit-user', $user)
                    <a href="{{ route('users.edit', $user->id) }}" class="profile-action profile-action--primary">
                        <i class="fas fa-pen"></i>
                        Editar Perfil
                    </a>
                @endcan
            </div>
        </header>

        <div class="profile-grid">
            <aside class="profile-sidebar">
                <article class="profile-card profile-card--main">
                    <div class="profile-status">{{ $user->activo ? 'ACTIVO' : 'INACTIVO' }}</div>

                    <div class="profile-avatar-wrap">
                        <div class="profile-avatar">
                            <i class="fas fa-hard-hat"></i>
                        </div>
                    </div>

                    <div class="profile-name-block">
                        <h2>{{ $user->name }} {{ $user->apellido }}</h2>
                        <p>{{ strtoupper($user->departamento ?: 'Sin departamento') }}</p>
                    </div>

                    <div class="profile-metadata">
                        <div>
                            <span>ID EMPLEADO</span>
                            <strong>AL-{{ str_pad((string) $user->id, 3, '0', STR_PAD_LEFT) }}</strong>
                        </div>
                        <div>
                            <span>DEPARTAMENTO</span>
                            <strong>{{ $user->departamento ?: '—' }}</strong>
                        </div>
                        <div>
                            <span>ANTIGÜEDAD</span>
                            <strong>{{ optional($user->created_at)->format('d M Y') }}</strong>
                        </div>
                    </div>
                </article>

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
            </aside>

            <section class="profile-main">
                <article class="profile-card profile-card--table">
                    <div class="profile-card__header">
                        <div>
                            <h3>Histórico de Salidas de Inventario</h3>
                            <p>Registro detallado de material y EPIs entregados</p>
                        </div>

                        <div class="profile-search">
                            <input type="search" placeholder="Buscar artículo..." disabled>
                            <button type="button" disabled><i class="fas fa-filter"></i></button>
                        </div>
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
            <i class="fas fa-turn-down-left"></i>
        </a>
    </section>
@endsection
