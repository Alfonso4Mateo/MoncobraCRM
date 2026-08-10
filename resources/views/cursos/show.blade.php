@extends('adminlte::page')

@section('title', 'Ficha del Curso')

@section('css')
    <style>
        /* Estilos corporativos para los botones */
        .cursos-btn { 
            display: inline-flex; 
            align-items: center; 
            border: 0; 
            border-radius: 10px; 
            padding: 8px 16px; 
            font-weight: 700; 
            font-size: 0.85rem; 
            transition: all 0.2s; 
            text-decoration: none;
        }
        .cursos-btn--primary { background: #173e67; color: #fff; }
        .cursos-btn--primary:hover { background: #0f2a47; color: #fff; text-decoration: none; }
        .cursos-btn--soft { background: #eef3f8; color: #173e67; }
        .cursos-btn--soft:hover { background: #dbe3ef; color: #173e67; text-decoration: none; }
        .cursos-btn--csv { background: #10b981; color: #fff; }
        .cursos-btn--csv:hover { background: #059669; color: #fff; text-decoration: none; }
        .cursos-btn--pdf { background: #ef4444; color: #fff; }
        .cursos-btn--pdf:hover { background: #dc2626; color: #fff; text-decoration: none; }

        /* Estilos de la tarjeta */
        .curso-header { background: #fff; border: 1px solid #e7ecf3; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(15,23,42,.03); }
        .curso-header h2 { color: #173e67; font-weight: 800; margin-bottom: 5px; font-size: 1.6rem; }
        .curso-meta { display: flex; gap: 20px; color: #667085; font-size: 0.9rem; margin-bottom: 15px; }
        .curso-meta-item { display: flex; align-items: center; gap: 6px; }
        .curso-desc { padding-top: 15px; border-top: 1px dashed #eef2f7; color: #475569; }
        
        /* Semáforo de estados */
        .status-dot { height: 10px; width: 10px; background-color: #bbb; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .status-dot.green { background-color: #10b981; box-shadow: 0 0 6px rgba(16,185,129,.4); }
        .status-dot.yellow { background-color: #f59e0b; box-shadow: 0 0 6px rgba(245,158,11,.4); }
        .status-dot.red { background-color: #ef4444; box-shadow: 0 0 6px rgba(239,68,68,.4); }
    </style>
@endsection

@section('content')
    <section class="p-3">
        <!-- Tarjeta de metadatos del curso -->
        <div class="curso-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge badge-secondary mb-2 text-uppercase">{{ $curso->categoria ?: 'Sin categoría' }}</span>
                    <h2>{{ $curso->nombre }}</h2>
                    <div class="curso-meta">
                        <div class="curso-meta-item">
                            <i class="fas fa-users"></i> {{ $curso->personal->count() }} trabajadores asignados
                        </div>
                        <div class="curso-meta-item">
                            <i class="fas fa-calendar-alt"></i> 
                            {{ $curso->meses_validez ? "Validez: {$curso->meses_validez} meses" : 'Sin caducidad' }}
                        </div>
                        @if($curso->meses_validez)
                        <div class="curso-meta-item">
                            <i class="fas fa-bell"></i> Aviso previo: {{ $curso->dias_aviso_previo }} días
                        </div>
                        @endif
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 12px;">
                    <a href="{{ route('cursos.gestion') }}" class="cursos-btn cursos-btn--soft">
                        <i class="fas fa-arrow-left mr-2"></i> Volver a gestión
                    </a>
                    <a href="{{ route('cursos.edit', $curso->id) }}" class="cursos-btn cursos-btn--primary">
                        <i class="fas fa-edit mr-2"></i> Editar curso
                    </a>
                </div>
            </div>
            
            @if($curso->descripcion)
                <div class="curso-desc">
                    <strong>Descripción:</strong><br>
                    {{ $curso->descripcion }}
                </div>
            @endif
        </div>

        <!-- Tabla de Trabajadores Asignados -->
        <div class="card">
            <div class="card-header border-bottom-0">
                <h3 class="card-title font-weight-bold" style="color: #173e67;">Listado de asistentes</h3>
                <div class="card-tools">
                    <!-- Espacio reservado para el futuro botón de exportación -->
                    <a href="{{ route('cursos.export', $curso->id) }}" class="cursos-btn cursos-btn--csv">
                        <i class="fas fa-file-excel" style="margin-right: 6px;"></i> Exportar CSV
                    </a>
                    <a href="{{ route('cursos.export', $curso->id) }}" class="cursos-btn cursos-btn--pdf">
                        <i class="fas fa-file-pdf" style="margin-right: 6px;"></i> Exportar PDF (próximamente)
                    </a>
                </div>
            </div>
            
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID RRHH</th>
                            <th>Trabajador</th>
                            <th>Departamento</th>
                            <th>DNI / NIE</th>
                            <th>Teléfono</th>
                            <th>Fecha Realización</th>
                            <th>Caducidad</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $hoy = \Carbon\Carbon::now()->startOfDay();
                        @endphp

                        @forelse($curso->personal as $trabajador)
                            @php
                                $estado = 'Vigente';
                                $color = 'green';
                                $fechaCaducidad = null;
                                $textoCaducidad = '—';
                                
                                // Recuperamos los datos de la tabla pivote
                                $fechaRealizacion = $trabajador->pivot->fecha_realizacion ? \Carbon\Carbon::parse($trabajador->pivot->fecha_realizacion)->startOfDay() : null;
                                $esApto = (bool) $trabajador->pivot->apto;

                                if (!$esApto) {
                                    $estado = 'No Apto';
                                    $color = 'red';
                                } elseif ($fechaRealizacion && $curso->meses_validez) {
                                    $fechaCaducidad = $fechaRealizacion->copy()->addMonths($curso->meses_validez);
                                    $fechaAviso = $fechaCaducidad->copy()->subDays($curso->dias_aviso_previo ?? 30);
                                    $textoCaducidad = $fechaCaducidad->format('d/m/Y');

                                    if ($hoy->gt($fechaCaducidad)) {
                                        $estado = 'Caducado';
                                        $color = 'red';
                                    } elseif ($hoy->gte($fechaAviso)) {
                                        $estado = 'En Aviso';
                                        $color = 'yellow';
                                    }
                                } elseif (!$fechaRealizacion) {
                                    $estado = 'Pendiente de fecha';
                                    $color = 'yellow';
                                }

                                // Descodificamos el departamento para la vista
                                $deptos = is_string($trabajador->departamento) 
                                    ? json_decode($trabajador->departamento, true) ?? explode(',', $trabajador->departamento) 
                                    : (array) $trabajador->departamento;
                                $departamentoStr = !empty($deptos) ? strtoupper(implode(', ', $deptos)) : '—';
                            @endphp

                            <tr>
                                <td class="text-muted font-weight-bold">{{ $trabajador->id_rrhh ?: '—' }}</td>
                                <td>
                                    <strong>{{ $trabajador->name }} {{ $trabajador->apellido }}</strong>
                                    @if(!$esApto)
                                        <span class="badge badge-danger ml-1" title="El trabajador no superó el curso">No apto</span>
                                    @endif
                                </td>
                                <td><span class="text-muted">{{ $departamentoStr }}</span></td>
                                <td class="text-muted">{{ $trabajador->dni_nie ?: '—' }}</td>
                                <td class="text-muted">{{ $trabajador->telefono ?: '—' }}</td>
                                <td>{{ $fechaRealizacion ? $fechaRealizacion->format('d/m/Y') : '—' }}</td>
                                <td>
                                    @if($estado === 'Caducado')
                                        <strong class="text-danger">{{ $textoCaducidad }}</strong>
                                    @elseif($estado === 'En Aviso')
                                        <strong class="text-warning">{{ $textoCaducidad }}</strong>
                                    @else
                                        {{ $textoCaducidad }}
                                    @endif
                                </td>
                                <td>
                                    <span class="status-dot {{ $color }}"></span>
                                    {{ $estado }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <!-- colspan ajustado a 8 -->
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-3 text-light"></i><br>
                                    Nadie ha sido asignado a este curso todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection