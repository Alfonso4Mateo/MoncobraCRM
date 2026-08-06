@extends('adminlte::page')

@section('title', 'Gestión de Cursos')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .cursos-hero,
        .cursos-shell,
        .cursos-panel { background: #fff; border: 1px solid #e7ecf3; border-radius: 18px; box-shadow: 0 16px 30px rgba(15, 23, 42, .06); }
        .cursos-hero { display: flex; justify-content: space-between; gap: 16px; padding: 20px 22px; margin-bottom: 16px; align-items: center; }
        .cursos-hero h1 { margin: 0 0 4px; font-size: 1.7rem; font-weight: 800; color: #173e67; }
        .cursos-hero p { margin: 0; color: #667085; font-size: .92rem; }
        .cursos-primary { display: inline-flex; align-items: center; gap: 10px; padding: 10px 16px; border-radius: 12px; background: linear-gradient(135deg, #173e67, #2f6b9c); color: #fff; text-decoration: none; font-weight: 800; }
        .cursos-shell { display: grid; grid-template-columns: 300px minmax(0, 1fr); gap: 16px; padding: 16px; }
        .cursos-sidebar { border-right: 1px solid #eef2f7; padding-right: 16px; }
        .cursos-main { min-width: 0; }
        .cursos-panel { padding: 14px; }
        .cursos-panel + .cursos-panel { margin-top: 12px; }
        .cursos-section-title { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 12px; }
        .cursos-section-title h3 { margin: 0; font-size: 1.05rem; font-weight: 800; color: #173e67; }
        .cursos-section-title p { margin: 0; color: #667085; font-size: .9rem; }
        .cursos-filter-row { display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px; }
        .cursos-filter-row input,
        .cursos-filter-row select,
        .cursos-local-search input { width: 100%; border: 1px solid #dbe3ef; border-radius: 12px; padding: 9px 12px; }
        .cursos-local-search { margin-bottom: 12px; }
        .personal-item {
            display: block;
            width: 100%;
            text-align: left;
            border: 1px solid #eef2f7;
            background: #f9fbfe;
            border-radius: 14px;
            padding: 10px 12px;
            margin-bottom: 8px;
            text-decoration: none;
            color: inherit;
        }
        .personal-item.is-active { border-color: #173e67; box-shadow: 0 0 0 3px rgba(23, 62, 103, .08); }
        .personal-item__top { display: flex; justify-content: space-between; gap: 8px; align-items: center; }
        .personal-item__name { font-weight: 800; color: #173e67; }
        .personal-item__meta { font-size: .82rem; color: #667085; margin-top: 2px; }
        .worker-strip {
            display: grid;
            grid-template-columns: minmax(180px, 1.2fr) minmax(120px, .7fr) minmax(180px, 1fr) minmax(120px, .5fr) minmax(120px, .4fr);
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid #e7ecf3;
            border-radius: 14px;
            background: #f9fbfe;
            align-items: center;
        }
        .worker-strip__item { min-width: 0; }
        .worker-strip__label { font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: #8a98ab; margin-bottom: 2px; }
        .worker-strip__value { font-size: .92rem; font-weight: 700; color: #173e67; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .worker-strip__value--muted { color: #667085; font-weight: 600; }
        .worker-status-pill {
            display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 9px; font-size: 11px; font-weight: 800;
        }
        .worker-status-pill--active { background: #e8f5e9; color: #2e7d32; }
        .worker-status-pill--inactive { background: #eef2f7; color: #667085; }
        .course-group { margin-bottom: 12px; }
        .course-group__head { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 8px; padding-top: 4px; }
        .course-group__head h4 { margin: 0; font-size: .9rem; font-weight: 800; color: #173e67; text-transform: uppercase; letter-spacing: .08em; }
        .course-group__head .course-card__badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 9px; font-size: 11px; font-weight: 800; background: #e8f5e9; color: #2e7d32; }
        .course-list { border: 1px solid #e7ecf3; border-radius: 14px; overflow: hidden; }
        
        /* Adjusted Grid for the entire row to give more space to controls */
        .course-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 40px minmax(0, 3fr);
            gap: 14px;
            align-items: center;
            padding: 10px 12px;
            border-top: 1px solid #edf2f7;
            background: #fff;
        }
        .course-row:first-child { border-top: 0; }
        .course-row__name { font-weight: 700; color: #173e67; line-height: 1.2; }
        .course-row__meta { font-size: .8rem; color: #667085; margin-top: 2px; }
        .course-row__count { justify-self: center; font-size: .85rem; font-weight: 800; color: #173e67; }
        .course-row__control { display: flex; align-items: center; gap: 10px; }
        
        .course-row__control-wrap {
            display: grid;
            grid-template-columns: 100px 70px 130px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            width: 100%;
        }

        .course-row__field {
            display: flex;
            flex-direction: column;
            width: 100%;
            min-width: 0; 
        }

        .course-row__field-label {
            font-size: .66rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 800;
            color: #8a98ab;
            margin-bottom: 4px;
        }
        .course-row__field input,
        .course-row__field select {
            width: 100%;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            padding: 7px 9px;
            font-size: .83rem;
            line-height: 1.15;
            background: #fff;
        }
        
        /* Adjusted Comment Textarea */
       .course-row__comment {
            width: 100%;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            padding: 7px 9px;
            font-size: .83rem;
            line-height: 1.15;
            min-height: 32px; 
            overflow: hidden;
            resize: none; 
            background: #fff;
            word-break: break-word;
            white-space: pre-wrap;
        }
        
        .course-toggle {
            position: relative;
            width: 48px;
            height: 26px;
            display: inline-block;
        }
        .course-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .course-toggle__track {
            position: absolute;
            inset: 0;
            background: #dbe3ef;
            border-radius: 999px;
            transition: all .18s ease;
        }
        .course-toggle__thumb {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .16);
            transition: all .18s ease;
        }
        .course-toggle input:checked + .course-toggle__track { background: #173e67; }
        .course-toggle input:checked + .course-toggle__track .course-toggle__thumb { transform: translateX(22px); }
        .course-row__assign-state { font-size: .75rem; font-weight: 800; color: #667085; }
        .course-row.is-assigned { background: #f8fbff; }
        .course-row.is-assigned .course-row__name { color: #0f5132; }
        .cursos-empty { padding: 14px; border: 1px dashed #dbe3ef; border-radius: 14px; color: #667085; text-align: center; }
        .cursos-main-header {
            padding: 12px 14px;
            border: 1px solid #e7ecf3;
            border-radius: 14px;
            background: #f9fbfe;
            margin-bottom: 12px;
        }

        /* Contenedor Flex para la etiqueta y el boton */
        .course-row__field-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        .course-row__field-header .course-row__field-label {
            margin-bottom: 0; 
        }
        
        /* Boton de guardado manual en la cabecera */
        .course-comment-save-btn {
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            width: 24px;
            height: 24px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }
        .course-comment-save-btn:hover {
            background: #059669;
        }
        .course-comment-save-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .course-row--caducado { border-left: 4px solid #ef4444 !important; background: #fffcfc; }
        .course-row--aviso { border-left: 4px solid #f59e0b !important; background: #fffefc; }

        .course-badge-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            margin-left: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            vertical-align: middle;
        }
        .course-badge-status--caducado { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .course-badge-status--aviso { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }

        /* 1. ESTILOS DEL ACORDEÓN Y CHIVATOS */
        .course-group__head {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s;
            padding: 8px 12px;
            border-radius: 8px;
        }
        .course-group__head:hover {
            background-color: #f8fafc;
        }
        .course-group__head-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .course-group__toggle-icon {
            transition: transform 0.3s ease;
            color: #8a98ab;
            font-size: 0.85rem;
        }
        /* Rota la flecha cuando está abierto */
        .course-group.is-open .course-group__toggle-icon {
            transform: rotate(180deg);
        }
        
        /* Truco CSS Grid para animación suave de altura */
        .course-list-wrapper {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.3s ease-in-out;
        }
        .course-list-wrapper > div {
            overflow: hidden;
        }
        .course-group.is-open .course-list-wrapper {
            grid-template-rows: 1fr;
        }
        .course-list {
            margin-top: 8px; /* Separación del título al abrir */
        }
        
        /* 2. ESTILOS DEL DASHBOARD DE INICIO (GRID DE TRABAJADORES) */
        .trabajadores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .trabajador-card {
            display: flex;
            flex-direction: column;
            background: #fff;
            border: 1px solid #e7ecf3;
            border-radius: 14px;
            padding: 16px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(15, 23, 42, 0.02);
        }
        .trabajador-card:hover {
            border-color: #173e67;
            box-shadow: 0 8px 15px rgba(15, 23, 42, 0.05);
            transform: translateY(-2px);
        }
        .trabajador-card__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .trabajador-card__name {
            font-size: 1.1rem;
            font-weight: 800;
            color: #173e67;
            margin: 0;
            line-height: 1.2;
        }
        .trabajador-card__depto {
            font-size: 0.85rem;
            color: #667085;
            margin-top: 4px;
        }
        .trabajador-card__stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px dashed #eef2f7;
        }
        .trabajador-card__alerts {
            display: flex;
            gap: 6px;
        }
        /* Modificador para que el panel principal ocupe todo el ancho si no hay sidebar */
        .cursos-shell--directory {
            grid-template-columns: 1fr !important;
        }

        @media (max-width: 1100px) {
            .cursos-shell { grid-template-columns: 1fr; }
            .cursos-sidebar { border-right: 0; padding-right: 0; border-bottom: 1px solid #eef2f7; padding-bottom: 18px; }
            .worker-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .course-row { grid-template-columns: minmax(0, 1fr) 40px minmax(0, 2fr); }
        }
        @media (max-width: 720px) {
            .worker-strip { grid-template-columns: 1fr; }
            .course-row { grid-template-columns: 1fr; gap: 8px; }
            .course-row__count { justify-self: start; }
            .course-row__control-wrap { grid-template-columns: 1fr; } /* Stack vertically on small screens */
        }
    </style>
@endsection

@section('content')
    <section class="cursos-page">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <header class="cursos-hero">
            <div>
                <div style="font-size:.75rem;font-weight:800;letter-spacing:.12em;color:#8a98ab;text-transform:uppercase;">Gestión de formación</div>
                <h1>Catálogo de Cursos</h1>
                <p>Centraliza la formación preventiva y técnica del personal.</p>
            </div>

            <!-- NUEVO CONTENEDOR PARA AGRUPAR LOS BOTONES -->
            <div style="display: flex; gap: 12px; align-items: center;">
                <a href="{{ route('cursos.create') }}" class="cursos-primary">
                    <i class="fas fa-graduation-cap"></i>
                    Nuevo Curso
                </a>

                <a href="{{ route('cursos.gestion') }}" class="cursos-primary" style="background: linear-gradient(135deg, #0f5132, #1f7a4d);">
                    <i class="fas fa-pen-to-square"></i>
                    Gestionar Cursos
                </a>
            </div>
        </header>

        <section class="cursos-shell {{ !$selectedPersonal ? 'cursos-shell--directory' : '' }}">
            
            @if($selectedPersonal)
                <aside class="cursos-sidebar">
                    <div class="cursos-section-title">
                        <div>
                            <h3>Trabajadores</h3>
                            <p>Selecciona un personal para ver su formación</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('cursos.index') }}" class="cursos-filter-row">
                        <select name="categoria" onchange="this.form.submit()">
                            <option value="all" @selected($categoria === 'all')>Todas las categorías</option>
                            @foreach($categorias as $categoriaOption)
                                <option value="{{ $categoriaOption }}" @selected($categoria === $categoriaOption)>{{ $categoriaOption }}</option>
                            @endforeach
                        </select>
                        <input type="search" name="q" value="{{ $query }}" placeholder="Buscar trabajador...">
                    </form>

                @forelse($personals as $personal)
                    @php
                        $nombreCompleto = trim($personal->name . ' ' . $personal->apellido);
                        $cursosCount = $personal->cursos->count();
                        $cursosAptos = $personal->cursos->filter(fn ($curso) => (bool) ($curso->pivot->apto ?? false))->count();
                        
                        // LÓGICA DE ALERTAS PARA LA BARRA LATERAL (AHORA NO EXCLUYENTES)
                        $hasCaducado = false;
                        $hasAviso = false;
                        $hoy = \Carbon\Carbon::now()->startOfDay();

                        foreach ($personal->cursos as $c) {
                            if (!empty($c->pivot->fecha_realizacion) && $c->meses_validez) {
                                $fRealizacion = \Carbon\Carbon::parse($c->pivot->fecha_realizacion)->startOfDay();
                                $fCaducidad = $fRealizacion->copy()->addMonths($c->meses_validez);
                                $fAviso = $fCaducidad->copy()->subDays($c->dias_aviso_previo ?? 30);

                                if ($hoy->gt($fCaducidad)) {
                                    $hasCaducado = true;
                                } elseif ($hoy->gte($fAviso)) {
                                    $hasAviso = true;
                                }
                                
                                // Optimizacion: Si ya hemos detectado ambos problemas, no hace falta seguir buscando
                                if ($hasCaducado && $hasAviso) {
                                    break; 
                                }
                            }
                        }
                    @endphp
                    
                    <a href="{{ route('cursos.index', ['personal_id' => $personal->id, 'categoria' => $categoria, 'q' => $query]) }}" class="personal-item {{ (int) $personal->id === (int) optional($selectedPersonal)->id ? 'is-active' : '' }}">
                        <div class="personal-item__top">
                            <div class="personal-item__name">
                                {{ $nombreCompleto }}
                                
                                <!-- ICONOS DE ALERTA INDEPENDIENTES -->
                                @if($hasCaducado)
                                    <i class="fas fa-exclamation-circle" style="color: #ef4444; margin-left: 4px;" title="Tiene cursos caducados"></i>
                                @endif
                                
                                @if($hasAviso)
                                    <i class="fas fa-exclamation-triangle" style="color: #f59e0b; margin-left: 4px;" title="Tiene cursos próximos a caducar"></i>
                                @endif
                            </div>
                            
                            <span class="course-card__badge {{ $cursosCount === $cursosAptos ? '' : 'course-card__badge--warn' }}">
                                {{ $cursosAptos }}/{{ $cursosCount ?: 0 }}
                            </span>
                        </div>
                        <div class="personal-item__meta">{{ $personal->dni_nie ?: ($personal->telefono ?: 'Sin identificador') }}</div>
                        <div class="personal-item__meta">
                            {{ $personal->departamento ? implode(', ', (array) $personal->departamento) : 'Sin departamento' }}
                        </div>
                    </a>
               @empty
                        <div class="cursos-empty">No hay trabajadores para mostrar.</div>
                    @endforelse
                </aside>
            @endif

            <main class="cursos-main">
               <div class="cursos-panel">
                    @if(!$selectedPersonal)
                        <!-- ========================================== -->
                        <!-- PANTALLA DE INICIO: DIRECTORIO DE PLANTILLA-->
                        <!-- ========================================== -->
                        <div class="cursos-section-title" style="margin-bottom: 20px;">
                            <div>
                                <h3>Directorio de Plantilla</h3>
                                <p>Selecciona un trabajador de la lista para gestionar sus certificaciones y visualizar alertas activas.</p>
                            </div>
                        </div>

                        <div class="trabajadores-grid">
                            @forelse($personals as $personal)
                                @php
                                    $nombreCompleto = trim($personal->name . ' ' . $personal->apellido);
                                    $cursosCount = $personal->cursos->count();
                                    
                                    // Calculamos las alertas para la tarjeta visual
                                    $hasCaducado = false;
                                    $hasAviso = false;
                                    $hoy = \Carbon\Carbon::now()->startOfDay();

                                    foreach ($personal->cursos as $c) {
                                        if (!empty($c->pivot->fecha_realizacion) && $c->meses_validez) {
                                            $fRealizacion = \Carbon\Carbon::parse($c->pivot->fecha_realizacion)->startOfDay();
                                            $fCaducidad = $fRealizacion->copy()->addMonths($c->meses_validez);
                                            $fAviso = $fCaducidad->copy()->subDays($c->dias_aviso_previo ?? 30);

                                            if ($hoy->gt($fCaducidad)) {
                                                $hasCaducado = true;
                                            } elseif ($hoy->gte($fAviso)) {
                                                $hasAviso = true;
                                            }
                                            if ($hasCaducado && $hasAviso) break; // Optimización
                                        }
                                    }
                                    
                                    $deptosActuales = is_string($personal->departamento)
                                        ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento)
                                        : (array) $personal->departamento;
                                @endphp

                                <a href="{{ route('cursos.index', ['personal_id' => $personal->id, 'categoria' => $categoria, 'q' => $query]) }}" class="trabajador-card">
                                    <div class="trabajador-card__header">
                                        <div>
                                            <h4 class="trabajador-card__name">{{ $nombreCompleto }}</h4>
                                            <div class="trabajador-card__depto">{{ !empty($deptosActuales) ? implode(', ', $deptosActuales) : 'Sin departamento' }}</div>
                                        </div>
                                        <span class="worker-status-pill {{ $personal->activo ? 'worker-status-pill--active' : 'worker-status-pill--inactive' }}">
                                            {{ $personal->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                    <div class="trabajador-card__stats">
                                        <span style="font-size: 0.85rem; font-weight: 700; color: #667085;">{{ $cursosCount }} cursos asignados</span>
                                        <div class="trabajador-card__alerts">
                                            @if($hasCaducado)
                                                <span class="course-badge-status course-badge-status--caducado" style="margin-left:0;" title="Cursos caducados"><i class="fas fa-exclamation-circle"></i></span>
                                            @endif
                                            @if($hasAviso)
                                                <span class="course-badge-status course-badge-status--aviso" style="margin-left:0;" title="Cursos por renovar"><i class="fas fa-exclamation-triangle"></i></span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="cursos-empty" style="grid-column: 1 / -1;">No se encontraron trabajadores con los filtros actuales.</div>
                            @endforelse
                        </div>

                    @else
                        <!-- ========================================== -->
                        <!-- VISTA DE DETALLE: TRABAJADOR Y ACORDEONES  -->
                        <!-- ========================================== -->
                        @php
                            $deptosActuales = is_string($selectedPersonal->departamento)
                                ? json_decode($selectedPersonal->departamento, true) ?? explode(',', $selectedPersonal->departamento)
                                : (array) $selectedPersonal->departamento;
                        @endphp

                        <!-- NUEVO BOTÓN: Volver al directorio -->
                        <div style="margin-bottom: 16px;">
                            <a href="{{ route('cursos.index') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; background: #fff; border: 1px solid #dbe3ef; border-radius: 10px; color: #173e67; text-decoration: none; font-weight: 700; font-size: 0.85rem; box-shadow: 0 2px 4px rgba(15,23,42,0.02); transition: all 0.2s;">
                                <i class="fas fa-arrow-left"></i> Volver al directorio
                            </a>
                        </div>

                        <div class="cursos-main-header">
                            <div class="worker-strip">
                                <div class="worker-strip__item">
                                    <div class="worker-strip__label">Trabajador</div>
                                    <div class="worker-strip__value">{{ $selectedPersonal->name }} {{ $selectedPersonal->apellido }}</div>
                                </div>

                                <div class="worker-strip__item">
                                    <div class="worker-strip__label">DNI/NIE</div>
                                    <div class="worker-strip__value {{ $selectedPersonal->dni_nie ? '' : 'worker-strip__value--muted' }}">{{ $selectedPersonal->dni_nie ?: '—' }}</div>
                                </div>

                                <div class="worker-strip__item">
                                    <div class="worker-strip__label">Departamento</div>
                                    <div class="worker-strip__value {{ !empty($deptosActuales) ? '' : 'worker-strip__value--muted' }}">{{ !empty($deptosActuales) ? implode(', ', $deptosActuales) : '—' }}</div>
                                </div>

                                <div class="worker-strip__item">
                                    <div class="worker-strip__label">Estado</div>
                                    <div class="worker-strip__value">
                                        <span class="worker-status-pill {{ $selectedPersonal->activo ? 'worker-status-pill--active' : 'worker-status-pill--inactive' }}">
                                            {{ $selectedPersonal->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="worker-strip__item">
                                    <div class="worker-strip__label">Cursos</div>
                                    <div class="worker-strip__value">{{ $selectedPersonal->cursos->count() }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="cursos-section-title" style="margin-bottom:10px;">
                            <div>
                                <h3>Cursos por categoría</h3>
                                <p>La categoría aparece una sola vez y cada curso se gestiona en una fila compacta.</p>
                            </div>
                        </div>

                        <div class="cursos-local-search">
                            <input type="search" id="course-local-search" placeholder="Buscar curso por nombre..." autocomplete="off">
                        </div>

                        <div id="course-list">
                            @forelse($cursosPorCategoria as $categoriaNombre => $cursosGrupo)
                                @php
                                    $catHasCaducado = false;
                                    $catHasAviso = false;
                                    $catHasAssigned = false;
                                    $hoy = \Carbon\Carbon::now()->startOfDay();

                                    foreach($cursosGrupo as $c) {
                                        $asignadoCheck = $selectedPersonal ? $selectedPersonal->cursos->firstWhere('id', $c->id) : null;
                                        
                                        if ($asignadoCheck) {
                                            $catHasAssigned = true; 
                                            
                                            if (!empty($asignadoCheck->pivot->fecha_realizacion) && $c->meses_validez) {
                                                $fRealizacion = \Carbon\Carbon::parse($asignadoCheck->pivot->fecha_realizacion)->startOfDay();
                                                $fCaducidad = $fRealizacion->copy()->addMonths($c->meses_validez);
                                                $fAviso = $fCaducidad->copy()->subDays($c->dias_aviso_previo ?? 30);

                                                if ($hoy->gt($fCaducidad)) {
                                                    $catHasCaducado = true;
                                                } elseif ($hoy->gte($fAviso)) {
                                                    $catHasAviso = true;
                                                }
                                            }
                                        }
                                    }
                                    
                                    $isOpenClass = $catHasAssigned ? 'is-open' : '';
                                @endphp

                                <section class="course-group course-group--filterable {{ $isOpenClass }}" data-category="{{ $categoriaNombre }}">
                                    <div class="course-group__head" onclick="this.closest('.course-group').classList.toggle('is-open')">
                                        <div class="course-group__head-left">
                                            <i class="fas fa-chevron-down course-group__toggle-icon"></i>
                                            <h4>{{ $categoriaNombre }}</h4>
                                            
                                            @if($catHasCaducado)
                                                <i class="fas fa-exclamation-circle" style="color: #ef4444;" title="Contiene cursos caducados"></i>
                                            @endif
                                            @if($catHasAviso)
                                                <i class="fas fa-exclamation-triangle" style="color: #f59e0b;" title="Contiene cursos próximos a caducar"></i>
                                            @endif
                                        </div>
                                        <span class="course-card__badge">{{ $cursosGrupo->count() }} cursos</span>
                                    </div>

                                    <div class="course-list-wrapper">
                                        <div>
                                            <div class="course-list">
                                                @foreach($cursosGrupo as $curso)
                                                    @php
                                                        $asignado = $selectedPersonal ? $selectedPersonal->cursos->firstWhere('id', $curso->id) : null;
                                                        $descripcion = trim((string) $curso->descripcion);

                                                        $estadoCaducidad = null;
                                                        if ($asignado && !empty($asignado->pivot->fecha_realizacion) && $curso->meses_validez) {
                                                            $fechaRealizacion = \Carbon\Carbon::parse($asignado->pivot->fecha_realizacion)->startOfDay();
                                                            $fechaCaducidad = $fechaRealizacion->copy()->addMonths($curso->meses_validez);
                                                            $fechaAviso = $fechaCaducidad->copy()->subDays($curso->dias_aviso_previo ?? 30);

                                                            if ($hoy->gt($fechaCaducidad)) {
                                                                $estadoCaducidad = 'caducado';
                                                            } elseif ($hoy->gte($fechaAviso)) {
                                                                $estadoCaducidad = 'aviso';
                                                            }
                                                        }
                                                        
                                                        $rowClasses = $asignado ? 'is-assigned' : '';
                                                        if ($estadoCaducidad === 'caducado') $rowClasses .= ' course-row--caducado';
                                                        if ($estadoCaducidad === 'aviso') $rowClasses .= ' course-row--aviso';
                                                    @endphp
                                                    
                                                    <div class="course-row {{ $rowClasses }}" data-course-name="{{ mb_strtolower($curso->nombre) }}">
                                                        <div>
                                                            <div class="course-row__name">
                                                                {{ $curso->nombre }}
                                                                @if($estadoCaducidad === 'caducado')
                                                                    <span class="course-badge-status course-badge-status--caducado" title="Caducó el {{ $fechaCaducidad->format('d/m/Y') }}">
                                                                        <i class="fas fa-exclamation-circle"></i> Caducado
                                                                    </span>
                                                                @elseif($estadoCaducidad === 'aviso')
                                                                    <span class="course-badge-status course-badge-status--aviso" title="Caduca el {{ $fechaCaducidad->format('d/m/Y') }}">
                                                                        <i class="fas fa-exclamation-triangle"></i> Renovar pronto
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @if($descripcion !== '')
                                                                <div class="course-row__meta">{{ \Illuminate\Support\Str::limit($descripcion, 100) }}</div>
                                                            @endif
                                                        </div>

                                                        <div class="course-row__count">
                                                            {{ $curso->personal_count }}
                                                        </div>

                                                        <div class="course-row__control-wrap">
                                                            <div class="course-row__control">
                                                                <span class="course-row__assign-state">{{ $asignado ? 'Asignado' : 'Disponible' }}</span>
                                                                <label class="course-toggle" title="Asignar o quitar curso">
                                                                    <input type="checkbox" class="course-toggle-input" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" data-assigned="{{ $asignado ? '1' : '0' }}" @checked($asignado)>
                                                                    <span class="course-toggle__track"><span class="course-toggle__thumb"></span></span>
                                                                </label>
                                                            </div>

                                                            <div class="course-row__field">
                                                                <div class="course-row__field-label">Apto</div>
                                                                <select class="course-apto-select" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}">
                                                                    <option value="1" @selected((bool) ($asignado->pivot->apto ?? false))>Sí</option>
                                                                    <option value="0" @selected($asignado && ! (bool) ($asignado->pivot->apto ?? false))>No</option>
                                                                </select>
                                                            </div>

                                                            <div class="course-row__field">
                                                                <div class="course-row__field-label">Fecha</div>
                                                                <input type="date" class="fecha-realizacion-input" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" value="{{ $asignado && !empty($asignado->pivot->fecha_realizacion) ? \Carbon\Carbon::parse($asignado->pivot->fecha_realizacion)->format('Y-m-d') : '' }}">
                                                            </div>

                                                            <div class="course-row__field">
                                                                <div class="course-row__field-header">
                                                                    <div class="course-row__field-label">Comentario</div>
                                                                    <button type="button" class="course-comment-save-btn" title="Guardar comentario" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}">
                                                                        <i class="fas fa-check"></i>
                                                                    </button>
                                                                </div>
                                                                <textarea class="course-row__comment course-comment-input" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" rows="1" placeholder="Comentario breve...">{{ $asignado ? ($asignado->pivot->descripcion_aptitud ?? '') : '' }}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            @empty
                                <div class="cursos-empty">No hay cursos para la categoría seleccionada.</div>
                            @endforelse
                        </div>
                    @endif
                </div>
            </main>
        </section>
    </section>
@endsection

@section('js')
    <script>
        (function () {
            const searchInput = document.getElementById('course-local-search');
            const courseRows = Array.from(document.querySelectorAll('[data-course-name]'));
            const courseGroups = Array.from(document.querySelectorAll('.course-group--filterable'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const toggleEmptyGroups = () => {
                courseGroups.forEach((group) => {
                    const visibleRows = Array.from(group.querySelectorAll('[data-course-name]')).filter((row) => !row.hidden);
                    group.hidden = visibleRows.length === 0;
                });
            };

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const term = this.value.trim().toLowerCase();

                    courseRows.forEach((row) => {
                        const name = row.getAttribute('data-course-name') || '';
                        row.hidden = term !== '' && !name.includes(term);
                    });

                    // SI ESTÁ BUSCANDO ALGO, ABRIMOS LOS ACORDEONES AUTOMÁTICAMENTE
                    if (term !== '') courseGroups.forEach(group => group.classList.add('is-open'));

                    toggleEmptyGroups();
                });
            }

           // Función centralizada para guardar datos extra con lectura de token en vivo
            const persistExtraData = async (personalId, cursoId, payload, row) => {
                // Leemos el token CSRF fresco
                const liveToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || csrfToken;
                
                // 1. RECOPILAMOS TODOS LOS DATOS DE LA FILA SIEMPRE
                const dateInput = row ? row.querySelector('.fecha-realizacion-input') : null;
                const aptoSelect = row ? row.querySelector('.course-apto-select') : null;
                const commentInput = row ? row.querySelector('.course-comment-input') : null;

                const response = await fetch(`{{ url('personal') }}/${personalId}/cursos`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': liveToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    // 2. ENVIAMOS LA "FOTO COMPLETA" DE LA FILA
                    body: JSON.stringify({
                        curso_id: cursoId,
                        fecha_realizacion: dateInput && dateInput.value ? dateInput.value : null,
                        apto: aptoSelect ? aptoSelect.value : 1,
                        descripcion_aptitud: commentInput ? commentInput.value : '',
                        ...payload, // Si el usuario acaba de tocar la fecha, este payload sobrescribe el valor para asegurar que va lo último
                    }),
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }
            };

            // 1. Lógica del interruptor (Asignar / Desasignar)
            document.querySelectorAll('.course-toggle-input').forEach((input) => {
                input.addEventListener('change', async function () {
                    const personalId = this.getAttribute('data-personal-id');
                    const cursoId = this.getAttribute('data-curso-id');
                    const assigned = this.checked;
                    const row = this.closest('.course-row');
                    const previousState = this.getAttribute('data-assigned') === '1';

                    if (!personalId || !cursoId) return;

                    // Capturamos la fecha actual o asignamos la de hoy si se está activando el curso
                    let fechaRealizacion = null;
                    const dateInput = row ? row.querySelector('.fecha-realizacion-input') : null;
                    
                    if (dateInput) {
                        if (assigned && !dateInput.value) {
                            // Autocompletar con la fecha de hoy visualmente
                            dateInput.value = new Date().toISOString().slice(0, 10);
                        }
                        fechaRealizacion = dateInput.value;
                    }

                    try {
                        const response = await fetch(
                            assigned
                                ? `{{ url('personal') }}/${personalId}/cursos`
                                : `{{ url('personal') }}/${personalId}/cursos/${cursoId}`,
                            {
                                method: assigned ? 'PUT' : 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Content-Type': 'application/json',
                                },
                                body: assigned ? JSON.stringify({
                                    curso_id: cursoId,
                                    fecha_realizacion: fechaRealizacion,
                                    apto: row.querySelector('.course-apto-select')?.value || 1,
                                    descripcion_aptitud: row.querySelector('.course-comment-input')?.value || '',
                                }) : null,
                            }
                        );

                        if (!response.ok) {
                            throw new Error('Request failed');
                        }

                        this.setAttribute('data-assigned', assigned ? '1' : '0');

                        if (row) {
                            row.classList.toggle('is-assigned', assigned);
                        }
                    } catch (error) {
                        this.checked = previousState;
                        alert('No se pudo actualizar la asignación del curso.');
                    }
                });
            });

            // 2. NUEVO: Lógica para autoguardar cuando se cambia la fecha manualmente
          // Escuchamos cuando cambia la fecha
            document.querySelectorAll('.fecha-realizacion-input').forEach(input => {
                input.addEventListener('change', async function() {
                    const row = this.closest('.course-row');
                    
                    // AHORA LEEMOS LOS IDs DIRECTAMENTE DEL INPUT
                    const personalId = this.dataset.personalId;
                    const cursoId = this.dataset.cursoId;
                    const fechaValue = this.value; 

                    try {
                        await persistExtraData(personalId, cursoId, {
                            fecha_realizacion: fechaValue
                        }, row);
                        
                        // Opcional: Feedback visual de éxito
                        this.classList.add('border-green-500');
                        setTimeout(() => this.classList.remove('border-green-500'), 2000);
                    } catch (error) {
                        console.error('Error al guardar la fecha:', error);
                        alert('No se pudo actualizar la fecha del curso.');
                    }
                });
            });

            // 3. Lógica del selector APTO
            document.querySelectorAll('.course-apto-select').forEach((select) => {
                select.addEventListener('change', async function () {
                    const row = this.closest('.course-row');
                    const toggleInput = row.querySelector('.course-toggle-input');
                    const personalId = toggleInput.getAttribute('data-personal-id');
                    const cursoId = toggleInput.getAttribute('data-curso-id');

                    try {
                        await persistExtraData(personalId, cursoId, {
                            apto: this.value,
                            descripcion_aptitud: row.querySelector('.course-comment-input')?.value || '',
                        }, row);
                    } catch (error) {
                        alert('No se pudo actualizar el estado apto del curso.');
                    }
                });
            });

            // 4. Lógica para auto-expandir el textarea (SIN guardar)
            const autoResizeTextarea = (textarea) => {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            };

            document.querySelectorAll('.course-comment-input').forEach((input) => {
                if(input.value.trim() !== '') {
                    autoResizeTextarea(input);
                }
                input.addEventListener('input', function () {
                    autoResizeTextarea(this);
                });
            });

            // 5. Lógica para guardar cuando se pulsa el botón verde
            document.querySelectorAll('.course-comment-save-btn').forEach((btn) => {
                btn.addEventListener('click', async function () {
                    const row = this.closest('.course-row');
                    const toggleInput = row.querySelector('.course-toggle-input');
                    
                    if (toggleInput && !toggleInput.checked) {
                        alert('Por favor, asigna primero el curso activando el interruptor para poder guardar un comentario.');
                        return;
                    }

                    const personalId = toggleInput.getAttribute('data-personal-id');
                    const cursoId = toggleInput.getAttribute('data-curso-id');
                    const textarea = row.querySelector('.course-comment-input');
                    const aptoSelect = row.querySelector('.course-apto-select');
                    
                    const originalIcon = this.innerHTML;

                    try {
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                        this.disabled = true;

                        await persistExtraData(personalId, cursoId, {
                            apto: aptoSelect ? aptoSelect.value : 1,
                            descripcion_aptitud: textarea.value,
                        }, row);

                        this.innerHTML = '<i class="fas fa-check-double"></i>';
                        setTimeout(() => {
                            this.innerHTML = originalIcon;
                            this.disabled = false;
                        }, 1200);

                    } catch (error) {
                        console.error('Error al guardar el comentario:', error);
                        this.innerHTML = '<i class="fas fa-times"></i>';
                        this.style.background = '#ef4444';
                        setTimeout(() => {
                            this.innerHTML = originalIcon;
                            this.style.background = '';
                            this.disabled = false;
                        }, 2000);
                    }
                });
            });
        })();
    </script>
@endsection