<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Mantenimiento Industrial</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #f4f6f9; color: #191c1e; margin: 0; padding: 30px 10px; }
        .container { max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,36,66,0.08); }
        .header { background: #002442; padding: 25px 30px; border-bottom: 4px solid #dca54a; }
        .header h1 { color: #ffffff; margin: 0; font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .header p { color: #87949b; margin: 5px 0 0; font-size: 13px; }
        .content { padding: 30px; }
        .intro { font-size: 14px; line-height: 1.6; color: #52636d; margin-bottom: 25px; }
        
        .section-title { font-size: 12px; text-transform: uppercase; font-weight: 800; letter-spacing: 0.1em; border-bottom: 1px solid #edf0f2; padding-bottom: 8px; margin: 30px 0 15px; }
        .text-red { color: #c92328; }
        .text-blue { color: #17619a; }
        
        .alert-card { background: #fdfdfe; border: 1px solid #edf0f2; border-left: 4px solid #c92328; padding: 12px 16px; margin-bottom: 10px; border-radius: 0 4px 4px 0; }
        .alert-card.blue { border-left-color: #17619a; background: #f8fbfe; }
        
        .asset-name { font-size: 14px; font-weight: 800; color: #191c1e; margin: 0 0 4px; }
        .asset-meta { font-size: 12px; color: #687681; }
        .asset-meta strong { color: #c92328; }
        
        .btn-container { text-align: center; margin-top: 35px; }
        .btn { display: inline-block; background: #002442; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 800; font-size: 13px; }
        .footer { background: #f7f9fb; padding: 20px; text-align: center; font-size: 11px; color: #87949b; border-top: 1px solid #edf0f2; }

        .text-amber { color: #dca54a; }
        .alert-card.amber { border-left-color: #dca54a; background: #fdfbf7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Control Tower - Mantenimiento</h1>
            <p>Reporte automatizado de activos e incidencias</p>
        </div>
        
        <div class="content">
            <div class="intro">
                Hola,<br><br>
                Este es el resumen automatizado con las incidencias críticas y partes de avería registrados en el ERP correspondientes a tu configuración de alertas.
            </div>

            <!-- BLOQUE 1: CADUCIDADES Y VENCIMIENTOS -->
            @if(isset($datos['caducidades']) && $datos['caducidades']->count() > 0)
                <div class="section-title text-red">Acción Urgente: Inspecciones Vencidas</div>
                
                @foreach($datos['caducidades'] as $item)
                    <div class="alert-card">
                        <p class="asset-name">{{ $item->nombre }} ({{ $item->codigo ?? 'S/C' }})</p>
                        <p class="asset-meta">Vencimiento: <strong>{{ \Carbon\Carbon::parse($item->fecha_inspeccion ?? clone $item->proxima_calibracion)->format('d/m/Y') }}</strong></p>
                    </div>
                @endforeach
            @endif

            <!-- BLOQUE 1.5: PREVENTIVOS -->
            @if(isset($datos['preventivos']) && $datos['preventivos']->count() > 0)
                <div class="section-title text-amber">Previsión a 30 días: Mantenimientos y OCAs</div>
                
                @foreach($datos['preventivos'] as $item)
                    <div class="alert-card amber">
                        <p class="asset-name">{{ $item->nombre }} ({{ $item->codigo ?? 'S/C' }})</p>
                        <p class="asset-meta">Programado para: <strong style="color: #b68532;">{{ \Carbon\Carbon::parse($item->fecha_inspeccion)->format('d/m/Y') }}</strong></p>
                    </div>
                @endforeach
            @endif

            <!-- BLOQUE 2: AVERÍAS Y REPARACIONES -->
            @if(isset($datos['averias']) && $datos['averias']->count() > 0)
                <div class="section-title text-blue">Partes de Avería (Últimos 7 días)</div>
                
                @foreach($datos['averias'] as $evento)
                    <div class="alert-card blue">
                        <p class="asset-name">{{ $evento->titulo }}</p>
                        <p class="asset-meta">
                            Activo: {{ $evento->eventable ? $evento->eventable->nombre : 'Equipo eliminado' }}<br>
                            Fecha del parte: {{ $evento->fecha->format('d/m/Y H:i') }}
                        </p>
                    </div>
                @endforeach
            @endif

            <div class="btn-container">
                <a href="{{ route('alertas.index') }}" class="btn">Acceder al Panel de Control</a>
            </div>
        </div>
        
        <div class="footer">
            Generado automáticamente por el módulo de activos de <strong>FactuMon ERP</strong>.<br>
            Este buzón no es monitorizado, por favor no respondas a este correo.
        </div>
    </div>
</body>
</html>