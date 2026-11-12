@extends('adminlte::page')

@section('title', 'Gestión de almacenes - MoncobraCRM')

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
        /* Modal Fallback & Styling */
        .erp-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 1050; display: none; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(2px); }
        .erp-modal-backdrop.is-open { display: flex; }
        .erp-modal-dialog { background: #fff; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.15); border: 1px solid #e2e8f0; overflow: hidden; animation: modalIn 0.18s ease-out; }
        @keyframes modalIn { from { opacity: 0; transform: translateY(-10px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .erp-modal-header { padding: 1.15rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .erp-modal-title { font-size: 1.15rem; font-weight: 800; color: #0f2747; margin: 0; }
        .erp-modal-close { background: transparent; border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer; line-height: 1; padding: 0.25rem; }
        .erp-modal-close:hover { color: #334155; }
        .erp-modal-body { padding: 1.5rem; }
        .erp-modal-footer { padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 0.75rem; }
        .erp-label { display: block; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 0.4rem; letter-spacing: 0.04em; }
        .erp-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0.6rem 0.85rem; font-weight: 600; color: #0f172a; font-size: 0.9rem; }
        .erp-input:focus { outline: none; border-color: #173e67; box-shadow: 0 0 0 3px rgba(23, 62, 103, 0.12); }
    </style>
@endsection

@section('content')
<section class="container-fluid erp-page">
    <div class="erp-header">
        <div>
            <a href="{{ route('inventario.index') }}" class="btn-erp-back mb-2">
                <i class="fas fa-arrow-left"></i> Volver al Inventario
            </a>
            <h1 class="erp-title mt-1">Gestión de Almacenes</h1>
            <p class="erp-subtitle">Administración de ubicaciones físicas, códigos logísticos y existencias asociadas.</p>
        </div>
        <button type="button" class="btn-erp-primary" id="btnOpenCrearAlmacen">
            <i class="fas fa-plus"></i> Nuevo Almacén
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="border-radius: 10px; font-weight: 700;">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" style="border-radius: 10px; font-weight: 700;">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
        </div>
    @endif

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table erp-table mb-0">
                <thead>
                    <tr>
                        <th>Nombre / Código</th>
                        <th>Descripción</th>
                        <th>Referencias con Stock</th>
                        <th>Unidades Totales</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($almacenes as $almacen)
                    <tr>
                        <td>
                            <strong style="color: #0f2747; font-weight: 800;">{{ $almacen->nombre }}</strong>
                            <small class="d-block text-muted font-weight-bold">{{ $almacen->codigo ?: 'ALM-' . $almacen->id }}</small>
                        </td>
                        <td>{{ $almacen->descripcion ?: '—' }}</td>
                        <td>
                            <span class="badge badge-light" style="font-size: 0.85rem; font-weight: 800; padding: 0.35rem 0.65rem; border: 1px solid #cbd5e1;">
                                {{ $almacen->stocks_count ?? 0 }}
                            </span>
                        </td>
                        <td>
                            <strong style="font-weight: 800; color: #173e67;">{{ number_format((int) ($almacen->unidades ?? 0), 0, ',', '.') }} uds.</strong>
                        </td>
                        <td>
                            @if($almacen->activo)
                                <span class="badge badge-success" style="padding: 0.35rem 0.6rem;">Activo</span>
                            @else
                                <span class="badge badge-secondary" style="padding: 0.35rem 0.6rem;">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="d-inline-flex align-items-center" style="gap: 0.4rem;">
                                <button type="button"
                                    class="action-icon-btn js-open-edit-almacen"
                                    title="Editar almacén"
                                    data-almacen-id="{{ $almacen->id }}"
                                    data-almacen-nombre="{{ $almacen->nombre }}"
                                    data-almacen-codigo="{{ $almacen->codigo }}"
                                    data-almacen-descripcion="{{ $almacen->descripcion }}"
                                    data-almacen-activo="{{ $almacen->activo ? '1' : '0' }}">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form action="{{ route('almacenes.destroy', $almacen) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Seguro que deseas eliminar este almacén?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-icon-btn action-icon-btn--danger" title="Eliminar almacén">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted font-weight-bold">No hay almacenes registrados todavía.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $almacenes->links() }}
    </div>
</section>

{{-- MODAL CREAR ALMACÉN --}}
<div class="erp-modal-backdrop" id="modalCrearAlmacen" aria-hidden="true">
    <div class="erp-modal-dialog" role="dialog" aria-modal="true">
        <form method="POST" action="{{ route('almacenes.store') }}">
            @csrf
            <div class="erp-modal-header">
                <h5 class="erp-modal-title"><i class="fas fa-warehouse mr-2 text-primary"></i> Crear Nuevo Almacén</h5>
                <button type="button" class="erp-modal-close" data-close-modal="modalCrearAlmacen">&times;</button>
            </div>
            <div class="erp-modal-body">
                <div class="mb-3">
                    <label class="erp-label" for="crear-almacen-nombre">Nombre del Almacén <span class="text-danger">*</span></label>
                    <input id="crear-almacen-nombre" name="nombre" type="text" class="erp-input" placeholder="Ej. Almacén Central / Taller" required>
                </div>
                <div class="mb-3">
                    <label class="erp-label" for="crear-almacen-codigo">Código Corto (Opcional)</label>
                    <input id="crear-almacen-codigo" name="codigo" type="text" class="erp-input" maxlength="50" placeholder="Ej. ALM-CEN">
                </div>
                <div class="mb-3">
                    <label class="erp-label" for="crear-almacen-descripcion">Descripción / Ubicación</label>
                    <textarea id="crear-almacen-descripcion" name="descripcion" class="erp-input" rows="2" placeholder="Detalles de la zona logística..."></textarea>
                </div>
                <div class="d-flex align-items-center" style="gap: 0.5rem;">
                    <input id="crear-almacen-activo" type="checkbox" name="activo" value="1" checked style="width: 18px; height: 18px;">
                    <label for="crear-almacen-activo" class="mb-0 font-weight-bold" style="color: #1e293b; cursor: pointer;">Almacén activo y disponible para movimientos</label>
                </div>
            </div>
            <div class="erp-modal-footer">
                <button type="button" class="btn-erp-back" data-close-modal="modalCrearAlmacen">Cancelar</button>
                <button type="submit" class="btn-erp-primary"><i class="fas fa-save"></i> Guardar Almacén</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDITAR ALMACÉN --}}
<div class="erp-modal-backdrop" id="modalEditarAlmacen" aria-hidden="true">
    <div class="erp-modal-dialog" role="dialog" aria-modal="true">
        <form id="formEditarAlmacen" method="POST">
            @csrf
            @method('PUT')
            <div class="erp-modal-header">
                <h5 class="erp-modal-title"><i class="fas fa-pen mr-2 text-primary"></i> Editar Almacén</h5>
                <button type="button" class="erp-modal-close" data-close-modal="modalEditarAlmacen">&times;</button>
            </div>
            <div class="erp-modal-body">
                <div class="mb-3">
                    <label class="erp-label" for="editar-almacen-nombre">Nombre del Almacén <span class="text-danger">*</span></label>
                    <input id="editar-almacen-nombre" name="nombre" type="text" class="erp-input" required>
                </div>
                <div class="mb-3">
                    <label class="erp-label" for="editar-almacen-codigo">Código Corto</label>
                    <input id="editar-almacen-codigo" name="codigo" type="text" class="erp-input" maxlength="50">
                </div>
                <div class="mb-3">
                    <label class="erp-label" for="editar-almacen-descripcion">Descripción / Ubicación</label>
                    <textarea id="editar-almacen-descripcion" name="descripcion" class="erp-input" rows="2"></textarea>
                </div>
                <div class="d-flex align-items-center" style="gap: 0.5rem;">
                    <input id="editar-almacen-activo" type="checkbox" name="activo" value="1" style="width: 18px; height: 18px;">
                    <label for="editar-almacen-activo" class="mb-0 font-weight-bold" style="color: #1e293b; cursor: pointer;">Almacén activo</label>
                </div>
            </div>
            <div class="erp-modal-footer">
                <button type="button" class="btn-erp-back" data-close-modal="modalEditarAlmacen">Cancelar</button>
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

    document.getElementById('btnOpenCrearAlmacen')?.addEventListener('click', () => {
        openModal('modalCrearAlmacen');
    });

    document.querySelectorAll('.js-open-edit-almacen').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('formEditarAlmacen').action = `/almacenes/${button.dataset.almacenId}`;
            document.getElementById('editar-almacen-nombre').value = button.dataset.almacenNombre || '';
            document.getElementById('editar-almacen-codigo').value = button.dataset.almacenCodigo || '';
            document.getElementById('editar-almacen-descripcion').value = button.dataset.almacenDescripcion || '';
            document.getElementById('editar-almacen-activo').checked = button.dataset.almacenActivo === '1';
            openModal('modalEditarAlmacen');
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