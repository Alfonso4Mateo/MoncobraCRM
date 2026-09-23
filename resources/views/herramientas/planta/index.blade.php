@extends('adminlte::page')

@section('title', 'Herramientas de Planta y Maquinaria')

@section('content_header')
    <div class="plant-heading">
        <div>
            <small>Gestión de activos <span>/</span> Herramientas de planta</small>
            <h1>Herramientas de Planta y Maquinaria</h1>
            <p>Control de custodia, trazabilidad operativa, ciclos de mantenimiento y estado de equipos.</p>
        </div>
        <div class="plant-actions">
            <a class="plant-btn plant-btn-soft" href="#"><i class="fas fa-file-signature"></i> Exportar datos</a>
            <a class="plant-btn plant-btn-main" href="{{ route('herramientas.create') }}"><i class="fas fa-plus"></i> Nueva herramienta</a>
        </div>
    </div>
@stop

@push('js')
    <script>
        document.querySelectorAll('.plant-footer a').forEach(function(link){
            if(link.querySelector('.fa-eye')) link.href = link.href.replace(/\/editar$/, '');
        });
    </script>
@endpush

@section('content')
@include('herramientas.partials.architectural-ledger')

<style>
    :root {
        --plant-navy: #002442;
        --plant-ink: #191c1e;
        --plant-bg: #f7f9fb;
        --plant-low: #eef2f5;
        --plant-muted: #687681;
        --plant-red: #c92328;
        --plant-green: #0d9d7b;
        --plant-amber: #9a6512;
    }
    
    .content-wrapper { background: var(--plant-bg); }
    
    /* Header & General */
    .plant-heading { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; margin-bottom: 18px; }
    .plant-heading small { font-size: 10px; text-transform: uppercase; letter-spacing: .12em; color: #73818b; font-weight: 800; }
    .plant-heading small span { margin: 0 6px; color: #a3adb4; }
    .plant-heading h1 { font-size: 34px; line-height: 1.05; font-weight: 800; color: var(--plant-ink); margin: 5px 0; }
    .plant-heading p { font-size: 14px; color: var(--plant-muted); margin: 0; }
    .plant-actions { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
    
    /* Botones Globales */
    .plant-btn { border: 0; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 14px; font-size: 12px; font-weight: 800; text-decoration: none; white-space: nowrap; cursor: pointer; transition: all 0.2s; }
    .plant-btn-soft { background: #e8edf1; color: #284252; }
    .plant-btn-soft:hover { background: #dcecf8; color: #0d4675; }
    .plant-btn-main { background: linear-gradient(110deg, #002442, #155785); color: #fff; box-shadow: 0 5px 12px rgba(0,36,66,0.15); }
    
    /* Navegación y KPIs */
    .plant-tabs { display: flex; gap: 27px; border-bottom: 1px solid #dfe5e9; margin-bottom: 18px; padding: 0 3px; }
    .plant-tabs a { padding: 11px 0; color: #687681; font-size: 12px; font-weight: 700; text-decoration: none; }
    .plant-tabs a.active { color: var(--plant-navy); border-bottom: 3px solid var(--plant-navy); }
    .plant-tabs .online { margin-left: auto; color: var(--plant-green); padding-top: 11px; font-size: 11px; font-weight: 700; }
    
    .plant-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .plant-kpi { background: #fff; min-height: 104px; padding: 16px 17px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); position: relative; border-radius: 6px; }
    .plant-kpi:after { content: ""; position: absolute; left: 17px; width: calc(100% - 34px); bottom: 12px; height: 3px; background: #173d66; }
    .plant-kpi.red:after { background: #e23434; }
    .plant-kpi.green:after { background: #123b57; }
    .plant-kpi .label { font-size: 10px; text-transform: uppercase; letter-spacing: .1em; color: #64727c; font-weight: 800; }
    .plant-kpi .value { font-size: 26px; font-weight: 800; line-height: 1.1; color: #20262c; margin-top: 8px; }
    .plant-kpi .value small { font-size: 10px; color: #73818b; margin-left: 4px; }
    .plant-kpi.red .value { color: var(--plant-red); }
    .plant-kpi .sub { font-size: 10px; color: #73818b; margin-top: 4px; }
    
    /* Buscador y Filtros */
    .plant-filters { background: #fff; padding: 14px 15px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); margin-bottom: 18px; border-radius: 6px; }
    .plant-filters .form-control { border: 0; background: var(--plant-low); height: 36px; font-size: 12px; color: #3d4d57; }
    .plant-filters .input-group-text { border: 0; background: var(--plant-low); color: #71818b; }
    
    .plant-chipbar { display: flex; gap: 7px; flex-wrap: wrap; margin-top: 12px; }
    .plant-chip { border: 0; background: #eef1f3; color: #44545d; padding: 6px 12px; border-radius: 4px; font-size: 10px; font-weight: 700; cursor: pointer; }
    .plant-chip.active { background: var(--plant-navy); color: #fff; }
    
    /* GRID COMPACTO DE ALTA DENSIDAD */
    .plant-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
    
    .plant-card-compact { background: #fff; box-shadow: 0 4px 12px rgba(25,28,30,0.04); border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; }
    
    /* Cabecera de Tarjeta */
    .plant-card-top { padding: 14px 16px; border-bottom: 1px solid #edf0f2; display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .plant-titles { flex: 1; }
    .plant-titles small { font-size: 10px; color: #697983; font-weight: 800; text-transform: uppercase; display: flex; align-items: center; gap: 5px; margin-bottom: 4px; letter-spacing: 0.05em; }
    .plant-titles h3 { font-size: 15px; font-weight: 800; color: #1e2b33; margin: 0; line-height: 1.2; }
    
    /* Botón de Cámara Integrado */
    .plant-btn-cam { background: #eef2f5; color: #17619a; border: 0; width: 32px; height: 32px; border-radius: 6px; display: grid; place-items: center; cursor: pointer; transition: all 0.2s; font-size: 13px; }
    .plant-btn-cam:hover { background: #dcecf8; color: #0d4675; }
    .plant-id-badge { background: #eef2f5; color: #52636d; font-size: 9px; font-weight: 800; padding: 4px 7px; border-radius: 4px; letter-spacing: 0.05em; }
    
    /* Datos Ultra Compactos (Píldoras) */
    .plant-specs-line { display: flex; gap: 12px; padding: 10px 16px; background: #fafbfc; border-bottom: 1px solid #edf0f2; font-size: 10px; color: #52636d; font-weight: 700; }
    .plant-specs-line span { display: inline-flex; align-items: center; gap: 5px; }
    .plant-specs-line i { color: #87949b; }
    
    .plant-data-grid { padding: 14px 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; flex: 1; }
    .plant-data-item label { display: block; font-size: 9px; color: #7b8991; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 2px; }
    .plant-data-item strong { display: block; font-size: 11px; color: #273740; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .plant-data-item .ok { color: var(--plant-green); }
    
    .plant-alert-ribbon { padding: 8px 16px; font-size: 10px; font-weight: 800; }
    .plant-alert-ribbon.danger { background: #fff0ef; color: #a12328; }
    .plant-alert-ribbon.warning { background: #fff4df; color: #705319; }
    
    /* Menú Desplegable de Estado */
    .plant-dropdown { position: relative; display: inline-block; }
    .plant-dropdown-toggle { cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 2px 6px; border-radius: 4px; transition: background 0.2s; }
    .plant-dropdown-toggle:hover { background: #eef2f5; }
    .plant-dropdown-menu { display: none; position: absolute; top: 100%; left: 0; background: #fff; box-shadow: 0 10px 30px rgba(0,36,66,0.15); border-radius: 6px; padding: 6px; z-index: 100; min-width: 170px; border: 1px solid #edf0f2; }
    .plant-dropdown-menu.show { display: flex; flex-direction: column; gap: 2px; }
    .plant-dropdown-item { text-align: left; background: transparent; border: 0; padding: 8px 10px; font-size: 10px; font-weight: 800; color: #52636d; border-radius: 4px; cursor: pointer; transition: all 0.2s; }
    .plant-dropdown-item:hover { background: #f7f9fb; color: #191c1e; }
    .plant-dropdown-item i { margin-right: 6px; font-size: 8px; }
    
    /* Footer de Botones */
    .plant-card-actions { display: flex; gap: 6px; padding: 12px 16px; border-top: 1px solid #edf0f2; }
    .plant-card-actions .plant-btn { flex: 1; justify-content: center; padding: 8px 10px; font-size: 10px; }
    
    .plant-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; background: #fff; padding: 14px 16px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); color: #687681; font-size: 11px; border-radius: 6px; }
    
    /* MODALES */
    .detail-modal { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.6); z-index: 1100; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); }
    .detail-modal.show { display: flex; }
    .detail-dialog { background: #fff; width: 100%; max-width: 500px; padding: 24px; box-shadow: 0 16px 45px rgba(0,36,66,0.2); border-radius: 8px; }

    /* Bordes dinámicos de alerta */
    .plant-card-compact { border: 1px solid transparent; background: #fff; box-shadow: 0 4px 12px rgba(25,28,30,0.04); border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; }
    .plant-card-compact.card-critical { border-left: 4px solid var(--plant-red) !important; border-color: #fee1df; }
    .plant-card-compact.card-warning { border-left: 4px solid var(--plant-amber) !important; border-color: #fbe6c4; }

    #photo-modal { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.75); z-index: 1200; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(5px); }
    #photo-modal.show { display: flex; }
    .photo-dialog { background: #fff; border-radius: 8px; overflow: hidden; max-width: 600px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
    .photo-header { padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf0f2; }
    .photo-header h4 { margin: 0; font-size: 15px; font-weight: 800; color: var(--plant-ink); }
    .photo-header button { border: 0; background: transparent; color: #87949b; cursor: pointer; font-size: 16px; }
    .photo-header button:hover { color: var(--plant-red); }
    .photo-body img { width: 100%; height: auto; display: block; max-height: 70vh; object-fit: contain; background: var(--plant-low); }

    @media(max-width: 900px) {
        .plant-heading { align-items: flex-start; flex-direction: column; }
        .plant-actions { justify-content: flex-start; }
        .plant-kpis { grid-template-columns: repeat(2, 1fr); }
    }
    @media(max-width: 650px) {
        .plant-grid { grid-template-columns: 1fr; }
        .plant-kpis { grid-template-columns: 1fr 1fr; }
        .plant-tabs { gap: 12px; overflow: auto; }
        .plant-tabs .online { display: none; }
        .plant-heading h1 { font-size: 25px; }
    }
</style>

<nav class="plant-tabs">
    <a href="{{ route('equipos.index') }}">Equipos informáticos</a>
    <a class="active" href="{{ route('herramientas.index') }}">Herramientas de planta</a>
    <a href="{{ route('calibrables.index') }}">Aparatos calibrables</a>
    <a href="{{ route('alertas.index') }}">Alertas y mantenimiento</a>
</nav>

<div class="plant-kpis">
    <div class="plant-kpi">
        <div class="label">Herramientas operativas</div>
        <div class="value">{{ $stats['total'] }} <small>{{ $stats['total'] ? round(($stats['operativos'] / $stats['total']) * 100, 1) : 0 }}% parque activo</small></div>
        <div class="sub">Disponibles o en custodia funcional</div>
    </div>
    <div class="plant-kpi green">
        <div class="label">En obra / asignadas</div>
        <div class="value">{{ $stats['operativos'] }} <small>cuadrillas</small></div>
        <div class="sub">Con trazabilidad de entrega</div>
    </div>
    <div class="plant-kpi red">
        <div class="label">En mantenimiento / taller</div>
        <div class="value">{{ $stats['mantenimiento'] }} <small>atención requerida</small></div>
        <div class="sub">Revisar preventivos y averías</div>
    </div>
    <div class="plant-kpi">
        <div class="label">Disponibilidad de planta</div>
        <div class="value">{{ $stats['total'] ? round(($stats['operativos'] / $stats['total']) * 100) : 0 }}% <small>índice operativo</small></div>
        <div class="sub">Objetivo: mantener parque disponible</div>
    </div>
</div>

<form class="plant-filters" method="GET" action="{{ route('herramientas.index') }}">
    <div class="row">
        <div class="col-lg-3 mb-2 mb-lg-0">
            <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                <input class="form-control" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por ID, modelo...">
            </div>
        </div>
        <div class="col-lg-2 mb-2 mb-lg-0">
            <select name="estado" class="form-control">
                <option value="">Estado (Todos)</option>
                @foreach($estados as $estado)
                    <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 mb-2 mb-lg-0">
            <select name="familia" class="form-control">
                <option value="">Familia energética</option>
                @foreach($familias as $familia)
                    <option value="{{ $familia->id }}" @selected(request('familia') == $familia->id)>{{ $familia->nombre }}</option>
                @endforeach
            </select>
        </div>
        <!-- NUEVO FILTRO DE ALERTAS LEGALES -->
        <div class="col-lg-4 mb-2 mb-lg-0">
            <select name="alerta" class="form-control">
                <option value="">Alertas de Inspección / Taller</option>
                <option value="oca_vencida" @selected(request('alerta') === 'oca_vencida')>Inspección Legal Caducada</option>
                <option value="oca_proxima" @selected(request('alerta') === 'oca_proxima')>Inspección Legal Próxima (<30 días)</option>
                <option value="mantenimiento" @selected(request('alerta') === 'mantenimiento')>Mantenimiento Interno Vencido</option>
            </select>
        </div>
        <div class="col-lg-1">
            <button class="plant-btn plant-btn-main w-100" title="Filtrar"><i class="fas fa-filter"></i></button>
        </div>
    </div>
</form>

<div class="plant-grid">
    @forelse($items as $item)
        @php 
            $image = $item->imagen ? asset('storage/'.$item->imagen) : null; 
            $blocked = $item->bloqueado;
            
            // Calculamos las alertas de fechas
            $inspeccionCaducada = $item->fecha_inspeccion && $item->fecha_inspeccion->isPast();
            $inspeccionProxima = $item->fecha_inspeccion && $item->fecha_inspeccion->diffInDays(now()) <= 30 && !$inspeccionCaducada;
            $mantenimientoCaducado = $item->fecha_mantenimiento && $item->fecha_mantenimiento->isPast();
            
            // Determinamos el nivel de criticidad de la tarjeta entera
            $cardLevelClass = '';
            if ($blocked || $inspeccionCaducada) {
                $cardLevelClass = 'card-critical'; // ROJO (Bloqueo o Ilegal)
            } elseif ($inspeccionProxima || $mantenimientoCaducado) {
                $cardLevelClass = 'card-warning'; // NARANJA (Aviso importante)
            }
        @endphp
        
        <article class="plant-card-compact {{ $cardLevelClass }}">
            <!-- Header Compacto con Garantía -->
            <div class="plant-card-top">
                <div class="plant-titles">
                    <div class="plant-dropdown" onclick="toggleDropdown(event, {{ $item->id }})">
                        <small class="plant-dropdown-toggle" title="Cambiar estado de la maquinaria">
                            <i class="fas fa-circle" style="color: {{ $blocked ? '#c92328' : ($alert ? '#9a6512' : '#0d9d7b') }}; font-size: 8px;"></i>
                            {{ $item->estado_label }}
                              <i class="fas fa-chevron-down" style="font-size: 8px; color: #87949b; margin-left: 2px;"></i>
                        </small>
                        
                        <!-- Menú rápido (se mantiene igual) -->
                        <div class="plant-dropdown-menu" id="dropdown-{{ $item->id }}">
                            @if($item->estado !== 'operativo')
                                <button type="button" class="plant-dropdown-item" onclick="openQuickStateModal({{ $item->id }}, 'operativo', 'Operativo (Devolver a Almacén)')">
                                    <i class="fas fa-box" style="color: #0d9d7b;"></i> Devolver a Almacén
                                </button>
                            @endif
                            @if(!in_array($item->estado, ['mantenimiento', 'reparacion']))
                                <button type="button" class="plant-dropdown-item" onclick="openQuickStateModal({{ $item->id }}, 'mantenimiento', 'En mantenimiento')">
                                    <i class="fas fa-wrench" style="color: #9a6512;"></i> Mantenimiento Preventivo
                                </button>
                                <button type="button" class="plant-dropdown-item" onclick="openQuickStateModal({{ $item->id }}, 'reparacion', 'En reparación (Bloqueo)')">
                                    <i class="fas fa-tools" style="color: #c92328;"></i> Reportar Avería / Taller
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Nombre y Píldora de Garantía -->
                    <h3 style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        {{ $item->nombre }}
                        @if($item->garantia_hasta && $item->garantia_hasta->isFuture())
                            <span style="font-size: 9px; padding: 2px 6px; background: #e2f7ee; color: #08734e; border-radius: 4px; border: 1px solid #b8e6d3; white-space: nowrap;">
                                <i class="fas fa-shield-alt mr-1"></i> Garantía de: {{ $item->garantia_hasta->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                            </span>
                        @elseif($item->garantia_hasta && $item->garantia_hasta->isPast())
                            <span style="font-size: 9px; padding: 2px 6px; background: #fee1df; color: #c92328; border-radius: 4px; border: 1px solid #f8c9c7; white-space: nowrap;">
                                <i class="fas fa-shield-alt mr-1"></i> Garantía Expirada
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
                    <button type="button" class="plant-btn-cam" onclick="openPhotoModal('{{ $image }}', '{{ addslashes($item->nombre) }}')" title="Ver fotografía">
                        <i class="fas fa-camera"></i>
                    </button>
                @else
                    <div class="plant-id-badge">{{ $item->codigo ?: 'S/C' }}</div>
                @endif
            </div>

            <!-- Specs Industriales -->
            <div class="plant-specs-line">
                <span><i class="fas fa-bolt"></i> {{ $item->potencia ?: 'Pot. N/A' }}</span>
                <span><i class="fas fa-plug"></i> {{ $item->tension ?: 'Ten. N/A' }}</span>
                <span><i class="fas fa-gas-pump"></i> {{ $item->combustible ?: 'Alim. N/A' }}</span>
            </div>

            <!-- Alertas Condicionales (Apiladas) -->
            @if($blocked)
                <div class="plant-alert-ribbon danger"><i class="fas fa-lock mr-1"></i> Bloqueada: {{ $item->motivo_bloqueo ?: 'En reparación/taller' }}</div>
            @endif
            
            <!-- Alertas de Inspección Legal -->
            @if($item->fecha_inspeccion && $item->fecha_inspeccion->isPast())
                <div class="plant-alert-ribbon danger"><i class="fas fa-ban mr-1"></i> INSPECCIÓN LEGAL CADUCADA (Prohibido su uso)</div>
            @elseif($item->fecha_inspeccion && $item->fecha_inspeccion->diffInDays(now()) <= 30)
                <div class="plant-alert-ribbon warning" style="background: #fff4df; color: #705319;"><i class="fas fa-clipboard-check mr-1"></i> Inspección legal caduca en {{ now()->diffInDays($item->fecha_inspeccion) }} días</div>
            @endif
            
            <!-- Alertas de Mantenimiento Interno -->
            @if($item->fecha_mantenimiento && $item->fecha_mantenimiento->isPast())
                <div class="plant-alert-ribbon warning"><i class="fas fa-exclamation-triangle mr-1"></i> Mantenimiento interno vencido</div>
            @elseif($item->fecha_mantenimiento && $item->fecha_mantenimiento->diffInDays(now()) <= 15)
                <div class="plant-alert-ribbon" style="background: #eef2f5; color: #52636d;"><i class="fas fa-wrench mr-1"></i> Mantenimiento en {{ now()->diffInDays($item->fecha_mantenimiento) }} días</div>
            @endif

            <!-- Datos principales -->
            <div class="plant-data-grid">
                <div class="plant-data-item">
                    <label>{{ $item->responsable ? 'Asignado a' : 'Último operario / Libre' }}</label>
                    <strong><i class="fas fa-hard-hat mr-1"></i>{{ $item->responsable ?: 'No asignado' }}</strong>
                </div>
                <div class="plant-data-item">
                    <label>Sitio / Obra actual</label>
                    <strong><i class="fas fa-map-marker-alt mr-1"></i>{{ $item->ubicacion ?: 'Almacén Central' }}</strong>
                </div>
                
                <!-- Fecha Mantenimiento (Se pinta de ámbar si está vencida) -->
                <div class="plant-data-item">
                    <label>Mantenimiento Interno</label>
                    <strong style="{{ $item->fecha_mantenimiento && $item->fecha_mantenimiento->isPast() ? 'color: var(--plant-amber);' : '' }}">
                        @if($item->fecha_mantenimiento)
                            <i class="fas fa-wrench mr-1" style="{{ $item->fecha_mantenimiento && $item->fecha_mantenimiento->isPast() ? 'color: var(--plant-amber);' : 'color: #87949b;' }}"></i>{{ $item->fecha_mantenimiento->format('d/m/Y') }}
                        @else
                            No requiere
                        @endif
                    </strong>
                </div>
                
                <!-- Fecha Inspección (Se pinta de rojo si está vencida) -->
                <div class="plant-data-item">
                    <label>Inspección Legal</label>
                    <strong style="{{ $item->fecha_inspeccion && $item->fecha_inspeccion->isPast() ? 'color: var(--plant-red);' : '' }}">
                        @if($item->fecha_inspeccion)
                            <i class="fas fa-stamp mr-1" style="{{ $item->fecha_inspeccion && $item->fecha_inspeccion->isPast() ? 'color: var(--plant-red);' : 'color: #87949b;' }}"></i>{{ $item->fecha_inspeccion->format('d/m/Y') }}
                        @else
                            No requiere
                        @endif
                    </strong>
                </div>
            </div>
            
            <!-- Máquina de Estados -->
            <div class="plant-card-actions">
                @if(in_array($item->estado, ['mantenimiento', 'reparacion']) || $blocked)
                    <a class="plant-btn plant-btn-main" href="{{ route('herramientas.planta.show', $item) }}"><i class="fas fa-tools"></i> Reparación</a>
                    <a class="plant-btn plant-btn-soft" href="{{ route('herramientas.planta.show', $item) }}"><i class="fas fa-upload"></i> Parte</a>
                @elseif(empty($item->responsable))
                    <button type="button" class="plant-btn plant-btn-main" onclick="openQuickAssignModal({{ $item->id }}, '{{ addslashes($item->nombre) }}')">
                        <i class="fas fa-dolly"></i> Entregar (Obra)
                    </button>
                    <a class="plant-btn plant-btn-soft" href="{{ route('herramientas.planta.show', $item) }}"><i class="fas fa-file-alt"></i> Ficha / Manual</a>
                @else
                    <a class="plant-btn plant-btn-soft" href="{{ route('herramientas.planta.show', $item) }}"><i class="fas fa-list"></i> Historial</a>
                    <button type="button" class="plant-btn plant-btn-main" onclick="openQuickAssignModal({{ $item->id }}, '{{ addslashes($item->nombre) }}')">
                        <i class="fas fa-exchange-alt"></i> Trasladar
                    </button>
                @endif
            </div>
        </article>
    @empty
        <div class="col-12">
            <div class="text-center p-5 bg-white" style="border-radius: 8px;">
                <i class="fas fa-tools fa-3x mb-3 text-muted"></i>
                <h4>No hay herramientas de planta registradas</h4>
                <p>Comienza agregando la maquinaria al catálogo.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="plant-bottom">
    <span>Mostrando {{ $items->firstItem() ?: 0 }} - {{ $items->lastItem() ?: 0 }} de {{ $items->total() }} herramientas activas</span>
    {{ $items->links() }}
</div>

<!-- Modal Global para previsualización de imágenes -->
<div id="photo-modal" onclick="closePhotoModal()">
    <div class="photo-dialog" onclick="event.stopPropagation()">
        <div class="photo-header">
            <h4 id="photo-modal-title">Fotografía de la herramienta</h4>
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
        <h2 style="font-size: 18px; margin-bottom: 5px;">Cambio de estado en planta</h2>
        <p style="font-size: 12px; color: #687681; margin-bottom: 20px;">
            La máquina pasará a estado: <strong id="new-state-label" style="color: #191c1e;">...</strong>
        </p>
        
        <form id="quick-state-form" method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <input type="hidden" name="estado" id="quick-state-input" value="">
            
            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Motivo / Incidencia (Bitácora) *</label>
            <input required type="text" name="titulo_evento" class="form-control" placeholder="Ej. Devolución a almacén central, Envío a taller por fallo de motor..." style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; margin-bottom: 12px;">
            
            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Foto o PDF inicial (Opcional)</label>
            <input type="file" name="archivo_adjunto" class="form-control" accept=".pdf, image/jpeg, image/png" style="width: 100%; border: 0; background: #eef2f5; padding: 6px; border-radius: 4px; margin-bottom: 18px;">

            <div id="dias-baja-wrapper" style="display: none;">
                <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Días aproximados de baja (estimación, opcional)</label>
                <input type="number" name="dias_estimados_baja" min="1" max="365" placeholder="Ej. 5" class="form-control" style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; margin-bottom: 18px;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="plant-btn plant-btn-soft" onclick="closeQuickStateModal()">Cancelar</button>
                <button type="submit" class="plant-btn plant-btn-main"><i class="fas fa-save"></i> Confirmar movimiento</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Asignación Rápida a Obra -->
<div id="quick-assign-modal" class="detail-modal" onclick="closeQuickAssignModal()">
    <div class="detail-dialog" onclick="event.stopPropagation()">
        <h2 style="font-size: 18px; margin-bottom: 5px;">Asignar / Trasladar maquinaria</h2>
        <p style="font-size: 12px; color: #687681; margin-bottom: 20px;">
            Asignando el equipo: <strong id="assign-equip-name" style="color: #191c1e;">...</strong>
        </p>

        <form id="quick-assign-form" method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Operario / Cuadrilla *</label>
                    <input required type="text" name="responsable" class="form-control" placeholder="A quién se entrega" style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px;">
                </div>
                <div>
                    <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Ubicación / Obra *</label>
                    <input required type="text" name="ubicacion" class="form-control" placeholder="A dónde va" style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px;">
                </div>
            </div>

            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Nota de entrega (Bitácora) *</label>
            <input required type="text" name="titulo_evento" class="form-control" placeholder="Ej. Entrega para inicio de fase 2..." style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; margin-bottom: 12px;">

            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Albarán o Foto de entrega (Opcional)</label>
            <input type="file" name="archivo_adjunto" class="form-control" accept=".pdf, image/jpeg, image/png" style="width: 100%; border: 0; background: #eef2f5; padding: 6px; border-radius: 4px; margin-bottom: 18px;">

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="plant-btn plant-btn-soft" onclick="closeQuickAssignModal()">Cancelar</button>
                <button type="submit" class="plant-btn plant-btn-main"><i class="fas fa-save"></i> Confirmar Entrega</button>
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
        document.querySelectorAll('.plant-dropdown-menu.show').forEach(menu => {
            if(menu.id !== 'dropdown-' + id) menu.classList.remove('show');
        });
        document.getElementById('dropdown-' + id).classList.toggle('show');
    }

    document.addEventListener('click', () => {
        document.querySelectorAll('.plant-dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
    });

    function openQuickStateModal(equipoId, nuevoEstado, labelEstado) {
        document.getElementById('new-state-label').innerText = labelEstado;
        document.getElementById('quick-state-input').value = nuevoEstado;
        // Apuntamos a la nueva ruta que crearemos en el controlador de HerramientasPlanta
        document.getElementById('quick-state-form').action = `/herramientas/${equipoId}/estado`;
        document.getElementById('dias-baja-wrapper').style.display = (nuevoEstado === 'operativo') ? 'none' : 'block';
        document.getElementById('quick-state-modal').classList.add('show');
    }

    function closeQuickStateModal() {
        document.getElementById('quick-state-modal').classList.remove('show');
    }

    function openQuickAssignModal(equipoId, nombreEquipo) {
        document.getElementById('assign-equip-name').innerText = nombreEquipo;
        // Apuntamos a la nueva ruta de asignación
        document.getElementById('quick-assign-form').action = `/herramientas/${equipoId}/asignar`;
        document.getElementById('quick-assign-modal').classList.add('show');
    }

    function closeQuickAssignModal() {
        document.getElementById('quick-assign-modal').classList.remove('show');
    }
</script>
@endpush
@stop