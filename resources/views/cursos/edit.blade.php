@extends('adminlte::page')

@section('title', 'Editar Curso')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .cursos-form-page { padding: 18px; }
        .cursos-form-card { max-width: 860px; margin: 0 auto; background: #fff; border: 1px solid #e7ecf3; border-radius: 18px; box-shadow: 0 16px 30px rgba(15, 23, 42, .06); padding: 24px; }
        .cursos-form-grid { display: grid; gap: 18px; }
        .cursos-field label { display:block; font-size:.76rem; font-weight:800; text-transform:uppercase; letter-spacing:.12em; color:#8a98ab; margin-bottom:8px; }
        .cursos-field input, .cursos-field textarea, .cursos-field select { width:100%; border:1px solid #dbe3ef; border-radius:12px; padding:12px 14px; }
        .cursos-actions { display:flex; justify-content:flex-end; gap:10px; margin-top: 20px; }
        .cursos-btn { border:0; border-radius:12px; padding:12px 18px; font-weight:800; }
        .cursos-btn--primary { background:#173e67; color:#fff; }
        .cursos-btn--soft { background:#eef3f8; color:#173e67; text-decoration:none; }
    </style>
@endsection

@section('content')
    <section class="cursos-form-page">
        <form class="cursos-form-card" action="{{ route('cursos.update', $curso->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="cursos-form-grid">
                <div class="cursos-field">
                    <label for="categoria">Categoría</label>
                    <input list="categorias-list" 
                           name="categoria" 
                           id="categoria" 
                           value="{{ old('categoria', $curso->categoria) }}" 
                           placeholder="Selecciona o escribe una nueva..." 
                           autocomplete="off" 
                           required>
                           
                    <datalist id="categorias-list">
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria }}">
                        @endforeach
                    </datalist>
                    @error('categoria') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="cursos-field">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $curso->nombre) }}" placeholder="Ej. PRL Básico, PEMP, Alturas">
                    @error('nombre') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="cursos-field">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="5" placeholder="Detalla el contenido, validez o requisitos del curso">{{ old('descripcion', $curso->descripcion) }}</textarea>
                    @error('descripcion') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="cursos-form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px;">
                    <div class="cursos-field">
                        <label for="meses_validez">Meses de validez</label>
                        <input id="meses_validez" name="meses_validez" type="number" min="1" max="120" value="{{ old('meses_validez', $curso->meses_validez) }}" placeholder="Opcional">
                        @error('meses_validez') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="cursos-field">
                        <label for="dias_aviso_previo">Días de aviso previo</label>
                        <input id="dias_aviso_previo" name="dias_aviso_previo" type="number" min="1" max="365" value="{{ old('dias_aviso_previo', $curso->dias_aviso_previo ?? 30) }}">
                        @error('dias_aviso_previo') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <div class="cursos-actions">
                <a href="{{ route('cursos.index') }}" class="cursos-btn cursos-btn--soft">Cancelar</a>
                <button type="submit" class="cursos-btn cursos-btn--primary">Guardar Cambios</button>
            </div>
        </form>
    </section>
@endsection