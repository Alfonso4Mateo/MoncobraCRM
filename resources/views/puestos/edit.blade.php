@extends('adminlte::page')

@section('title', 'Matriz de Formación')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .header-panel { background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(15,23,42,0.02); display: flex; justify-content: space-between; align-items: center; }
        .header-panel h2 { margin: 0; font-weight: 800; color: #173e67; }
        .macro-container { background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; padding: 20px; }

        .category-group { margin-bottom: 20px; }
        .category-title { font-size: 1rem; font-weight: 800; color: #173e67; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #f1f5f9; text-transform: uppercase; letter-spacing: 0.05em; }

        .course-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 1px solid #edf2f7; border-radius: 10px; margin-bottom: 8px; background: #f8fafc; transition: all 0.2s; }
        .course-item:hover { border-color: #cbd5e1; background: #fff; }
        .course-name { font-weight: 700; color: #334155; }

        /* Estilos del Toggle heredados de tu interfaz */
        .course-toggle { position: relative; width: 48px; height: 26px; display: inline-block; margin: 0; }
        .course-toggle input { opacity: 0; width: 0; height: 0; }
        .course-toggle__track { position: absolute; inset: 0; background: #dbe3ef; border-radius: 999px; transition: all .18s ease; cursor: pointer; }
        .course-toggle__thumb { position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; background: #fff; border-radius: 50%; box-shadow: 0 2px 8px rgba(15, 23, 42, .16); transition: all .18s ease; }
        .course-toggle input:checked + .course-toggle__track { background: #173e67; }
        .course-toggle input:checked + .course-toggle__track .course-toggle__thumb { transform: translateX(22px); }

        .btn-save { background: #173e67; color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
        .btn-save:hover { background: #0f2a47; color: white; }

        .btn-save--epi { background: #0369a1; }
        .btn-save--epi:hover { background: #075985; }

        /* --- Pestañas (Cursos / EPIs) --- */
        .section-tabs { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; }
        .tab-btn {
            background: none; border: none; cursor: pointer;
            padding: 12px 18px; font-weight: 800; font-size: 0.95rem; color: #8a98ab;
            border-bottom: 3px solid transparent; margin-bottom: -2px;
            display: inline-flex; align-items: center; gap: 8px; transition: all 0.15s ease;
        }
        .tab-btn:hover { color: #173e67; }
        .tab-btn.is-active { color: #173e67; border-bottom-color: #173e67; }
        .tab-btn .tab-count {
            background: #eef2f7; color: #667085; font-size: 0.7rem; font-weight: 800;
            padding: 2px 8px; border-radius: 999px;
        }
        .tab-btn.is-active .tab-count { background: #173e67; color: #fff; }
        .tab-panel { display: none; }
        .tab-panel.is-active { display: block; }

        /* --- Lista de asignación de EPIs (misma UI que epis/index.blade.php) --- */
        .puesto-assign-list { border: 1px solid #e2e8f0; border-radius: 10px; max-height: 420px; overflow-y: auto; background: #f8fafc; padding: 10px; }
        .puesto-assign-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #e2e8f0; background: #fff; border-radius: 8px; margin-bottom: 6px; }
        .puesto-assign-item:last-child { margin-bottom: 0; }
        .puesto-assign-item__desc { font-size: 0.75rem; color: #94a3b8; margin-top: 2px; }
        .qty-input { width: 70px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; }
        .qty-input:disabled { background: #e2e8f0; opacity: 0.6; cursor: not-allowed; }
        .epis-empty { padding: 30px; text-align: center; border: 2px dashed #cbd5e1; border-radius: 12px; color: #64748b; }

        @media (max-width: 720px) {
            .section-tabs { overflow-x: auto; }
        }
    </style>
@endsection

@section('content')
    <section class="p-3">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" style="border-radius: 12px; font-weight: 600;">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Panel Superior (Edición de nombre) -->
        <div class="header-panel">
            <div>
                <p class="text-muted mb-1" style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase;">Configurando Matriz para:</p>

                <form action="{{ route('puestos.update', $puesto->id) }}" method="POST" class="d-flex align-items-center" style="gap: 10px;">
                    @csrf
                    @method('PUT')
                    <input type="text" name="nombre" value="{{ $puesto->nombre }}" class="form-control" style="font-size: 1.5rem; font-weight: 800; color: #173e67; border: 1px solid transparent; background: transparent; padding: 0; width: auto;" required>
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Renombrar puesto"><i class="fas fa-save"></i></button>
                </form>
            </div>

            <div style="display: flex; gap: 10px;">
                <a href="{{ route('puestos.auditoria.export', $puesto->id) }}" class="btn" style="background: #10b981; color: white; border-radius: 10px; font-weight: 700; display: inline-flex; align-items: center; border: none;">
                    <i class="fas fa-file-excel mr-2"></i> Descargar Auditoría CSV
                </a>

                <a href="{{ route('puestos.index') }}" class="btn btn-outline-secondary" style="border-radius: 10px; font-weight: 700; display: inline-flex; align-items: center;">
                    <i class="fas fa-arrow-left mr-2"></i> Volver
                </a>
            </div>
        </div>

        <!-- Contenedor con pestañas: Cursos Obligatorios / Dotación de EPIs -->
        <div class="macro-container">

            <div class="section-tabs" id="puesto-tabs">
                <button type="button" class="tab-btn" data-tab="cursos">
                    <i class="fas fa-graduation-cap"></i> Cursos Obligatorios
                    <span class="tab-count">{{ count($cursosAsignados) }}</span>
                </button>
                <button type="button" class="tab-btn" data-tab="epis">
                    <i class="fas fa-hard-hat"></i> Dotación de EPIs
                    <span class="tab-count">{{ count($episAsignados) }}</span>
                </button>
            </div>

            <!-- ===================== PESTAÑA: CURSOS ===================== -->
            <div class="tab-panel" data-panel="cursos">
                <div class="mb-4">
                    <h4 style="font-weight: 800; color: #173e67;">Asignación de Cursos Obligatorios</h4>
                    <p class="text-muted">Activa los cursos que el sistema deberá exigir automáticamente a cualquier trabajador que ocupe este puesto.</p>
                </div>

                <form action="{{ route('puestos.sync-cursos', $puesto->id) }}" method="POST">
                    @csrf

                    <div class="row">
                        @forelse($cursosPorCategoria as $categoria => $cursos)
                            <div class="col-md-6 col-lg-4">
                                <div class="category-group">
                                    <div class="category-title">{{ $categoria }}</div>

                                    @foreach($cursos as $curso)
                                        <div class="course-item">
                                            <span class="course-name">{{ $curso->nombre }}</span>

                                            <label class="course-toggle" title="Marcar como obligatorio">
                                                <!-- Si el curso está en el array de asignados, lo marcamos como checked -->
                                                <input type="checkbox" name="cursos[]" value="{{ $curso->id }}" @checked(in_array($curso->id, $cursosAsignados))>
                                                <span class="course-toggle__track"><span class="course-toggle__thumb"></span></span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="epis-empty">
                                    <i class="fas fa-graduation-cap fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">No hay cursos registrados en el catálogo.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4 pt-3 border-top text-right">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Guardar Matriz de Formación
                        </button>
                    </div>
                </form>
            </div>

            <!-- ===================== PESTAÑA: EPIs ===================== -->
            <div class="tab-panel" data-panel="epis">
                <div class="card-header bg-white" style="border-radius: 14px 14px 0 0; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 class="card-title" style="font-weight: 800; color: #173e67; margin: 0; font-size: 1.1rem;">
                            <i class="fas fa-hard-hat mr-2 text-primary"></i> Dotación de EPIs Obligatorios
                        </h3>
                        <p class="text-muted text-sm mb-0 mt-1">Selecciona los equipos de protección que este puesto de trabajo exige.</p>
                    </div>
                    
                    <!-- NUEVO BOTÓN DE DESCARGA PDF -->
                    @can('personal.export')
                        <a href="{{ route('puestos.epis.pdf', $puesto->id) }}" target="_blank" class="btn btn-sm" style="background: #ef4444; color: white; border-radius: 8px; font-weight: 700; border: none; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);">
                            <i class="fas fa-file-pdf mr-1"></i> Sacar Documento EPIs
                        </a>
                    @endcan
                </div>

                <form action="{{ route('puestos.sync-epis', $puesto->id) }}" method="POST">
                    @csrf

                    @if($episDisponibles->isEmpty())
                        <div class="epis-empty">
                            <i class="fas fa-hard-hat fa-2x mb-2 text-muted"></i>
                            <p class="mb-1">Todavía no hay EPIs activos en el catálogo.</p>
                            <a href="{{ route('epis.index') }}" style="font-weight: 700; color: #0369a1;">Ir a Gestionar EPIs <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                    @else
                        <div class="puesto-assign-list">
                            @foreach($episDisponibles as $epi)
                                @php
                                    $estaAsignado = array_key_exists($epi->id, $episAsignados);
                                    $cantidadActual = $estaAsignado ? $episAsignados[$epi->id]['cantidad'] : 1;
                                @endphp
                                <div class="puesto-assign-item">
                                    <label style="margin: 0; display: flex; align-items: center; gap: 10px; cursor: pointer; flex: 1; min-width: 0;">
                                        <input
                                            type="checkbox"
                                            name="epis[]"
                                            value="{{ $epi->id }}"
                                            class="epi-checkbox"
                                            data-target="epi-qty-{{ $epi->id }}"
                                            @checked($estaAsignado)
                                        >
                                        <span>
                                            <span style="font-weight: 700; color: #334155; display: block;">{{ $epi->nombre }}</span>
                                            @if($epi->descripcion)
                                                <span class="puesto-assign-item__desc">{{ \Illuminate\Support\Str::limit($epi->descripcion, 80) }}</span>
                                            @endif
                                        </span>
                                    </label>
                                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                        <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">UDS:</span>
                                        <input
                                            type="number"
                                            name="cantidades[{{ $epi->id }}]"
                                            id="epi-qty-{{ $epi->id }}"
                                            class="qty-input"
                                            value="{{ $cantidadActual }}"
                                            min="1"
                                            @disabled(!$estaAsignado)
                                        >
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 pt-3 border-top text-right">
                            <button type="submit" class="btn-save btn-save--epi">
                                <i class="fas fa-save"></i> Guardar Dotación de EPIs
                            </button>
                        </div>
                    @endif
                </form>
            </div>

        </div>
    </section>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ---------------------------------------------------------
            // Lógica de pestañas Cursos / EPIs
            // ---------------------------------------------------------
            const tabButtons = document.querySelectorAll('#puesto-tabs .tab-btn');
            const tabPanels = document.querySelectorAll('.tab-panel');
            const storageKey = 'puestoEditActiveTab';

            const activateTab = (tabName) => {
                tabButtons.forEach(b => b.classList.toggle('is-active', b.dataset.tab === tabName));
                tabPanels.forEach(p => p.classList.toggle('is-active', p.dataset.panel === tabName));
                localStorage.setItem(storageKey, tabName);
            };

            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => activateTab(btn.dataset.tab));
            });

            // Determinamos qué pestaña abrir por defecto:
            // 1) Si Laravel nos dice qué formulario fallo (old('epis')/old('cantidades') presentes) -> esa
            // 2) Si no, recordamos la última pestaña usada por el usuario (localStorage)
            // 3) Si no hay nada, "cursos" por defecto
            @php
                $oldTieneEpis = old('epis') !== null || old('cantidades') !== null;
                $oldTieneCursos = old('cursos') !== null;
            @endphp

            let defaultTab = 'cursos';
            @if($oldTieneEpis)
                defaultTab = 'epis';
            @elseif($oldTieneCursos)
                defaultTab = 'cursos';
            @else
                defaultTab = localStorage.getItem(storageKey) || 'cursos';
            @endif

            activateTab(defaultTab);

            // ---------------------------------------------------------
            // Habilitar/deshabilitar el input de cantidad al marcar un EPI
            // ---------------------------------------------------------
            document.querySelectorAll('.epi-checkbox').forEach(chk => {
                chk.addEventListener('change', function() {
                    const targetId = this.getAttribute('data-target');
                    const inputQty = document.getElementById(targetId);

                    if (this.checked) {
                        inputQty.disabled = false;
                        inputQty.focus();
                    } else {
                        inputQty.disabled = true;
                        inputQty.value = 1;
                    }
                });
            });
        });
    </script>
@endsection