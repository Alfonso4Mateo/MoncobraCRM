@extends('adminlte::page')

@section('title', 'Editar Usuario')

@vite(['resources/css/usuarios-edit.css'])

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Editar Usuario</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Panel de Usuarios</a></li>
                <li class="breadcrumb-item active">Editar</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="usuarios-edit-page">
        <div class="row">
            <!-- COLUMNA IZQUIERDA (Información del Usuario) -->
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">{{ $user->name }}</h3>
                    </div>

                    <form action="{{ route('users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error:</strong>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="name">Nombre</label>
                                <input 
                                    type="text" 
                                    class="form-control @error('name') is-invalid @enderror" 
                                    id="name" 
                                    name="name" 
                                    value="{{ old('name', $user->name) }}"
                                    required
                                >
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input 
                                    type="email" 
                                    class="form-control @error('email') is-invalid @enderror" 
                                    id="email" 
                                    name="email" 
                                    value="{{ old('email', $user->email) }}"
                                    required
                                >
                                @error('email')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="role">Rol</label>
                                <select 
                                    class="form-control @error('role') is-invalid @enderror" 
                                    id="role" 
                                    name="role" 
                                    required
                                >
                                    @if(auth()->user()->role === 'superadmin' || auth()->user()->role === 'admin')
                                        <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>
                                            Usuario
                                        </option>
                                    @endif
                                    @if(auth()->user()->role === 'superadmin' || auth()->user()->role === 'admin')
                                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>
                                            Admin
                                        </option>
                                    @endif
                                    @if(auth()->user()->role === 'superadmin')
                                        <option value="superadmin" {{ old('role', $user->role) === 'superadmin' ? 'selected' : '' }}>
                                            Super Admin
                                        </option>
                                    @endif
                                </select>
                                @error('role')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">
                                    @if(auth()->user()->role === 'admin')
                                        <i class="fas fa-info-circle"></i> Como Admin, puedes asignar los roles de Usuario y Admin, pero no puedes asignar Super Admin.
                                    @elseif(auth()->user()->role === 'superadmin')
                                        <i class="fas fa-info-circle"></i> Como Super Admin, puedes asignar cualquier rol.
                                    @endif
                                </small>
                                <small class="form-text text-info d-none" id="superadmin-proyectos-info-edit">
                                    <i class="fas fa-diagram-project"></i>
                                    Los Super Admin tienen acceso automático a todos los proyectos.
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="departamento">Cargo / Función principal</label>
                                <input 
                                    type="text" 
                                    class="form-control @error('departamento') is-invalid @enderror" 
                                    id="departamento" 
                                    name="departamento" 
                                    value="{{ old('departamento', $user->departamento) }}"
                                    placeholder="Ej. Técnico PRL, Comercial, Administrativo..."
                                >
                                @error('departamento')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input 
                                    type="text" 
                                    class="form-control @error('telefono') is-invalid @enderror" 
                                    id="telefono" 
                                    name="telefono" 
                                    value="{{ old('telefono', $user->telefono) }}"
                                >
                                @error('telefono')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea 
                                    class="form-control @error('descripcion') is-invalid @enderror" 
                                    id="descripcion" 
                                    name="descripcion" 
                                    rows="3"
                                >{{ old('descripcion', $user->descripcion) }}</textarea>
                                @error('descripcion')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input 
                                        type="checkbox" 
                                        class="custom-control-input" 
                                        id="activo" 
                                        name="activo" 
                                        value="1"
                                        {{ old('activo', $user->activo) ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="activo">
                                        Usuario Activo
                                    </label>
                                </div>
                            </div>

                            <div class="form-group mt-4" id="proyectos-wrapper">
                                <label>Asignación de Proyectos</label>
                                <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 10px;">
                                    Selecciona los proyectos a los que tendrá acceso este usuario en el sistema.
                                </p>
                                
                                <div class="row">
                                    @foreach($proyectos as $proyecto)
                                        <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input 
                                                    type="checkbox" 
                                                    name="proyecto_ids[]" 
                                                    value="{{ $proyecto->id }}" 
                                                    id="proyecto_{{ $proyecto->id }}"
                                                    class="custom-control-input proyecto-checkbox"
                                                    @checked(in_array($proyecto->id, old('proyecto_ids', $user->proyectos->pluck('id')->toArray())))
                                                >
                                                <label class="custom-control-label" for="proyecto_{{ $proyecto->id }}" style="font-weight: normal; cursor: pointer;">
                                                    {{ $proyecto->nombre }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="alert alert-info mt-4">
                                <strong>Información adicional:</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Creado:</strong> {{ $user->created_at->format('d/m/Y H:i') }}</li>
                                    <li><strong>Última actualización:</strong> {{ $user->updated_at->format('d/m/Y H:i') }}</li>
                                    @if($user->ultimo_acceso)
                                        <li><strong>Último acceso:</strong> {{ $user->ultimo_acceso->format('d/m/Y H:i') }}</li>
                                    @else
                                        <li><strong>Último acceso:</strong> Nunca</li>
                                    @endif
                                </ul>
                            </div>
                        </div>

                        <div class="card-footer">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary float-right">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- COLUMNA DERECHA (Información de Rol) -->
            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Información de Rol</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Rol Actual:</strong></p>
                        @if($user->role === 'superadmin')
                            <div class="alert alert-danger">
                                <i class="fas fa-crown"></i> Super Admin
                            </div>
                        @elseif($user->role === 'admin')
                            <div class="alert alert-warning">
                                <i class="fas fa-user-shield"></i> Admin
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-user"></i> Usuario
                            </div>
                        @endif

                        <p class="mt-3"><strong>Permisos:</strong></p>
                        @if($user->role === 'superadmin')
                            <ul class="mb-0">
                                <li>Acceso total al sistema</li>
                                <li>Gestionar todos los usuarios</li>
                                <li>Asignar roles</li>
                                <li>Cambiar estado de usuarios</li>
                            </ul>
                        @elseif($user->role === 'admin')
                            <ul class="mb-0">
                                <li>Gestionar usuarios regulares</li>
                                <li>Ver reporte de usuarios</li>
                                <li>Cambiar estado de usuarios</li>
                            </ul>
                        @else
                            <ul class="mb-0">
                                <li>Acceso a su perfil</li>
                                <li>Ver información básica</li>
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        (function () {
            const roleSelect = document.getElementById('role');
            const superadminInfo = document.getElementById('superadmin-proyectos-info-edit');

            if (!roleSelect) {
                return;
            }

            const setEnabledState = (container, enabled) => {
                container.querySelectorAll('input, select, textarea, button').forEach((element) => {
                    element.disabled = !enabled;
                });
            };

            const toggleProjectBlocksByRole = () => {
                const isSuperadmin = roleSelect.value === 'superadmin';

                if (superadminInfo) {
                    superadminInfo.classList.toggle('d-none', !isSuperadmin);
                }

                // Si existen bloques de proyectos en esta vista, se ocultan y bloquean para superadmin.
                const projectBlocks = document.querySelectorAll('[data-project-block], #proyectos-wrapper, .project-selector');

                projectBlocks.forEach((block) => {
                    block.style.display = isSuperadmin ? 'none' : '';
                    setEnabledState(block, !isSuperadmin);
                });
            };

            roleSelect.addEventListener('change', toggleProjectBlocksByRole);
            toggleProjectBlocksByRole();
        })();
    </script>
@endsection