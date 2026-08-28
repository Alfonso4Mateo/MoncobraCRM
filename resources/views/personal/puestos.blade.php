@extends('adminlte::page')

@section('title', 'Puestos de Trabajo')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .pt-btn { display: inline-flex; align-items: center; border: 0; border-radius: 10px; padding: 8px 16px; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; text-decoration: none; cursor: pointer; }
        .pt-btn--primary { background: #173e67; color: #fff; }
        .pt-btn--primary:hover { background: #0f2a47; color: #fff; }
        .pt-btn--icon { padding: 8px 10px; }

        .pt-create-container { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 20px; margin-bottom: 25px; }

        .pt-table-wrap { background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; overflow: hidden; }
        .pt-table { width: 100%; border-collapse: collapse; }
        .pt-table th { text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: .06em; color: #8a98ab; padding: 14px 18px; background: #f8fafc; border-bottom: 1px solid #e7ecf3; }
        .pt-table td { padding: 14px 18px; border-bottom: 1px solid #eef2f6; vertical-align: middle; }
        .pt-table tr:last-child td { border-bottom: none; }
        .pt-nombre { font-weight: 800; color: #173e67; }
        .pt-badge { display: inline-flex; align-items: center; gap: 6px; background: #eef3f8; color: #173e67; border-radius: 999px; padding: 4px 12px; font-size: 0.8rem; font-weight: 700; }
        .pt-badge--muted { background: #f1f5f9; color: #64748b; }

        /* Modal simple de edición */
        .pt-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 1050; align-items: center; justify-content: center; }
        .pt-modal-overlay.is-open { display: flex; }
        .pt-modal { background: #fff; border-radius: 14px; padding: 24px; width: 100%; max-width: 420px; }
        .pt-modal h4 { font-weight: 800; color: #173e67; margin-bottom: 16px; }
        .pt-modal .form-group label { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #8a98ab; }
    </style>
@endsection

@section('content')
    <section class="p-3">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 style="font-weight: 800; color: #173e67;">Puestos de Trabajo</h2>
                <p class="text-muted mb-0">Define los puestos y cada cuántos meses corresponde el reconocimiento médico. Esa periodicidad se usará para calcular automáticamente la próxima revisión de cada trabajador.</p>
            </div>

            <a href="{{ route('personal.index') }}" class="btn btn-outline-secondary" style="border-radius: 10px; font-weight: 700;">
                <i class="fas fa-arrow-left mr-2"></i> Volver a Personal
            </a>
        </div>

        <!-- Formulario rápido para crear un puesto de trabajo nuevo -->
        <div class="pt-create-container">
            <form action="{{ route('personal.puestos-trabajo.store') }}" method="POST" class="form-row align-items-end">
                @csrf
                <div class="col-md-6 mb-2">
                    <label class="mb-1" style="font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#8a98ab;">Nombre del puesto</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: SOLDADOR/A, ADMINISTRATIVO/A..." required style="border-radius: 10px;">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="mb-1" style="font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#8a98ab;">Periodicidad (meses)</label>
                    <input type="number" name="periodicidad_meses" min="1" max="120" class="form-control" placeholder="Ej: 12" style="border-radius: 10px;">
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="pt-btn pt-btn--primary" style="width:100%; justify-content:center;">
                        <i class="fas fa-plus mr-2"></i> Crear puesto
                    </button>
                </div>
            </form>
        </div>

        <!-- Listado de puestos de trabajo -->
        <div class="pt-table-wrap">
            <table class="pt-table">
                <thead>
                    <tr>
                        <th>Puesto</th>
                        <th>Periodicidad revisión médica</th>
                        <th>Trabajadores asignados</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($puestosTrabajo as $puesto)
                        <tr>
                            <td class="pt-nombre">{{ $puesto->nombre }}</td>
                            <td>
                                @if($puesto->periodicidad_meses)
                                    <span class="pt-badge"><i class="fas fa-notes-medical"></i> Cada {{ $puesto->periodicidad_meses }} {{ Str::plural('mes', $puesto->periodicidad_meses) }}</span>
                                @else
                                    <span class="pt-badge pt-badge--muted">Sin periodicidad definida</span>
                                @endif
                            </td>
                            <td>{{ $puesto->personal_count }}</td>
                            <td style="text-align:right;">
                                <button type="button"
                                    class="pt-btn pt-btn--icon"
                                    style="background:#eef3f8; color:#173e67;"
                                    title="Editar"
                                    onclick="abrirEdicionPuesto({{ $puesto->id }}, '{{ addslashes($puesto->nombre) }}', {{ $puesto->periodicidad_meses ?? 'null' }})">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form action="{{ route('personal.puestos-trabajo.destroy', $puesto->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que quieres eliminar este puesto de trabajo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pt-btn pt-btn--icon" style="background:#fee2e2; color:#ef4444;" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No hay puestos de trabajo registrados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Modal de edición -->
    <div class="pt-modal-overlay" id="pt-edit-overlay">
        <div class="pt-modal">
            <h4><i class="fas fa-pen mr-2"></i> Editar puesto de trabajo</h4>
            <form id="pt-edit-form" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="pt-edit-nombre">Nombre del puesto</label>
                    <input type="text" id="pt-edit-nombre" name="nombre" class="form-control" required style="border-radius:10px;">
                </div>
                <div class="form-group">
                    <label for="pt-edit-meses">Periodicidad (meses)</label>
                    <input type="number" id="pt-edit-meses" name="periodicidad_meses" min="1" max="120" class="form-control" style="border-radius:10px;">
                </div>
                <div class="d-flex justify-content-end" style="gap:10px; margin-top:16px;">
                    <button type="button" class="pt-btn" style="background:#f1f5f9; color:#334155;" onclick="cerrarEdicionPuesto()">Cancelar</button>
                    <button type="submit" class="pt-btn pt-btn--primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function abrirEdicionPuesto(id, nombre, meses) {
            const overlay = document.getElementById('pt-edit-overlay');
            const form = document.getElementById('pt-edit-form');
            form.action = `{{ url('personal/puestos-trabajo') }}/${id}`;
            document.getElementById('pt-edit-nombre').value = nombre;
            document.getElementById('pt-edit-meses').value = meses ?? '';
            overlay.classList.add('is-open');
        }

        function cerrarEdicionPuesto() {
            document.getElementById('pt-edit-overlay').classList.remove('is-open');
        }

        document.getElementById('pt-edit-overlay').addEventListener('click', function (e) {
            if (e.target === this) cerrarEdicionPuesto();
        });
    </script>
@endsection
