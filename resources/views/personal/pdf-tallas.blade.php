<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Tallas y Equipamiento</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; margin: 16px; }
        .header { border-bottom: 2px solid #173e67; padding-bottom: 8px; margin-bottom: 14px; display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; color: #64748b; }
        h1 { margin: 0; font-size: 15px; color: #173e67; text-transform: uppercase; }
        h2 { margin: 14px 0 6px; font-size: 11px; color: #173e67; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        .summary-grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary-grid th { background: #f1f5f9; color: #173e67; text-align: left; padding: 5px 8px; border: 1px solid #cbd5e1; font-size: 8px; text-transform: uppercase; }
        .summary-grid td { padding: 5px 8px; border: 1px solid #e2e8f0; vertical-align: top; }
        .pill { display: inline-block; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; padding: 2px 5px; border-radius: 3px; margin: 1px 2px; font-weight: bold; }
        .pill-missing { display: inline-block; background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 2px 5px; border-radius: 3px; margin: 1px 2px; font-weight: bold; }
        table.detail { width: 100%; border-collapse: collapse; }
        table.detail th { background: #173e67; color: #fff; padding: 5px 4px; text-align: left; font-size: 7.5px; text-transform: uppercase; }
        table.detail td { padding: 4px; border-bottom: 1px solid #e2e8f0; font-size: 8px; }
        table.detail tr:nth-child(even) { background: #f8fafc; }
        .text-danger { color: #b91c1c; font-weight: bold; }
        .text-muted { color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>Resumen de Tallas y Equipamiento (EPIs)</h1>
            <div style="margin-top: 3px; font-weight: bold; color: #475569;">
                Delegación / Proyecto: {{ $proyectoActivo ? strtoupper($proyectoActivo->nombre) : 'GENERAL' }}
            </div>
        </div>
        <div class="header-right">
            Fecha de emisión: {{ date('d/m/Y H:i') }}<br>
            Trabajadores incluidos: <strong>{{ $personals->count() }}</strong>
        </div>
    </div>

    <h2>1. Resumen Agregado por Prenda y Talla</h2>
    <table class="summary-grid">
        <thead>
            <tr>
                <th style="width: 18%;">Prenda / EPI</th>
                <th>Desglose de Tallas y Cantidades</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resumenTallas as $info)
                <tr>
                    <td><strong>{{ $info['label'] }}</strong></td>
                    <td>
                        @forelse($info['counts'] as $talla => $cantidad)
                            <span class="pill">{{ $talla }}: {{ $cantidad }}</span>
                        @empty
                            @if($info['missing'] === 0)
                                <span class="text-muted">Sin datos</span>
                            @endif
                        @endforelse
                        @if($info['missing'] > 0)
                            <span class="pill-missing">Faltan: {{ $info['missing'] }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>2. Listado Detallado por Trabajador</h2>
    <table class="detail">
        <thead>
            <tr>
                <th>ID RRHH</th>
                <th>Trabajador</th>
                <th>Departamento</th>
                <th>Estado</th>
                @foreach($columns as $c)
                    <th>{{ $c === 'bolsa_fod' ? 'Bolsa F.O.D.' : ucfirst($c) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($personals as $p)
                @php
                    $deptos = is_string($p->departamento) ? json_decode($p->departamento, true) ?? explode(',', $p->departamento) : (array) $p->departamento;
                    $deptosStr = !empty($deptos) ? strtoupper(implode(', ', $deptos)) : '—';
                @endphp
                <tr>
                    <td>{{ $p->id_rrhh ?: '—' }}</td>
                    <td>
                        <strong>{{ $p->name }} {{ $p->apellido }}</strong>
                        @if($p->sin_tallas) <span class="text-muted">(Oficina)</span> @endif
                    </td>
                    <td>{{ $deptosStr }}</td>
                    <td>{{ $p->activo ? 'Activo' : 'Baja' }}</td>
                    @foreach($columns as $c)
                        <td>
                            @if($p->sin_tallas)
                                <span class="text-muted">N/A</span>
                            @elseif(!empty($p->{$c}))
                                <strong>{{ $p->{$c} }}</strong>
                            @else
                                <span class="text-danger">Falta</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>