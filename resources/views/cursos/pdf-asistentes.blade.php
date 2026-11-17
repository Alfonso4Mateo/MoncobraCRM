<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Listado de Asistentes - {{ $curso->nombre }}</title>
    <style>
        @page { margin: 14mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #17385d; margin: 0; font-size: 10px; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; border-bottom: 2px solid #2a6fb0; padding-bottom: 8px; }
        .title { font-size: 15px; font-weight: bold; text-transform: uppercase; margin: 0 0 4px 0; color: #17385d; }
        .meta { font-size: 10px; color: #64748b; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th { background: #2a6fb0; color: #fff; padding: 7px 6px; text-align: left; font-size: 9px; text-transform: uppercase; border: 1px solid #2a6fb0; }
        .data-table td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: middle; }
        .data-table tr:nth-child(even) td { background: #f8fafc; }
        .badge-ok { color: #166534; font-weight: bold; }
        .badge-warn { color: #b45309; font-weight: bold; }
        .badge-bad { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td width="60%">
                <div class="title">{{ $curso->nombre }}</div>
                <div class="meta">
                    Categoría: <strong>{{ $curso->categoria ?: 'Sin categoría' }}</strong> •
                    Validez: <strong>{{ $curso->meses_validez ? $curso->meses_validez . ' meses' : 'Sin caducidad' }}</strong> •
                    Total asistentes: <strong>{{ $curso->personal->count() }}</strong>
                </div>
            </td>
            <td width="40%" align="right">
                <strong>MONCOBRA, S.A.</strong><br>
                <span class="meta">Dpto. Prevención y Formación • {{ now()->format('d/m/Y H:i') }}</span>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="9%">ID RRHH</th>
                <th width="23%">Trabajador</th>
                <th width="16%">Departamento</th>
                <th width="16%">Puesto</th>
                <th width="10%">DNI / NIE</th>
                <th width="9%">Realización</th>
                <th width="9%">Caducidad</th>
                <th width="8%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @php $hoy = \Carbon\Carbon::now()->startOfDay(); @endphp
            @forelse($curso->personal as $trabajador)
                @php
                    $estado = 'Vigente';
                    $clase = 'badge-ok';
                    $textoCaducidad = '—';
                    $fReal = $trabajador->pivot->fecha_realizacion ? \Carbon\Carbon::parse($trabajador->pivot->fecha_realizacion)->startOfDay() : null;
                    $esApto = (bool) $trabajador->pivot->apto;

                    if (!$esApto) {
                        $estado = $fReal ? 'No Apto' : 'Pendiente';
                        $clase = $fReal ? 'badge-bad' : 'badge-warn';
                    } elseif ($fReal && $curso->meses_validez) {
                        $fCad = $fReal->copy()->addMonths($curso->meses_validez);
                        $fAviso = $fCad->copy()->subDays($curso->dias_aviso_previo ?? 30);
                        $textoCaducidad = $fCad->format('d/m/Y');
                        if ($hoy->gt($fCad)) { $estado = 'Caducado'; $clase = 'badge-bad'; }
                        elseif ($hoy->gte($fAviso)) { $estado = 'En Aviso'; $clase = 'badge-warn'; }
                    } elseif ($fReal) {
                        $textoCaducidad = 'Sin caducidad';
                    } else {
                        $estado = 'Pendiente fecha';
                        $clase = 'badge-warn';
                    }

                    $deptos = is_string($trabajador->departamento)
                        ? json_decode($trabajador->departamento, true) ?? explode(',', $trabajador->departamento)
                        : (array) $trabajador->departamento;
                    $departamentoStr = !empty($deptos) ? strtoupper(implode(', ', $deptos)) : '—';
                    $nombrePuesto = optional($trabajador->puestoTrabajo)->nombre ?: ($trabajador->puesto ?: '—');
                @endphp
                <tr>
                    <td>{{ $trabajador->id_rrhh ?: '—' }}</td>
                    <td><strong>{{ $trabajador->name }} {{ $trabajador->apellido }}</strong></td>
                    <td>{{ $departamentoStr }}</td>
                    <td>{{ $nombrePuesto }}</td>
                    <td>{{ $trabajador->dni_nie ?: '—' }}</td>
                    <td>{{ $fReal ? $fReal->format('d/m/Y') : '—' }}</td>
                    <td>{{ $textoCaducidad }}</td>
                    <td class="{{ $clase }}">{{ $estado }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align: center; padding: 20px;">No hay trabajadores asignados a este curso.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>