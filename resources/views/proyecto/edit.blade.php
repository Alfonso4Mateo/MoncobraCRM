@extends('adminlte::page')

@section('title', 'Editar Proyecto')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/proyecto-edit.css'])
@endsection

@section('content_header')
    <div class="proyecto-edit-header-shell">
        <div class="proyecto-edit-header">
            <div>
                <h1 class="m-0">Editar Proyecto</h1>
                <p class="proyecto-edit-subtitle">Ajusta la información del proyecto y gestiona su imagen de portada.</p>
            </div>
            <div class="proyecto-edit-header-actions">
                <a href="{{ route('herramientas.proyectos.show', $proyecto) }}" class="btn-back-proyecto-edit">
                    <i class="fas fa-arrow-left"></i>
                    Volver al detalle
                </a>
                <a href="{{ route('herramientas.proyectos.index') }}" class="btn-back-index-edit">
                    <i class="fas fa-list"></i>
                    Ir al listado
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $imagen = $proyecto->imagen;
        $esUrlCompleta = is_string($imagen) && (str_starts_with($imagen, 'http://') || str_starts_with($imagen, 'https://'));
        $esRutaStoragePublica = is_string($imagen) && str_starts_with($imagen, 'storage/');
        $imagenUrl = $imagen
            ? ($esUrlCompleta ? $imagen : ($esRutaStoragePublica ? asset($imagen) : asset('storage/' . ltrim($imagen, '/'))))
            : null;
    @endphp

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

    <div class="proyecto-edit-shell">
        <form action="{{ route('herramientas.proyectos.update', $proyecto) }}" method="POST" class="proyecto-edit-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

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
                            value="{{ old('nombre', $proyecto->nombre) }}"
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
                            value="{{ old('localizacion', $proyecto->localizacion) }}"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="form-group mb-0">
                        <label for="imagen">Reemplazar imagen (opcional)</label>
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
                    <h3>Imagen actual</h3>

                    @if($imagenUrl)
                        <img src="{{ $imagenUrl }}" alt="Imagen actual de {{ $proyecto->nombre }}" class="proyecto-edit-image-preview" loading="lazy">

                        <div class="form-check proyecto-edit-remove-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="eliminar_imagen"
                                name="eliminar_imagen"
                                value="1"
                                {{ old('eliminar_imagen') ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="eliminar_imagen">Eliminar imagen actual</label>
                        </div>
                    @else
                        <div class="proyecto-edit-no-image">
                            <i class="fas fa-image"></i>
                            <p>Este proyecto no tiene imagen por ahora.</p>
                        </div>
                    @endif
                </section>
            </div>

            <div class="proyecto-edit-actions">
                <a href="{{ route('herramientas.proyectos.show', $proyecto) }}" class="btn-cancel-edit">Cancelar</a>
                <button type="submit" class="btn-save-edit">
                    <i class="fas fa-floppy-disk"></i>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
@endsection
