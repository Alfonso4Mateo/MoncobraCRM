@extends('adminlte::page')

@section('title', 'Gestión de Personal')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/personal-index.css'])
    <style>
        .personal-course-status,
        .personal-course-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 9px;
            margin: 0 6px 6px 0;
        }

        .personal-course-status--ok,
        .personal-course-pill--ok { background: #e8f5e9; color: #2e7d32; }
        .personal-course-status--warn,
        .personal-course-pill--warn { background: #fff8e1; color: #b8860b; }
        .personal-course-status--muted,
        .personal-course-pill--muted { background: #eef2f7; color: #667085; }
        .personal-course-cell { min-width: 220px; }
        .personal-course-list { margin-top: 6px; }
    </style>
@endsection

@section('content')
    <section class="personal-page">
        <header class="personal-hero">
            <div class="personal-hero-copy">
                <div class="personal-crumbs">GESTIÓN DE PERSONAL <span>•</span> LISTADO</div>
                <h1>Listado de Personal y Tallas</h1>
                <p>Consulta y administra el equipo de trabajo con información de equipamiento.</p>
            </div>

            <div class="personal-hero-actions">
                <a href="{{ route('cursos.index') }}" class="personal-action-btn personal-action-btn--soft" style="margin-right:12px;">
                    <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                    Catálogo de Cursos
                </a>

                <a href="{{ route('personal.tallas') }}" class="personal-action-btn personal-action-btn--soft" style="margin-right:12px;">
                    <i class="fas fa-tshirt" aria-hidden="true"></i>
                    Gestionar Tallas
                </a>

                <a href="{{ route('personal.create') }}" class="personal-primary-action">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    + Añadir Nuevo Trabajador
                </a>
            </div>
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
                        <p>
                            {{ $personals->total() }} trabajadores
                            @if(($avisosCount ?? 0) > 0)
                                &middot; <strong style="color:#c0392b;">{{ $avisosCount }} con aviso médico</strong>
                            @endif
                        </p>
                    </div>

                    <div class="personal-card__actions">
                        <form method="GET" action="{{ route('personal.index') }}" class="personal-recognition-form">
                            <input type="hidden" name="q" value="{{ $query ?? '' }}">
                            <input type="hidden" name="alerta_nombre" value="{{ $alertaNombre ?? 'any' }}">
                            <label for="alerta_dias" class="personal-recognition-label">Gestionar reconocimientos</label>
                            <div class="personal-recognition-control">
                                <i class="fas fa-stethoscope" aria-hidden="true"></i>
                                <select id="alerta_dias" name="alerta_dias" onchange="this.form.submit()">
                                    <option value="0" @selected(($alertaDias ?? 0) === 0)>Sin aviso</option>
                                    <option value="30" @selected(($alertaDias ?? 0) === 30)>Avisar en 30 días</option>
                                    <option value="60" @selected(($alertaDias ?? 0) === 60)>Avisar en 60 días</option>
                                    <option value="90" @selected(($alertaDias ?? 0) === 90)>Avisar en 90 días</option>
                                    <option value="120" @selected(($alertaDias ?? 0) === 120)>Avisar en 120 días</option>
                                    <option value="180" @selected(($alertaDias ?? 0) === 180)>Avisar en 180 días</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </header>

                <div class="personal-search-wrap">
                    <form method="GET" action="{{ route('personal.index') }}" class="personal-search-form">
                        <input type="hidden" name="alerta_dias" value="{{ $alertaDias ?? 0 }}">

                        <div class="personal-search-filters" style="margin-bottom:10px; display:flex; gap:12px; align-items:center;">
                            <label for="alerta_nombre" class="personal-recognition-label" style="margin:0;">Filtrar por aviso médico</label>
                            <div class="personal-recognition-control">
                                <select id="alerta_nombre" name="alerta_nombre" onchange="this.form.submit()">
                                    <option value="any" @selected(($alertaNombre ?? 'any') === 'any')>Todos</option>
                                    <option value="with" @selected(($alertaNombre ?? '') === 'with')>Con aviso</option>
                                    <option value="without" @selected(($alertaNombre ?? '') === 'without')>Sin aviso</option>
                                </select>
                            </div>
                        </div>

                        <div class="personal-search-field personal-search-field--search">
                            <label for="q"></label>
                            <input type="search" id="q" name="q" value="{{ $query ?? '' }}" placeholder="Buscar por nombre o apellidos..." autocomplete="on">
                        </div>
                    </form>
                </div>

                <div class="table-responsive personal-table-wrap">
                    <table class="table personal-table">
                        <thead>
                            <tr>
                                <th>ID RRHH</th>
                                <th>NOMBRE COMPLETO</th>
                                <th>DEPARTAMENTO</th>
                                <th>VINCULACIÓN</th>
                                <th>CAMISETA</th>
                                <th>CHAQUETA</th>
                                <th>SUDADERA</th>
                                <th>PANTALÓN</th>
                                <th>CALZADO</th>
                                <th>GUANTES</th>
                                <th>GAFAS</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($personals as $personal)
                                @php
                                    $alertaRevision = $personal->alerta_revision_medica ?? false;
                                @endphp
                                @php
                                    $initials = collect(explode(' ', trim($personal->name)))
                                        ->filter()
                                        ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                                        ->take(2)
                                        ->implode('');
                                    $userCode = str_pad((string) $personal->id_rrhh, 4, '0', STR_PAD_LEFT);
                                @endphp
                                <tr class="{{ $alertaRevision ? 'personal-row--alert' : '' }}">
                                    <td data-label="ID RRHH">
                                        <span class="personal-code">{{ $userCode }}</span>
                                    </td>
                                    <td data-label="NOMBRE COMPLETO">
                                        <div class="personal-person-cell">
                                            <span class="personal-avatar">{{ $initials ?: 'U' }}</span>
                                            <div class="personal-person-copy">
                                                <div class="personal-person-name">
                                                    <strong>{{ $personal->name }} {{ $personal->apellido }}</strong>
                                                    @if($alertaRevision)
                                                        <span class="personal-alert-icon" title="Revisión médica próxima">
                                                            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                                <span style="font-size: 12px; color: #999;">{{ $personal->dni_nie ?: ($personal->telefono ?: '—') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                   <td data-label="DEPARTAMENTO">
                                        @php
                                            $deptosActuales = is_string($personal->departamento) ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento) : (array) $personal->departamento;
                                        @endphp
                                        <span class="personal-muted">{{ !empty($deptosActuales) ? strtoupper(implode(', ', $deptosActuales)) : '—' }}</span>
                                    </td>
                                    <td data-label="VINCULACIÓN">
                                        @if($personal->activo)
                                            <span class="personal-status personal-status--active">Plantilla Fija</span>
                                        @else
                                            <span class="personal-status personal-status--inactive">Personal Externo</span>
                                        @endif
                                    </td>
                                    <td data-label="CAMISETA">
                                        <span class="personal-size-badge">{{ $personal->camiseta ?? '—' }}</span>
                                    </td>
                                    <td data-label="CHAQUETA">
                                        <span class="personal-size-badge">{{ $personal->chaqueta ?? '—' }}</span>
                                    </td>
                                    <td data-label="SUDADERA">
                                        <span class="personal-size-badge">{{ $personal->sudadera ?? '—' }}</span>
                                    </td>
                                    <td data-label="PANTALÓN">
                                        <span class="personal-size-badge">{{ $personal->pantalon ?? '—' }}</span>
                                    </td>
                                    <td data-label="CALZADO">
                                        <span class="personal-size-badge">{{ $personal->calzado ?? '—' }}</span>
                                    </td>
                                    <td data-label="GUANTES">
                                        <span class="personal-size-badge">{{ $personal->guantes ?? '—' }}</span>
                                    </td>
                                    <td data-label="GAFAS">
                                        <span class="personal-size-badge">{{ $personal->gafas ?? '—' }}</span>
                                    </td>
                                    <td data-label="ACCIONES" class="text-right">
                                        <div class="personal-actions">
                                            <a href="{{ route('personal.show', $personal->id) }}" class="personal-action-icon" title="Ver">
                                                <i class="far fa-eye"></i>
                                            </a>
                                            @can('manage-users')
                                                <a href="{{ route('personal.edit', $personal->id) }}" class="personal-action-icon" title="Editar">
                                                    <i class="far fa-pen-to-square"></i>
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12">
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
                    <span class="personal-toolbar-label">Mostrando {{ $personals->firstItem() ?? 0 }} - {{ $personals->lastItem() ?? 0 }} de {{ number_format($personals->total(), 0, ',', '.') }} trabajadores</span>

                    @if ($personals->hasPages())
                        <div class="personal-pagination">
                            {{ $personals->onEachSide(1)->links('pagination::bootstrap-4') }}
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
                timer = setTimeout(()=> document.querySelector('.personal-search-form').submit(), 800);
            });
        })();
    </script>
@endsection
