@extends('adminlte::page')

@section('title', 'Editar Clase - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/clases-create.css'])
@endsection

@section('content')
    <section class="clases-create-page">
        <section class="clases-create-head">
            <h1>Editar Clase</h1>
            <p>Actualiza el nombre de la categoría del inventario.</p>
        </section>

        <div class="clases-create-shell">
            <form class="clases-create-card" action="{{ route('clases.update', $clase->id) }}" method="POST" novalidate>
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div style="margin-bottom: 1.2rem; padding: 0.85rem; border-radius: 0.6rem; background: #fee2e2; border: 1px solid #fca5a5; color: #dc2626;">
                        <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                        <strong>Errores encontrados:</strong>
                        <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="clases-form-grid">
                    <div class="field-group">
                        <label for="nombre">Nombre de la Clase</label>
                        <div class="input-wrap">
                            <i class="fas fa-tag"></i>
                            <input
                                id="nombre"
                                name="nombre"
                                type="text"
                                value="{{ old('nombre', $clase->nombre) }}"
                                placeholder="Ej: EPI, Herramientas, Consumibles"
                                required
                            >
                        </div>
                        @error('nombre')
                            <small style="color: #dc2626; font-size: 0.8rem;">{{ $message }}</small>
                        @enderror
                    </div>

                    <small style="color: #72849f; font-size: 0.85rem; display: block;">
                        <i class="fas fa-info-circle"></i>
                        Items asociados a esta clase: <strong>{{ $clase->inventarios_count ?? 0 }}</strong>
                    </small>
                </div>

                <footer class="clases-create-actions">
                    <a href="{{ route('clases.index') }}" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-create">
                        <i class="fas fa-save"></i>
                        Actualizar Clase
                    </button>
                </footer>
            </form>
        </div>
    </section>
@endsection
