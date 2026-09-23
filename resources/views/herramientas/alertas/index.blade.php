@extends('adminlte::page')

@section('title', 'Centro de Control y Alertas')

@section('content_header')
    <div class="dash-heading">
        <div>
            <div class="dash-kicker">Gestión de activos <span>/</span> Control Tower</div>
            <h1>Panel de Alertas</h1>
        </div>
        <div class="dash-actions">
            <button class="dash-btn dash-btn-light" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
            <a href="{{ route('alertas.configuracion') }}" class="dash-btn dash-btn-light"><i class="fas fa-cog"></i> Configurar Envíos</a>
            <a href="{{ route('alertas.enviar') }}" class="dash-btn dash-btn-main"><i class="fas fa-paper-plane"></i> Forzar Reporte</a>
        </div>
    </div>
@stop

@section('content')
@include('herramientas.partials.architectural-ledger')

<style>
    :root {
        --dash-navy: #002442; --dash-ink: #191c1e; --dash-bg: #f4f6f9;
        --dash-low: #eef2f5; --dash-muted: #687681;
        --dash-red: #c92328; --dash-amber: #dca54a; --dash-blue: #17619a;
    }
    
    .content-wrapper { background: var(--dash-bg); }
    
    /* Encabezado Principal */
    .dash-heading { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; padding-top: 10px; }
    .dash-kicker { font-size: 10px; text-transform: uppercase; letter-spacing: .12em; color: #7b8991; font-weight: 800; margin-bottom: 4px; }
    .dash-kicker span { margin: 0 6px; color: #a3adb4; }
    .dash-heading h1 { font-size: 32px; font-weight: 800; color: var(--dash-ink); margin: 0; letter-spacing: -0.5px; }
    
    /* Botones Modernizados */
    .dash-actions { display: flex; gap: 12px; }
    .dash-btn { border: 0; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 18px; font-size: 13px; font-weight: 800; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; }
    .dash-btn:active { transform: scale(0.97); }
    .dash-btn-light { background: #fff; color: #284252; box-shadow: 0 4px 12px rgba(25,28,30,0.04); border: 1px solid #edf0f2; }
    .dash-btn-light:hover { background: #fdfdfe; box-shadow: 0 6px 16px rgba(25,28,30,0.08); color: var(--dash-navy); }
    .dash-btn-main { background: linear-gradient(110deg, #002442, #155785); color: #fff; box-shadow: 0 8px 20px rgba(0,36,66,0.22); }
    .dash-btn-main:hover { box-shadow: 0 10px 28px rgba(0,36,66,0.3); color: #fff; }

    /* Pestañas */
    .dash-tabs { display: flex; gap: 32px; border-bottom: 1px solid #dce4e9; margin-bottom: 28px; padding: 0 4px; }
    .dash-tabs a { padding: 12px 0; color: #87949b; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; border-bottom: 3px solid transparent; }
    .dash-tabs a:hover { color: var(--dash-navy); }
    .dash-tabs a.active { color: var(--dash-navy); border-bottom: 3px solid var(--dash-navy); background: transparent !important; }

    .dash-grid { display: grid; grid-template-columns: 1.25fr 1fr; gap: 24px; align-items: start; }
    @media(max-width: 1024px) { .dash-grid { grid-template-columns: 1fr; } }

    /* Tarjetas UI (Paneles) */
    .dash-panel { background: #fff; box-shadow: 0 8px 32px rgba(25,28,30,0.04); border-radius: 12px; overflow: hidden; margin-bottom: 24px; border: 1px solid #edf0f2; transition: box-shadow 0.3s ease; }
    .dash-panel:hover { box-shadow: 0 12px 48px rgba(25,28,30,0.06); }
    .panel-red { border-left: 4px solid var(--dash-red); }
    .panel-amber { border-left: 4px solid var(--dash-amber); }
    .panel-blue { border-left: 4px solid var(--dash-blue); }
    
    .dash-panel-header { padding: 20px 24px; border-bottom: 1px solid #edf0f2; display: flex; justify-content: space-between; align-items: center; background: #fcfdfe; }
    .dash-panel-header h2 { font-size: 16px; font-weight: 800; color: var(--dash-ink); margin: 0; display: flex; align-items: center; gap: 10px; }
    
    /* Insignias de estado (Badges) */
    .dash-badge { padding: 6px 14px; font-size: 11px; font-weight: 800; border-radius: 99px; letter-spacing: 0.02em; }
    .dash-badge.red { background: #fff0ef; color: var(--dash-red); border: 1px solid #fee1df; }
    .dash-badge.amber { background: #fef7ec; color: #b67610; border: 1px solid #fbe6c4; }
    .dash-badge.blue { background: #f0f6fa; color: var(--dash-blue); border: 1px solid #dcecf8; }

    /* Tablas refinadas */
    .dash-table { width: 100%; border-collapse: collapse; }
    .dash-table th { font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em; color: #87949b; text-align: left; padding: 14px 24px; background: #fcfdfe; border-bottom: 1px solid #edf0f2; }
    .dash-table td { padding: 16px 24px; font-size: 13px; color: #273740; border-bottom: 1px solid #edf0f2; vertical-align: middle; }
    .dash-table tr:last-child td { border-bottom: 0; }
    .dash-table tr:hover td { background: #f8fafc; }
    
    .cell-asset { display: flex; align-items: center; gap: 12px; font-weight: 800; color: var(--dash-ink); }
    .cell-asset i { color: #87949b; width: 18px; text-align: center; }
    .cell-alert { font-size: 12px; font-weight: 800; }
    
    /* Fechas empaquetadas */
    .cell-date { white-space: nowrap; font-family: 'JetBrains Mono', monospace; font-size: 12px; background: #f4f6f9; padding: 6px 10px; border-radius: 6px; font-weight: 600; display: inline-block; }
    .text-red { color: var(--dash-red); }
    .text-amber { color: #b67610; }
    .cell-date.text-red { background: #fff0ef; }
    .cell-date.text-amber { background: #fef7ec; }

    .dash-btn-sm { display: inline-flex; place-items: center; width: 34px; height: 34px; background: #f4f6f9; color: #52636d; border-radius: 8px; text-decoration: none; transition: 0.2s; justify-content: center; }
    .dash-btn-sm:hover { background: var(--dash-blue); color: #fff; transform: translateX(3px); }

    /* Bitácora de averías */
    .log-item { padding: 20px 24px; border-bottom: 1px solid #edf0f2; transition: background 0.2s; }
    .log-item:hover { background: #fcfdfe; }
    .log-item:last-child { border-bottom: 0; }
    .log-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .log-title { font-size: 14px; font-weight: 800; color: var(--dash-ink); }
    .log-meta { font-size: 11px; color: #7b8991; background: #f4f6f9; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-family: monospace; }
    .log-desc { font-size: 13px; color: #52636d; margin: 0 0 14px; line-height: 1.5; }
    
    .log-footer { display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: 700; }
    .log-link { color: var(--dash-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: #f0f6fa; padding: 6px 12px; border-radius: 6px; transition: 0.2s; }
    .log-link:hover { background: #dcecf8; }
    .log-user { color: #87949b; display: flex; align-items: center; gap: 6px; }

    .empty-state { padding: 60px 20px; text-align: center; color: #87949b; font-size: 13px; }
</style>

<nav class="dash-tabs">
    <a href="{{ route('equipos.index') }}">Equipos informáticos</a>
    <a href="{{ route('herramientas.index') }}">Herramientas de planta</a>
    <a href="{{ route('calibrables.index') }}">Aparatos calibrables</a>
    <a class="active" href="{{ route('alertas.index') }}">Alertas y mantenimiento</a>
</nav>

<div class="dash-grid">
    <!-- COLUMNA IZQUIERDA: Alertas e Intervenciones -->
    <div>
        <!-- Bloque 1: Alertas Críticas (Caducadas) -->
        <div class="dash-panel panel-red">
            <div class="dash-panel-header">
                <h2><i class="fas fa-exclamation-triangle" style="color: var(--dash-red);"></i> Requiere Intervención Urgente</h2>
                <span class="dash-badge red">{{ $criticas->count() }} alertas activas</span>
            </div>
            @if($criticas->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-check-circle fa-2x" style="color: #0d9d7b; opacity: 0.3; margin-bottom: 12px;"></i><br>
                    Cero caducidades. Parque 100% al día.
                </div>
            @else
                <table class="dash-table">
                    <thead><tr><th>Activo afectado</th><th>Motivo / Alerta</th><th>Fecha vto.</th><th></th></tr></thead>
                    <tbody>
                        @foreach($criticas as $alerta)
                            <tr>
                                <td>
                                    <div class="cell-asset"><i class="fas {{ $alerta->icono }}"></i> {{ $alerta->nombre }}</div>
                                    <div style="font-size: 11px; color: #7b8991; margin-top: 4px; font-weight: 600;">{{ $alerta->modulo }}</div>
                                </td>
                                <td class="cell-alert text-red">{{ $alerta->alerta }}</td>
                                <td><span class="cell-date text-red">{{ \Carbon\Carbon::parse($alerta->fecha)->format('d/m/Y') }}</span></td>
                                <td><a href="{{ $alerta->url }}" class="dash-btn-sm" title="Ir a la ficha"><i class="fas fa-arrow-right"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Bloque 2: Previsión a 30 días -->
        <div class="dash-panel panel-amber">
            <div class="dash-panel-header">
                <h2><i class="fas fa-clock" style="color: var(--dash-amber);"></i> Vencimientos a 30 días</h2>
                <span class="dash-badge amber">{{ $proximos->count() }} preventivos</span>
            </div>
            @if($proximos->isEmpty())
                <div class="empty-state">No hay mantenimientos ni calibraciones programadas para los próximos 30 días.</div>
            @else
                <table class="dash-table">
                    <thead><tr><th>Activo afectado</th><th>Tipo de preventivo</th><th>Programado</th><th></th></tr></thead>
                    <tbody>
                        @foreach($proximos as $alerta)
                            <tr>
                                <td>
                                    <div class="cell-asset"><i class="fas {{ $alerta->icono }}"></i> {{ $alerta->nombre }}</div>
                                    <div style="font-size: 11px; color: #7b8991; margin-top: 4px; font-weight: 600;">{{ $alerta->modulo }}</div>
                                </td>
                                <td class="cell-alert text-amber">{{ $alerta->alerta }}</td>
                                <td><span class="cell-date text-amber">{{ \Carbon\Carbon::parse($alerta->fecha)->format('d/m/Y') }}</span></td>
                                <td><a href="{{ $alerta->url }}" class="dash-btn-sm" title="Ir a la ficha"><i class="fas fa-arrow-right"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- COLUMNA DERECHA: Reporte de Averías (Últimos 7 días) -->
    <div>
        <div class="dash-panel panel-blue">
            <div class="dash-panel-header">
                <h2><i class="fas fa-tools" style="color: var(--dash-blue);"></i> Averías Recientes (Últimos 7 días)</h2>
                <span class="dash-badge blue">{{ $averias->count() }} incidencias</span>
            </div>
            
            @if($averias->isEmpty())
                <div class="empty-state">No se han registrado partes de avería ni reparaciones en la última semana.</div>
            @else
                @foreach($averias as $averia)
                    @php
                        $activo = $averia->eventable;
                        $nombreActivo = $activo ? $activo->nombre : 'Activo eliminado';
                        $url = '#';
                        if ($activo) {
                            if (class_basename($activo) === 'EquipoInformatico') $url = route('equipos.show', $activo);
                            elseif (class_basename($activo) === 'HerramientaPlanta') $url = route('herramientas.planta.show', $activo);
                            elseif (class_basename($activo) === 'AparatoCalibrable') $url = route('calibrables.show', $activo);
                        }
                    @endphp
                    <div class="log-item">
                        <div class="log-header">
                            <div class="log-title">{{ $averia->titulo }}</div>
                            <div class="log-meta">{{ $averia->fecha->format('d/m/Y H:i') }}</div>
                        </div>
                        <p class="log-desc">{{ $averia->descripcion ?: 'Sin descripción detallada en el parte.' }}</p>
                        <div class="log-footer">
                            <a href="{{ $url }}" class="log-link"><i class="fas fa-link"></i> {{ class_basename($activo) === 'EquipoInformatico' ? 'IT' : (class_basename($activo) === 'AparatoCalibrable' ? 'Metrología' : 'Planta') }}: {{ $nombreActivo }}</a>
                            <span class="log-user"><i class="fas fa-user-circle"></i> {{ optional($averia->usuario)->name ?: 'Sistema' }}</span>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@stop