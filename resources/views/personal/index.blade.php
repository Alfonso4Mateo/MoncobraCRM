@extends('adminlte::page')

@section('title', 'Gestión de Personal')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/personal-index.css'])
@endsection

@section('content')
    @php
        $exportUrl = route('personal.index', array_merge(request()->query(), ['export' => 'csv']));
    @endphp

    <section class="personal-page">
        <header class="personal-hero">
            <div class="personal-hero-copy">
                <div class="personal-crumbs">GESTIÓN DE PERSONAL <span>•</span> LISTADO</div>
                <h1>Listado de Personal</h1>
                <p>Consulta, filtra y administra el equipo de trabajo desde una única pantalla.</p>
            </div>

            <a href="{{ route('users.create') }}" class="personal-primary-action">
                <i class="fas fa-user-plus" aria-hidden="true"></i>
                + Añadir Nuevo Trabajador
            </a>
        </header>

        @if (session('success'))
            <div class="personal-alert personal-alert-success">
                <i class="fas fa-circle-check"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="personal-alert personal-alert-error">
                <i class="fas fa-triangle-exclamation"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="personal-shell">
            <section class="personal-stats">
                <article class="personal-stat-card personal-stat-card--blue">
                    <div class="personal-stat-card__icon"><i class="fas fa-users"></i></div>
                    <span class="personal-stat-card__label">Personal total</span>
                    <div class="personal-stat-card__value">{{ number_format($usersTotal, 0, ',', '.') }}</div>
                    <div class="personal-stat-card__note"><strong>{{ number_format($usuariosActivos, 0, ',', '.') }}</strong> activos ahora mismo</div>
                </article>

                <article class="personal-stat-card personal-stat-card--orange">
                    <div class="personal-stat-card__icon"><i class="fas fa-shield-halved"></i></div>
                    <span class="personal-stat-card__label">Administrativos</span>
                    <div class="personal-stat-card__value">{{ number_format($usuariosAdministrativos, 0, ',', '.') }}</div>
                    <div class="personal-stat-card__note"><strong>Control de permisos</strong> para roles elevados</div>
                </article>

                <article class="personal-stat-card personal-stat-card--navy">
                    <div class="personal-stat-card__icon"><i class="fas fa-folder-open"></i></div>
                    <span class="personal-stat-card__label">Sin proyectos</span>
                    <div class="personal-stat-card__value">{{ number_format($usuariosSinProyectos, 0, ',', '.') }}</div>
                    <div class="personal-stat-card__note"><strong>Revisar asignaciones</strong> pendientes</div>
                </article>
            </section>

            <article class="personal-card">
                <header class="personal-card__header">
                    <div>
                        <h3>Registro general de personal</h3>
                        <p>{{ $users->total() }} trabajadores</p>
                    </div>

                    <div class="personal-card__actions">
                        <a href="{{ $exportUrl }}" class="personal-action-btn personal-action-btn--soft">
                            <i class="fas fa-download" aria-hidden="true"></i>
                            Exportar
                        </a>
                    </div>
                </header>

                <div class="personal-search-wrap">
                    <form method="GET" action="{{ route('personal.index') }}" class="personal-search-form">
                        <div class="personal-search-field personal-search-field--search">
                            <label for="q">Buscar</label>
                            <input type="search" id="q" name="q" value="{{ $query }}" placeholder="Buscar por nombre o apellidos..." autocomplete="off">
                        </div>

                        <div class="personal-search-actions">
                            <button type="submit" class="personal-search-submit">
                                <i class="fas fa-search"></i>
                                Buscar
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive personal-table-wrap">
                    <table class="table personal-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre completo</th>
                                <th>Rol</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Vinculación</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                @php
                                    $initials = collect(explode(' ', trim($user->name)))
                                        ->filter()
                                        ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                                        ->take(2)
                                        ->implode('');
                                    $userCode = '#USR-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
                                @endphp
                                <tr>
                                    <td data-label="ID">
                                        <span class="personal-code">{{ $userCode }}</span>
                                    </td>
                                    <td data-label="Nombre completo">
                                        <div class="personal-person-cell">
                                            <span class="personal-avatar">{{ $initials ?: 'U' }}</span>
                                            <div class="personal-person-copy">
                                                <strong>{{ $user->name }}</strong>
                                                <span>{{ $user->proyectos->pluck('nombre')->join(', ') ?: 'Sin proyectos asignados' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Rol">
                                        @if($user->role === 'superadmin')
                                            <span class="personal-pill personal-pill--danger">Super Admin</span>
                                        @elseif($user->role === 'admin')
                                            <span class="personal-pill personal-pill--warning">Admin</span>
                                        @else
                                            <span class="personal-pill personal-pill--info">Usuario</span>
                                        @endif
                                    </td>
                                    <td data-label="Email">
                                        <span class="personal-email">{{ $user->email }}</span>
                                    </td>
                                    <td data-label="Teléfono">
                                        <span class="personal-muted">{{ $user->telefono ?? '—' }}</span>
                                    </td>
                                    <td data-label="Vinculación">
                                        @if($user->activo)
                                            <span class="personal-status personal-status--active">Plantilla Fija</span>
                                        @else
                                            <span class="personal-status personal-status--inactive">Personal Externo</span>
                                        @endif
                                    </td>
                                    <td data-label="Acciones" class="text-right">
                                        <div class="personal-actions">
                                            <a href="{{ route('personal.show', $user->id) }}" class="personal-action-icon" title="Ver">
                                                <i class="far fa-eye"></i>
                                            </a>
                                            @can('edit-user', $user)
                                                <a href="{{ route('users.edit', $user->id) }}" class="personal-action-icon" title="Editar">
                                                    <i class="far fa-pen-to-square"></i>
                                                </a>
                                            @endcan
                                            @can('edit-user', $user)
                                                <form action="{{ route('users.toggleActive', $user->id) }}" method="POST" class="personal-inline-form">
                                                    @csrf
                                                    <button type="submit" class="personal-action-icon personal-action-icon--toggle" title="Cambiar estado">
                                                        <i class="fas fa-power-off"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="personal-empty-state">
                                            <i class="fas fa-users-slash"></i>
                                            <strong>No hay personal para mostrar</strong>
                                            <span>Prueba a cambiar el buscador o crea un nuevo trabajador.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="personal-toolbar personal-toolbar-footer">
                    <span class="personal-toolbar-label">Mostrando {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} de {{ number_format($users->total(), 0, ',', '.') }} trabajadores</span>

                    @if ($users->hasPages())
                        <div class="personal-pagination">
                            {{ $users->onEachSide(1)->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </article>

            <section class="personal-footer-grid">
                <article class="personal-foot-card personal-foot-card--blue">
                    <div class="personal-foot-card__title">Acceso y control</div>
                    <strong>Panel seguro</strong>
                    <p>Solo usuarios autorizados pueden acceder a la gestión completa del personal.</p>
                </article>

                <article class="personal-foot-card personal-foot-card--gray">
                    <div class="personal-foot-card__title">Estado operativo</div>
                    <strong>Vista unificada</strong>
                    <p>Consulta nombres, roles, vinculación y acciones sin salir de esta pantalla.</p>
                </article>

                <article class="personal-foot-card personal-foot-card--orange">
                    <div class="personal-foot-card__title">Exportación</div>
                    <strong>CSV disponible</strong>
                    <p>Descarga el listado filtrado para compartirlo o revisarlo fuera del sistema.</p>
                </article>
            </section>
        </div>
    </section>
@endsection

@section('js')
    <script>
        (function(){
            const input = document.getElementById('q');
            if(!input) return;
            let timer = null;
            input.addEventListener('input', function(){
                clearTimeout(timer);
                timer = setTimeout(()=> document.querySelector('.personal-search-form').submit(), 450);
            });
        })();
    </script>
@endsection
