<div class="table-responsive personal-table-wrap">
    <table class="table personal-table">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">
                    <input type="checkbox" id="checkbox-master" title="Seleccionar todos">
                </th>
                <th>ID RRHH</th>
                <th>NOMBRE COMPLETO</th>
                <th>DEPARTAMENTO</th>
                <th>ESTADO</th>
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
                    // Evaluamos el estado para asignar la clase de fondo correcta a la fila
                    $claseFila = '';
                    if (isset($personal->estado_medico)) {
                        if ($personal->estado_medico === 'caducada') {
                            $claseFila = 'personal-row--danger'; // Fondo rojizo
                        } elseif ($personal->estado_medico === 'aviso') {
                            $claseFila = 'personal-row--alert';  // Fondo amarillento
                        }
                    }
                    
                    $initials = collect(explode(' ', trim($personal->name)))
                        ->filter()
                        ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                        ->take(2)
                        ->implode('');
                    $userCode = str_pad((string) $personal->id_rrhh, 4, '0', STR_PAD_LEFT);
                @endphp

                <tr class="{{ $claseFila }}" data-id="{{ $personal->id }}">
                    <td style="text-align: center;">
                        <input type="checkbox" class="checkbox-row" value="{{ $personal->id }}">
                    </td>
                    <td data-label="ID RRHH">
                        <span class="personal-code">{{ $userCode }}</span>
                    </td>
                    <td data-label="NOMBRE COMPLETO">
                        <div class="personal-person-cell">
                            <span class="personal-avatar">{{ $initials ?: 'U' }}</span>
                            <div class="personal-person-copy">
                                <div class="personal-person-name">
                                    <strong>{{ $personal->name }} {{ $personal->apellido }}</strong>
                                    @if(isset($personal->estado_medico) && $personal->estado_medico === 'caducada')
                                        <span class="personal-alert-icon" title="Revisión médica CADUCADA" style="color: #b91c1c;">
                                            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                                        </span>
                                    @elseif(isset($personal->estado_medico) && $personal->estado_medico === 'aviso')
                                        <span class="personal-alert-icon" title="Revisión médica próxima" style="color: #f59e0b;">
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
                    <td data-label="ESTADO">
                        @if($personal->activo)
                            <span class="personal-status personal-status--active">Activo</span>
                        @else
                            <span class="personal-status personal-status--inactive" style="background: #fee2e2; color: #b91c1c;">De Baja</span>
                        @endif
                    </td>
                    @php
                        $columnasEpi = ['camiseta', 'chaqueta', 'sudadera', 'pantalon', 'calzado', 'guantes', 'gafas'];
                    @endphp
                    
                    @foreach($columnasEpi as $prenda)
                        <td data-label="{{ strtoupper($prenda) }}">
                            @if($personal->sin_tallas)
                                <span style="color: #cbd5e1; font-size: 0.85rem;">N/A</span>
                            @else
                                <span class="personal-size-badge">{{ $personal->$prenda ?? '—' }}</span>
                            @endif
                        </td>
                    @endforeach
                    <td data-label="ACCIONES" class="text-right">
                        <div class="personal-actions">
                            
                            <!-- CANDADO PARA VER FICHA DETALLADA -->
                            @can('personal.acciones')
                                <a href="{{ route('personal.show', $personal->id) }}" class="personal-action-icon" title="Ver detalle e historial">
                                    <i class="far fa-eye"></i>
                                </a>
                            @endcan

                            <!-- CANDADO EXCLUSIVO PARA EDITAR DATOS -->
                            @can('personal.edit')
                                <a href="{{ route('personal.edit', $personal->id) }}" class="personal-action-icon" title="Editar trabajador">
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