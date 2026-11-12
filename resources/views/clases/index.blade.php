@extends('adminlte::page')

@section('title', 'Gestión de Clases - MoncobraCRM')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .erp-page { font-family: 'Manrope', sans-serif; padding: 1.25rem 0.5rem; }
        .erp-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .erp-title { font-size: 1.65rem; font-weight: 800; color: #0f2747; margin: 0; }
        .erp-subtitle { color: #64748b; font-size: 0.9rem; margin: 0.2rem 0 0 0; }
        .btn-erp-back { display: inline-flex; align-items: center; gap: 0.5rem; background: #fff; border: 1px solid #cbd5e1; color: #334155; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 1rem; border-radius: 10px; text-decoration: none !important; transition: all 0.15s; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
        .btn-erp-back:hover { background: #f8fafc; border-color: #94a3b8; color: #0f172a; }
        .btn-erp-primary { display: inline-flex; align-items: center; gap: 0.5rem; background: #173e67; border: 1px solid #173e67; color: #fff; font-weight: 800; font-size: 0.88rem; padding: 0.6rem 1.2rem; border-radius: 10px; cursor: pointer; transition: all 0.15s; }
        .btn-erp-primary:hover { background: #0f2747; color: #fff; }
        .erp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 12px rgba(15,23,42,0.03); overflow: hidden; }
        .erp-table th { background: #f8fafc; color: #64748b; font-size: 0.73rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; padding: 0.9rem 1.25rem; border-bottom: 1px solid #e2e8f0; }
        .erp-table td { padding: 1rem 1.25rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 0.9rem; }
        .action-icon-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #475569; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
        .action-icon-btn:hover { background: #f1f5f9; color: #0f172a; }
        .action-icon-btn--danger { border-color: #fecaca; background: #fff5f5; color: #dc2626; }
        .action-icon-btn--danger:hover { background: #fee2e2; color: #b91c1c; }
        .erp-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 1050; display: none; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(2px); }
        .erp-modal-backdrop.is-open { display: flex; }
        .erp-modal-dialog { background: #fff; border-radius: 16px; width: 100%; max-width: 440px; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.15); border: 1px solid #e2e8f0; overflow: hidden; }
        .erp-modal-header { padding: 1.15rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .erp-modal-title { font-size: 1.15rem; font-weight: 800; color: #0f2747; margin: 0; }
        .erp-modal-close { background: transparent; border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer; line-height: 1; padding: 0.25rem; }
        .erp-modal-body { padding: 1.5rem; }
        .erp-modal-footer { padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 0.75rem; }
        .erp-label { display: block; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 0.4rem; letter-spacing: 0.04em; }
        .erp-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0.6rem 0.85rem; font-weight: 600; color: #0f172a; font-size: 0.9rem; }
    </style>
@endsection

@section('content')
<section class="container-fluid erp-page">
    <div class="erp-header">
        <div>
            <a href="{{ route('inventario.index') }}" class="btn-erp-back mb-2">
                <i class="fas fa-arrow-left"></i> Volver al Inventario
            </a>
            <h1 class="erp-title mt-1">Gestión de Clases</h1>
            <p class="erp-subtitle">Administración de categorías para clasificar los artículos del inventario.</p>
        </div>
        <button type="button" class="btn-erp-primary" id="btnOpenCrearClase">
            <i class="fas fa-plus"></i> Nueva Clase
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="border-radius: 10px; font-weight: 700;">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" style="border-radius: 10px; font-weight: 700;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table erp-table mb-0">
                <thead>
                    <tr>
                        <th>Nombre de Clase</th>
                        <th>Ítems Asociados</th>
                        <th>Fecha de Creación</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($clases as $clase)
                    <tr>
                        <td><strong style="color: #0f2747; font-weight: 800;">{{ $clase->nombre }}</strong></td>
                        <td>
                            <span class="badge badge-info" style="font-size: 0.82rem; font-weight: 800; padding: 0.35rem 0.65rem;">
                                {{ $clase->inventarios_count ?? $clase->articulos_count ?? 0 }}
                            </span>
                        </td>
                        <td>{{ optional($clase->created_at)->format('d/m/Y H:i') ?: '—' }}</td>
                        <td class="text-right">
                            <div class="d-inline-flex align-items-center" style="gap: 0.4rem;">
                                <button type="button"
                                    class="action-icon-btn js-open-edit-clase"
                                    title="Editar clase"
                                    data-clase-id="{{ $clase->id }}"
                                    data-clase-nombre="{{ $clase->nombre }}">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form action="{{ route('clases.destroy', $clase) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Eliminar esta clase?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-icon-btn action-icon-btn--danger" title="Eliminar clase">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted font-weight-bold">No hay clases registradas.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- MODAL CREAR CLASE --}}
<div class="erp-modal-backdrop" id="modalCrearClase" aria-hidden="true">
    <div class="erp-modal-dialog" role="dialog" aria-modal="true">
        <form method="POST" action="{{ route('clases.store') }}">
            @csrf
            <div class="erp-modal-header">
                <h5 class="erp-modal-title"><i class="fas fa-tags mr-2 text-primary"></i> Crear Nueva Clase</h5>
                <button type="button" class="erp-modal-close" data-close-modal="modalCrearClase">&times;</button>
            </div>
            <div class="erp-modal-body">
                <label class="erp-label" for="crear-clase-nombre">Nombre de la Clase <span class="text-danger">*</span></label>
                <input id="crear-clase-nombre" name="nombre" type="text" class="erp-input" placeholder="Ej: EPI, Herramientas, Consumibles..." required>
            </div>
            <div class="erp-modal-footer">
                <button type="button" class="btn-erp-back" data-close-modal="modalCrearClase">Cancelar</button>
                <button type="submit" class="btn-erp-primary"><i class="fas fa-save"></i> Crear Clase</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDITAR CLASE --}}
<div class="erp-modal-backdrop" id="modalEditarClase" aria-hidden="true">
    <div class="erp-modal-dialog" role="dialog" aria-modal="true">
        <form id="formEditarClase" method="POST">
            @csrf
            @method('PUT')
            <div class="erp-modal-header">
                <h5 class="erp-modal-title"><i class="fas fa-pen mr-2 text-primary"></i> Editar Clase</h5>
                <button type="button" class="erp-modal-close" data-close-modal="modalEditarClase">&times;</button>
            </div>
            <div class="erp-modal-body">
                <label class="erp-label" for="editar-clase-nombre">Nombre de la Clase <span class="text-danger">*</span></label>
                <input id="editar-clase-nombre" name="nombre" type="text" class="erp-input" required>
            </div>
            <div class="erp-modal-footer">
                <button type="button" class="btn-erp-back" data-close-modal="modalEditarClase">Cancelar</button>
                <button type="submit" class="btn-erp-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    const openModal = (id) => {
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('is-open');
            el.setAttribute('aria-hidden', 'false');
        }
    };

    const closeModal = (id) => {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('is-open');
            el.setAttribute('aria-hidden', 'true');
        }
    };

    document.getElementById('btnOpenCrearClase')?.addEventListener('click', () => {
        openModal('modalCrearClase');
    });

    document.querySelectorAll('.js-open-edit-clase').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('formEditarClase').action = `/clases/${button.dataset.claseId}`;
            document.getElementById('editar-clase-nombre').value = button.dataset.claseNombre || '';
            openModal('modalEditarClase');
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach((btn) => {
        btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close-modal')));
    });

    document.querySelectorAll('.erp-modal-backdrop').forEach((backdrop) => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) closeModal(backdrop.id);
        });
    });
})();
</script>
@endsection