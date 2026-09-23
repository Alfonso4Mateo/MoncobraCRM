@extends('adminlte::page')

@section('title', 'Catálogo de EPIs')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Reutilizamos tu sistema de diseño base */
        .erp-hero { background: #fff; border: 1px solid #e7ecf3; border-radius: 18px; box-shadow: 0 16px 30px rgba(15, 23, 42, .06); display: flex; justify-content: space-between; gap: 16px; padding: 20px 22px; margin-bottom: 20px; align-items: center; }
        .erp-hero h1 { margin: 0 0 4px; font-size: 1.7rem; font-weight: 800; color: #173e67; }
        .erp-hero p { margin: 0; color: #667085; font-size: .92rem; }
        .erp-primary-btn { display: inline-flex; align-items: center; gap: 10px; padding: 10px 16px; border-radius: 12px; background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; text-decoration: none; font-weight: 800; border: none; cursor: pointer; transition: opacity 0.2s; }
        .erp-primary-btn:hover { opacity: 0.9; color: #fff; }
        .erp-primary-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        /* Barra de búsqueda / filtros */
        .epi-toolbar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
        .epi-toolbar input[type="search"],
        .epi-toolbar select { border: 1px solid #dbe3ef; border-radius: 12px; padding: 9px 12px; font-size: 0.9rem; }
        .epi-toolbar input[type="search"] { flex: 1; min-width: 220px; }
        .filter-toggle-inactive { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 700; color: #64748b; cursor: pointer; user-select: none; margin: 0; }

        /* Agrupación por categoría */
        .epi-category-group { margin-bottom: 28px; }
        .epi-category-title {
            font-size: 0.85rem; font-weight: 800; color: #173e67; text-transform: uppercase;
            letter-spacing: 0.06em; margin-bottom: 12px; padding-bottom: 8px;
            border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; gap: 8px;
        }
        .epi-category-count { background: #eef2f7; color: #667085; font-size: 0.7rem; padding: 2px 8px; border-radius: 999px; font-weight: 800; text-transform: none; letter-spacing: 0; }

        /* Grid de EPIs */
        .epi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .epi-card { background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; padding: 18px; transition: all 0.2s ease; display: flex; flex-direction: column; }
        .epi-card:hover { border-color: #0284c7; box-shadow: 0 8px 15px rgba(15, 23, 42, 0.05); transform: translateY(-2px); }
        .epi-card__title { font-size: 1.15rem; font-weight: 800; color: #173e67; margin: 0 0 8px 0; }
        .epi-card__desc { font-size: 0.85rem; color: #64748b; margin-bottom: 16px; flex-grow: 1; }
        .epi-card__footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px dashed #eef2f7; }
        .epi-badge { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; font-size: 0.75rem; font-weight: 800; padding: 4px 10px; border-radius: 999px; }

        /* Barra de acciones de la tarjeta */
        .epi-card__actions { display: flex; gap: 6px; padding-top: 10px; margin-top: 10px; border-top: 1px dashed #eef2f7; }
        .epi-action-btn {
            flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 7px 8px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc;
            font-size: 0.75rem; font-weight: 700; color: #475569; cursor: pointer; transition: all 0.15s ease;
        }
        .epi-action-btn:hover { background: #f1f5f9; }
        .epi-action-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .epi-action-btn--edit:hover { color: #0369a1; border-color: #7dd3fc; background: #f0f9ff; }
        .epi-action-btn--baja:hover { color: #b45309; border-color: #fcd34d; background: #fffbeb; }
        .epi-action-btn--activar:hover { color: #166534; border-color: #bbf7d0; background: #f0fdf4; }
        .epi-action-btn--delete:hover { color: #b91c1c; border-color: #fca5a5; background: #fef2f2; }

        .epi-empty-state { grid-column: 1 / -1; padding: 40px; text-align: center; border: 2px dashed #cbd5e1; border-radius: 16px; color: #64748b; }

        /* Modal Estilos (compartido: crear, editar, confirmación) */
        .erp-modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 1060; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; backdrop-filter: blur(2px); }
        .erp-modal-overlay.is-open { display: flex; opacity: 1; }
        .erp-modal-panel { background: #fff; border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transform: translateY(20px); transition: transform 0.2s; padding: 24px; }
        .erp-modal-overlay.is-open .erp-modal-panel { transform: translateY(0); }

        /* Formulario Puestos List */
        .puesto-assign-list { border: 1px solid #e2e8f0; border-radius: 10px; max-height: 250px; overflow-y: auto; background: #f8fafc; padding: 10px; margin-top: 10px; }
        .puesto-assign-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #e2e8f0; background: #fff; border-radius: 8px; margin-bottom: 6px; }
        .puesto-assign-item:last-child { margin-bottom: 0; }
        .qty-input { width: 70px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; }
        .qty-input:disabled { background: #e2e8f0; opacity: 0.6; cursor: not-allowed; }

        /* Toasts */
        #toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 2000; display: flex; flex-direction: column; gap: 10px; max-width: 360px; }
        .erp-toast {
            background: #fff; border-left: 4px solid #10b981; border-radius: 10px;
            padding: 12px 16px; box-shadow: 0 10px 25px rgba(15,23,42,0.12);
            font-size: 0.85rem; font-weight: 700; color: #1e293b;
            display: flex; align-items: center; gap: 8px;
            opacity: 0; transform: translateY(10px); transition: all 0.25s ease;
        }
        .erp-toast.is-visible { opacity: 1; transform: translateY(0); }
        .erp-toast--success { border-left-color: #10b981; }
        .erp-toast--success i { color: #10b981; }
        .erp-toast--error { border-left-color: #dc2626; }
        .erp-toast--error i { color: #dc2626; }
    </style>
@endsection

@section('content')
    <section style="font-family: 'Manrope', sans-serif;">
        <!-- Mensajes de feedback (fallback sin JS) -->
        @if(session('success'))
            <div class="alert alert-success" style="border-radius: 12px; font-weight: 600;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger" style="border-radius: 12px; font-weight: 600;">{{ session('error') }}</div>
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

        <!-- Cabecera -->
        <header class="erp-hero">
            <div>
                <div style="font-size:.75rem;font-weight:800;letter-spacing:.12em;color:#8a98ab;text-transform:uppercase;">Prevención y Seguridad</div>
                <h1>Catálogo de EPIs</h1>
                <p>Gestiona los Equipos de Protección Individual y vincúlalos a los perfiles de trabajo.</p>
            </div>
            <div>
                <button type="button" class="erp-primary-btn" id="btn-open-modal">
                    <i class="fas fa-plus"></i> Crear Nuevo EPI
                </button>
            </div>
        </header>

        <!-- Buscador y filtros -->
        <div class="epi-toolbar">
            <input type="search" id="epi-search" placeholder="Buscar EPI por nombre..." autocomplete="off">

            <select id="epi-filter-categoria">
                <option value="all">Todas las categorías</option>
                @foreach($categoriasDisponibles as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>

            <label class="filter-toggle-inactive">
                <input type="checkbox" id="epi-toggle-inactive" checked>
                Mostrar inactivos
            </label>
        </div>

        <!-- Catálogo agrupado por categoría -->
        <div id="epis-catalog">
            @forelse($episPorCategoria as $categoriaNombre => $episGrupo)
                <section class="epi-category-group" data-category="{{ $categoriaNombre }}">
                    <div class="epi-category-title">
                        {{ $categoriaNombre }}
                        <span class="epi-category-count">{{ $episGrupo->count() }}</span>
                    </div>
                    <div class="epi-grid">
                        @foreach($episGrupo as $epi)
                            @include('epis.card', ['epi' => $epi])
                        @endforeach
                    </div>
                </section>
            @empty
            @endforelse
        </div>

        <!-- Estado vacío: no hay ningún EPI en el sistema -->
        <div id="epis-empty-global" class="epi-empty-state" style="{{ $episPorCategoria->isEmpty() ? '' : 'display:none;' }}">
            <i class="fas fa-hard-hat fa-3x mb-3 text-muted"></i>
            <h4>No hay EPIs registrados</h4>
            <p>Utiliza el botón superior para crear tu primer Equipo de Protección Individual.</p>
        </div>

        <!-- Estado vacío: hay EPIs pero ninguno coincide con el filtro/búsqueda -->
        <div id="epis-empty-search" class="epi-empty-state" style="display:none;">
            <i class="fas fa-search fa-2x mb-2 text-muted"></i>
            <h4>Sin resultados</h4>
            <p>No hay EPIs que coincidan con la búsqueda o los filtros aplicados.</p>
        </div>
    </section>

    <!-- Datalist compartido de categorías (crear + editar) -->
    <datalist id="categorias-datalist">
        @foreach($categoriasDisponibles as $cat)
            <option value="{{ $cat }}"></option>
        @endforeach
    </datalist>

    <!-- Modal para Crear EPI -->
    <div class="erp-modal-overlay" id="modal-create-epi">
        <div class="erp-modal-panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <h3 style="margin: 0; font-weight: 800; color: #173e67;">Crear Nuevo Ítem EPI</h3>
                <button type="button" id="btn-close-modal" style="background: none; border: none; font-size: 1.2rem; color: #64748b; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="form-create-epi" action="{{ route('epis.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Nombre del EPI <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" style="border-radius: 8px;" placeholder="Ej. Casco de seguridad Clase A" required value="{{ old('nombre') }}">
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Categoría</label>
                    <input type="text" name="categoria" list="categorias-datalist" class="form-control" style="border-radius: 8px;" placeholder="Ej. Protección Craneal" value="{{ old('categoria') }}">
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Descripción / Normativa</label>
                    <textarea name="descripcion" class="form-control" style="border-radius: 8px;" rows="2" placeholder="Detalles técnicos o normativa aplicable...">{{ old('descripcion') }}</textarea>
                </div>

                <!-- Lista de vinculación rápida a Puestos -->
                <div class="form-group" style="margin-top: 24px;">
                    <label style="font-weight: 700; color: #1e293b; margin-bottom: 4px;">Vinculación Inicial a Perfiles de Trabajo</label>
                    <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 0;">Selecciona a qué puestos aplica este EPI y especifica la cantidad necesaria por trabajador.</p>

                    <div class="puesto-assign-list">
                        @foreach($puestos as $puesto)
                            <div class="puesto-assign-item">
                                <label style="margin: 0; display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; color: #334155;">
                                    <input type="checkbox" name="puestos[]" value="{{ $puesto->id }}" class="puesto-checkbox" data-target="qty-{{ $puesto->id }}">
                                    {{ $puesto->nombre }}
                                </label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">UDS:</span>
                                    <input type="number" name="cantidades[{{ $puesto->id }}]" id="qty-{{ $puesto->id }}" class="qty-input" value="1" min="1" disabled>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-group text-right" style="margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    <button type="button" class="btn btn-light mr-2" id="btn-cancel-modal" style="border-radius: 8px; font-weight: 600;">Cancelar</button>
                    <button type="submit" class="erp-primary-btn">
                        <i class="fas fa-save"></i> Guardar Ítem
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Editar EPI (compartido, se rellena por JS al pulsar "Editar" en una tarjeta) -->
    <div class="erp-modal-overlay" id="modal-edit-epi">
        <div class="erp-modal-panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <h3 style="margin: 0; font-weight: 800; color: #173e67;">Editar Ítem EPI</h3>
                <button type="button" id="btn-close-edit-modal" style="background: none; border: none; font-size: 1.2rem; color: #64748b; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="form-edit-epi" action="" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Nombre del EPI <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="edit-epi-nombre" class="form-control" style="border-radius: 8px;" required>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Categoría</label>
                    <input type="text" name="categoria" id="edit-epi-categoria" list="categorias-datalist" class="form-control" style="border-radius: 8px;" placeholder="Ej. Protección Craneal">
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #1e293b;">Descripción / Normativa</label>
                    <textarea name="descripcion" id="edit-epi-descripcion" class="form-control" style="border-radius: 8px;" rows="2"></textarea>
                </div>

                <div class="form-group" style="margin-top: 24px;">
                    <label style="font-weight: 700; color: #1e293b; margin-bottom: 4px;">Vinculación a Perfiles de Trabajo</label>
                    <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 0;">Ajusta a qué puestos aplica este EPI y la cantidad necesaria por trabajador.</p>

                    <div class="puesto-assign-list">
                        @foreach($puestos as $puesto)
                            <div class="puesto-assign-item">
                                <label style="margin: 0; display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; color: #334155;">
                                    <input type="checkbox" name="puestos[]" value="{{ $puesto->id }}" class="puesto-checkbox-edit" data-target="edit-qty-{{ $puesto->id }}">
                                    {{ $puesto->nombre }}
                                </label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">UDS:</span>
                                    <input type="number" name="cantidades[{{ $puesto->id }}]" id="edit-qty-{{ $puesto->id }}" class="qty-input" value="1" min="1" disabled>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-group text-right" style="margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                    <button type="button" class="btn btn-light mr-2" id="btn-cancel-edit-modal" style="border-radius: 8px; font-weight: 600;">Cancelar</button>
                    <button type="submit" class="erp-primary-btn">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación genérico (reemplaza al confirm() nativo del navegador) -->
    <div class="erp-modal-overlay" id="confirm-modal">
        <div class="erp-modal-panel" style="max-width: 420px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: #fef2f2; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i>
                </div>
                <h3 style="margin: 0; font-weight: 800; color: #173e67; font-size: 1.05rem;">Confirmar acción</h3>
            </div>
            <p id="confirm-modal-message" style="color: #475569; margin-bottom: 24px;"></p>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" id="confirm-modal-no" class="btn btn-light" style="border-radius: 8px; font-weight: 600;">Cancelar</button>
                <button type="button" id="confirm-modal-yes" class="btn" style="background: #dc2626; color: #fff; border-radius: 8px; font-weight: 700;">Sí, eliminar</button>
            </div>
        </div>
    </div>

    <!-- Contenedor de notificaciones toast -->
    <div id="toast-container"></div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const baseEpisUrl = "{{ url('epis') }}";

            const catalog = document.getElementById('epis-catalog');
            const emptyGlobal = document.getElementById('epis-empty-global');
            const emptySearch = document.getElementById('epis-empty-search');

            // ---------------------------------------------------------
            // Toasts
            // ---------------------------------------------------------
            function showToast(message, type = 'success') {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = `erp-toast erp-toast--${type}`;
                toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i><span>${message}</span>`;
                container.appendChild(toast);
                requestAnimationFrame(() => toast.classList.add('is-visible'));
                setTimeout(() => {
                    toast.classList.remove('is-visible');
                    setTimeout(() => toast.remove(), 300);
                }, 3500);
            }

            // ---------------------------------------------------------
            // Modal de confirmación genérico (basado en Promesas)
            // ---------------------------------------------------------
            function confirmAction(message) {
                return new Promise((resolve) => {
                    const modal = document.getElementById('confirm-modal');
                    document.getElementById('confirm-modal-message').textContent = message;
                    modal.classList.add('is-open');

                    const btnYes = document.getElementById('confirm-modal-yes');
                    const btnNo = document.getElementById('confirm-modal-no');

                    const cleanup = (result) => {
                        modal.classList.remove('is-open');
                        btnYes.removeEventListener('click', onYes);
                        btnNo.removeEventListener('click', onNo);
                        modal.removeEventListener('click', onOverlay);
                        resolve(result);
                    };
                    const onYes = () => cleanup(true);
                    const onNo = () => cleanup(false);
                    const onOverlay = (e) => { if (e.target === modal) cleanup(false); };

                    btnYes.addEventListener('click', onYes);
                    btnNo.addEventListener('click', onNo);
                    modal.addEventListener('click', onOverlay);
                });
            }

            // ---------------------------------------------------------
            // Helpers de DOM: insertar/reemplazar/eliminar tarjetas y
            // mantener las secciones de categoría coherentes.
            // ---------------------------------------------------------
            function getOrCreateCategoryGroup(categoriaNombre) {
                let group = catalog.querySelector(`.epi-category-group[data-category="${CSS.escape(categoriaNombre)}"]`);
                if (group) return group;

                group = document.createElement('section');
                group.className = 'epi-category-group';
                group.dataset.category = categoriaNombre;
                group.innerHTML = `
                    <div class="epi-category-title">${categoriaNombre} <span class="epi-category-count"></span></div>
                    <div class="epi-grid"></div>
                `;
                catalog.appendChild(group);
                return group;
            }

            function updateCategoryCount(group) {
                if (!document.body.contains(group)) return;
                const count = group.querySelectorAll('.epi-card').length;
                if (count === 0) {
                    group.remove();
                    return;
                }
                const counter = group.querySelector('.epi-category-count');
                if (counter) counter.textContent = count;
            }

            function insertOrReplaceCard(html, categoriaNombre) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const newCard = wrapper.firstElementChild;
                const epiId = newCard.dataset.epiId;

                // Si ya existía (edición o cambio de estado), la quitamos primero,
                // porque puede haber cambiado de categoría.
                const existing = document.getElementById(`epi-card-${epiId}`);
                const oldGroup = existing ? existing.closest('.epi-category-group') : null;
                if (existing) existing.remove();

                const targetGroup = getOrCreateCategoryGroup(categoriaNombre);
                targetGroup.querySelector('.epi-grid').appendChild(newCard);
                updateCategoryCount(targetGroup);

                if (oldGroup && oldGroup !== targetGroup) updateCategoryCount(oldGroup);

                checkEmptyStates();
                applyFilters();
                return newCard;
            }

            function removeCard(epiId) {
                const card = document.getElementById(`epi-card-${epiId}`);
                if (!card) return;
                const group = card.closest('.epi-category-group');
                card.remove();
                if (group) updateCategoryCount(group);
                checkEmptyStates();
                applyFilters();
            }

            function checkEmptyStates() {
                const totalCards = catalog.querySelectorAll('.epi-card').length;
                emptyGlobal.style.display = totalCards === 0 ? '' : 'none';
            }

            // ---------------------------------------------------------
            // Búsqueda + filtro de categoría + mostrar/ocultar inactivos
            // ---------------------------------------------------------
            const searchInput = document.getElementById('epi-search');
            const categoriaSelect = document.getElementById('epi-filter-categoria');
            const toggleInactive = document.getElementById('epi-toggle-inactive');
            const storageKey = 'epiCatalogoMostrarInactivos';

            const savedState = localStorage.getItem(storageKey);
            toggleInactive.checked = savedState !== null ? savedState === 'true' : true;

            function applyFilters() {
                const term = searchInput.value.trim().toLowerCase();
                const categoriaFiltro = categoriaSelect.value;
                const showInactive = toggleInactive.checked;
                let anyVisible = false;

                catalog.querySelectorAll('.epi-category-group').forEach(group => {
                    let groupHasVisible = false;

                    group.querySelectorAll('.epi-card').forEach(card => {
                        const nombre = (card.dataset.epiNombre || '').toLowerCase();
                        const categoria = card.dataset.epiCategoria || 'Sin categoría';
                        const activo = card.dataset.epiActivo === '1';

                        const matchesSearch = term === '' || nombre.includes(term);
                        const matchesCategoria = categoriaFiltro === 'all' || categoria === categoriaFiltro;
                        const matchesActivo = showInactive || activo;

                        const visible = matchesSearch && matchesCategoria && matchesActivo;
                        card.style.display = visible ? '' : 'none';
                        if (visible) { groupHasVisible = true; anyVisible = true; }
                    });

                    group.style.display = groupHasVisible ? '' : 'none';
                });

                const totalCards = catalog.querySelectorAll('.epi-card').length;
                emptySearch.style.display = (totalCards > 0 && !anyVisible) ? '' : 'none';
            }

            searchInput.addEventListener('input', applyFilters);
            categoriaSelect.addEventListener('change', applyFilters);
            toggleInactive.addEventListener('change', function () {
                localStorage.setItem(storageKey, this.checked);
                applyFilters();
            });

            checkEmptyStates();
            applyFilters();

            // ---------------------------------------------------------
            // Modal genérico open/close
            // ---------------------------------------------------------
            const toggleModal = (modal, show) => {
                if (show === undefined) modal.classList.toggle('is-open');
                else modal.classList.toggle('is-open', show);
            };

            // ---------------------------------------------------------
            // Modal CREAR
            // ---------------------------------------------------------
            const modalCreate = document.getElementById('modal-create-epi');
            const btnOpen = document.getElementById('btn-open-modal');
            const btnClose = document.getElementById('btn-close-modal');
            const btnCancel = document.getElementById('btn-cancel-modal');
            const formCreate = document.getElementById('form-create-epi');

            btnOpen.addEventListener('click', () => toggleModal(modalCreate, true));
            btnClose.addEventListener('click', () => toggleModal(modalCreate, false));
            btnCancel.addEventListener('click', () => toggleModal(modalCreate, false));
            modalCreate.addEventListener('click', (e) => { if (e.target === modalCreate) toggleModal(modalCreate, false); });

            function bindQtyToggle(checkboxSelector) {
                document.querySelectorAll(checkboxSelector).forEach(chk => {
                    chk.addEventListener('change', function () {
                        const inputQty = document.getElementById(this.dataset.target);
                        inputQty.disabled = !this.checked;
                        if (!this.checked) inputQty.value = 1; else inputQty.focus();
                    });
                });
            }
            bindQtyToggle('.puesto-checkbox');
            bindQtyToggle('.puesto-checkbox-edit');

            @if($errors->any())
                toggleModal(modalCreate, true);
            @endif

            formCreate.addEventListener('submit', async function (e) {
                e.preventDefault();
                const btnSubmit = formCreate.querySelector('button[type="submit"]');
                btnSubmit.disabled = true;

                try {
                    const response = await fetch(formCreate.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: new FormData(formCreate),
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Error al crear el EPI.');
                        throw new Error(firstError);
                    }

                    insertOrReplaceCard(data.html, data.categoria);
                    showToast(data.message, 'success');
                    toggleModal(modalCreate, false);
                    formCreate.reset();
                    document.querySelectorAll('.puesto-checkbox').forEach(chk => {
                        document.getElementById(chk.dataset.target).disabled = true;
                    });
                } catch (error) {
                    showToast(error.message, 'error');
                } finally {
                    btnSubmit.disabled = false;
                }
            });

            // ---------------------------------------------------------
            // Modal EDITAR
            // ---------------------------------------------------------
            const modalEdit = document.getElementById('modal-edit-epi');
            const btnCloseEdit = document.getElementById('btn-close-edit-modal');
            const btnCancelEdit = document.getElementById('btn-cancel-edit-modal');
            const formEdit = document.getElementById('form-edit-epi');
            const inputEditNombre = document.getElementById('edit-epi-nombre');
            const inputEditDescripcion = document.getElementById('edit-epi-descripcion');
            const inputEditCategoria = document.getElementById('edit-epi-categoria');

            btnCloseEdit.addEventListener('click', () => toggleModal(modalEdit, false));
            btnCancelEdit.addEventListener('click', () => toggleModal(modalEdit, false));
            modalEdit.addEventListener('click', (e) => { if (e.target === modalEdit) toggleModal(modalEdit, false); });

            function openEditModal(card) {
                const epiId = card.dataset.epiId;
                const puestosAsignados = JSON.parse(card.dataset.epiPuestos || '{}');

                formEdit.action = `${baseEpisUrl}/${epiId}`;
                inputEditNombre.value = card.dataset.epiNombre;
                inputEditDescripcion.value = card.dataset.epiDescripcion;
                inputEditCategoria.value = card.dataset.epiCategoria;

                document.querySelectorAll('.puesto-checkbox-edit').forEach(chk => {
                    chk.checked = false;
                    const inputQty = document.getElementById(chk.dataset.target);
                    inputQty.disabled = true;
                    inputQty.value = 1;
                });

                Object.keys(puestosAsignados).forEach(puestoId => {
                    const chk = document.querySelector(`.puesto-checkbox-edit[value="${puestoId}"]`);
                    const inputQty = document.getElementById(`edit-qty-${puestoId}`);
                    if (chk && inputQty) {
                        chk.checked = true;
                        inputQty.disabled = false;
                        inputQty.value = puestosAsignados[puestoId];
                    }
                });

                toggleModal(modalEdit, true);
            }

            formEdit.addEventListener('submit', async function (e) {
                e.preventDefault();
                const btnSubmit = formEdit.querySelector('button[type="submit"]');
                btnSubmit.disabled = true;

                try {
                    // Enviamos como POST con _method=PUT (spoofing) para evitar el
                    // conocido problema de PHP con PUT + multipart/form-data.
                    const response = await fetch(formEdit.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: new FormData(formEdit),
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Error al actualizar el EPI.');
                        throw new Error(firstError);
                    }

                    insertOrReplaceCard(data.html, data.categoria);
                    showToast(data.message, 'success');
                    toggleModal(modalEdit, false);
                } catch (error) {
                    showToast(error.message, 'error');
                } finally {
                    btnSubmit.disabled = false;
                }
            });

            // ---------------------------------------------------------
            // Delegación de eventos sobre las tarjetas: editar / baja / eliminar
            // (delegado en el contenedor porque las tarjetas se insertan
            // dinámicamente y no tendrían listeners propios)
            // ---------------------------------------------------------
            catalog.addEventListener('click', async function (e) {
                const btnEdit = e.target.closest('.js-btn-edit-epi');
                if (btnEdit) {
                    openEditModal(btnEdit.closest('.epi-card'));
                    return;
                }

                const btnToggle = e.target.closest('.js-btn-toggle-epi');
                if (btnToggle) {
                    const card = btnToggle.closest('.epi-card');
                    const epiId = card.dataset.epiId;
                    btnToggle.disabled = true;

                    try {
                        const response = await fetch(`${baseEpisUrl}/${epiId}/toggle-activo`, {
                            method: 'PATCH',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Error al cambiar el estado del EPI.');

                        insertOrReplaceCard(data.html, data.categoria);
                        showToast(data.message, 'success');
                    } catch (error) {
                        showToast(error.message, 'error');
                        btnToggle.disabled = false;
                    }
                    return;
                }

                const btnDelete = e.target.closest('.js-btn-delete-epi');
                if (btnDelete) {
                    const card = btnDelete.closest('.epi-card');
                    const epiId = card.dataset.epiId;
                    const nombre = card.dataset.epiNombre;

                    const confirmado = await confirmAction(`¿Seguro que quieres eliminar "${nombre}"? Esta acción no se puede deshacer.`);
                    if (!confirmado) return;

                    btnDelete.disabled = true;

                    try {
                        const response = await fetch(`${baseEpisUrl}/${epiId}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Error al eliminar el EPI.');

                        removeCard(epiId);
                        showToast(data.message, 'success');
                    } catch (error) {
                        showToast(error.message, 'error');
                        btnDelete.disabled = false;
                    }
                }
            });
        });
    </script>
@endsection