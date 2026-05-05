@extends('adminlte::page')

@section('title', 'Gestión de Personal')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/personal-index.css'])
@endsection

@section('content')
    <section class="personal-page">
        <header class="personal-hero">
            <div class="personal-hero-copy">
                <div class="personal-crumbs">GESTIÓN DE PERSONAL <span>•</span> LISTADO</div>
                <h1>Listado de Personal y Tallas</h1>
                <p>Consulta y administra el equipo de trabajo con información de equipamiento.</p>
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
            <article class="personal-card">
                <header class="personal-card__header">
                    <div>
                        <h3>Registro general de personal</h3>
                        <p>{{ $users->total() }} trabajadores</p>
                    </div>

                    <div class="personal-card__actions">
                    </div>
                </header>

                <div class="personal-search-wrap">
                    <form method="GET" action="{{ route('personal.index') }}" class="personal-search-form">
                        <div class="personal-search-field personal-search-field--search">
                            <label for="q"></label>
                            <input type="search" id="q" name="q" value="{{ $query ?? '' }}" placeholder="Buscar por nombre o apellidos..." autocomplete="off">
                        </div>
                    </form>
                </div>

                <div class="table-responsive personal-table-wrap">
                    <table class="table personal-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>NOMBRE COMPLETO</th>
                                <th>DEPARTAMENTO</th>
                                <th>VINCULACIÓN</th>
                                <th>CAMISETA</th>
                                <th>CHAQUETA</th>
                                <th>SUDADERA</th>
                                <th>PANTALÓN</th>
                                <th>CALZADO</th>
                                <th>GUANTES</th>
                                <th>ACCIONES</th>
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
                                    $userCode = str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
                                @endphp
                                <tr>
                                    <td data-label="ID">
                                        <span class="personal-code">{{ $userCode }}</span>
                                    </td>
                                    <td data-label="NOMBRE COMPLETO">
                                        <div class="personal-person-cell">
                                            <span class="personal-avatar">{{ $initials ?: 'U' }}</span>
                                            <div class="personal-person-copy">
                                                <strong>{{ $user->name }} {{ $user->apellido }}</strong>
                                                <span style="font-size: 12px; color: #999;">{{ $user->email ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="DEPARTAMENTO">
                                        <span class="personal-muted">{{ $user->departamento ?? '—' }}</span>
                                    </td>
                                    <td data-label="VINCULACIÓN">
                                        @if($user->activo)
                                            <span class="personal-status personal-status--active">Plantilla Fija</span>
                                        @else
                                            <span class="personal-status personal-status--inactive">Personal Externo</span>
                                        @endif
                                    </td>
                                    <td data-label="CAMISETA">
                                        <span class="personal-size-badge">{{ $user->camiseta ?? '—' }}</span>
                                    </td>
                                    <td data-label="CHAQUETA">
                                        <span class="personal-size-badge">{{ $user->chaqueta ?? '—' }}</span>
                                    </td>
                                    <td data-label="SUDADERA">
                                        <span class="personal-size-badge">{{ $user->sudadera ?? '—' }}</span>
                                    </td>
                                    <td data-label="PANTALÓN">
                                        <span class="personal-size-badge">{{ $user->pantalon ?? '—' }}</span>
                                    </td>
                                    <td data-label="CALZADO">
                                        <span class="personal-size-badge">{{ $user->calzado ?? '—' }}</span>
                                    </td>
                                    <td data-label="GUANTES">
                                        <span class="personal-size-badge">{{ $user->guantes ?? '—' }}</span>
                                    </td>
                                    <td data-label="ACCIONES" class="text-right">
                                        <div class="personal-actions">
                                            <a href="{{ route('personal.show', $user->id) }}" class="personal-action-icon" title="Ver">
                                                <i class="far fa-eye"></i>
                                            </a>
                                            @can('edit-user', $user)
                                                <a href="{{ route('personal.edit', $user->id) }}" class="personal-action-icon" title="Editar">
                                                    <i class="far fa-pen-to-square"></i>
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11">
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
                    <div class="personal-foot-card__title">
                        <i class="fas fa-file-shield"></i>
                        Permisos Pendientes EPIS
                    </div>
                    <strong class="personal-foot-card__value">24</strong>
                    <p>Equipos sobre revisar</p>
                </article>

                <article class="personal-foot-card personal-foot-card--orange">
                    <div class="personal-foot-card__title">
                        <i class="fas fa-ruler-combined"></i>
                        Tallas No Registradas
                    </div>
                    <strong class="personal-foot-card__value">12</strong>
                    <p>A completar registro</p>
                </article>

                <article class="personal-foot-card personal-foot-card--navy">
                    <div class="personal-foot-card__title">
                        <i class="fas fa-calendar-check"></i>
                        Próxima Revisión
                    </div>
                    <strong class="personal-foot-card__value">SEP 2025</strong>
                    <p>Vencimiento Programado</p>
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
