@extends('adminlte::page')

@section('title', 'Gestión Proyectos')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/gestion-proyectos.css'])
@endsection

@section('content_header')
    <div class="proyectos-header">
        <div>
            <h1 class="m-0">Gestión Proyectos</h1>
            <p class="proyectos-subtitle">Visualiza y selecciona el proyecto activo desde una vista rápida por tarjetas.</p>
        </div>
        <a href="{{ route('herramientas.proyectos.create') }}" class="btn-create-project">
            <i class="fas fa-plus"></i>
            Crear nuevo proyecto
        </a>
    </div>
@endsection

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <strong>Éxito:</strong> {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($message = Session::get('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Error:</strong> {{ $message }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if($proyectos->isEmpty())
        <div class="proyectos-empty">
            <i class="fas fa-diagram-project"></i>
            <h3>No hay proyectos creados</h3>
            <p>Cuando existan proyectos en base de datos aparecerán aquí en formato tarjeta.</p>
        </div>
    @else
        <div class="proyectos-grid">
            @foreach($proyectos as $proyecto)
                @php
                    $imagen = $proyecto->imagen;
                    $esUrlCompleta = is_string($imagen) && (str_starts_with($imagen, 'http://') || str_starts_with($imagen, 'https://'));
                    $esRutaStoragePublica = is_string($imagen) && str_starts_with($imagen, 'storage/');
                    $imagenUrl = $imagen
                        ? ($esUrlCompleta ? $imagen : ($esRutaStoragePublica ? asset($imagen) : asset('storage/' . ltrim($imagen, '/'))))
                        : null;
                @endphp

                <a href="{{ route('herramientas.proyectos.show', $proyecto) }}" class="proyecto-card" title="Ver detalle del proyecto {{ $proyecto->nombre }}">
                    <div class="proyecto-card__media">
                        @if($imagenUrl)
                            <img src="{{ $imagenUrl }}" alt="Imagen de {{ $proyecto->nombre }}" loading="lazy">
                        @else
                            <div class="proyecto-card__placeholder">
                                <i class="fas fa-image"></i>
                                <span>Sin imagen</span>
                            </div>
                        @endif
                    </div>

                    <div class="proyecto-card__body">
                        <h3>{{ $proyecto->nombre }}</h3>
                        <div class="proyecto-card__meta">
                            <span class="usuarios-count">
                                <i class="fas fa-users"></i>
                                {{ $proyecto->usuarios_count }} {{ $proyecto->usuarios_count === 1 ? 'usuario' : 'usuarios' }}
                            </span>
                            <span class="interactivo-indicator" aria-hidden="true">
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
