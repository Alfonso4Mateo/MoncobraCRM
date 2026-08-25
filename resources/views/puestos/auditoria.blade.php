@extends('adminlte::page')

@section('title', 'Auditoría de Matriz')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .audit-hero { background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; padding: 20px 24px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(15,23,42,0.02); display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px; }
        .audit-title h1 { font-size: 1.6rem; font-weight: 800; color: #173e67; margin: 0 0 5px; text-transform: uppercase; }
        .audit-title p { color: #64748b; margin: 0; font-size: 0.9rem; }
        
        .kpi-container { display: flex; gap: 15px; }
        .kpi-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 20px; min-width: 140px; }
        .kpi-card span { display: block; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-card strong { display: block; font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-top: 4px; }
        .kpi-card--green strong { color: #166534; }
        .kpi-card--red strong { color: #b91c1c; }

        .matrix-container { background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; padding: 0; overflow-x: auto; box-shadow: 0 4px 6px rgba(15,23,42,0.02); }
        .matrix-table { width: 100%; border-collapse: collapse; min-width: 800px; }
        .matrix-table th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; border-right: 1px solid #f1f5f9; padding: 12px; text-align: center; font-size: 0.75rem; font-weight: 800; color: #475569; letter-spacing: 0.05em; white-space: nowrap; }
        .matrix-table th.col-worker { text-align: left; position: sticky; left: 0; background: #f8fafc; z-index: 10; box-shadow: 2px 0 5px rgba(0,0,0,0.02); border-right: 2px solid #e2e8f0; min-width: 250px; }
        
        .matrix-table td { border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; padding: 10px; text-align: center; vertical-align: middle; }
        .matrix-table td.col-worker { text-align: left; position: sticky; left: 0; background: #fff; z-index: 10; box-shadow: 2px 0 5px rgba(0,0,0,0.02); border-right: 2px solid #e2e8f0; font-weight: 600; color: #1e293b; }
        .matrix-table tbody tr:hover td { background: #f8fafc; }
        .matrix-table tbody tr:hover td.col-worker { background: #f1f5f9; }

        /* Semáforos */
        .status-dot { display: inline-flex; justify-content: center; align-items: center; width: 24px; height: 24px; border-radius: 50%; font-size: 10px; cursor: help; }
        .status-vigente { background: #dcfce7; color: #166534; box-shadow: 0 0 0 1px #bbf7d0; }
        .status-aviso { background: #fef9c3; color: #854d0e; box-shadow: 0 0 0 1px #fef08a; }
        .status-caducado { background: #fee2e2; color: #b91c1c; box-shadow: 0 0 0 1px #fecaca; }
        .status-pendiente { background: #f1f5f9; color: #94a3b8; box-shadow: 0 0 0 1px #e2e8f0; }

        .legend { display: flex; gap: 15px; padding: 15px; background: #fff; border-top: 1px solid #e2e8f0; font-size: 0.8rem; font-weight: 600; color: #64748b; }
        .legend-item { display: flex; align-items: center; gap: 6px; }

        /* Estilos del Modal de Detalles */
        .audit-modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 1050; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; backdrop-filter: blur(2px); }
        .audit-modal-overlay.is-active { display: flex; opacity: 1; }
        .audit-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 450px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transform: translateY(20px); transition: transform 0.2s; overflow: hidden; }
        .audit-modal-overlay.is-active .audit-modal { transform: translateY(0); }
        .audit-modal-header { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; background: #f8fafc; }
        .audit-modal-title { font-size: 1.1rem; font-weight: 800; color: #1e293b; margin: 0; line-height: 1.3; }
        .audit-modal-subtitle { font-size: 0.85rem; color: #64748b; margin-top: 4px; }
        .audit-modal-close { background: transparent; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer; transition: color 0.2s; }
        .audit-modal-close:hover { color: #0f172a; }
        .audit-modal-body { padding: 24px; }
        .audit-data-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .audit-data-row:last-child { border-bottom: none; padding-bottom: 0; }
        .audit-data-label { font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .audit-data-value { font-size: 0.95rem; font-weight: 800; color: #0f172a; text-align: right; }
        
        /* Cursor pointer para las celdas */
        .matrix-table td.cell-interactive { cursor: pointer; transition: background 0.2s; }
        .matrix-table td.cell-interactive:hover { background: #e0e7ff !important; }
    </style>
@endsection

@section('content')
    <section class="p-3">
        <!-- Cabecera y KPIs -->
        <div class="audit-hero">
            <div class="audit-title">
                <div style="font-size: 0.75rem; font-weight: 800; color: #8a98ab; letter-spacing: 0.1em; margin-bottom: 4px;">REPORTE DE AUDITORÍA</div>
                <h1>{{ $puesto->nombre }}</h1>
                <p>Matriz de cumplimiento de cursos obligatorios por trabajador.</p>
                
                <div class="mt-3 d-flex" style="gap: 10px;">
                    <a href="{{ route('puestos.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px; font-weight: 700;">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <a href="{{ route('puestos.edit', $puesto->id) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px; font-weight: 700;">
                        <i class="fas fa-cog"></i> Editar Matriz
                    </a>
                    <a href="{{ route('puestos.auditoria.export', $puesto->id) }}" class="btn btn-sm" style="background: #10b981; color: white; border-radius: 8px; font-weight: 700;">
                        <i class="fas fa-file-excel"></i> Exportar CSV
                    </a>
                </div>
            </div>

            <div class="kpi-container">
                <div class="kpi-card">
                    <span>Plantilla Evaluada</span>
                    <strong>{{ $totalTrabajadores }}</strong>
                </div>
                <div class="kpi-card kpi-card--green">
                    <span>Cumplimiento Global</span>
                    <strong>{{ $porcentajeCumplimiento }}%</strong>
                </div>
                <div class="kpi-card kpi-card--red">
                    <span>Alertas (Caducados)</span>
                    <strong>{{ $alertas }}</strong>
                </div>
            </div>
        </div>

        <!-- Matriz Visual (Tabla de doble entrada) -->
        <div class="matrix-container">
            <table class="matrix-table">
                <thead>
                        <tr>
                            <th class="col-worker">TRABAJADOR / ID / DNI</th>
                            <th>DEPARTAMENTO</th>
                            <th>PUESTO</th>
                            @foreach($cursosExigidos as $curso)
                                <th title="{{ $curso->nombre }}">
                                    {{ \Illuminate\Support\Str::limit($curso->nombre, 25) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                <tbody>
                    @forelse($trabajadores as $trabajador)
                        @php
                            // Procesamos el departamento igual que en las otras vistas
                            $deptos = is_string($trabajador->departamento) 
                                ? json_decode($trabajador->departamento, true) ?? explode(',', $trabajador->departamento) 
                                : (array) $trabajador->departamento;
                            $departamentoStr = !empty($deptos) ? implode(', ', $deptos) : 'Sin departamento';
                        @endphp
                        <tr>
                            <td class="col-worker">
                                <div style="line-height: 1.3;">
                                    <strong>{{ $trabajador->name }} {{ $trabajador->apellido }}</strong>
                                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 600; margin-top: 2px;">
                                        ID RRHH: {{ $trabajador->id_rrhh ?: '—' }} | DNI: {{ $trabajador->dni_nie ?: '—' }}
                                    </div>
                                </div>
                            </td>
                            <td><span class="text-muted" style="font-size: 0.85rem; font-weight: 600;">{{ strtoupper($departamentoStr ?: '—') }}</span></td>
                            <td><span class="text-muted" style="font-size: 0.85rem; font-weight: 600;">{{ $trabajador->puesto ?: '—' }}</span></td>
                            
                            @foreach($cursosExigidos as $curso)
                                @php
                                    $info = $trabajador->matriz_cursos[$curso->id];
                                    $icono = 'fa-minus';
                                    $estadoTexto = 'Pendiente';
                                    $colorClase = 'status-pendiente';
                                    
                                    if ($info['estado'] === 'vigente') { $icono = 'fa-check'; $estadoTexto = 'Vigente'; $colorClase = 'status-vigente'; }
                                    if ($info['estado'] === 'aviso') { $icono = 'fa-exclamation'; $estadoTexto = 'En Aviso'; $colorClase = 'status-aviso'; }
                                    if ($info['estado'] === 'caducado') { $icono = 'fa-times'; $estadoTexto = 'Caducado'; $colorClase = 'status-caducado'; }
                                @endphp
                                
                                <td class="cell-interactive js-open-detail" 
                                    data-trabajador="{{ $trabajador->name }} {{ $trabajador->apellido }}"
                                    data-curso="{{ $info['curso_nombre'] }}"
                                    data-estado-clase="{{ $colorClase }}"
                                    data-estado-texto="{{ $estadoTexto }}"
                                    data-realizacion="{{ $info['fecha_realizacion'] }}"
                                    data-vencimiento="{{ $info['fecha_vencimiento'] }}"
                                    data-restante="{{ $info['dias_restantes'] }}"
                                    title="Clic para ver detalles">
                                    <span class="status-dot {{ $colorClase }}">
                                        <i class="fas {{ $icono }}"></i>
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $cursosExigidos->count() + 1 }}" class="text-center py-5 text-muted">
                                <i class="fas fa-users-slash fa-2x mb-2 text-light"></i><br>
                                No hay trabajadores asignados al perfil de {{ $puesto->nombre }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="legend">
                <div class="legend-item"><span class="status-dot status-vigente"><i class="fas fa-check"></i></span> Vigente / Apto</div>
                <div class="legend-item"><span class="status-dot status-aviso"><i class="fas fa-exclamation"></i></span> Próximo a caducar</div>
                <div class="legend-item"><span class="status-dot status-caducado"><i class="fas fa-times"></i></span> Caducado</div>
                <div class="legend-item"><span class="status-dot status-pendiente"><i class="fas fa-minus"></i></span> Pendiente de realizar</div>
            </div>
        </div>
    </section>

    <!-- MODAL DE DETALLES -->
    <div class="audit-modal-overlay" id="detail-modal">
        <div class="audit-modal">
            <div class="audit-modal-header">
                <div>
                    <h3 class="audit-modal-title" id="modal-curso-nombre">Nombre del Curso</h3>
                    <div class="audit-modal-subtitle" id="modal-trabajador-nombre">Nombre del Trabajador</div>
                </div>
                <button class="audit-modal-close" id="close-modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="audit-modal-body">
                <div class="audit-data-row">
                    <span class="audit-data-label">Estado</span>
                    <span class="audit-data-value"><span class="status-dot" id="modal-estado-dot" style="position:relative; top:2px; margin-right:6px;"></span> <span id="modal-estado-texto">Vigente</span></span>
                </div>
                <div class="audit-data-row">
                    <span class="audit-data-label">Fecha Realización</span>
                    <span class="audit-data-value" id="modal-fecha-realizacion">10/05/2023</span>
                </div>
                <div class="audit-data-row">
                    <span class="audit-data-label">Fecha Vencimiento</span>
                    <span class="audit-data-value" id="modal-fecha-vencimiento">10/05/2026</span>
                </div>
                <div class="audit-data-row">
                    <span class="audit-data-label">Tiempo Restante</span>
                    <span class="audit-data-value" id="modal-dias-restantes">145 días</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // Lógica del Modal de Detalles
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('detail-modal');
            const btnClose = document.getElementById('close-modal');
            const cells = document.querySelectorAll('.js-open-detail');

            // Elementos del modal
            const mCurso = document.getElementById('modal-curso-nombre');
            const mTrabajador = document.getElementById('modal-trabajador-nombre');
            const mEstadoDot = document.getElementById('modal-estado-dot');
            const mEstadoTexto = document.getElementById('modal-estado-texto');
            const mRealizacion = document.getElementById('modal-fecha-realizacion');
            const mVencimiento = document.getElementById('modal-fecha-vencimiento');
            const mRestante = document.getElementById('modal-dias-restantes');

            cells.forEach(cell => {
                cell.addEventListener('click', function() {
                    // Leer datos de la celda
                    mCurso.textContent = this.dataset.curso;
                    mTrabajador.textContent = this.dataset.trabajador;
                    mEstadoTexto.textContent = this.dataset.estadoTexto;
                    mRealizacion.textContent = this.dataset.realizacion;
                    mVencimiento.textContent = this.dataset.vencimiento;
                    mRestante.textContent = this.dataset.restante;
                    
                    // Actualizar semáforo
                    mEstadoDot.className = 'status-dot ' + this.dataset.estadoClase;

                    // Mostrar modal
                    modal.classList.add('is-active');
                });
            });

            // Cerrar modal
            const closeModal = () => modal.classList.remove('is-active');
            
            btnClose.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if(e.target === modal) closeModal(); // Clic fuera del modal
            });
            document.addEventListener('keydown', (e) => {
                if(e.key === 'Escape') closeModal();
            });
        });
    </script>
@endsection