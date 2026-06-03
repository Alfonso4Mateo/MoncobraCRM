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
                        {{ $proyecto->usuarios_count ?? $proyecto->usuarios->count() }} usuarios asociados
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
            <div class="proyecto-users-card__header" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h3 style="margin: 0;">Usuarios del Proyecto</h3>
                    <span class="badge badge-info" style="font-size: 1rem;">{{ $proyecto->usuarios_count ?? $proyecto->usuarios->count() }}</span>
                </div>
                
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAddUser">
                    <i class="fas fa-user-plus"></i> Añadir Usuario
                </button>
            </div>

            @if($proyecto->usuarios->isEmpty())
                <div class="empty-users" style="padding: 2rem; text-align: center; color: #6c757d;">
                    <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 1rem;"></i>
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
                                    <td>{{ $usuario->name }} {{ $usuario->apellido }}</td>
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

    <div class="modal fade" id="modalAddUser" tabindex="-1" role="dialog" aria-labelledby="modalAddUserLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('herramientas.proyectos.assignUser', $proyecto) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalAddUserLabel"><i class="fas fa-user-plus"></i> Asignar Usuario</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="user_id">Selecciona un usuario disponible:</label>
                            @if($availableUsers->count() > 0)
                                <select name="user_id" id="user_id" class="form-control" required>
                                    <option value="">-- Elige un usuario --</option>
                                    @foreach($availableUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} {{ $user->apellido }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                            @else
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-info-circle"></i> Todos los usuarios activos ya están asignados a este proyecto.
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        @if($availableUsers->count() > 0)
                            <button type="submit" class="btn btn-primary">Asignar al Proyecto</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection