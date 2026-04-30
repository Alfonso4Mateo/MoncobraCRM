@extends('adminlte::page')

@section('title', 'Detalles del Usuario')

@vite(['resources/css/usuarios-show.css'])

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Detalles del Usuario</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Panel de Usuarios</a></li>
                <li class="breadcrumb-item active">Detalles</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="usuarios-show-page">
        <div class="row">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $user->name }}</h3>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> {{ $user->name }}</p>
                            <p><strong>Email:</strong> <a href="mailto:{{ $user->email }}">{{ $user->email }}</a></p>
                            <p><strong>Teléfono:</strong> {{ $user->telefono ?? 'No especificado' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Rol:</strong>
                                @if($user->role === 'superadmin')
                                    <span class="badge badge-danger">Super Admin</span>
                                @elseif($user->role === 'admin')
                                    <span class="badge badge-warning">Admin</span>
                                @else
                                    <span class="badge badge-info">Usuario</span>
                                @endif
                            </p>
                            <p><strong>Estado:</strong>
                                @if($user->activo)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                            </p>
                            <p><strong>Proyectos:</strong>
                                @if($user->proyectos->isNotEmpty())
                                    {{ $user->proyectos->pluck('nombre')->join(', ') }}
                                @else
                                    No asignado
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($user->descripcion)
                        <div class="mt-3">
                            <p><strong>Descripción:</strong></p>
                            <p>{{ $user->descripcion }}</p>
                        </div>
                    @endif

                    <hr>

                    <div class="mt-3">
                        <p><strong>Creado:</strong> {{ $user->created_at->format('d/m/Y H:i') }}</p>
                        <p><strong>Última actualización:</strong> {{ $user->updated_at->format('d/m/Y H:i') }}</p>
                        @if($user->ultimo_acceso)
                            <p><strong>Último acceso:</strong> {{ $user->ultimo_acceso->format('d/m/Y H:i') }}</p>
                        @else
                            <p><strong>Último acceso:</strong> Nunca</p>
                        @endif
                    </div>
                </div>

                <div class="card-footer">
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    @can('edit-user', $user)
                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary float-right">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Información de Rol</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Nivel de Acceso:</strong></p>
                    @if($user->role === 'superadmin')
                        <div class="alert alert-danger">
                            <i class="fas fa-crown"></i> Super Admin
                            <p class="text-sm mt-2 mb-0">Acceso total al sistema</p>
                        </div>
                    @elseif($user->role === 'admin')
                        <div class="alert alert-warning">
                            <i class="fas fa-user-shield"></i> Admin
                            <p class="text-sm mt-2 mb-0">Acceso administrativo limitado</p>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-user"></i> Usuario Regular
                            <p class="text-sm mt-2 mb-0">Acceso estándar</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection