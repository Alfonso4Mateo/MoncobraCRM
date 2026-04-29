@extends('adminlte::page')

@section('title', 'Ajustar correlativo - Presupuestos')

@section('content')
    <section class="container mt-4">
        <h1>Ajustar número correlativo de presupuestos</h1>

        <div class="card mt-3">
            <div class="card-body">
                <p>Máximo correlativo actual: <strong>{{ $max ?? '0' }}</strong></p>
                @if($override)
                    <p>Override actual fijado por administrador: <strong>{{ $override }}</strong></p>
                @endif
                <form action="{{ route('presupuestos.correlativo.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="next">Siguiente número correlativo</label>
                        <input type="number" id="next" name="next" class="form-control" min="1" value="{{ old('next', $suggested) }}" required>
                        @error('next')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('presupuestos.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Fijar</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
