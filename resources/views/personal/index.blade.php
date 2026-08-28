@extends('adminlte::page')

@section('title', 'Gestión de Personal')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/personal-index.css'])
    <style>
        .personal-course-status,
        .personal-course-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 9px;
            margin: 0 6px 6px 0;
        }

        .personal-course-status--ok,
        .personal-course-pill--ok { background: #e8f5e9; color: #2e7d32; }
        .personal-course-status--warn,
        .personal-course-pill--warn { background: #fff8e1; color: #b8860b; }
        .personal-course-status--muted,
        .personal-course-pill--muted { background: #eef2f7; color: #667085; }
        .personal-course-cell { min-width: 220px; }
        .personal-course-list { margin-top: 6px; }
    </style>
@endsection

@section('content')
    <section class="personal-page">
        <header class="personal-hero">
            <div class="personal-hero-copy">
                <div class="personal-crumbs">GESTIÓN DE PERSONAL <span>•</span> LISTADO</div>
                <h1>Listado de Personal y Tallas</h1>
                <p>Consulta y administra el equipo de trabajo con información de equipamiento.</p>
            </div>

            <div class="personal-hero-actions">
            
            @can('cursos.view')
                <a href="{{ route('cursos.index') }}" class="personal-action-btn personal-action-btn--soft" style="margin-right:12px;">
                    <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                    Catálogo de Cursos
                </a>
            @endcan

            @can('personal.edit')
                <a href="{{ route('personal.puestos-trabajo.index') }}" class="personal-action-btn personal-action-btn--soft" style="margin-right:12px;">
                    <i class="fas fa-briefcase" aria-hidden="true"></i>
                    Gestionar Puestos
                </a>
            @endcan

            @can('personal.tallas')
                <a href="{{ route('personal.tallas') }}" class="personal-action-btn personal-action-btn--soft" style="margin-right:12px;">
                    <i class="fas fa-tshirt" aria-hidden="true"></i>
                    Gestionar Tallas
                </a>
            @endcan

            @can('personal.create')
                <a href="{{ route('personal.create') }}" class="personal-primary-action">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    + Añadir Nuevo Trabajador
                </a>
            @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="personal-alert personal-alert-success">
                <i class="fas fa-circle-check"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="personal-alert personal-alert-error">
                <i class="fas fa-triangle-exclamation"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="personal-shell">
            <article class="personal-card">
                <header class="personal-card__header">
                    <div>
                        <h3>Registro general de personal</h3>
                        <p>
                            {{ $personals->total() }} trabajadores
                            @if(($avisosCount ?? 0) > 0)
                                &middot; <strong style="color:#c0392b;">{{ $avisosCount }} con aviso médico</strong>
                            @endif
                        </p>
                    </div>

                @can('personal.medico')
                    <div class="personal-card__actions">
                        <form method="GET" action="{{ route('personal.index') }}" class="personal-recognition-form">
                            <input type="hidden" name="q" value="{{ $query ?? '' }}">
                            <input type="hidden" name="alerta_nombre" value="{{ $alertaNombre ?? 'any' }}">
                            <label for="alerta_dias" class="personal-recognition-label">Gestionar reconocimientos</label>
                            <div class="personal-recognition-control">
                                <i class="fas fa-stethoscope" aria-hidden="true"></i>
                                <select id="alerta_dias" name="alerta_dias" onchange="this.form.submit()">
                                    <option value="0" @selected(($alertaDias ?? 0) === 0)>Sin aviso</option>
                                    <option value="30" @selected(($alertaDias ?? 0) === 30)>Avisar en 30 días</option>
                                    <option value="60" @selected(($alertaDias ?? 0) === 60)>Avisar en 60 días</option>
                                    <option value="90" @selected(($alertaDias ?? 0) === 90)>Avisar en 90 días</option>
                                    <option value="120" @selected(($alertaDias ?? 0) === 120)>Avisar en 120 días</option>
                                    <option value="180" @selected(($alertaDias ?? 0) === 180)>Avisar en 180 días</option>
                                </select>
                            </div>
                        </form>
                    </div>
                @endcan
                </header>

                <div class="personal-search-wrap">
                    <form method="GET" action="{{ route('personal.index') }}" class="personal-search-form">
                        <input type="hidden" name="alerta_dias" value="{{ $alertaDias ?? 0 }}">

                    @can('personal.medico')
                        <div class="personal-search-filters" style="margin-bottom:10px; display:flex; gap:12px; align-items:center;">
                            <label for="alerta_nombre" class="personal-recognition-label" style="margin:0;">Filtrar por aviso médico</label>
                            <div class="personal-recognition-control">
                                <select id="alerta_nombre" name="alerta_nombre" onchange="this.form.submit()">
                                    <option value="any" @selected(($alertaNombre ?? 'any') === 'any')>Todos</option>
                                    <option value="with" @selected(($alertaNombre ?? '') === 'with')>Con aviso</option>
                                    <option value="without" @selected(($alertaNombre ?? '') === 'without')>Sin aviso</option>
                                </select>
                            </div>
                        </div>
                    @endcan

                        <div class="personal-search-field personal-search-field--search">
                            <label for="q"></label>
                            <input type="search" id="q" name="q" value="{{ $query ?? '' }}" placeholder="Buscar por nombre o apellidos..." autocomplete="on">
                        </div>
                    </form>
                </div>

                <div id="personal-table-container">
                    @include('personal.partials.table', ['personals' => $personals])
                </div>

                <div class="personal-toolbar personal-toolbar-footer">
                    <span class="personal-toolbar-label">Mostrando {{ $personals->firstItem() ?? 0 }} - {{ $personals->lastItem() ?? 0 }} de {{ number_format($personals->total(), 0, ',', '.') }} trabajadores</span>

                    @if ($personals->hasPages())
                        <div class="personal-pagination">
                            {{ $personals->onEachSide(1)->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </article>

            <section class="personal-footer-grid">
                @php
                    $tallasFaltantes = 0;
                    $personalNecesitaEpi = \App\Models\Personal::where('sin_tallas', false)->get(['camiseta', 'chaqueta', 'sudadera', 'pantalon', 'calzado', 'guantes', 'casco', 'gafas']);
                    
                    $columnas = ['camiseta', 'chaqueta', 'sudadera', 'pantalon', 'calzado', 'guantes', 'casco', 'gafas'];
                    
                    foreach ($personalNecesitaEpi as $p) {
                        foreach ($columnas as $col) {
                            if (empty($p->{$col})) {
                                $tallasFaltantes++;
                            }
                        }
                    }
                @endphp

                <article class="personal-foot-card personal-foot-card--blue">
                    <div class="personal-foot-card__title">
                        <i class="fas fa-users"></i>
                        Plantilla (Activa / Total)
                    </div>
                    <strong class="personal-foot-card__value">{{ $personalActivos ?? 0 }} / {{ $personalTotal ?? 0 }}</strong>
                    <p>Empleados registrados</p>
                </article>

            @can('personal.tallas')
                <article class="personal-foot-card personal-foot-card--orange">
                    <div class="personal-foot-card__title">
                        <i class="fas fa-ruler-combined"></i>
                        Tallas No Registradas
                    </div>
                    <strong class="personal-foot-card__value">{{ $tallasFaltantes }}</strong>
                    <p>Prendas individuales por asignar</p>
                </article>
            @endcan

            @can('personal.medico')
                <article class="personal-foot-card personal-foot-card--navy">
                    <div class="personal-foot-card__title">
                        <i class="fas fa-notes-medical"></i>
                        Avisos Médicos
                    </div>
                    <strong class="personal-foot-card__value">{{ $avisosCount ?? 0 }}</strong>
                    <p>Revisiones próximas o vencidas</p>
                </article>
            @endcan
            </section>
        </div>

        <!-- BARRA FLOTANTE DE ACCIONES MASIVAS -->
        <div id="bulk-action-bar" class="bulk-action-bar">
            <div class="bulk-action-info">
                <span id="bulk-count" class="bulk-action-count">0</span>
                <span>Trabajadores seleccionados</span>
                <button type="button" id="btn-view-selected" style="background: transparent; border: none; color: #93c5fd; cursor: pointer; font-size: 0.85rem; font-weight: 700; text-decoration: underline; margin-left: 8px;">
                    Ver lista
                </button>
            </div>
            <div class="bulk-action-buttons">
                @can('personal.bulk')
                <button type="button" class="bulk-btn" id="btn-bulk-depto">
                    <i class="fas fa-building"></i> Departamento
                </button>
                @endcan
                
                @can('cursos.edit')
                <button type="button" class="bulk-btn" id="btn-bulk-curso">
                    <i class="fas fa-graduation-cap"></i> Asignar Curso
                </button>
                @endcan

                @can('personal.export')
                <button type="button" class="bulk-btn" id="btn-bulk-export">
                    <i class="fas fa-file-excel"></i> Exportar
                </button>
                @endcan
            </div>
        </div>
    </section>

    <!-- MODAL DE ASIGNACIÓN MASIVA DE CURSOS (Solo para quien pueda editar cursos) -->
    @can('cursos.edit')
        <div class="bulk-modal" id="bulk-course-modal" aria-hidden="true" role="dialog" aria-modal="true">
            <div class="bulk-modal__panel">
                <h2 class="bulk-modal__title">Asignar Curso Masivamente</h2>
                <p class="bulk-modal__text">
                    Vas a asignar este curso a <strong id="bulk-modal-count-text" style="color: var(--personal-primary); font-size: 1.1em;">0</strong> trabajadores seleccionados.
                </p>

                <form id="bulk-assign-form">
                    <div class="personal-search-field" style="margin-bottom: 12px;">
                        <label for="bulk_curso_id">Curso</label>
                        <select id="bulk_curso_id" name="curso_id" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #e4ebf5; background: #f7f9fc;">
                            <option value="">Selecciona un curso...</option>
                            @if(isset($cursosCatalogo))
                                @foreach($cursosCatalogo as $curso)
                                    <option value="{{ $curso->id }}">{{ $curso->nombre }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div class="personal-search-field">
                            <label for="bulk_fecha_realizacion">Fecha realización</label>
                            <input type="date" id="bulk_fecha_realizacion" name="fecha_realizacion" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #e4ebf5; background: #f7f9fc;">
                        </div>

                        <div class="personal-search-field">
                            <label for="bulk_apto">Apto</label>
                            <select id="bulk_apto" name="apto" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #e4ebf5; background: #f7f9fc;">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>

                    <div class="personal-search-field">
                        <label for="bulk_descripcion_aptitud">Observaciones generales</label>
                        <textarea id="bulk_descripcion_aptitud" name="descripcion_aptitud" rows="2" placeholder="Se aplicará la misma observación a todos..." style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #e4ebf5; background: #f7f9fc; resize: vertical; font-family: inherit;"></textarea>
                    </div>

                    <div class="bulk-modal__actions">
                        <button type="button" class="bulk-modal__btn bulk-modal__btn--cancel" id="btn-close-bulk-modal">Cancelar</button>
                        <button type="submit" class="bulk-modal__btn bulk-modal__btn--submit" id="btn-submit-bulk-modal">Asignar a todos</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <!-- MODAL DE CAMBIO MASIVO DE DEPARTAMENTO (Solo para quien pueda hacer acciones bulk) -->
    @can('personal.bulk')
    <div class="bulk-modal" id="bulk-depto-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="bulk-modal__panel" style="max-width: 480px;">
            <h2 class="bulk-modal__title">Cambiar Departamento</h2>
            <p class="bulk-modal__text">
                Actualizando información para <strong id="bulk-depto-count-text" style="color: var(--personal-primary); font-size: 1.1em;">0</strong> trabajadores.
            </p>

            <form id="bulk-depto-form">
                <div class="personal-search-field">
                    <label for="bulk_departamento">Selecciona el nuevo departamento</label>
                    <select id="bulk_departamento" name="departamento[]" multiple style="width: 100%; height: 160px; padding: 10px 12px; border-radius: 8px; border: 1px solid #e4ebf5; background: #f7f9fc;">
                        @if(isset($departamentos))
                            @foreach($departamentos as $depto)
                                <option value="{{ $depto->nombre }}">{{ strtoupper($depto->nombre) }}</option>
                            @endforeach
                        @endif
                    </select>
                    <small style="font-size: 0.75rem; color: #8a98ab; margin-top: 6px; display: block;">
                        Ctrl (Windows) o Cmd (Mac) para seleccionar varios. Si lo dejas en blanco, se borrará el departamento actual.
                    </small>
                </div>

                <div class="bulk-modal__actions">
                    <button type="button" class="bulk-modal__btn bulk-modal__btn--cancel" id="btn-close-depto-modal">Cancelar</button>
                    <button type="submit" class="bulk-modal__btn bulk-modal__btn--submit" id="btn-submit-depto-modal">Actualizar a todos</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    <!-- MODAL DE GESTIÓN DE SELECCIONADOS (Si tiene algún permiso de acción masiva) -->
    @canany(['personal.bulk', 'cursos.edit'])
    <div class="bulk-modal" id="bulk-selected-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="bulk-modal__panel" style="max-width: 500px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2 class="bulk-modal__title" style="margin: 0;">Trabajadores en Selección</h2>
                <button type="button" id="btn-clear-all-selected" style="background: #fee2e2; color: #b91c1c; border: none; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; cursor: pointer;">
                    Limpiar todo
                </button>
            </div>
            <p class="bulk-modal__text" style="margin-bottom: 16px;">
                Puedes eliminar de la lista a quien desees sin necesidad de buscarlo en la tabla.
            </p>

            <div id="selected-workers-list" style="max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; border: 1px solid var(--personal-line); border-radius: 8px; padding: 12px; background: #f7f9fc;">
                <!-- Se rellena por JavaScript -->
            </div>

            <div class="bulk-modal__actions" style="margin-top: 16px;">
                <button type="button" class="bulk-modal__btn bulk-modal__btn--submit" id="btn-close-selected-modal">Cerrar</button>
            </div>
        </div>
    </div>
    @endcanany
@endsection

@section('js')
    <script>
        // --- LÓGICA DE SELECCIÓN MASIVA, MODALES Y BÚSQUEDA AJAX ---
        (function() {
            // 1. Elementos DOM Generales y Contenedores
            const input = document.getElementById('q');
            const tableContainer = document.getElementById('personal-table-container');
            const masterCheckbox = document.getElementById('checkbox-master');
            const bulkBar = document.getElementById('bulk-action-bar');
            const bulkCount = document.getElementById('bulk-count');
            const btnBulkExport = document.getElementById('btn-bulk-export');
            let selectedIds = [];
            
            // Elementos Modal Cursos
            const btnBulkCurso = document.getElementById('btn-bulk-curso');
            const bulkCourseModal = document.getElementById('bulk-course-modal');
            const btnCloseCourseModal = document.getElementById('btn-close-bulk-modal');
            const bulkCourseForm = document.getElementById('bulk-assign-form');
            const courseCountText = document.getElementById('bulk-modal-count-text');
            const btnSubmitCourseModal = document.getElementById('btn-submit-bulk-modal');

            // Elementos Modal Departamentos
            const btnBulkDepto = document.getElementById('btn-bulk-depto');
            const bulkDeptoModal = document.getElementById('bulk-depto-modal');
            const btnCloseDeptoModal = document.getElementById('btn-close-depto-modal');
            const bulkDeptoForm = document.getElementById('bulk-depto-form');
            const deptoCountText = document.getElementById('bulk-depto-count-text');
            const btnSubmitDeptoModal = document.getElementById('btn-submit-bulk-modal');

            // Elementos Modal Ver Seleccionados
            const btnViewSelected = document.getElementById('btn-view-selected');
            const bulkSelectedModal = document.getElementById('bulk-selected-modal');
            const btnCloseSelectedModal = document.getElementById('btn-close-selected-modal');
            const selectedWorkersList = document.getElementById('selected-workers-list');
            const btnClearAllSelected = document.getElementById('btn-clear-all-selected');

            // 2. Función global para actualizar la barra flotante y sincronizar estado
            window.updateBulkUI = () => {
                const rowCheckboxes = document.querySelectorAll('.checkbox-row');
                
                // Recopilamos todos los marcados actualmente en el DOM visible
                rowCheckboxes.forEach(cb => {
                    if (cb.checked && !selectedIds.includes(cb.value)) {
                        selectedIds.push(cb.value);
                    } else if (!cb.checked && selectedIds.includes(cb.value)) {
                        selectedIds = selectedIds.filter(id => id !== cb.value);
                    }
                });

                bulkCount.textContent = selectedIds.length;

                if (selectedIds.length > 0) {
                    bulkBar.classList.add('is-visible');
                } else {
                    bulkBar.classList.remove('is-visible');
                    if(masterCheckbox) masterCheckbox.checked = false;
                }

                if(masterCheckbox && rowCheckboxes.length > 0) {
                    const allChecked = Array.from(rowCheckboxes).every(cb => cb.checked);
                    masterCheckbox.checked = allChecked;
                }
            };

            // 3. Función global para re-vincular eventos al recargar la tabla por AJAX
            window.rebindTableEvents = function() {
                const rowCheckboxes = document.querySelectorAll('.checkbox-row');
                
                rowCheckboxes.forEach(cb => {
                    // Si este ID ya estaba seleccionado previamente en la memoria global, lo marcamos visualmente
                    if (selectedIds.includes(cb.value)) {
                        cb.checked = true;
                    }
                    // Escuchamos cambios en los checkboxes de las filas de la tabla
                    cb.removeEventListener('change', updateBulkUI); // Evita duplicar listeners
                    cb.addEventListener('change', updateBulkUI);
                });

                updateBulkUI();
            };

            // Evento para el checkbox Maestro inicial
            if (masterCheckbox) {
                masterCheckbox.addEventListener('change', function() {
                    const rowCheckboxes = document.querySelectorAll('.checkbox-row');
                    rowCheckboxes.forEach(cb => { 
                        cb.checked = this.checked; 
                    });
                    updateBulkUI();
                });
            }

            // Vincular checkboxes iniciales al cargar la página
            document.querySelectorAll('.checkbox-row').forEach(cb => cb.addEventListener('change', updateBulkUI));

            // 4. Búsqueda Reactiva por AJAX (Debounce de 400ms)
            if(input && tableContainer) {
                let timer = null;
                input.addEventListener('input', function(){
                    clearTimeout(timer);
                    timer = setTimeout(async () => {
                        const queryValue = input.value;
                        const url = `{{ route('personal.index') }}?q=${encodeURIComponent(queryValue)}`;

                        try {
                            const response = await fetch(url, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            
                            if(response.ok) {
                                const html = await response.text();
                                tableContainer.innerHTML = html;
                                
                                // Mantenemos las selecciones de la memoria y re-vinculamos eventos en la nueva tabla
                                rebindTableEvents();
                            }
                        } catch (error) {
                            console.error('Error en la búsqueda asíncrona:', error);
                        }
                    }, 400);
                });
            }
            
            // 5. Control del Modal de Cursos
            const openCourseModal = () => {
                courseCountText.textContent = selectedIds.length;
                bulkCourseModal.classList.add('is-open');
            };

            const closeCourseModal = () => {
                bulkCourseModal.classList.remove('is-open');
                bulkCourseForm.reset();
            };

            if(btnBulkCurso) btnBulkCurso.addEventListener('click', openCourseModal);
            if(btnCloseCourseModal) btnCloseCourseModal.addEventListener('click', closeCourseModal);
            if(bulkCourseModal) {
                bulkCourseModal.addEventListener('click', (e) => {
                    if (e.target === bulkCourseModal) closeCourseModal();
                });
            }

            // 6. Control del Modal de Departamentos
            const openDeptoModal = () => {
                deptoCountText.textContent = selectedIds.length;
                bulkDeptoModal.classList.add('is-open');
            };

            const closeDeptoModal = () => {
                bulkDeptoModal.classList.remove('is-open');
                bulkDeptoForm.reset();
            };

            if(btnBulkDepto) btnBulkDepto.addEventListener('click', openDeptoModal);
            if(btnCloseDeptoModal) btnCloseDeptoModal.addEventListener('click', closeDeptoModal);
            if(bulkDeptoModal) {
                bulkDeptoModal.addEventListener('click', (e) => {
                    if (e.target === bulkDeptoModal) closeDeptoModal();
                });
            }

            // 7. Envío masivo de Cursos por AJAX
            if(bulkCourseForm) {
                bulkCourseForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    if (selectedIds.length === 0) return;

                    const originalText = btnSubmitCourseModal.innerHTML;
                    btnSubmitCourseModal.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Asignando...';
                    btnSubmitCourseModal.disabled = true;

                    const formData = new FormData(this);
                    const payload = {
                        personal_ids: selectedIds,
                        curso_id: formData.get('curso_id'),
                        fecha_realizacion: formData.get('fecha_realizacion') || null,
                        apto: formData.get('apto'),
                        descripcion_aptitud: formData.get('descripcion_aptitud')
                    };

                    try {
                        const response = await fetch(`{{ route('personal.cursos.bulk') }}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (response.ok) {
                            window.location.reload(); 
                        } else {
                            const errorData = await response.json();
                            alert('Error: ' + (errorData.message || 'No se pudo asignar a todos.'));
                            btnSubmitCourseModal.innerHTML = originalText;
                            btnSubmitCourseModal.disabled = false;
                        }
                    } catch (error) {
                        alert('Error de conexión con el servidor.');
                        btnSubmitCourseModal.innerHTML = originalText;
                        btnSubmitCourseModal.disabled = false;
                    }
                });
            }

            // 8. Envío masivo de Departamentos por AJAX
            if(bulkDeptoForm) {
                bulkDeptoForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    if (selectedIds.length === 0) return;

                    const originalText = btnSubmitDeptoModal.innerHTML;
                    btnSubmitDeptoModal.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
                    btnSubmitDeptoModal.disabled = true;

                    const selectElement = document.getElementById('bulk_departamento');
                    const selectedDeptos = Array.from(selectElement.selectedOptions).map(opt => opt.value);

                    const payload = {
                        personal_ids: selectedIds,
                        departamento: selectedDeptos
                    };

                    try {
                        const response = await fetch(`{{ route('personal.departamento.bulk') }}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (response.ok) {
                            window.location.reload(); 
                        } else {
                            const errorData = await response.json();
                            alert('Error: ' + (errorData.message || 'No se pudo actualizar los departamentos.'));
                            btnSubmitDeptoModal.innerHTML = originalText;
                            btnSubmitDeptoModal.disabled = false;
                        }
                    } catch (error) {
                        alert('Error de conexión con el servidor.');
                        btnSubmitDeptoModal.innerHTML = originalText;
                        btnSubmitDeptoModal.disabled = false;
                    }
                });
            }

            // 9. Envío masivo para Exportar a CSV
            if(btnBulkExport) {
                btnBulkExport.addEventListener('click', function() {
                    if (selectedIds.length === 0) return;

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `{{ route('personal.export.bulk') }}`;
                    
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    form.appendChild(csrfInput);

                    selectedIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'personal_ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                });
            }

            // Control Modal Seleccionados
            const openSelectedModal = () => {
                selectedWorkersList.innerHTML = '';
                
                if (selectedIds.length === 0) {
                    selectedWorkersList.innerHTML = '<span style="color: var(--personal-muted); text-align: center; padding: 20px;">No hay trabajadores seleccionados.</span>';
                } else {
                    // Buscamos los nombres de los trabajadores seleccionados recorriendo las filas visibles de la tabla
                    selectedIds.forEach(id => {
                        const row = document.querySelector(`tr[data-id="${id}"]`);
                        const name = row ? row.querySelector('.personal-person-name strong').textContent : `ID: ${id}`;
                        
                        const item = document.createElement('div');
                        item.style.cssText = "display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--personal-line); font-size: 0.88rem; font-weight: 700; color: var(--personal-ink);";
                        item.innerHTML = `
                            <span>${name}</span>
                            <button type="button" data-remove-id="${id}" style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-weight: 800;" title="Quitar de la selección">
                                <i class="fas fa-xmark"></i> Quitar
                            </button>
                        `;
                        selectedWorkersList.appendChild(item);
                    });
                }
                bulkSelectedModal.classList.add('is-open');
            };

            const closeSelectedModal = () => {
                bulkSelectedModal.classList.remove('is-open');
            };

            if(btnViewSelected) btnViewSelected.addEventListener('click', openSelectedModal);
            if(btnCloseSelectedModal) btnCloseSelectedModal.addEventListener('click', closeSelectedModal);
            if(bulkSelectedModal) {
                bulkSelectedModal.addEventListener('click', (e) => {
                    if (e.target === bulkSelectedModal) closeSelectedModal();
                });
            }

            // Eliminar un trabajador individual desde dentro del modal
            selectedWorkersList.addEventListener('click', function(e) {
                const removeBtn = e.target.closest('[data-remove-id]');
                if (!removeBtn) return;

                const idToRemove = removeBtn.getAttribute('data-remove-id');
                
                // Quitamos el ID de la memoria global
                selectedIds = selectedIds.filter(id => id !== idToRemove);

                // Desmarcamos su checkbox en la tabla si está visible
                const checkboxInTable = document.querySelector(`.checkbox-row[value="${idToRemove}"]`);
                if (checkboxInTable) checkboxInTable.checked = false;

                // Actualizamos la interfaz de la barra y redibujamos la lista del modal
                updateBulkUI();
                openSelectedModal(); 
            });

            // Botón Limpiar Todo
            if(btnClearAllSelected) {
                btnClearAllSelected.addEventListener('click', function() {
                    selectedIds = [];
                    document.querySelectorAll('.checkbox-row').forEach(cb => cb.checked = false);
                    updateBulkUI();
                    closeSelectedModal();
                });
            }
        })();
    </script>
@endsection