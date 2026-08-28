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

                @can('cursos.edit')
                    <a href="{{ route('cursos.edit', $curso->id) }}" class="cursos-btn cursos-btn--primary">
                        <i class="fas fa-edit mr-2"></i> Editar curso
                    </a>
                @endcan
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
                @can('cursos.export')
                    <!-- Espacio reservado para el futuro botón de exportación -->
                    <a href="{{ route('cursos.export', $curso->id) }}" class="cursos-btn cursos-btn--csv">
                        <i class="fas fa-file-excel" style="margin-right: 6px;"></i> Exportar CSV
                    </a>
                @endcan

                @can('cursos.export')
                    <a href="{{ route('cursos.export', $curso->id) }}" class="cursos-btn cursos-btn--pdf">
                        <i class="fas fa-file-pdf" style="margin-right: 6px;"></i> Exportar PDF (próximamente)
                    </a>
                @endcan
                </div>
            </div>
            
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID RRHH</th>
                            <th>Trabajador</th>
                            <th>Departamento</th>
                            <th>Puesto</th>
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
                                    <strong>{{ $trabajador->name ?: '—' }} {{ $trabajador->apellido ?: '—' }}</strong>
                                    @if(!$esApto)
                                        <span class="badge badge-danger ml-1" title="El trabajador no superó el curso">No apto</span>
                                    @endif
                                </td>
                                <td><span class="text-muted">{{ $departamentoStr ?: '—' }}</span></td>
                                <td class="text-muted">{{ optional($trabajador->puestoTrabajo)->nombre ?: '—' }}</td>
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
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="status-dot {{ $color }}"></span>
                                            {{ $estado }}
                                        </div>
                                        <!-- Botón de Historial -->
                                        <button type="button" class="btn btn-sm btn-link js-open-history p-0 ml-2" 
                                                data-personal-id="{{ $trabajador->id }}" 
                                                data-curso-id="{{ $curso->id }}" 
                                                data-trabajador-nombre="{{ $trabajador->name }} {{ $trabajador->apellido }}" 
                                                title="Ver historial de renovaciones">
                                            <i class="fas fa-history text-primary"></i>
                                        </button>
                                    </div>
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
                Trabajador: <strong id="history-trabajador-name" style="color: #0f172a;">Cargando...</strong><br>
                Curso: <strong style="color: #173e67;">{{ $curso->nombre }}</strong>
            </p>

            <div id="history-timeline" style="display: flex; flex-direction: column; gap: 10px;">
                <!-- Aquí se inyectan los datos -->
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnsOpen = document.querySelectorAll('.js-open-history');
            const modalHistory = document.getElementById('history-modal');
            
            if (!modalHistory || btnsOpen.length === 0) return;

            const btnClose = modalHistory.querySelectorAll('[data-close-history]');
            const timelineContainer = document.getElementById('history-timeline');
            const titleTrabajador = document.getElementById('history-trabajador-name');

            const closeHistoryModal = () => {
                modalHistory.classList.remove('is-open');
                modalHistory.setAttribute('aria-hidden', 'true');
            };

            btnClose.forEach(btn => btn.addEventListener('click', closeHistoryModal));
            modalHistory.addEventListener('click', (e) => { if(e.target === modalHistory) closeHistoryModal(); });
            document.addEventListener('keydown', (e) => { if(e.key === 'Escape') closeHistoryModal(); });

            btnsOpen.forEach(btn => {
                btn.addEventListener('click', async function() {
                    const personalId = this.getAttribute('data-personal-id');
                    const cursoId = this.getAttribute('data-curso-id');
                    const trabajadorNombre = this.getAttribute('data-trabajador-nombre');
                    
                    titleTrabajador.textContent = trabajadorNombre;
                    timelineContainer.innerHTML = '<div style="text-align: center; padding: 20px; color: #64748b;"><i class="fas fa-spinner fa-spin"></i> Consultando registros...</div>';
                    
                    modalHistory.classList.add('is-open');
                    modalHistory.setAttribute('aria-hidden', 'false');

                    try {
                        const response = await fetch(`/personal/${personalId}/cursos/${cursoId}/historial`);
                        if (!response.ok) throw new Error('Error al obtener datos');
                        
                        const data = await response.json();
                        timelineContainer.innerHTML = '';

                        // Renderizamos el curso actual
                        if (data.actual) {
                            const isApto = data.actual.apto;
                            const badgeColor = isApto ? '#166534' : '#b91c1c';
                            const badgeBg = isApto ? '#dcfce7' : '#fee2e2';
                            const badgeTexto = isApto ? 'APTO' : 'NO APTO';
                            
                            const diplomaBtn = data.actual.diploma_url 
                                ? `<a href="${data.actual.diploma_url}" target="_blank" style="margin-right: 12px; font-size: 0.75rem; text-decoration: none; color: #166534; background: #dcfce7; padding: 4px 10px; border-radius: 6px; font-weight: 800; border: 1px solid #bbf7d0;"><i class="fas fa-file-pdf"></i> Ver certificado</a>` 
                                : '';

                            timelineContainer.innerHTML += `
                                <div style="padding: 12px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-size: 0.75rem; font-weight: 800; color: #166534; text-transform: uppercase;">Certificado Actual</div>
                                        <div style="font-weight: 700; color: #14532d; font-size: 1.1rem;">${data.actual.fecha}</div>
                                    </div>
                                    <div style="display: flex; align-items: center;">
                                        ${diplomaBtn}
                                        <span style="font-size: 11px; font-weight: 800; padding: 5px 9px; border-radius: 999px; background: ${badgeBg}; color: ${badgeColor};">${badgeTexto}</span>
                                    </div>
                                </div>
                            `;
                        }

                        // Renderizamos los cursos pasados
                        if (data.historico && data.historico.length > 0) {
                            data.historico.forEach(item => {
                                const isApto = item.apto;
                                const badgeColor = isApto ? '#166534' : '#b91c1c';
                                const badgeBg = isApto ? '#dcfce7' : '#fee2e2';
                                const badgeTexto = isApto ? 'APTO' : 'NO APTO';
                                
                                const diplomaBtn = item.diploma_url 
                                    ? `<a href="${item.diploma_url}" target="_blank" style="margin-right: 12px; font-size: 0.75rem; text-decoration: none; color: #475569; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-weight: 800; border: 1px solid #e2e8f0;"><i class="fas fa-file-pdf"></i> Ver certificado</a>` 
                                    : '';

                                timelineContainer.innerHTML += `
                                    <div style="padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; opacity: 0.85;">
                                        <div>
                                            <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Registro Archivado</div>
                                            <div style="font-weight: 700; color: #475569; font-size: 1rem;">${item.fecha}</div>
                                        </div>
                                        <div style="display: flex; align-items: center;">
                                            ${diplomaBtn}
                                            <span style="font-size: 11px; font-weight: 800; padding: 5px 9px; border-radius: 999px; background: #e2e8f0; color: #64748b;">${badgeTexto}</span>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            if (!data.actual) {
                                timelineContainer.innerHTML = '<div style="text-align: center; color: #64748b;">No hay registros de fechas para este trabajador en este curso.</div>';
                            } else {
                                timelineContainer.innerHTML += '<div style="text-align: center; color: #94a3b8; font-size: 0.85rem; margin-top: 10px;">No existen renovaciones anteriores.</div>';
                            }
                        }

                    } catch (error) {
                        timelineContainer.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 20px;">Error al cargar el historial.</div>';
                    }
                });
            });
        });
    </script>
@endsection