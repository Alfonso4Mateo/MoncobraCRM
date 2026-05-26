@extends('adminlte::page')

@section('title', 'Detalle Proyecto')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/proyecto-show.css'])
@endsection

@section('content_header')
    <div class="proyecto-show-header">
        <div>
            <h1 class="m-0">Detalle del Proyecto</h1>
            <p class="proyecto-show-subtitle">Información completa del proyecto seleccionado.</p>
        </div>
        <div class="proyecto-show-actions">
            <a href="{{ route('herramientas.proyectos.index') }}" class="btn-back-proyectos">
                <i class="fas fa-arrow-left"></i>
                Volver a Gestión Proyectos
            </a>
            <a href="{{ route('herramientas.proyectos.edit', $proyecto) }}" class="btn-editar-proyecto">
                <i class="fas fa-pen"></i>
                Editar proyecto
            </a>
        </div>
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

    @php
        $imagen = $proyecto->imagen;
        $esUrlCompleta = is_string($imagen) && (str_starts_with($imagen, 'http://') || str_starts_with($imagen, 'https://'));
        $esRutaStoragePublica = is_string($imagen) && str_starts_with($imagen, 'storage/');
        $imagenUrl = $imagen
            ? ($esUrlCompleta ? $imagen : ($esRutaStoragePublica ? asset($imagen) : asset('storage/' . ltrim($imagen, '/'))))
            : null;
    @endphp

    <div class="proyecto-show-grid">
        <section class="proyecto-main-card">
            <div class="proyecto-main-card__media">
                @if($imagenUrl)
                    <img src="{{ $imagenUrl }}" alt="Imagen de {{ $proyecto->nombre }}" loading="lazy">
                @else
                    <div class="proyecto-main-card__placeholder">
                        <i class="fas fa-image"></i>
                        <span>Sin imagen asociada</span>
                    </div>
                @endif
            </div>

            <div class="proyecto-main-card__body">
                <h2>{{ $proyecto->nombre }}</h2>
                <p class="proyecto-main-card__location">
                    <i class="fas fa-location-dot"></i>
                    {{ $proyecto->localizacion }}
                </p>

                <div class="proyecto-badges">
                    <span class="badge-item">
                        <i class="fas fa-users"></i>
                        {{ $proyecto->usuarios_count }} {{ $proyecto->usuarios_count === 1 ? 'usuario asociado' : 'usuarios asociados' }}
                    </span>
                    <span class="badge-item">
                        <i class="fas fa-hashtag"></i>
                        ID {{ $proyecto->id }}
                    </span>
                </div>

                <div class="proyecto-info-grid">
                    <div class="info-item">
                        <span class="info-label">Fecha de creación</span>
                        <span class="info-value">{{ optional($proyecto->created_at)->format('d/m/Y H:i') ?: '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Última actualización</span>
                        <span class="info-value">{{ optional($proyecto->updated_at)->format('d/m/Y H:i') ?: '—' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="proyecto-users-card">
            <div class="proyecto-users-card__header">
                <h3>Usuarios del Proyecto</h3>
                <span>{{ $proyecto->usuarios_count }}</span>
            </div>

            @if($proyecto->usuarios->isEmpty())
                <div class="empty-users">
                    <i class="fas fa-user-slash"></i>
                    <p>Este proyecto no tiene usuarios asignados.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table proyecto-users-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($proyecto->usuarios as $usuario)
                                <tr>
                                    <td>{{ $usuario->name }}</td>
                                    <td>{{ $usuario->email }}</td>
                                    <td>
                                        @if($usuario->role === 'superadmin')
                                            <span class="role-badge role-superadmin">Super Admin</span>
                                        @elseif($usuario->role === 'admin')
                                            <span class="role-badge role-admin">Admin</span>
                                        @else
                                            <span class="role-badge role-user">Usuario</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($usuario->activo)
                                            <span class="status-badge status-active">Activo</span>
                                        @else
                                            <span class="status-badge status-inactive">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
