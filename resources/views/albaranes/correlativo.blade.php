@extends('adminlte::page')

@section('title', 'Ajustar correlativo - Albaranes')

@section('content')
    <section class="container mt-4">
        <h1>Ajustar número correlativo de albaranes</h1>

        <div class="card mt-3">
            <div class="card-body">
                <p>Formato actual: <strong>{{ $formatoActual }}</strong></p>
                <p>Máximo correlativo usado con este formato: <strong>{{ $max ?? '0' }}</strong></p>
                @if($override)
                    <p>Override actual fijado por administrador: <strong>{{ $override }}</strong></p>
                @endif
                <form action="{{ route('albaranes.correlativo.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="formato">Formato base</label>
                        <input type="text" id="formato" name="formato" class="form-control" value="{{ old('formato', $formatoActual) }}" placeholder="ALB-2026-000" required>
                        <small class="form-text text-muted">Debe terminar en ceros (ej. <strong>ALB-2026-000</strong> o <strong>ALB-2026-0000</strong> según el formato configurado).</small>
                        @error('formato')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group mt-3">
                        <label for="next">Siguiente número correlativo</label>
                        <input type="number" id="next" name="next" class="form-control" min="1" value="{{ old('next', $suggested) }}" required>
                        <small class="form-text text-muted">Vista previa con configuración actual: <strong>{{ $ejemplo }}</strong>.</small>
                        @error('next')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('albaranes.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Fijar</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
