@extends('adminlte::page')

@section('title', 'Parque Informático y Estaciones IT')

@section('content_header')
    <div class="it-header">
        <div>
            <div class="it-kicker">Gestión de activos <span>/</span> Equipos informáticos</div>
            <h1>Parque Informático y Estaciones IT</h1>
            <p>Control de hardware corporativo, trazabilidad de custodios, estado del equipo y ciclo de vida.</p>
        </div>
        <div class="it-header-actions">
            <a href="#" class="it-btn it-btn-light"><i class="fas fa-file-export"></i> Exportar datos</a>
            <a href="{{ route('equipos.create') }}" class="it-btn it-btn-primary"><i class="fas fa-plus"></i> Registrar equipo</a>
        </div>
    </div>
@stop

@section('content')
@include('herramientas.partials.architectural-ledger')

<style>
    :root {
        --it-navy: #002442;
        --it-ink: #191c1e;
        --it-surface: #f7f9fb;
        --it-low: #eef2f5;
        --it-muted: #66727e;
        --it-blue: #17619a;
        --it-green: #0c9b77;
        --it-red: #c92b2b;
        --it-amber: #c27c15;
    }
    
    .content-wrapper { background: var(--it-surface); }
    
    /* Header & General */
    .it-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; margin: 0 0 20px; }
    .it-kicker { color: #73808d; font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .it-kicker span { padding: 0 5px; color: #a4adb6; }
    .it-header h1 { color: var(--it-ink); font-size: 34px; line-height: 1.05; font-weight: 800; letter-spacing: 0; margin: 5px 0; }
    .it-header p { color: var(--it-muted); font-size: 14px; margin: 0; }
    .it-header-actions { display: flex; justify-content: flex-end; align-items: center; gap: 8px; flex-wrap: wrap; }
    
    /* Botones Globales */
    .it-btn { border: 0; border-radius: 5px; padding: 10px 14px; font-size: 11px; font-weight: 800; white-space: nowrap; text-decoration: none; display: inline-flex; align-items: center; cursor: pointer; }
    .it-btn i { margin-right: 6px; }
    .it-btn-light { background: #e8edf1; color: #274053; transition: background 0.2s; }
    .it-btn-light:hover { background: #dcecf8; color: #0d4675; }
    .it-btn-primary { background: linear-gradient(110deg, #002442, #135381); box-shadow: 0 5px 12px rgba(0,36,66,0.15); color: #fff; }
    
    /* Navegación y KPIs */
    .it-tabs { display: flex; gap: 27px; border-bottom: 1px solid #dfe5e9; margin: 0 0 18px; padding: 0 3px; }
    .it-tabs a { color: #66727e; font-size: 12px; font-weight: 700; padding: 11px 0; text-decoration: none; }
    .it-tabs a.active { color: var(--it-navy); border-bottom: 3px solid var(--it-navy); }
    
    .it-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 11px; margin-bottom: 18px; }
    .it-kpi { background: #fff; min-height: 100px; padding: 16px 17px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); position: relative; border-radius: 6px; }
    .it-kpi:after { content: ""; position: absolute; bottom: 12px; left: 17px; width: calc(100% - 34px); height: 3px; background: #0d2e4d; }
    .it-kpi.red:after { background: #e23434; }
    .it-kpi.blue:after { background: #173d66; }
    .it-kpi.sky:after { background: #8fc5fa; }
    .it-kpi .label { font-size: 10px; letter-spacing: .1em; text-transform: uppercase; font-weight: 800; color: #65727e; }
    .it-kpi .value { font-size: 26px; line-height: 1.1; font-weight: 800; color: #20262c; margin-top: 8px; }
    .it-kpi .value small { font-size: 10px; font-weight: 700; color: #71808e; margin-left: 4px; }
    .it-kpi.red .value { color: var(--it-red); }
    
    /* Buscador */
    .it-toolbar { background: #fff; padding: 14px 15px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); margin-bottom: 18px; border-radius: 6px; }
    .it-toolbar .form-control { border: 0; background: var(--it-low); height: 36px; font-size: 12px; color: #3d4b56; }
    .it-toolbar .input-group-text { border: 0; background: var(--it-low); color: #76838e; }
    
    /* GRID COMPACTO DE ALTA DENSIDAD */
    .it-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
    
    .it-card-compact { background: #fff; box-shadow: 0 4px 12px rgba(25,28,30,0.04); border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; border: 1px solid transparent; }
    
    /* Bordes dinámicos de alerta IT */
    .it-card-compact.card-critical { border-left: 4px solid var(--it-red) !important; border-color: #fee1df; }
    .it-card-compact.card-warning { border-left: 4px solid var(--it-amber) !important; border-color: #fce8cd; }

    /* Cabecera de Tarjeta */
    .it-card-top { padding: 14px 16px; border-bottom: 1px solid #edf0f2; display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .it-titles { flex: 1; }
    .it-titles small { font-size: 10px; color: #66727e; font-weight: 800; text-transform: uppercase; display: flex; align-items: center; gap: 5px; margin-bottom: 4px; }
    .it-titles h3 { font-size: 15px; font-weight: 800; color: #191c1e; margin: 0; line-height: 1.2; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    
    /* Botón de Cámara Integrado */
    .it-btn-cam { background: #eef2f5; color: #17619a; border: 0; width: 32px; height: 32px; border-radius: 6px; display: grid; place-items: center; cursor: pointer; transition: all 0.2s; font-size: 13px; }
    .it-btn-cam:hover { background: #dcecf8; color: #0d4675; }
    .it-id-badge { background: #eef2f5; color: #52636d; font-size: 9px; font-weight: 800; padding: 4px 7px; border-radius: 4px; }
    
    /* Datos Ultra Compactos */
    .it-specs-line { display: flex; gap: 12px; padding: 10px 16px; background: #fafbfc; border-bottom: 1px solid #edf0f2; font-size: 10px; color: #52636d; font-weight: 700;}
    .it-specs-line span { display: inline-flex; align-items: center; gap: 5px; }
    .it-specs-line i { color: #87949b; }
    
    .it-data-grid { padding: 14px 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; flex: 1; }
    .it-data-item label { display: block; font-size: 9px; color: #7a8790; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 2px; }
    .it-data-item strong { display: block; font-size: 11px; color: #26343d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    /* Cintas apilables (Ribbons) */
    .it-alert-ribbon { padding: 8px 16px; font-size: 10px; font-weight: 800; }
    .it-alert-ribbon.danger { background: #fff0ef; color: #a22429; }
    .it-alert-ribbon.warning { background: #fff4e4; color: #6f541a; }
    
    /* Menú Desplegable de Estado */
    .it-dropdown { position: relative; display: inline-block; }
    .it-dropdown-toggle { cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 2px 6px; border-radius: 4px; transition: background 0.2s; }
    .it-dropdown-toggle:hover { background: #eef2f5; }
    .it-dropdown-menu { display: none; position: absolute; top: 100%; left: 0; background: #fff; box-shadow: 0 10px 30px rgba(0,36,66,0.15); border-radius: 6px; padding: 6px; z-index: 100; min-width: 170px; border: 1px solid #edf0f2; }
    .it-dropdown-menu.show { display: flex; flex-direction: column; gap: 2px; }
    .it-dropdown-item { text-align: left; background: transparent; border: 0; padding: 8px 10px; font-size: 10px; font-weight: 800; color: #52636d; border-radius: 4px; cursor: pointer; transition: all 0.2s; }
    .it-dropdown-item:hover { background: #f7f9fb; color: #191c1e; }
    
    /* Footer de Botones */
    .it-card-actions { display: flex; gap: 6px; padding: 12px 16px; border-top: 1px solid #edf0f2; }
    .it-card-actions .it-btn { flex: 1; justify-content: center; padding: 8px 10px; font-size: 10px; }
    
    .it-bottom { display: flex; justify-content: space-between; align-items: center; background: #fff; margin-top: 18px; padding: 14px 16px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); font-size: 11px; color: #66727e; border-radius: 6px; }
    
    /* MODALES */
    .detail-modal { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.6); z-index: 1100; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); }
    .detail-modal.show { display: flex; }
    .detail-dialog { background: #fff; width: 100%; max-width: 500px; padding: 24px; box-shadow: 0 16px 45px rgba(0,36,66,0.2); border-radius: 8px; }

    #photo-modal { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.75); z-index: 1200; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(5px); }
    #photo-modal.show { display: flex; }
    .photo-dialog { background: #fff; border-radius: 8px; overflow: hidden; max-width: 600px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
    .photo-header { padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf0f2; }
    .photo-header h4 { margin: 0; font-size: 15px; font-weight: 800; color: var(--it-ink); }
    .photo-header button { border: 0; background: transparent; color: #87949b; cursor: pointer; font-size: 16px; }
    .photo-header button:hover { color: var(--it-red); }
    .photo-body img { width: 100%; height: auto; display: block; max-height: 70vh; object-fit: contain; background: var(--it-low); }

    @media(max-width: 900px) {
        .it-header { align-items: flex-start; flex-direction: column; }
        .it-kpis { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<nav class="it-tabs">
    <a class="active" href="{{ route('equipos.index') }}">Equipos informáticos</a>
    <a href="{{ route('herramientas.index') }}">Herramientas de planta</a>
    <a href="{{ route('calibrables.index') }}">Aparatos calibrables</a>
    <a href="{{ route('alertas.index') }}">Alertas y mantenimiento</a>
</nav>

<div class="it-kpis">
    <div class="it-kpi">
        <div class="label">Total equipos</div>
        <div class="value">{{ $stats['total'] }} <small>100% activo en BD</small></div>
    </div>
    <div class="it-kpi red">
        <div class="label">En taller / reparación</div>
        <div class="value">{{ $stats['mantenimiento'] }} <small>del parque</small></div>
        <div class="sub">Requieren seguimiento</div>
    </div>
    <div class="it-kpi blue">
        <div class="label">Asignados a personal</div>
        <div class="value">{{ $stats['operativos'] }} <small>en producción</small></div>
    </div>
    <div class="it-kpi sky">
        <div class="label">Disponibles en rack IT</div>
        <div class="value">{{ max(0, $stats['total'] - $stats['operativos'] - $stats['mantenimiento']) }} <small>listos para entrega</small></div>
    </div>
</div>

<form class="it-toolbar" method="GET" action="{{ route('equipos.index') }}">
    <div class="row">
        <div class="col-md-4 mb-2 mb-md-0">
            <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                <input class="form-control" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre, marca, S/N o custodio">
            </div>
        </div>
        <div class="col-md-2 mb-2 mb-md-0">
            <select class="form-control" name="estado">
                <option value="">Estado (Todos)</option>
                @foreach($estados as $estado)
                    <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 mb-2 mb-md-0">
            <select class="form-control" name="ubicacion">
                <option value="">Ubicación (Todas)</option>
                @foreach($ubicaciones as $ubicacion)
                    <option value="{{ $ubicacion }}" @selected(request('ubicacion') === $ubicacion)>{{ $ubicacion }}</option>
                @endforeach
            </select>
        </div>
        <!-- NUEVO FILTRO DE ALERTAS IT -->
        <div class="col-md-3 mb-2 mb-md-0">
            <select class="form-control" name="alerta">
                <option value="">Alertas de Ciclo / EOL</option>
                <option value="obsoleto" @selected(request('alerta') === 'obsoleto')>Obsoletos (EOL Superado)</option>
                <option value="mantenimiento" @selected(request('alerta') === 'mantenimiento')>Mantenimiento Vencido</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="it-btn it-btn-primary w-100" title="Filtrar"><i class="fas fa-filter"></i></button>
        </div>
    </div>
</form>

<div class="it-grid">
    @forelse($items as $item)
        @php 
            $image = $item->imagen ? asset('storage/'.$item->imagen) : null; 
            
            // MOTOR LÓGICO DE ALERTAS IT
            $isReparacion = $item->estado === 'reparacion';
            $isMantenimiento = $item->estado === 'mantenimiento';
            $isObsoleto = $item->soporte_hasta && $item->soporte_hasta->isPast();
            $mantenimientoCaducado = $item->fecha_mantenimiento && $item->fecha_mantenimiento->isPast();
            
            // DETERMINAR COLOR DEL BORDE
            $cardLevelClass = '';
            if ($isReparacion || $isObsoleto) {
                $cardLevelClass = 'card-critical'; // ROJO
            } elseif ($isMantenimiento || $mantenimientoCaducado) {
                $cardLevelClass = 'card-warning'; // NARANJA
            }
        @endphp
        
        <article class="it-card-compact {{ $cardLevelClass }}">
            <!-- Header Compacto -->
            <div class="it-card-top">
                <div class="it-titles">
                    <div class="it-dropdown" onclick="toggleDropdown(event, {{ $item->id }})">
                        <small class="it-dropdown-toggle" title="Cambiar estado del equipo">
                            <i class="fas fa-circle" style="color: {{ $isReparacion ? 'var(--it-red)' : ($isMantenimiento ? 'var(--it-amber)' : 'var(--it-green)') }}; font-size: 8px;"></i>
                            {{ $item->estado_label }}
                            <i class="fas fa-chevron-down" style="font-size: 8px; color: #87949b; margin-left: 2px;"></i>
                        </small>
                        
                        <div class="it-dropdown-menu" id="dropdown-{{ $item->id }}">
                            @if($item->estado !== 'operativo')
                                <button type="button" class="it-dropdown-item" onclick="openQuickStateModal({{ $item->id }}, 'operativo', 'Operativo / Disponible (Devolver a Stock)')">
                                    <i class="fas fa-box" style="color: #0c9b77;"></i> Devolver a Stock (Operativo)
                                </button>
                            @endif
                            @if(!in_array($item->estado, ['mantenimiento', 'reparacion']))
                                <button type="button" class="it-dropdown-item" onclick="openQuickStateModal({{ $item->id }}, 'mantenimiento', 'En mantenimiento')">
                                    <i class="fas fa-wrench" style="color: #c27c15;"></i> Enviar a Mantenimiento
                                </button>
                                <button type="button" class="it-dropdown-item" onclick="openQuickStateModal({{ $item->id }}, 'reparacion', 'En reparación')">
                                    <i class="fas fa-tools" style="color: #c92b2b;"></i> Enviar a Reparación
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Nombre y Píldora de Garantía -->
                    <h3>
                        {{ $item->nombre }}
                        @if($item->garantia_hasta && $item->garantia_hasta->isFuture())
                            <span style="font-size: 9px; padding: 2px 6px; background: #e2f7ee; color: #08734e; border-radius: 4px; border: 1px solid #b8e6d3; white-space: nowrap;">
                                <i class="fas fa-shield-alt mr-1"></i> Garantía: {{ $item->garantia_hasta->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                            </span>
                        @endif
                    </h3>
                    @if(in_array($item->estado, ['reparacion', 'mantenimiento']) && $item->dias_estimados_baja)
                        @php
                            $fechaLimiteBaja = \Carbon\Carbon::parse($item->fecha_baja)->addDays($item->dias_estimados_baja);
                            $retrasoBaja = $item->fecha_baja && now()->gt($fechaLimiteBaja);
                            $diasRetraso = $retrasoBaja ? $fechaLimiteBaja->diffInDays(now()) : 0;
                        @endphp
                        <small style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; color: {{ $retrasoBaja ? '#c92328' : '#87949b' }}; margin-top: 2px;">
                            <i class="fas fa-hourglass-half"></i>
                            {{ $retrasoBaja ? 'Retraso de ' . $diasRetraso . ' día' . ($diasRetraso == 1 ? '' : 's') : 'Baja aprox. ' . $item->dias_estimados_baja . ' días' }}
                        </small>
                    @endif
                </div>
                
                @if($image)
                    <button type="button" class="it-btn-cam" onclick="openPhotoModal('{{ $image }}', '{{ addslashes($item->nombre) }}')" title="Ver fotografía">
                        <i class="fas fa-camera"></i>
                    </button>
                @else
                    <div class="it-id-badge">{{ $item->codigo ?: 'S/C' }}</div>
                @endif
            </div>

            <!-- Specs en una línea -->
            <div class="it-specs-line">
                <span><i class="fas fa-microchip"></i> {{ $item->procesador ?: 'CPU N/A' }}</span>
                <span><i class="fas fa-memory"></i> {{ $item->memoria_ram ?: 'RAM N/A' }}</span>
                <span><i class="fas fa-hdd"></i> {{ $item->almacenamiento ?: 'SSD N/A' }}</span>
            </div>

            <!-- CINTAS APILADAS (STACKED RIBBONS) -->
            @if($isReparacion)
                <div class="it-alert-ribbon danger"><i class="fas fa-tools mr-1"></i> Equipo en reparación (No operativo)</div>
            @endif
            @if($isMantenimiento)
                <div class="it-alert-ribbon warning"><i class="fas fa-wrench mr-1"></i> En mantenimiento / taller interno</div>
            @endif
            
            @if($isObsoleto)
                <div class="it-alert-ribbon danger"><i class="fas fa-exclamation-triangle mr-1"></i> CICLO DE VIDA AGOTADO (Planificar renovación)</div>
            @endif
            
            @if($mantenimientoCaducado)
                <div class="it-alert-ribbon warning"><i class="fas fa-exclamation-triangle mr-1"></i> Revisión de mantenimiento vencida</div>
            @endif

            <!-- Datos principales -->
            <div class="it-data-grid">
                <div class="it-data-item">
                    <label>{{ $item->responsable ? 'Persona asignada' : 'Último custodio / Libre' }}</label>
                    <strong><i class="fas fa-user mr-1"></i>{{ $item->responsable ?: 'No asignado' }}</strong>
                </div>
                <div class="it-data-item">
                    <label>Sitio actual / Guarda</label>
                    <strong><i class="fas fa-map-marker-alt mr-1"></i>{{ $item->ubicacion ?: 'Almacén Central' }}</strong>
                </div>
                
                <!-- Fecha Mantenimiento (Coordinado con color Ámbar) -->
                <div class="it-data-item">
                    <label>Próx. Mantenimiento</label>
                    <strong style="{{ $mantenimientoCaducado ? 'color: var(--it-amber);' : '' }}">
                        @if($item->fecha_mantenimiento)
                            <i class="fas fa-wrench mr-1" style="{{ $mantenimientoCaducado ? 'color: var(--it-amber);' : 'color: #87949b;' }}"></i>{{ $item->fecha_mantenimiento->format('d/m/Y') }}
                        @else
                            No requiere
                        @endif
                    </strong>
                </div>
                
                <!-- Renovación Programada (Coordinado con color Rojo) -->
                <div class="it-data-item">
                    <label>Renovación / Fin de ciclo</label>
                    <strong style="{{ $isObsoleto ? 'color: var(--it-red);' : '' }}">
                        @if($item->soporte_hasta)
                            <i class="fas fa-sync-alt mr-1" style="{{ $isObsoleto ? 'color: var(--it-red);' : 'color: #87949b;' }}"></i>{{ $item->soporte_hasta->format('d/m/Y') }}
                        @else
                            No planificada
                        @endif
                    </strong>
                </div>
            </div>
            
            <!-- Máquina de Estados para Botones -->
            <div class="it-card-actions">
                @if(in_array($item->estado, ['mantenimiento', 'reparacion']))
                    <a class="it-btn it-btn-primary" href="{{ route('equipos.show', $item) }}"><i class="fas fa-tools"></i> Seguimiento</a>
                    <a class="it-btn it-btn-light" href="{{ route('equipos.show', $item) }}"><i class="fas fa-upload"></i> Subir Parte</a>
                @elseif(empty($item->responsable))
                    <button type="button" class="it-btn it-btn-primary" onclick="openQuickAssignModal({{ $item->id }}, '{{ addslashes($item->nombre) }}')">
                        <i class="fas fa-user-plus"></i> Asignar Operario
                    </button>
                    <a class="it-btn it-btn-light" href="{{ route('equipos.show', $item) }}"><i class="fas fa-file-alt"></i> Ver Ficha</a>
                @else
                    <a class="it-btn it-btn-light" href="{{ route('equipos.show', $item) }}"><i class="fas fa-list"></i> Incidencias</a>
                    <button type="button" class="it-btn it-btn-primary" onclick="openQuickAssignModal({{ $item->id }}, '{{ addslashes($item->nombre) }}')">
                        <i class="fas fa-exchange-alt"></i> Reasignar
                    </button>
                @endif
            </div>
        </article>
    @empty
        <div class="col-12">
            <div class="text-center p-5 bg-white" style="border-radius: 8px;">
                <i class="fas fa-desktop fa-3x mb-3 text-muted"></i>
                <h4>No hay equipos informáticos registrados</h4>
            </div>
        </div>
    @endforelse
</div>

<div class="it-bottom">
    <span>Mostrando {{ $items->firstItem() ?: 0 }} - {{ $items->lastItem() ?: 0 }} de {{ $items->total() }} equipos</span>
    {{ $items->links() }}
</div>

<!-- Modal Global para previsualización de imágenes -->
<div id="photo-modal" onclick="closePhotoModal()">
    <div class="photo-dialog" onclick="event.stopPropagation()">
        <div class="photo-header">
            <h4 id="photo-modal-title">Fotografía del equipo</h4>
            <button type="button" onclick="closePhotoModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="photo-body">
            <img id="photo-modal-img" src="" alt="Vista previa">
        </div>
    </div>
</div>

<!-- Modal de Cambio Rápido de Estado -->
<div id="quick-state-modal" class="detail-modal" onclick="closeQuickStateModal()">
    <div class="detail-dialog" onclick="event.stopPropagation()">
        <h2 style="font-size: 18px; margin-bottom: 5px;">Cambio de estado operativo</h2>
        <p style="font-size: 12px; color: #687681; margin-bottom: 20px;">
            El equipo pasará a estado: <strong id="new-state-label" style="color: #191c1e;">...</strong>
        </p>
        
        <form id="quick-state-form" method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <input type="hidden" name="estado" id="quick-state-input" value="">
            
            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Motivo / Incidencia (Bitácora) *</label>
            <input required type="text" name="titulo_evento" class="form-control" placeholder="Ej. Envío a taller por fallo de RAM, Devuelto a almacén..." style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; margin-bottom: 12px;">
            
            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Foto o PDF adjunto (Opcional)</label>
            <input type="file" name="archivo_adjunto" class="form-control" accept=".pdf, image/jpeg, image/png" style="width: 100%; border: 0; background: #eef2f5; padding: 6px; border-radius: 4px; margin-bottom: 18px;">

            <div id="dias-baja-wrapper" style="display: none;">
                <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Días aproximados de baja (estimación, opcional)</label>
                <input type="number" name="dias_estimados_baja" min="1" max="365" placeholder="Ej. 5" class="form-control" style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; margin-bottom: 18px;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="it-btn it-btn-light" onclick="closeQuickStateModal()">Cancelar</button>
                <button type="submit" class="it-btn it-btn-primary"><i class="fas fa-save"></i> Confirmar cambio</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Asignación Rápida IT -->
<div id="quick-assign-modal" class="detail-modal" onclick="closeQuickAssignModal()">
    <div class="detail-dialog" onclick="event.stopPropagation()">
        <h2 style="font-size: 18px; margin-bottom: 5px;">Asignar / Reasignar equipo IT</h2>
        <p style="font-size: 12px; color: #687681; margin-bottom: 20px;">
            Gestionando el equipo: <strong id="assign-equip-name" style="color: #191c1e;">...</strong>
        </p>

        <form id="quick-assign-form" method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Usuario / Responsable *</label>
                    <input required type="text" name="responsable" class="form-control" placeholder="A quién se asigna" style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px;">
                </div>
                <div>
                    <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Ubicación / Puesto *</label>
                    <input required type="text" name="ubicacion" class="form-control" placeholder="Ej. Oficina Técnica, Nave 2" style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px;">
                </div>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Fotografía / Comprobante de entrega (Opcional)</label>
                <input type="file" name="archivo_adjunto" class="form-control" accept=".pdf, image/jpeg, image/png, image/webp" style="padding: 6px; border: 0; background: #eef2f5; font-size: 12px; width: 100%; border-radius: 4px;">
            </div>

            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Nota de entrega (Bitácora) *</label>
            <input required type="text" name="titulo_evento" class="form-control" placeholder="Ej. Renovación de equipo, Nueva incorporación..." style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; margin-bottom: 18px;">

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="it-btn it-btn-light" onclick="closeQuickAssignModal()">Cancelar</button>
                <button type="submit" class="it-btn it-btn-primary"><i class="fas fa-save"></i> Confirmar Entrega</button>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
    function openPhotoModal(imageUrl, equipmentName) {
        document.getElementById('photo-modal-img').src = imageUrl;
        document.getElementById('photo-modal-title').textContent = equipmentName;
        document.getElementById('photo-modal').classList.add('show');
    }
    function closePhotoModal() {
        document.getElementById('photo-modal').classList.remove('show');
        setTimeout(() => { document.getElementById('photo-modal-img').src = ''; }, 200);
    }
    function toggleDropdown(event, id) {
        event.stopPropagation();
        document.querySelectorAll('.it-dropdown-menu.show').forEach(menu => {
            if(menu.id !== 'dropdown-' + id) menu.classList.remove('show');
        });
        document.getElementById('dropdown-' + id).classList.toggle('show');
    }
    document.addEventListener('click', () => {
        document.querySelectorAll('.it-dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
    });
    function openQuickStateModal(equipoId, nuevoEstado, labelEstado) {
        document.getElementById('new-state-label').innerText = labelEstado;
        document.getElementById('quick-state-input').value = nuevoEstado;
        document.getElementById('quick-state-form').action = `/equipos/${equipoId}/estado`;
        document.getElementById('dias-baja-wrapper').style.display = (nuevoEstado === 'operativo') ? 'none' : 'block';
        document.getElementById('quick-state-modal').classList.add('show');
    }
    function closeQuickStateModal() {
        document.getElementById('quick-state-modal').classList.remove('show');
    }
    function openQuickAssignModal(equipoId, nombreEquipo) {
        document.getElementById('assign-equip-name').innerText = nombreEquipo;
        document.getElementById('quick-assign-form').action = `/equipos/${equipoId}/asignar`;
        document.getElementById('quick-assign-modal').classList.add('show');
    }
    function closeQuickAssignModal() {
        document.getElementById('quick-assign-modal').classList.remove('show');
    }
</script>
@endpush
@stop