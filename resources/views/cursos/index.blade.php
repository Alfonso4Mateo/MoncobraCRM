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
            grid-template-columns: minmax(150px, 1.2fr) minmax(90px, .7fr) minmax(130px, 1fr) minmax(100px, .8fr) minmax(160px, 1.2fr) minmax(80px, .5fr) minmax(70px, .4fr);
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
        
        .course-row {
            display: grid;
            grid-template-columns: minmax(220px, 1.2fr) minmax(0, 3.8fr); 
            gap: 20px; 
            align-items: start; 
            padding: 16px 14px;
            border-top: 1px solid #edf2f7;
            background: #fff;
        }

        .course-row:first-child { border-top: 0; }
        .course-row__name { font-weight: 800; color: #173e67; line-height: 1.3; font-size: 0.95rem; }
        .course-row__meta { font-size: .8rem; color: #64748b; margin-top: 4px; }
        
        .course-row__control-wrap {
            display: grid;
            grid-template-columns: 140px 75px 135px 110px minmax(180px, 1fr);
            gap: 16px;
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
            font-size: .65rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            font-weight: 800;
            color: #8a98ab;
            margin-bottom: 6px;
            height: 14px;
        }
        
        .course-row__field input,
        .course-row__field select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0 10px;
            font-size: .85rem;
            font-weight: 600;
            color: #1e293b;
            background: #f8fafc;
            height: 34px; 
            transition: all 0.2s;
        }
        .course-row__field input:focus:not(:disabled), .course-row__field select:focus:not(:disabled) { border-color: #3b82f6; background: #fff; outline: none; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .course-row__field input:disabled, .course-row__field select:disabled { cursor: not-allowed; background: #f1f5f9; color: #94a3b8; }
        
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
        .course-row__comment[readonly] { background: #f1f5f9; color: #64748b; cursor: not-allowed; }
        
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
        .cursos-main-header { padding: 12px 14px; border: 1px solid #e7ecf3; border-radius: 14px; background: #f9fbfe; margin-bottom: 12px; }

        .course-row__field-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
        .course-row__field-header .course-row__field-label { margin-bottom: 0; }
        
        .course-comment-save-btn { background: #10b981; color: white; border: none; border-radius: 6px; width: 24px; height: 24px; font-size: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s; }
        .course-comment-save-btn:hover { background: #059669; }
        .course-comment-save-btn:disabled { background: #9ca3af; cursor: not-allowed; }

        .course-row--caducado { border-left: 4px solid #ef4444 !important; background: #fffcfc; }
        .course-row--aviso { border-left: 4px solid #f59e0b !important; background: #fffefc; }

        .course-badge-status { display: inline-flex; align-items: center; gap: 4px; font-size: 0.65rem; font-weight: 800; padding: 3px 8px; border-radius: 6px; margin-left: 8px; text-transform: uppercase; letter-spacing: 0.05em; vertical-align: middle; }
        .course-badge-status--caducado { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .course-badge-status--aviso { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }

        .course-group__head { cursor: pointer; user-select: none; transition: background-color 0.2s; padding: 8px 12px; border-radius: 8px; }
        .course-group__head:hover { background: #f8fafc; }
        .course-group__head-left { display: flex; align-items: center; gap: 10px; }
        .course-group__toggle-icon { transition: transform 0.3s ease; color: #8a98ab; font-size: 0.85rem; }
        .course-group.is-open .course-group__toggle-icon { transform: rotate(180deg); }
        
        .course-list-wrapper { display: grid; grid-template-rows: 0fr; transition: grid-template-rows 0.3s ease-in-out; }
        .course-list-wrapper > div { overflow: hidden; }
        .course-group.is-open .course-list-wrapper { grid-template-rows: 1fr; }
        .course-list { margin-top: 8px; }
        
        .trabajadores-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 16px; }
        .trabajador-card { display: flex; flex-direction: column; background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; padding: 16px; text-decoration: none; color: inherit; transition: all 0.2s ease; box-shadow: 0 4px 6px rgba(15, 23, 42, 0.02); }
        .trabajador-card:hover { border-color: #173e67; box-shadow: 0 8px 15px rgba(15, 23, 42, 0.05); transform: translateY(-2px); }
        .trabajador-card__header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .trabajador-card__name { font-size: 1.1rem; font-weight: 800; color: #173e67; margin: 0; line-height: 1.2; }
        .trabajador-card__depto { font-size: 0.85rem; color: #667085; margin-top: 4px; }
        .trabajador-card__stats { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 12px; border-top: 1px dashed #eef2f7; }
        .trabajador-card__alerts { display: flex; gap: 6px; }
        .cursos-shell--directory { grid-template-columns: 1fr !important; }

        .erp-puestos-panel { padding: 14px 16px; background: #fff; border: 1px dashed #cbd5e1; border-radius: 12px; margin-top: 12px; }
        .erp-puestos-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .erp-puestos-title { font-size: 0.75rem; font-weight: 800; color: #8a98ab; text-transform: uppercase; letter-spacing: 0.05em; margin: 0; }
        .erp-tags-container { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .erp-chip { display: inline-flex; align-items: center; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 4px 10px; border-radius: 999px; font-size: 0.8rem; font-weight: 700; transition: all 0.2s; }
        .erp-chip-remove { background: transparent; border: none; color: #166534; margin-left: 6px; cursor: pointer; padding: 0 2px; opacity: 0.6; transition: opacity 0.2s; }
        .erp-chip-remove:hover { opacity: 1; color: #dc2626; }
        .erp-add-wrapper { position: relative; display: inline-block; }
        .erp-add-btn { background: #f8fafc; border: 1px dashed #cbd5e1; color: #64748b; font-size: 0.8rem; font-weight: 700; padding: 4px 12px; border-radius: 999px; cursor: pointer; transition: all 0.2s; height: 28px; display: inline-flex; align-items: center; justify-content: center; }
        .erp-add-btn:hover { background: #f1f5f9; color: #173e67; border-color: #94a3b8; }
        .erp-select-input { display: none; border: 1px solid #173e67; border-radius: 999px; padding: 0 12px; font-size: 0.8rem; font-weight: 600; color: #173e67; outline: none; box-shadow: 0 4px 12px rgba(23, 62, 103, 0.1); cursor: pointer; height: 28px; line-height: 28px; }

        /* ESTILOS DE INACTIVOS */
        .personal-item.is-inactive {
            opacity: 0.65;
            background: #f1f5f9;
            border-color: #e2e8f0;
        }
        .trabajador-card.is-inactive {
            opacity: 0.65;
            background: #f8fafc;
            border-color: #e2e8f0;
            filter: grayscale(80%);
        }
        .filter-toggle-inactive {
            display: flex; 
            align-items: center; 
            gap: 8px; 
            font-size: 0.85rem; 
            font-weight: 700; 
            color: #64748b; 
            cursor: pointer; 
            user-select: none;
        }

        /* ETIQUETA DE NUEVO INGRESO */
        .badge-nuevo {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            font-size: 0.6rem;
            padding: 3px 6px;
            border-radius: 6px;
            margin-left: 6px;
            font-weight: 800;
            text-transform: uppercase;
            vertical-align: middle;
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
            animation: pulse-soft 2s infinite;
        }
        @keyframes pulse-soft {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            70% { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }

        @media (max-width: 1100px) {
            .cursos-shell { grid-template-columns: 1fr; }
            .cursos-sidebar { border-right: 0; padding-right: 0; border-bottom: 1px solid #eef2f7; padding-bottom: 18px; }
            .worker-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .course-row { grid-template-columns: minmax(0, 1.2fr) minmax(0, 2fr); }
        }
        @media (max-width: 720px) {
            .worker-strip { grid-template-columns: 1fr; }
            .course-row { grid-template-columns: 1fr; gap: 8px; }
            .course-row__count { justify-self: start; }
            .course-row__control-wrap { grid-template-columns: 1fr; }
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

            <!-- CONTENEDOR DE BOTONES BLINDADO -->
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                @can('cursos.create')
                    <a href="{{ route('cursos.create') }}" class="cursos-primary">
                        <i class="fas fa-graduation-cap"></i> Nuevo Curso
                    </a>
                @endcan

                @can('cursos.edit')
                    <a href="{{ route('cursos.gestion') }}" class="cursos-primary" style="background: linear-gradient(135deg, #0f5132, #1f7a4d);">
                        <i class="fas fa-pen-to-square"></i> Gestionar Cursos
                    </a>
                @endcan

                @can('cursos.normas')
                    <a href="{{ route('puestos.index') }}" class="cursos-primary" style="background: linear-gradient(135deg, #6366f1, #4338ca);">
                        <i class="fas fa-sitemap"></i> Panel de Normas
                    </a>
                @endcan

                @can('cursos.alertas')
                    <a href="{{ route('cursos.config.alertas') }}" class="cursos-primary" style="background: linear-gradient(135deg, #ea580c, #c2410c);">
                        <i class="fas fa-cog"></i> Configurar Alertas
                    </a>
                @endcan
            </div>
        </header>
        
        @php
            // 1. Calculamos la fecha de hoy para poder comparar
            $hoy = \Carbon\Carbon::now()->startOfDay();

            // 2. ORDENACIÓN INTELIGENTE: Primero Nuevos, luego Alfabéticamente
            $personals = $personals->sortBy(function($personal) use ($hoy) {
                $esNuevo = $personal->created_at && $personal->created_at->diffInDays($hoy) <= 14;
                
                // Si es nuevo recibe un "0-", si es antiguo un "1-". 
                // Al pegarle el nombre detrás (ej: "0-alfonso"), Laravel ordena 
                // primero los 0 alfabéticamente, y luego los 1 alfabéticamente.
                return ($esNuevo ? '0-' : '1-') . mb_strtolower(trim($personal->name . ' ' . $personal->apellido));
            });

            // 3. Extracción de departamentos (lo que ya tenías)
            $listaDepartamentos = collect();
            foreach($personals as $p) {
                $ds = is_string($p->departamento) ? json_decode($p->departamento, true) ?? explode(',', $p->departamento) : (array) $p->departamento;
                $listaDepartamentos = $listaDepartamentos->merge($ds);
            }
            $listaDepartamentos = $listaDepartamentos->map(fn($d) => trim($d))->filter()->unique()->sort();
            
            $currentDepto = request('departamento', 'all');
        @endphp

        <section class="cursos-shell {{ !$selectedPersonal ? 'cursos-shell--directory' : '' }}">
            
            @if($selectedPersonal)
                @can('cursos.plantilla')
                <aside class="cursos-sidebar">
                    <div class="cursos-section-title">
                        <div>
                            <h3>Trabajadores</h3>
                            <p>Selecciona un personal para ver su formación</p>
                        </div>
                    </div>

                    <div class="cursos-filter-row">
                        <input type="search" id="js-worker-search" value="{{ $query ?? '' }}" placeholder="Buscar trabajador..." autocomplete="off">
                        
                        <select id="js-worker-category">
                            <option value="all" @selected(($categoria ?? 'all') === 'all')>Todas las categorías</option>
                            @foreach($categorias as $categoriaOption)
                                <option value="{{ $categoriaOption }}" @selected(($categoria ?? '') === $categoriaOption)>{{ $categoriaOption }}</option>
                            @endforeach
                        </select>

                        <select id="js-worker-depto">
                            <option value="all">Todos los departamentos</option>
                            @foreach($listaDepartamentos as $depto)
                                <option value="{{ mb_strtolower($depto) }}" @selected($currentDepto === mb_strtolower($depto))>{{ strtoupper($depto) }}</option>
                            @endforeach
                        </select>

                        <!-- NUEVO: FILTRO DE ESTADO FORMATIVO -->
                        <select id="js-worker-status-filter" style="margin-top: 10px;">
                            <option value="all">Cualquier estado formativo</option>
                            <option value="nuevos">🌟 Nuevas altas (14 días)</option>
                            <option value="sin_cursos">⚠️ Sin cursos asignados</option>
                        </select>
                    </div>

                    @forelse($personals as $personal)
                    @php
                        $workerCats = mb_strtolower($personal->cursos->pluck('categoria')->filter()->unique()->implode(' '));
                        $nombreCompleto = trim($personal->name . ' ' . $personal->apellido);
                        $cursosCount = $personal->cursos->count();
                        $cursosAptos = $personal->cursos->filter(fn ($curso) => (bool) ($curso->pivot->apto ?? false))->count();
                        
                        $hasCaducado = false;
                        $hasAviso = false;
                        $hoy = \Carbon\Carbon::now()->startOfDay();
                        $esNuevo = $personal->created_at && $personal->created_at->diffInDays($hoy) <= 14;

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
                                
                                if ($hasCaducado && $hasAviso) break; 
                            }
                        }

                        $deptosActuales = is_string($personal->departamento)
                            ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento)
                            : (array) $personal->departamento;
                    @endphp
                    
                    <a href="{{ route('cursos.index', ['personal_id' => $personal->id, 'categoria' => $categoria, 'q' => $query]) }}" 
                        class="personal-item js-worker-item {{ (int) $personal->id === (int) optional($selectedPersonal)->id ? 'is-active' : '' }} {{ !$personal->activo ? 'is-inactive' : '' }}"
                        data-name="{{ mb_strtolower($nombreCompleto) }}"
                        data-cats="{{ $workerCats }}"
                        data-activo="{{ $personal->activo ? '1' : '0' }}"
                        data-depto="{{ mb_strtolower(!empty($deptosActuales) ? implode(' ', $deptosActuales) : 'sin departamento') }}"
                        data-nuevo="{{ $esNuevo ? '1' : '0' }}"
                        data-cursos="{{ $cursosCount }}">
                        
                       
                        <div class="personal-item__top">
                            <div class="personal-item__name">
                                {{ $nombreCompleto }}
                                    @if($esNuevo)
                                        <span class="badge-nuevo" title="Alta reciente (14 días)">Nuevo</span>
                                    @endif
                                
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
                @endcan
            @endif

            <main class="cursos-main">
               <div class="cursos-panel">
                    @if(!$selectedPersonal)
                        @can('cursos.plantilla')
                            <div class="cursos-section-title" style="margin-bottom: 20px;">
                                <div>
                                    <h3>Directorio de Plantilla</h3>
                                    <p>Selecciona un trabajador de la lista para gestionar sus certificaciones y visualizar alertas activas.</p>
                                </div>
                            </div>

                            <div class="cursos-filter-row" style="flex-direction: row; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                                <input type="search" id="js-worker-search" value="{{ $query ?? '' }}" placeholder="Buscar por nombre..." style="flex: 1; min-width: 200px;" autocomplete="off">

                                <select id="js-worker-depto" style="width: auto; min-width: 180px;">
                                    <option value="all">Todos los departamentos</option>
                                    @foreach($listaDepartamentos as $depto)
                                        <option value="{{ mb_strtolower($depto) }}" @selected($currentDepto === mb_strtolower($depto))>{{ strtoupper($depto) }}</option>
                                    @endforeach
                                </select>

                                <!-- NUEVO: FILTRO DE ESTADO FORMATIVO -->
                                <select id="js-worker-status-filter" style="width: auto; min-width: 220px;">
                                    <option value="all">Cualquier estado formativo</option>
                                    <option value="nuevos">🌟 Nuevas altas (14 días)</option>
                                    <option value="sin_cursos">⚠️ Sin cursos asignados</option>
                                </select>

                                <label class="filter-toggle-inactive mb-0 ml-2">
                                    <input type="checkbox" id="js-toggle-inactive-grid" checked>
                                    Mostrar inactivos
                                </label>
                            </div>

                            <div class="trabajadores-grid">
                                @forelse($personals as $personal)
                                    @php
                                        $workerCats = mb_strtolower($personal->cursos->pluck('categoria')->filter()->unique()->implode(' '));
                                        $nombreCompleto = trim($personal->name . ' ' . $personal->apellido);
                                        $cursosCount = $personal->cursos->count();
                                        
                                        $hasCaducado = false;
                                        $hasAviso = false;
                                        $hoy = \Carbon\Carbon::now()->startOfDay();
                                        $esNuevo = $personal->created_at && $personal->created_at->diffInDays($hoy) <= 14;

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
                                                if ($hasCaducado && $hasAviso) break; 
                                            }
                                        }
                                        
                                        $deptosActuales = is_string($personal->departamento)
                                            ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento)
                                            : (array) $personal->departamento;
                                    @endphp
                                    
                                    <a href="{{ route('cursos.index', ['personal_id' => $personal->id, 'categoria' => $categoria, 'q' => $query]) }}" 
                                        class="trabajador-card js-worker-item {{ !$personal->activo ? 'is-inactive' : '' }}"
                                        data-name="{{ mb_strtolower($nombreCompleto) }}"
                                        data-cats="{{ $workerCats }}"
                                        data-activo="{{ $personal->activo ? '1' : '0' }}"
                                        data-depto="{{ mb_strtolower(!empty($deptosActuales) ? implode(' ', $deptosActuales) : 'sin departamento') }}"
                                        data-nuevo="{{ $esNuevo ? '1' : '0' }}"
                                        data-cursos="{{ $cursosCount }}">

                                        <div class="trabajador-card__header">
                                            <div>
                                                <h4 class="trabajador-card__name">{{ $nombreCompleto }}</h4>
                                                    @if($esNuevo)
                                                        <span class="badge-nuevo" title="Alta reciente (14 días)">Nuevo</span>
                                                    @endif
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
                            <div class="cursos-empty" style="grid-column: 1 / -1; padding: 60px 20px;">
                                <i class="fas fa-lock fa-3x mb-3" style="color: #cbd5e1;"></i>
                                <h4 style="color: #1e293b; font-weight: 800;">Acceso Restringido</h4>
                                <p style="color: #64748b; font-size: 1rem;">No tienes permisos para visualizar el directorio de trabajadores.</p>
                            </div>
                        @endcan

                    @else
                        @php
                            $deptosActuales = is_string($selectedPersonal->departamento)
                                ? json_decode($selectedPersonal->departamento, true) ?? explode(',', $selectedPersonal->departamento)
                                : (array) $selectedPersonal->departamento;
                        @endphp

                        <div style="margin-bottom: 16px;">
                            <a href="{{ route('cursos.index') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; background: #fff; border: 1px solid #dbe3ef; border-radius: 10px; color: #173e67; text-decoration: none; font-weight: 700; font-size: 0.85rem; box-shadow: 0 2px 4px rgba(15,23,42,0.02); transition: all 0.2s;">
                                <i class="fas fa-arrow-left"></i> Volver al directorio
                            </a>
                        </div>

                        <div>
                            <label class="filter-toggle-inactive mt-2">
                                <input type="checkbox" id="js-toggle-inactive-sidebar" checked>
                                Mostrar inactivos
                            </label>
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
                                    <div class="worker-strip__label">Departamento RRHH</div>
                                    <div class="worker-strip__value {{ !empty($deptosActuales) ? '' : 'worker-strip__value--muted' }}">{{ !empty($deptosActuales) ? implode(', ', $deptosActuales) : '—' }}</div>
                                </div>

                                <div class="worker-strip__item">
                                    <div class="worker-strip__label">Puesto</div>
                                    <div class="worker-strip__value {{ $selectedPersonal->puesto ? '' : 'worker-strip__value--muted' }}">{{ $selectedPersonal->puesto ?: '—' }}</div>
                                </div>

                                <div class="worker-strip__item">
                                    <div class="worker-strip__label">Teléfono</div>
                                    <div class="worker-strip__value {{ $selectedPersonal->telefono ? '' : 'worker-strip__value--muted' }}">{{ $selectedPersonal->telefono ?: '—' }}</div>
                                </div>

                                <div class="worker-strip__item">
                                    <div class="worker-strip__label">Correo</div>
                                    <div class="worker-strip__value {{ $selectedPersonal->correo ? '' : 'worker-strip__value--muted' }}" title="{{ $selectedPersonal->correo }}">{{ $selectedPersonal->correo ?: '—' }}</div>
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

                            <div class="erp-puestos-panel">
                                <div class="erp-puestos-header">
                                    <h4 class="erp-puestos-title">Perfiles Formativos (Macros Automáticas)</h4>
                                </div>
                                
                                <div class="erp-tags-container" id="puestos-chip-container">
                                    @foreach($selectedPersonal->puestos as $puesto)
                                        <div class="erp-chip" id="chip-puesto-{{ $puesto->id }}">
                                            {{ $puesto->nombre }}
                                            @can('cursos.edit')
                                                <button type="button" class="erp-chip-remove js-remove-puesto" data-puesto-id="{{ $puesto->id }}" title="Quitar perfil">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    @endforeach

                                    @can('cursos.edit')
                                        <div class="erp-add-wrapper">
                                            <button type="button" class="erp-add-btn" id="js-btn-add-puesto">
                                                <i class="fas fa-plus"></i> Añadir perfil
                                            </button>
                                            
                                            <select id="js-select-add-puesto" class="erp-select-input">
                                                <option value="">Buscar y seleccionar...</option>
                                                @foreach($todosLosPuestos as $p)
                                                    @if(!$selectedPersonal->puestos->contains($p->id))
                                                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="cursos-section-title" style="margin-top: 24px; margin-bottom: 16px; border-top: 1px solid #eef2f7; padding-top: 16px;">
                            <div>
                                <h3>Formación del Trabajador</h3>
                                <p>Mostrando los cursos asignados o inyectados por sus perfiles formativos.</p>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 700; color: #173e67; margin: 0; user-select: none;">
                                    <div class="course-toggle" style="width: 36px; height: 20px;">
                                        <input type="checkbox" id="js-toggle-catalog">
                                        <span class="course-toggle__track" style="background: #cbd5e1;"><span class="course-toggle__thumb" style="width: 14px; height: 14px; top: 3px; left: 3px;"></span></span>
                                    </div>
                                    Mostrar todo el catálogo
                                </label>
                            </div>
                        </div>

                        <div class="cursos-local-search">
                            <input type="search" id="course-local-search" placeholder="Buscar entre los cursos visibles..." autocomplete="off">
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

                                                        <div class="course-row__control-wrap">
                                                            
                                                            <!-- COLUMNA 1: ASIGNACIÓN Y BOTONES -->
                                                            <div class="course-row__field">
                                                                <div class="course-row__field-label" style="opacity: 0; user-select: none;">Acción</div>
                                                                
                                                                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; height: 34px; margin-bottom: 6px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0 10px; border-radius: 8px;">
                                                                    <span class="course-row__assign-state" style="margin:0; font-size: 0.8rem;">{{ $asignado ? 'Asignado' : 'Disponible' }}</span>
                                                                    @can('cursos.edit')
                                                                        <label class="course-toggle" title="Asignar o quitar curso" style="margin:0;">
                                                                            <input type="checkbox" class="course-toggle-input" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" data-assigned="{{ $asignado ? '1' : '0' }}" @checked($asignado)>
                                                                            <span class="course-toggle__track"><span class="course-toggle__thumb"></span></span>
                                                                        </label>
                                                                    @else
                                                                        <i class="fas {{ $asignado ? 'fa-check text-success' : 'fa-lock text-muted' }}"></i>
                                                                    @endcan
                                                                </div>
                                                                
                                                                @if($asignado)
                                                                    <div style="display: flex; gap: 6px; width: 100%;">
                                                                        <button type="button" class="btn btn-sm js-open-history" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" data-curso-nombre="{{ $curso->nombre }}" style="flex:1; font-size: 0.7rem; padding: 4px; color: #4338ca; background: #e0e7ff; border: 1px solid #c7d2fe; border-radius: 6px; font-weight: 700;" title="Historial">
                                                                            <i class="fas fa-history"></i> Historial
                                                                        </button>
                                                                        @can('cursos.edit')
                                                                            @if(!empty($asignado->pivot->fecha_realizacion))
                                                                                <button type="button" class="btn btn-sm js-renovar-curso" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" style="flex:1; font-size: 0.7rem; padding: 4px; color: #d97706; background: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; font-weight: 700;" title="Renovar">
                                                                                    <i class="fas fa-sync-alt"></i> Renovar
                                                                                </button>
                                                                            @endif
                                                                        @endcan
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            <!-- COLUMNA 2: APTO -->
                                                            <div class="course-row__field">
                                                                <div class="course-row__field-label">Apto</div>
                                                                <select class="course-apto-select" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" @cannot('cursos.edit') disabled @endcannot>
                                                                    <option value="1" @selected((bool) ($asignado->pivot->apto ?? false))>Sí</option>
                                                                    <option value="0" @selected($asignado && ! (bool) ($asignado->pivot->apto ?? false))>No</option>
                                                                </select>
                                                            </div>

                                                            <!-- COLUMNA 3: FECHA -->
                                                            <div class="course-row__field">
                                                                <div class="course-row__field-label">Fecha</div>
                                                                <input type="date" class="fecha-realizacion-input" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" value="{{ $asignado && !empty($asignado->pivot->fecha_realizacion) ? \Carbon\Carbon::parse($asignado->pivot->fecha_realizacion)->format('Y-m-d') : '' }}" @cannot('cursos.edit') disabled @endcannot>
                                                            </div>

                                                            <!-- COLUMNA 4: DIPLOMA -->
                                                            <div class="course-row__field">
                                                                <div class="course-row__field-label">Diploma</div>
                                                                <div style="display: flex; gap: 6px; align-items: center; height: 34px;">
                                                                    @if($asignado && !empty($asignado->pivot->archivo_diploma))
                                                                        <a href="{{ Storage::url($asignado->pivot->archivo_diploma) }}" target="_blank" class="btn btn-sm" style="flex:1; height: 100%; display: flex; align-items: center; justify-content: center; background: #3b82f6; color: white; font-weight: 700; border-radius: 8px; font-size: 0.75rem; border: none; box-shadow: 0 2px 4px rgba(59,130,246,0.2);" title="Ver Diploma">
                                                                            <i class="fas fa-file-pdf"></i>
                                                                        </a>
                                                                        @can('cursos.edit')
                                                                            <label class="btn btn-sm m-0 js-upload-label" style="cursor: pointer; height: 100%; width: 34px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border: 1px solid #cbd5e1; color: #64748b; border-radius: 8px;" title="Reemplazar">
                                                                                <i class="fas fa-sync-alt"></i>
                                                                                <input type="file" class="curso-diploma-input" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" accept=".pdf,.jpg,.png" style="display: none;">
                                                                            </label>
                                                                        @endcan
                                                                    @else
                                                                        @can('cursos.edit')
                                                                            <label class="btn btn-sm m-0 w-100 js-upload-label" style="cursor: pointer; height: 100%; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #f8fafc; border: 1px dashed #cbd5e1; color: #64748b; font-weight: 700; font-size: 0.75rem;" title="Subir diploma">
                                                                                <i class="fas fa-upload mr-2"></i> Subir
                                                                                <input type="file" class="curso-diploma-input" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" accept=".pdf,.jpg,.png" style="display: none;">
                                                                            </label>
                                                                        @else
                                                                            <span style="font-size: 0.75rem; color: #64748b; margin: auto;">No adjunto</span>
                                                                        @endcan
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <!-- COLUMNA 5: COMENTARIO -->
                                                            <div class="course-row__field">
                                                                <div class="course-row__field-header">
                                                                    <div class="course-row__field-label">Comentario</div>
                                                                    @can('cursos.edit')
                                                                        <button type="button" class="course-comment-save-btn" title="Guardar comentario" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}">
                                                                            <i class="fas fa-check"></i>
                                                                        </button>
                                                                    @endcan
                                                                </div>
                                                                <textarea class="course-row__comment course-comment-input" data-personal-id="{{ $selectedPersonal->id }}" data-curso-id="{{ $curso->id }}" rows="1" placeholder="Añadir observaciones..." @cannot('cursos.edit') readonly @endcannot>{{ $asignado ? ($asignado->pivot->descripcion_aptitud ?? '') : '' }}</textarea>
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

        <!-- MODAL DE HISTORIAL DE RENOVACIONES -->
        <style>
            .curso-modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 1060; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; backdrop-filter: blur(2px); }
            .curso-modal-overlay.is-open { display: flex; opacity: 1; }
            .curso-modal-panel { background: #fff; border-radius: 16px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transform: translateY(20px); transition: transform 0.2s; overflow: hidden; padding: 24px; }
            .curso-modal-overlay.is-open .curso-modal-panel { transform: translateY(0); }
        </style>

        <div class="curso-modal-overlay" id="history-modal" aria-hidden="true" role="dialog" aria-modal="true">
            <div class="curso-modal-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 15px;">
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: #173e67; margin: 0;">Historial de Renovaciones</h3>
                    <button type="button" data-close-history style="background:none; border:none; font-size:1.2rem; color:#94a3b8; cursor:pointer;"><i class="fas fa-times"></i></button>
                </div>
                
                <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 15px;">
                    Curso: <strong id="history-curso-name" style="color: #173e67;">Cargando...</strong>
                </p>

                <div id="history-timeline" style="display: flex; flex-direction: column; gap: 10px;"></div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const autoResizeTextarea = (textarea) => {
            if (textarea.offsetParent === null) return; 
            textarea.style.height = '34px'; 
            textarea.style.height = Math.max(34, textarea.scrollHeight) + 'px'; 
        };

        document.querySelectorAll('.course-comment-input').forEach(textarea => {
            autoResizeTextarea(textarea);
            textarea.addEventListener('input', () => autoResizeTextarea(textarea));
        });

        document.querySelectorAll('.course-group__head').forEach(head => {
            head.removeAttribute('onclick'); 
            head.addEventListener('click', function() {
                const group = this.closest('.course-group');
                group.classList.toggle('is-open');
                if (group.classList.contains('is-open')) {
                    group.querySelectorAll('.course-comment-input').forEach(autoResizeTextarea);
                }
            });
        });

        const searchInput = document.getElementById('course-local-search');
        const toggleCatalog = document.getElementById('js-toggle-catalog');
        const courseRows = document.querySelectorAll('.course-row');
        const courseGroups = document.querySelectorAll('.course-group--filterable');

        const updateRowVisibility = () => {
            const term = searchInput ? searchInput.value.trim().toLowerCase() : '';
            const showAll = toggleCatalog ? toggleCatalog.checked : false;

            courseRows.forEach(row => {
                const name = row.getAttribute('data-course-name') || '';
                const isAssigned = row.classList.contains('is-assigned');
                const matchesSearch = term === '' || name.includes(term);
                const matchesAssigned = showAll || isAssigned;

                row.style.display = (matchesSearch && matchesAssigned) ? '' : 'none';
                row.hidden = !(matchesSearch && matchesAssigned);
            });

            if (term !== '') {
                courseGroups.forEach(group => {
                    group.classList.add('is-open');
                    group.querySelectorAll('.course-comment-input').forEach(autoResizeTextarea);
                });
            }

            courseGroups.forEach(group => {
                const visibleRows = Array.from(group.querySelectorAll('.course-row')).filter(r => !r.hidden);
                group.hidden = visibleRows.length === 0;
            });
        };

        if (searchInput) searchInput.addEventListener('input', updateRowVisibility);
        if (toggleCatalog) toggleCatalog.addEventListener('change', updateRowVisibility);
        updateRowVisibility();

        const persistExtraData = async (personalId, cursoId, payload, row) => {
            const dateInput = row ? row.querySelector('.fecha-realizacion-input') : null;
            const aptoSelect = row ? row.querySelector('.course-apto-select') : null;
            const commentInput = row ? row.querySelector('.course-comment-input') : null;
            const fileInput = row ? row.querySelector('.curso-diploma-input') : null;

            const formData = new FormData();
            formData.append('curso_id', cursoId);
            formData.append('_method', 'PUT'); 

            let finalApto = payload.apto !== undefined ? payload.apto : (aptoSelect ? aptoSelect.value : '1');
            formData.append('apto', finalApto);

            if (dateInput && dateInput.value) formData.append('fecha_realizacion', dateInput.value);
            if (payload.fecha_realizacion) formData.set('fecha_realizacion', payload.fecha_realizacion);
            
            if (commentInput && commentInput.value) formData.append('descripcion_aptitud', commentInput.value);
            
            if (payload.archivo_diploma) {
                formData.append('archivo_diploma', payload.archivo_diploma);
            } else if (fileInput && fileInput.files.length > 0) {
                formData.append('archivo_diploma', fileInput.files[0]);
            }

            const response = await fetch(`{{ url('personal') }}/${personalId}/cursos`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            if (!response.ok) {
                const errorData = await response.json();
                const firstError = errorData.errors ? Object.values(errorData.errors)[0][0] : (errorData.message || 'Error desconocido');
                throw new Error(firstError);
            }
        };

        document.body.addEventListener('change', async function(e) {
            
            if (e.target.matches('.course-toggle-input')) {
                const input = e.target;
                const personalId = input.getAttribute('data-personal-id');
                const cursoId = input.getAttribute('data-curso-id');
                const assigned = input.checked;
                const row = input.closest('.course-row');
                const previousState = input.getAttribute('data-assigned') === '1';

                if (!personalId || !cursoId) return;

                let fechaRealizacion = null;
                const dateInput = row.querySelector('.fecha-realizacion-input');
                if (dateInput && assigned && !dateInput.value) {
                    dateInput.value = new Date().toISOString().slice(0, 10);
                    fechaRealizacion = dateInput.value;
                } else if (dateInput) {
                    fechaRealizacion = dateInput.value;
                }

                try {
                    const response = await fetch(assigned ? `{{ url('personal') }}/${personalId}/cursos` : `{{ url('personal') }}/${personalId}/cursos/${cursoId}`, {
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
                    });

                    if (!response.ok) throw new Error('Failed');

                    input.setAttribute('data-assigned', assigned ? '1' : '0');
                    window.location.reload();
                } catch (error) {
                    input.checked = previousState;
                    alert('No se pudo actualizar la asignación del curso.');
                }
            }

            if (e.target.matches('.fecha-realizacion-input')) {
                const input = e.target;
                try {
                    await persistExtraData(input.dataset.personalId, input.dataset.cursoId, { fecha_realizacion: input.value }, input.closest('.course-row'));
                    input.classList.add('border-green-500');
                    setTimeout(() => input.classList.remove('border-green-500'), 2000);
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }

            if (e.target.matches('.course-apto-select')) {
                const select = e.target;
                try {
                    await persistExtraData(select.dataset.personalId, select.dataset.cursoId, { apto: select.value }, select.closest('.course-row'));
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }

            if (e.target.matches('.curso-diploma-input')) {
                const input = e.target;
                if (!input.files || input.files.length === 0) return;
                if (input.files[0].size > 10 * 1024 * 1024) {
                    alert('El archivo supera los 10MB permitidos.');
                    input.value = '';
                    return;
                }

                const fileToUpload = input.files[0];
                const row = input.closest('.course-row');
                const personalId = input.dataset.personalId;
                const cursoId = input.dataset.cursoId;
                const labelWrap = input.closest('.js-upload-label');
                const originalHtml = labelWrap.innerHTML;
                
                labelWrap.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
                labelWrap.style.pointerEvents = 'none';

                try {
                    await persistExtraData(personalId, cursoId, { archivo_diploma: fileToUpload }, row);
                    labelWrap.innerHTML = '<i class="fas fa-check text-success"></i>';
                    setTimeout(() => window.location.reload(), 800);
                } catch (error) {
                    alert('Error al subir: ' + error.message);
                    labelWrap.innerHTML = originalHtml;
                    labelWrap.style.pointerEvents = 'auto';
                }
            }
        });

        document.body.addEventListener('click', async function(e) {
            
            const btnSaveComment = e.target.closest('.course-comment-save-btn');
            if (btnSaveComment) {
                const row = btnSaveComment.closest('.course-row');
                const toggle = row.querySelector('.course-toggle-input');
                if (toggle && !toggle.checked) return alert('Asigna el curso primero.');

                const originalIcon = btnSaveComment.innerHTML;
                btnSaveComment.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btnSaveComment.disabled = true;

                try {
                    await persistExtraData(btnSaveComment.dataset.personalId, btnSaveComment.dataset.cursoId, {}, row);
                    btnSaveComment.innerHTML = '<i class="fas fa-check-double"></i>';
                    setTimeout(() => { btnSaveComment.innerHTML = originalIcon; btnSaveComment.disabled = false; }, 1200);
                } catch (error) {
                    alert('Error: ' + error.message);
                    btnSaveComment.innerHTML = '<i class="fas fa-times"></i>';
                    setTimeout(() => { btnSaveComment.innerHTML = originalIcon; btnSaveComment.disabled = false; }, 2000);
                }
            }

            const btnRenovar = e.target.closest('.js-renovar-curso');
            if (btnRenovar) {
                if (!confirm('¿Iniciar proceso de renovación? Esto archivará el diploma y fecha actual en el historial.')) return;
                
                const originalHtml = btnRenovar.innerHTML;
                btnRenovar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btnRenovar.disabled = true;

                try {
                    const response = await fetch(`/personal/${btnRenovar.dataset.personalId}/cursos/${btnRenovar.dataset.cursoId}/renovar`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (response.ok) window.location.reload();
                    else throw new Error('Fallo en la renovación');
                } catch (error) {
                    alert('Error al intentar renovar.');
                    btnRenovar.innerHTML = originalHtml;
                    btnRenovar.disabled = false;
                }
            }

            const btnHistorial = e.target.closest('.js-open-history');
            if (btnHistorial) {
                const modal = document.getElementById('history-modal');
                const timeline = document.getElementById('history-timeline');
                document.getElementById('history-curso-name').textContent = btnHistorial.dataset.cursoNombre;
                
                timeline.innerHTML = '<div style="text-align: center; padding: 20px; color: #64748b;"><i class="fas fa-spinner fa-spin"></i> Consultando registros...</div>';
                modal.classList.add('is-open');

                try {
                    const response = await fetch(`/personal/${btnHistorial.dataset.personalId}/cursos/${btnHistorial.dataset.cursoId}/historial`);
                    if (!response.ok) throw new Error('Error');
                    const data = await response.json();
                    
                    timeline.innerHTML = '';
                    if (data.actual) {
                        const isApto = data.actual.apto;
                        const badgeBg = isApto ? '#dcfce7' : '#fee2e2';
                        const badgeColor = isApto ? '#166534' : '#b91c1c';
                        const dBtn = data.actual.diploma_url ? `<a href="${data.actual.diploma_url}" target="_blank" style="margin-right:12px; font-size:0.75rem; text-decoration:none; color:#166534; background:#dcfce7; padding:4px 10px; border-radius:6px; font-weight:800; border:1px solid #bbf7d0;"><i class="fas fa-file-pdf"></i> Ver certificado</a>` : '';
                        
                        timeline.innerHTML += `
                            <div style="padding:12px 16px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                                <div><div style="font-size:0.75rem; font-weight:800; color:#166534;">CERTIFICADO ACTUAL</div><div style="font-weight:700; color:#14532d; font-size:1.1rem;">${data.actual.fecha}</div></div>
                                <div style="display:flex; align-items:center;">${dBtn}<span style="font-size:11px; font-weight:800; padding:5px 9px; border-radius:999px; background:${badgeBg}; color:${badgeColor};">${isApto?'APTO':'NO APTO'}</span></div>
                            </div>`;
                    }
                    if (data.historico && data.historico.length > 0) {
                        data.historico.forEach(item => {
                            const isApto = item.apto;
                            const dBtn = item.diploma_url ? `<a href="${item.diploma_url}" target="_blank" style="margin-right:12px; font-size:0.75rem; text-decoration:none; color:#475569; background:#f1f5f9; padding:4px 10px; border-radius:6px; font-weight:800; border:1px solid #e2e8f0;"><i class="fas fa-file-pdf"></i> Ver certificado</a>` : '';
                            
                            timeline.innerHTML += `
                                <div style="padding:12px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; display:flex; justify-content:space-between; align-items:center; opacity:0.85;">
                                    <div><div style="font-size:0.75rem; font-weight:800; color:#64748b;">REGISTRO ARCHIVADO</div><div style="font-weight:700; color:#475569; font-size:1rem;">${item.fecha}</div></div>
                                    <div style="display:flex; align-items:center;">${dBtn}<span style="font-size:11px; font-weight:800; padding:5px 9px; border-radius:999px; background:#e2e8f0; color:#64748b;">${isApto?'APTO':'NO APTO'}</span></div>
                                </div>`;
                        });
                    } else if (!data.actual) {
                        timeline.innerHTML = '<div style="text-align: center; color: #64748b;">No hay registros de fechas.</div>';
                    }
                } catch (error) {
                    timeline.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 20px;">Error de conexión.</div>';
                }
            }

            if (e.target.closest('[data-close-history]') || e.target.id === 'history-modal') {
                document.getElementById('history-modal').classList.remove('is-open');
            }
        });

        // -------------------------------------------------------------
        // FILTRO DE TRABAJADORES (BUSCADOR, CATEGORÍA, DEPTO E INACTIVOS)
        // -------------------------------------------------------------
        (function() {
            const categorySelect = document.getElementById('js-worker-category');
            const searchInput = document.getElementById('js-worker-search');
            const deptoSelect = document.getElementById('js-worker-depto');
            
            // Nuevo selector de estado formativo
            const statusFilter = document.getElementById('js-worker-status-filter');
            
            const toggleInactiveSidebar = document.getElementById('js-toggle-inactive-sidebar');
            const toggleInactiveGrid = document.getElementById('js-toggle-inactive-grid');
            const workerItems = document.querySelectorAll('.js-worker-item');

            const storageKey = 'mostrarInactivosCursos'; 
            const savedState = localStorage.getItem(storageKey);
            const isChecked = savedState !== null ? savedState === 'true' : true;
            
            if (toggleInactiveSidebar) toggleInactiveSidebar.checked = isChecked;
            if (toggleInactiveGrid) toggleInactiveGrid.checked = isChecked;

            const saveStateAndFilter = (e) => {
                const newState = e.target.checked;
                localStorage.setItem(storageKey, newState); 
                
                if (toggleInactiveSidebar && e.target !== toggleInactiveSidebar) toggleInactiveSidebar.checked = newState;
                if (toggleInactiveGrid && e.target !== toggleInactiveGrid) toggleInactiveGrid.checked = newState;
                
                filterWorkers(); 
            };

            const filterWorkers = () => {
                if (!workerItems.length) return;

                const term = searchInput ? searchInput.value.toLowerCase().trim() : '';
                const category = categorySelect ? categorySelect.value.toLowerCase() : 'all';
                const depto = deptoSelect ? deptoSelect.value.toLowerCase() : 'all';
                const status = statusFilter ? statusFilter.value : 'all'; // Capturamos el filtro de estado
                
                let showInactive = true;
                if (toggleInactiveSidebar) showInactive = toggleInactiveSidebar.checked;
                else if (toggleInactiveGrid) showInactive = toggleInactiveGrid.checked;

                workerItems.forEach(item => {
                    const name = item.getAttribute('data-name') || '';
                    const cats = item.getAttribute('data-cats') || '';
                    const workerDepto = item.getAttribute('data-depto') || '';
                    const isActivo = item.getAttribute('data-activo') === '1'; 
                    
                    // Capturamos los datos nuevos
                    const isNuevo = item.getAttribute('data-nuevo') === '1';
                    const numCursos = parseInt(item.getAttribute('data-cursos') || '0', 10);

                    const matchesSearch = term === '' || name.includes(term);
                    const matchesCategory = category === 'all' || cats.includes(category);
                    const matchesDepto = depto === 'all' || workerDepto.includes(depto);
                    const matchesActivo = showInactive || isActivo;

                    // Lógica del filtro de estado
                    let matchesStatus = true;
                    if (status === 'nuevos' && !isNuevo) matchesStatus = false;
                    if (status === 'sin_cursos' && numCursos > 0) matchesStatus = false;

                    item.style.display = (matchesSearch && matchesCategory && matchesDepto && matchesActivo && matchesStatus) ? '' : 'none'; 
                });
            };

            if (categorySelect) categorySelect.addEventListener('change', filterWorkers);
            if (searchInput) searchInput.addEventListener('input', filterWorkers);
            if (deptoSelect) deptoSelect.addEventListener('change', filterWorkers);
            if (statusFilter) statusFilter.addEventListener('change', filterWorkers); // Listener del nuevo filtro
            
            if (toggleInactiveSidebar) toggleInactiveSidebar.addEventListener('change', saveStateAndFilter);
            if (toggleInactiveGrid) toggleInactiveGrid.addEventListener('change', saveStateAndFilter);
            
            filterWorkers();
        })();

        // -------------------------------------------------------------
        // LÓGICA DE PERFILES FORMATIVOS (AÑADIR / QUITAR)
        // -------------------------------------------------------------
        (function() {
            const btnAdd = document.getElementById('js-btn-add-puesto');
            const selectAdd = document.getElementById('js-select-add-puesto');
            
            if (!btnAdd || !selectAdd) return;

            btnAdd.addEventListener('click', function() {
                this.style.display = 'none';
                selectAdd.style.display = 'inline-block';
                selectAdd.focus();
            });

            selectAdd.addEventListener('blur', function() {
                this.style.display = 'none';
                btnAdd.style.display = 'inline-block';
                this.value = '';
            });

            selectAdd.addEventListener('change', async function() {
                const puestoId = this.value;
                if (!puestoId) return;

                const personalId = '{{ $selectedPersonal->id ?? "" }}';
                this.disabled = true;

                try {
                    const response = await fetch(`/personal/${personalId}/puestos/add`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ puesto_id: puestoId })
                    });
                    if (response.ok) window.location.reload(); 
                    else alert('Error al asignar el perfil.');
                } catch (error) {
                    alert('Fallo de conexión.');
                }
            });

            document.querySelectorAll('.js-remove-puesto').forEach(btn => {
                btn.addEventListener('click', async function() {
                    if(!confirm('¿Seguro que quieres quitar este perfil formativo?')) return;
                    const puestoId = this.getAttribute('data-puesto-id');
                    const personalId = '{{ $selectedPersonal->id ?? "" }}';
                    const chip = document.getElementById(`chip-puesto-${puestoId}`);
                    chip.style.opacity = '0.5';

                    try {
                        const response = await fetch(`/personal/${personalId}/puestos/${puestoId}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        
                        if (response.ok) {
                            // Extraemos el texto del chip ignorando el botón de borrar
                            const puestoNombre = chip.firstChild.textContent.trim();
                            const selectAdd = document.getElementById('js-select-add-puesto');
                            
                            if (selectAdd) {
                                const opt = document.createElement('option');
                                opt.value = puestoId;
                                opt.textContent = puestoNombre;
                                selectAdd.appendChild(opt);
                            }
                            
                            chip.remove(); 
                        } else { 
                            chip.style.opacity = '1'; 
                            alert('Error al quitar el perfil.'); 
                        }
                    } catch (error) {
                        chip.style.opacity = '1';
                        alert('Fallo de conexión.');
                    }
                });
            });
        })();
    </script>
@endsection