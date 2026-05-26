@extends('adminlte::page')

@section('title', 'Crear Proyecto')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/proyecto-edit.css'])
@endsection

@section('content_header')
    <div class="proyecto-edit-header-shell">
        <div class="proyecto-edit-header">
            <div>
                <h1 class="m-0">Crear Proyecto</h1>
                <p class="proyecto-edit-subtitle">Añade un nuevo proyecto con la misma estructura visual del módulo.</p>
            </div>
            <div class="proyecto-edit-header-actions">
                <a href="{{ route('herramientas.proyectos.index') }}" class="btn-back-proyecto-edit">
                    <i class="fas fa-arrow-left"></i>
                    Volver al listado
                </a>
                <a href="{{ route('herramientas.proyectos.index') }}" class="btn-back-index-edit">
                    <i class="fas fa-diagram-project"></i>
                    Gestión Proyectos
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="proyecto-edit-shell">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Revisa los datos:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <form action="{{ route('herramientas.proyectos.store') }}" method="POST" class="proyecto-edit-form" enctype="multipart/form-data">
            @csrf

            <div class="proyecto-edit-grid">
                <section class="proyecto-edit-card proyecto-edit-card--form">
                    <h3>Datos del proyecto</h3>

                    <div class="form-group">
                        <label for="nombre">Nombre del proyecto</label>
                        <input
                            type="text"
                            name="nombre"
                            id="nombre"
                            class="form-control @error('nombre') is-invalid @enderror"
                            value="{{ old('nombre') }}"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="localizacion">Localización</label>
                        <input
                            type="text"
                            name="localizacion"
                            id="localizacion"
                            class="form-control @error('localizacion') is-invalid @enderror"
                            value="{{ old('localizacion') }}"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="form-group mb-0">
                        <label for="imagen">Imagen (opcional)</label>
                        <input
                            type="file"
                            name="imagen"
                            id="imagen"
                            class="form-control @error('imagen') is-invalid @enderror"
                            accept="image/png,image/jpeg,image/webp,image/gif"
                        >
                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, WEBP o GIF. Máx: 4 MB.</small>
                    </div>
                </section>

                <section class="proyecto-edit-card proyecto-edit-card--image">
                    <h3>Vista previa</h3>

                    <div class="proyecto-edit-no-image">
                        <i class="fas fa-image"></i>
                        <p>Sube una imagen para personalizar la portada del proyecto.</p>
                    </div>
                </section>
            </div>

            <div class="proyecto-edit-actions">
                <a href="{{ route('herramientas.proyectos.index') }}" class="btn-cancel-edit">Cancelar</a>
                <button type="submit" class="btn-save-edit">
                    <i class="fas fa-floppy-disk"></i>
                    Guardar proyecto
                </button>
            </div>
        </form>
    </div>
@endsection
