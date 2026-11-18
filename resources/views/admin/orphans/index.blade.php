@extends('adminlte::page')

@section('title', 'Centro de Saneamiento')

@section('content')
<div class="container-fluid py-3">
    @if(session('success') || session('warning'))
        <div class="alert alert-{{ session('warning') ? 'warning' : 'success' }}">{{ session('success') ?? session('warning') }}</div>
    @endif

    <div class="card text-white mb-4" style="background:#173e67;">
        <div class="card-body">
            <h1 class="h3 mb-2">Centro de Saneamiento de Datos Huérfanos</h1>
            <p class="mb-2">Detecta registros sin sede asignada o con referencias a proyectos eliminados.</p>
            <strong class="h4">{{ $totalHuerfanos }} registros sin sede detectados</strong>
        </div>
    </div>

    <div class="row">
        @forelse($resumenModulos->filter(fn ($count) => $count > 0) as $modulo => $count)
            <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                <a class="card h-100 text-decoration-none {{ $moduloFiltro === $modulo ? 'border-primary shadow' : '' }}"
                   href="{{ route('admin.orphans.index', ['modulo' => $modulo, 'q' => $query]) }}">
                    <div class="card-body">
                        <div class="text-muted small">{{ $catalogoModelos[$modulo]['area'] ?? 'Sistema' }}</div>
                        <h5 class="text-dark mb-1">{{ $modulo }}</h5>
                        <span class="badge badge-danger">{{ $count }} huérfano(s)</span>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12 mb-3"><div class="alert alert-success">Sistema íntegro: 0 huérfanos</div></div>
        @endforelse
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.orphans.index') }}" class="form-row align-items-end">
                <div class="col-md-3 mb-2">
                    <label for="modulo">Módulo</label>
                    <select class="form-control" id="modulo" name="modulo">
                        <option value="all">Todos</option>
                        @foreach($catalogoModelos as $modulo => $definition)
                            @if(($resumenModulos[$modulo] ?? 0) > 0)
                                <option value="{{ $modulo }}" @selected($moduloFiltro === $modulo)>{{ $modulo }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 mb-2">
                    <label for="q">Buscar</label>
                    <input class="form-control" id="q" name="q" value="{{ $query }}" placeholder="Referencia, módulo o ID">
                </div>
                <div class="col-md-2 mb-2"><button class="btn btn-primary" type="submit">Filtrar</button></div>
            </form>
        </div>

        <form method="POST" id="bulk-form">
            @csrf
            <div class="card-body border-bottom d-flex flex-wrap align-items-end" style="gap:12px;">
                <div>
                    <label for="proyecto_id">Sede / Proyecto destino</label>
                    <select class="form-control" id="proyecto_id" name="proyecto_id" form="assign-form" required>
                        @foreach($proyectos as $proyecto)
                            <option value="{{ $proyecto->id }}" @selected((int) $proyectoActivoId === (int) $proyecto->id)>{{ $proyecto->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-success" type="submit" form="assign-form">Asignar seleccionados a esta sede</button>
                <button class="btn btn-danger" type="submit" form="destroy-form" onclick="return confirm('¿Eliminar los registros seleccionados? Esta acción no se puede deshacer.')">Eliminar seleccionados</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.orphans.assign') }}" id="assign-form">@csrf</form>
        <form method="POST" action="{{ route('admin.orphans.destroy') }}" id="destroy-form">
            @csrf
            @method('DELETE')
        </form>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th><input type="checkbox" id="check-all"></th><th>Módulo</th><th>ID</th><th>Referencia / Descripción</th><th>Motivo</th><th>Fecha creación</th><th></th></tr></thead>
                <tbody>
                    @forelse($registros as $item)
                        <tr>
                            <td><input class="item-check" type="checkbox" name="items[]" value="{{ $item->model_key }}:{{ $item->id }}" form="assign-form"></td>
                            <td><span class="badge badge-info">{{ $item->model_key }}</span></td>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->reference }}</td>
                            <td>{{ $item->reason }}</td>
                            <td>{{ $item->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>
                                <button type="submit" class="btn btn-sm btn-outline-success" form="assign-form" name="items[]" value="{{ $item->model_key }}:{{ $item->id }}">Rescatar</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4">No hay registros huérfanos para este filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $registros->links() }}</div>
    </div>
</div>
@endsection

@section('js')
<script>
document.getElementById('check-all')?.addEventListener('change', function () {
    document.querySelectorAll('.item-check').forEach((checkbox) => checkbox.checked = this.checked);
});
document.querySelectorAll('#assign-form, #destroy-form').forEach((form) => form.addEventListener('submit', function () {
    document.querySelectorAll('.item-check:checked').forEach((checkbox) => {
        if (checkbox.form !== this) {
            const copy = checkbox.cloneNode(true);
        copy.setAttribute('form', this.id);
            this.appendChild(copy);
        }
    });
}));
</script>
@endsection
