@extends('adminlte::page')

@section('title', 'Aparatos Calibrables y Metrología')

@section('content_header')
    <div class="cal-heading">
        <div>
            <div class="cal-kicker">Gestión de activos <span>/</span> Aparatos calibrables</div>
            <h1>Metrología y Equipos de Medición</h1>
            <p>Control de certificaciones, fechas de calibración y exactitud operativa de los equipos.</p>
        </div>
        <div class="cal-actions">
            <a class="cal-btn cal-btn-soft" href="#"><i class="fas fa-file-signature"></i> Exportar datos</a>
            <a class="cal-btn cal-btn-main" href="{{ route('calibrables.create') }}"><i class="fas fa-plus"></i> Registrar equipo</a>
        </div>
    </div>
@stop

@section('content')
@include('herramientas.partials.architectural-ledger')

<style>
    :root {
        --cal-navy: #002442;
        --cal-ink: #191c1e;
        --cal-bg: #f7f9fb;
        --cal-low: #eef2f5;
        --cal-muted: #687681;
        --cal-red: #c92328;
        --cal-green: #0d9d7b;
        --cal-amber: #dca54a;
        --cal-blue: #17619a;
    }

    /* Modales */
    .detail-modal { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.6); z-index: 1100; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); }
    .detail-modal.show { display: flex; }
    .detail-dialog { background: #fff; width: 100%; max-width: 500px; padding: 24px; box-shadow: 0 16px 45px rgba(0,36,66,0.2); border-radius: 8px; }
    
    /* Modal Global de Imagen */
    #photo-modal { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.75); z-index: 1200; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(5px); }
    #photo-modal.show { display: flex; }
    .photo-dialog { background: #fff; border-radius: 8px; overflow: hidden; max-width: 600px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
    .photo-header { padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf0f2; }
    .photo-header h4 { margin: 0; font-size: 15px; font-weight: 800; color: var(--cal-ink); }
    .photo-header button { border: 0; background: transparent; color: #87949b; cursor: pointer; font-size: 16px; }
    .photo-header button:hover { color: var(--cal-red); }
    .photo-body img { width: 100%; height: auto; display: block; max-height: 70vh; object-fit: contain; background: var(--cal-low); }
    
    .content-wrapper { background: var(--cal-bg); }
    
    /* Header & General */
    .cal-heading { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; margin-bottom: 20px; }
    .cal-kicker { font-size: 10px; text-transform: uppercase; letter-spacing: .12em; color: #73818b; font-weight: 800; }
    .cal-kicker span { margin: 0 6px; color: #a3adb4; }
    .cal-heading h1 { font-size: 34px; line-height: 1.05; font-weight: 800; color: var(--cal-ink); margin: 5px 0; }
    .cal-heading p { font-size: 14px; color: var(--cal-muted); margin: 0; }
    .cal-actions { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
    
    /* Botones Globales */
    .cal-btn { border: 0; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 14px; font-size: 12px; font-weight: 800; text-decoration: none; white-space: nowrap; cursor: pointer; transition: all 0.2s; }
    .cal-btn-soft { background: #e8edf1; color: #284252; }
    .cal-btn-soft:hover { background: #dcecf8; color: #0d4675; }
    .cal-btn-main { background: linear-gradient(110deg, #002442, #155785); color: #fff; box-shadow: 0 5px 12px rgba(0,36,66,0.15); }
    
    /* Navegación y KPIs */
    .cal-tabs { display: flex; gap: 27px; border-bottom: 1px solid #dfe5e9; margin-bottom: 18px; padding: 0 3px; }
    .cal-tabs a { padding: 11px 0; color: #687681; font-size: 12px; font-weight: 700; text-decoration: none; }
    .cal-tabs a.active { color: var(--cal-navy); border-bottom: 3px solid var(--cal-navy); }
    
    .cal-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .cal-kpi { background: #fff; min-height: 100px; padding: 16px 17px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); position: relative; border-radius: 6px; }
    .cal-kpi:after { content: ""; position: absolute; left: 17px; width: calc(100% - 34px); bottom: 12px; height: 3px; background: #173d66; }
    .cal-kpi.red:after { background: #e23434; }
    .cal-kpi.amber:after { background: var(--cal-amber); }
    .cal-kpi .label { font-size: 10px; text-transform: uppercase; letter-spacing: .1em; color: #64727c; font-weight: 800; }
    .cal-kpi .value { font-size: 26px; font-weight: 800; line-height: 1.1; color: #20262c; margin-top: 8px; }
    .cal-kpi .value small { font-size: 10px; color: #73818b; margin-left: 4px; }
    .cal-kpi.red .value { color: var(--cal-red); }
    .cal-kpi .sub { font-size: 10px; color: #73818b; margin-top: 4px; }
    
    /* Buscador */
    .cal-filters { background: #fff; padding: 14px 15px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); margin-bottom: 18px; border-radius: 6px; }
    .cal-filters .form-control { border: 0; background: var(--cal-low); height: 36px; font-size: 12px; color: #3d4d57; }
    .cal-filters .input-group-text { border: 0; background: var(--cal-low); color: #71818b; }
    
    /* GRID COMPACTO */
    .cal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
    .cal-card-compact { background: #fff; box-shadow: 0 4px 12px rgba(25,28,30,0.04); border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; border: 1px solid transparent; }
    
    /* Bordes dinámicos de alerta */
    .cal-card-compact.card-critical { border-left: 4px solid var(--cal-red) !important; border-color: #fee1df; }
    .cal-card-compact.card-warning { border-left: 4px solid var(--cal-amber) !important; border-color: #fbe6c4; }
    .cal-card-compact.card-info { border-left: 4px solid var(--cal-blue) !important; border-color: #dcecf8; }

    /* Cabecera de Tarjeta */
    .cal-card-top { padding: 14px 16px; border-bottom: 1px solid #edf0f2; display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .cal-titles { flex: 1; }
    .cal-titles small { font-size: 10px; color: #697983; font-weight: 800; text-transform: uppercase; display: flex; align-items: center; gap: 5px; margin-bottom: 4px; letter-spacing: 0.05em; }
    .cal-titles h3 { font-size: 15px; font-weight: 800; color: #1e2b33; margin: 0; line-height: 1.2; }
    
    /* Botón de Cámara */
    .cal-btn-cam { background: #eef2f5; color: #17619a; border: 0; width: 32px; height: 32px; border-radius: 6px; display: grid; place-items: center; cursor: pointer; transition: all 0.2s; font-size: 13px; }
    .cal-btn-cam:hover { background: #dcecf8; color: #0d4675; }
    .cal-id-badge { background: #eef2f5; color: #52636d; font-size: 9px; font-weight: 800; padding: 4px 7px; border-radius: 4px; letter-spacing: 0.05em; }
    
    /* Menú Desplegable */
    .cal-dropdown { position: relative; display: inline-block; }
    .cal-dropdown-toggle { cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 2px 6px; border-radius: 4px; transition: background 0.2s; }
    .cal-dropdown-toggle:hover { background: #eef2f5; }
    .cal-dropdown-menu { display: none; position: absolute; top: 100%; left: 0; background: #fff; box-shadow: 0 10px 30px rgba(0,36,66,0.15); border-radius: 6px; padding: 6px; z-index: 100; min-width: 190px; border: 1px solid #edf0f2; }
    .cal-dropdown-menu.show { display: flex; flex-direction: column; gap: 2px; }
    .cal-dropdown-item { text-align: left; background: transparent; border: 0; padding: 8px 10px; font-size: 10px; font-weight: 800; color: #52636d; border-radius: 4px; cursor: pointer; transition: all 0.2s; }
    .cal-dropdown-item:hover { background: #f7f9fb; color: #191c1e; }
    
    /* Datos y Alertas */
    .cal-specs-line { display: flex; gap: 12px; padding: 10px 16px; background: #fafbfc; border-bottom: 1px solid #edf0f2; font-size: 10px; color: #52636d; font-weight: 700; }
    .cal-specs-line span { display: inline-flex; align-items: center; gap: 5px; }
    .cal-specs-line i { color: #87949b; }
    
    .cal-data-grid { padding: 14px 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; flex: 1; }
    .cal-data-item label { display: block; font-size: 9px; color: #7b8991; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 2px; }
    .cal-data-item strong { display: block; font-size: 11px; color: #273740; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    /* Cintas apilables (Ribbons) */
    .cal-alert-ribbon { padding: 8px 16px; font-size: 10px; font-weight: 800; display: flex; align-items: center; gap: 6px; }
    .cal-alert-ribbon.danger { background: #fff0ef; color: #a12328; }
    .cal-alert-ribbon.warning { background: #fff4df; color: #705319; }
    .cal-alert-ribbon.info { background: #eef2f5; color: #17619a; }
    
    /* Footer de Botones */
    .cal-card-actions { display: flex; gap: 6px; padding: 12px 16px; border-top: 1px solid #edf0f2; }
    .cal-card-actions .cal-btn { flex: 1; justify-content: center; padding: 8px 10px; font-size: 10px; }
    
    .cal-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; background: #fff; padding: 14px 16px; box-shadow: 0 4px 16px rgba(25,28,30,0.03); color: #687681; font-size: 11px; border-radius: 6px; }
    
    /* Modales */
    .detail-modal { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.6); z-index: 1100; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); }
    .detail-modal.show { display: flex; }
    .detail-dialog { background: #fff; width: 100%; max-width: 500px; padding: 24px; box-shadow: 0 16px 45px rgba(0,36,66,0.2); border-radius: 8px; }
    #photo-modal { z-index: 1200; }

    @media(max-width: 900px) {
        .cal-heading { align-items: flex-start; flex-direction: column; }
        .cal-kpis { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<nav class="cal-tabs">
    <a href="{{ route('equipos.index') }}">Equipos informáticos</a>
    <a href="{{ route('herramientas.index') }}">Herramientas de planta</a>
    <a class="active" href="{{ route('calibrables.index') }}">Aparatos calibrables</a>
    <a href="{{ route('alertas.index') }}">Alertas y mantenimiento</a>
</nav>

<div class="cal-kpis">
    <div class="cal-kpi">
        <div class="label">Total Metrología</div>
        <div class="value">{{ $stats['total'] ?? 0 }} <small>instrumentos en BD</small></div>
    </div>
    <div class="cal-kpi amber">
        <div class="label">En Laboratorio</div>
        <div class="value">{{ $stats['mantenimiento'] ?? 0 }} <small>calibrándose / tránsito</small></div>
        <div class="sub">Requieren seguimiento de entrega</div>
    </div>
    <div class="cal-kpi red">
        <div class="label">Fallo o Caducados</div>
        <div class="value">{{ $stats['alertas'] ?? 0 }} <small>No aptos para uso</small></div>
        <div class="sub">Equipos aislados y vencidos</div>
    </div>
    <div class="cal-kpi">
        <div class="label">Aptos / Vigentes</div>
        <div class="value">{{ $stats['operativos'] ?? 0 }} <small>certificados OK</small></div>
    </div>
</div>

<form class="cal-filters" method="GET" action="{{ route('calibrables.index') }}">
    <div class="row">
        <div class="col-lg-5 mb-2 mb-lg-0">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input class="form-control" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por ID, modelo, marca o magnitud">
            </div>
        </div>
        <div class="col-lg-3 mb-2 mb-lg-0">
            <select name="estado" class="form-control">
                <option value="">Estado operacional</option>
                <option value="operativo" @selected(request('estado') === 'operativo')>Operativo / Vigente</option>
                <option value="calibracion" @selected(request('estado') === 'calibracion')>En Laboratorio / Calibración</option>
                <option value="aislado" @selected(request('estado') === 'aislado')>Aislado / Fallo metrológico</option>
            </select>
        </div>
        <div class="col-lg-3 mb-2 mb-lg-0">
            <select name="alerta" class="form-control">
                <option value="">Estado de certificación</option>
                <option value="caducado" @selected(request('alerta') === 'caducado')>Certificado caducado</option>
                <option value="proximo" @selected(request('alerta') === 'proximo')>Caducan en < 30 días</option>
            </select>
        </div>
        <div class="col-lg-1">
            <button class="cal-btn cal-btn-main w-100" title="Filtrar"><i class="fas fa-filter"></i></button>
        </div>
    </div>
</form>

<div class="cal-grid">
    @forelse($items as $item)
        @php 
            // MOTOR LÓGICO DE METROLOGÍA
            $isAislado = $item->estado === 'aislado';
            $isLaboratorio = $item->estado === 'calibracion';
            $isExpired = $item->proxima_calibracion && $item->proxima_calibracion->isPast();
            $daysLeft = $item->proxima_calibracion ? now()->diffInDays($item->proxima_calibracion, false) : 999;
            $isExpiringSoon = $daysLeft >= 0 && $daysLeft <= 30 && !$isExpired;
            $image = $item->imagen ? asset('storage/'.$item->imagen) : null; 
            
            // Determinamos el color del borde y punto de la tarjeta
            $cardLevelClass = '';
            $statusColor = '#0d9d7b'; // Verde por defecto
            
            if ($isAislado || $isExpired) {
                $cardLevelClass = 'card-critical';
                $statusColor = 'var(--cal-red)';
            } elseif ($isExpiringSoon) {
                $cardLevelClass = 'card-warning';
                $statusColor = 'var(--cal-amber)';
            } elseif ($isLaboratorio) {
                $cardLevelClass = 'card-info';
                $statusColor = 'var(--cal-blue)';
            }
        @endphp
        
        <article class="cal-card-compact {{ $cardLevelClass }}">
            <!-- Header Compacto -->
            <div class="cal-card-top">
                <div class="cal-titles">
                    <div class="cal-dropdown" onclick="toggleDropdown(event, {{ $item->id }})">
                        <small class="cal-dropdown-toggle" title="Cambiar estado metrológico">
                            <i class="fas fa-circle" style="color: {{ $statusColor }}; font-size: 8px;"></i>
                            {{ $item->estado_label ?? ucfirst($item->estado) }}
                            <i class="fas fa-chevron-down" style="font-size: 8px; color: #87949b; margin-left: 2px;"></i>
                        </small>
                        
                        <div class="cal-dropdown-menu" id="dropdown-{{ $item->id }}">
                            @if($item->estado !== 'operativo')
                                <button type="button" class="cal-dropdown-item" onclick="openQuickStateModal({{ $item->id }}, 'operativo', 'Operativo / Apto')">
                                    <i class="fas fa-check-circle" style="color: #0d9d7b;"></i> Marcar como Apto / Operativo
                                </button>
                            @endif
                            @if($item->estado !== 'calibracion')
                                <button type="button" class="cal-dropdown-item" onclick="openQuickStateModal({{ $item->id }}, 'calibracion', 'En Laboratorio Externo')">
                                    <i class="fas fa-truck" style="color: var(--cal-blue);"></i> Enviar a calibrar (Laboratorio)
                                </button>
                            @endif
                            @if($item->estado !== 'aislado')
                                <button type="button" class="cal-dropdown-item" onclick="openQuickStateModal({{ $item->id }}, 'aislado', 'Aislado / No Conforme')">
                                    <i class="fas fa-ban" style="color: var(--cal-red);"></i> Aislar (No Conforme / Fallo)
                                </button>
                            @endif
                        </div>
                    </div>
                    <h3>{{ $item->nombre }}</h3>
                    @if(in_array($item->estado, ['calibracion', 'aislado']) && $item->dias_estimados_baja)
                        @php
                            $fechaLimiteBaja = \Carbon\Carbon::parse($item->fecha_baja)->addDays($item->dias_estimados_baja);
                            $retrasoBaja = $item->fecha_baja && now()->gt($fechaLimiteBaja);
                            $diasRetraso = $retrasoBaja ? $fechaLimiteBaja->diffInDays(now()) : 0;
                        @endphp
                        <small style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; color: {{ $retrasoBaja ? 'var(--cal-red)' : '#87949b' }}; margin-top: 2px;">
                            <i class="fas fa-hourglass-half"></i>
                            {{ $retrasoBaja ? 'Retraso de ' . $diasRetraso . ' día' . ($diasRetraso == 1 ? '' : 's') : 'Baja aprox. ' . $item->dias_estimados_baja . ' días' }}
                        </small>
                    @endif
                </div>
                
                @if($image)
                    <button type="button" class="cal-btn-cam" onclick="openPhotoModal('{{ $image }}', '{{ addslashes($item->nombre) }}')" title="Ver fotografía">
                        <i class="fas fa-camera"></i>
                    </button>
                @else
                    <div class="cal-id-badge">{{ $item->codigo ?: 'S/C' }}</div>
                @endif
            </div>

            <!-- Specs Metrológicos Compactos -->
            <div class="cal-specs-line">
                <span><i class="fas fa-ruler-combined"></i> {{ $item->instrumento ?: 'Mag. N/A' }}</span>
                <span><i class="fas fa-bullseye"></i> {{ $item->rango_medida ?: 'Rango N/A' }}</span>
                <span><i class="fas fa-microscope"></i> {{ $item->clase_exactitud ?: 'Clase N/A' }}</span>
            </div>

            <!-- ALERTAS APILADAS (RIBBONS) -->
            @if($isAislado)
                <div class="cal-alert-ribbon danger"><i class="fas fa-lock mr-1"></i> EQUIPO AISLADO: No cumple requisitos / Tolerancias</div>
            @endif
            @if($isLaboratorio)
                <div class="cal-alert-ribbon info"><i class="fas fa-shipping-fast mr-1"></i> Equipo en tránsito / Laboratorio externo</div>
            @endif
            @if($isExpired)
                <div class="cal-alert-ribbon danger"><i class="fas fa-times-circle mr-1"></i> CALIBRACIÓN CADUCADA (Prohibido su uso)</div>
            @elseif($isExpiringSoon)
                <div class="cal-alert-ribbon warning"><i class="fas fa-exclamation-triangle mr-1"></i> Caduca en {{ (int)$daysLeft }} días. Planificar envío a laboratorio.</div>
            @endif

            <!-- Datos principales -->
            <div class="cal-data-grid">
                <div class="cal-data-item">
                    <label>Ubicación / Área</label>
                    <strong><i class="fas fa-map-marker-alt mr-1"></i>{{ $item->ubicacion ?: 'Almacén Central' }}</strong>
                </div>
                <div class="cal-data-item">
                    <label>Identificador (S/N)</label>
                    <strong>{{ $item->numero_serie ?: 'Pendiente' }}</strong>
                </div>
                
                <div class="cal-data-item">
                    <label>Certificado Actual</label>
                    <strong>{{ $item->certificado ?: 'Sin certificado' }}</strong>
                </div>
                
                <!-- Próxima Calibración (Dinámica con Color) -->
                <div class="cal-data-item">
                    <label>Próxima Calibración</label>
                    <strong style="{{ $isExpired ? 'color: var(--cal-red);' : ($isExpiringSoon ? 'color: var(--cal-amber);' : '') }}">
                        @if($item->proxima_calibracion)
                            <i class="fas fa-calendar-check mr-1" style="{{ $isExpired ? 'color: var(--cal-red);' : ($isExpiringSoon ? 'color: var(--cal-amber);' : 'color: #87949b;') }}"></i>{{ $item->proxima_calibracion->format('d/m/Y') }}
                        @else
                            No planificada
                        @endif
                    </strong>
                </div>
            </div>
            
            <!-- Botonera Rápida -->
            <div class="cal-card-actions">
                <a class="cal-btn cal-btn-main" href="{{ route('calibrables.show', $item) }}"><i class="fas fa-file-certificate"></i> Trazabilidad</a>
                <button type="button" class="cal-btn cal-btn-soft" onclick="openQuickAssignModal({{ $item->id }}, '{{ addslashes($item->nombre) }}')">
                    <i class="fas fa-exchange-alt"></i> Asignar a área
                </button>
            </div>
        </article>
    @empty
        <div class="col-12">
            <div class="text-center p-5 bg-white" style="border-radius: 8px;">
                <i class="fas fa-balance-scale fa-3x mb-3 text-muted"></i>
                <h4>No hay equipos de medición registrados</h4>
            </div>
        </div>
    @endforelse
</div>

<div class="cal-bottom">
    <span>Mostrando {{ $items->firstItem() ?: 0 }} - {{ $items->lastItem() ?: 0 }} de {{ $items->total() }} equipos</span>
    {{ $items->links() }}
</div>

<!-- Modal Global Foto -->
<div id="photo-modal" onclick="closePhotoModal()">
    <div class="photo-dialog" onclick="event.stopPropagation()">
        <div class="photo-header">
            <h4 id="photo-modal-title">Fotografía</h4>
            <button type="button" onclick="closePhotoModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="photo-body">
            <img id="photo-modal-img" src="" alt="Vista previa">
        </div>
    </div>
</div>

<!-- Modal Cambio Estado Rápido -->
<div id="quick-state-modal" class="detail-modal" onclick="closeQuickStateModal()">
    <div class="detail-dialog" onclick="event.stopPropagation()">
        <h2 style="font-size: 18px; margin-bottom: 5px;">Actualización Metrológica</h2>
        <p style="font-size: 12px; color: #687681; margin-bottom: 20px;">
            El equipo pasará a estado: <strong id="new-state-label" style="color: #191c1e;">...</strong>
        </p>
        
        <form id="quick-state-form" method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <input type="hidden" name="estado" id="quick-state-input" value="">
            
            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Motivo o Albarán (Bitácora) *</label>
            <input required type="text" name="titulo_evento" class="form-control" placeholder="Ej. Envío anual a laboratorio, Equipo de vuelta y validado..." style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; margin-bottom: 12px;">
            
            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Albarán, Foto o Certificado PDF (Opcional)</label>
            <input type="file" name="archivo_adjunto" class="form-control" accept=".pdf, image/*" style="width: 100%; border: 0; background: #eef2f5; padding: 6px; border-radius: 4px; margin-bottom: 18px;">

            <div id="dias-baja-wrapper" style="display: none;">
                <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Días aproximados de baja (estimación, opcional)</label>
                <input type="number" name="dias_estimados_baja" min="1" max="365" placeholder="Ej. 5" class="form-control" style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; margin-bottom: 18px;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="cal-btn cal-btn-soft" onclick="closeQuickStateModal()">Cancelar</button>
                <button type="submit" class="cal-btn cal-btn-main"><i class="fas fa-save"></i> Registrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Asignación Rápida -->
<div id="quick-assign-modal" class="detail-modal" onclick="closeQuickAssignModal()">
    <div class="detail-dialog" onclick="event.stopPropagation()">
        <h2 style="font-size: 18px; margin-bottom: 5px;">Asignar equipo de medición</h2>
        <p style="font-size: 12px; color: #687681; margin-bottom: 20px;">
            Asignando el equipo: <strong id="assign-equip-name" style="color: #191c1e;">...</strong>
        </p>

        <form id="quick-assign-form" method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Responsable / Área *</label>
                    <input required type="text" name="responsable" class="form-control" placeholder="A quién se entrega" style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px;">
                </div>
                <div>
                    <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Ubicación *</label>
                    <input required type="text" name="ubicacion" class="form-control" placeholder="Taller, Laboratorio interno..." style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px;">
                </div>
            </div>
            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Nota de entrega *</label>
            <input required type="text" name="titulo_evento" class="form-control" placeholder="Ej. Prestado para medición de piezas..." style="width: 100%; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; margin-bottom: 12px;">

            <label style="display: block; font-size: 10px; text-transform: uppercase; font-weight: 800; color: #586873; margin-bottom: 5px;">Albarán o Foto de entrega (Opcional)</label>
            <input type="file" name="archivo_adjunto" class="form-control" accept=".pdf, image/jpeg, image/png" style="width: 100%; border: 0; background: #eef2f5; padding: 6px; border-radius: 4px; margin-bottom: 18px;">

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="cal-btn cal-btn-soft" onclick="closeQuickAssignModal()">Cancelar</button>
                <button type="submit" class="cal-btn cal-btn-main"><i class="fas fa-save"></i> Confirmar Entrega</button>
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
        document.querySelectorAll('.cal-dropdown-menu.show').forEach(menu => {
            if(menu.id !== 'dropdown-' + id) menu.classList.remove('show');
        });
        document.getElementById('dropdown-' + id).classList.toggle('show');
    }
    document.addEventListener('click', () => {
        document.querySelectorAll('.cal-dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
    });
    
    function openQuickStateModal(equipoId, nuevoEstado, labelEstado) {
        document.getElementById('new-state-label').innerText = labelEstado;
        document.getElementById('quick-state-input').value = nuevoEstado;
        document.getElementById('quick-state-form').action = `/calibrables/${equipoId}/estado`;
        document.getElementById('dias-baja-wrapper').style.display = (nuevoEstado === 'operativo') ? 'none' : 'block';
        document.getElementById('quick-state-modal').classList.add('show');
    }
    function closeQuickStateModal() {
        document.getElementById('quick-state-modal').classList.remove('show');
    }

    function openQuickAssignModal(equipoId, nombreEquipo) {
        document.getElementById('assign-equip-name').innerText = nombreEquipo;
        document.getElementById('quick-assign-form').action = `/calibrables/${equipoId}/asignar`;
        document.getElementById('quick-assign-modal').classList.add('show');
    }
    function closeQuickAssignModal() {
        document.getElementById('quick-assign-modal').classList.remove('show');
    }
</script>
@endpush
@stop