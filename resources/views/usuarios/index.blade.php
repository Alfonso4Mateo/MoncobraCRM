@extends('adminlte::page')

@section('title', 'Panel de Usuarios')

@section('css')
    {{-- Fuentes Externas --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    {{-- Importamos el CSS mediante Vite --}}
    @vite(['resources/css/usuarios-panel.css'])
@endsection

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Listado de Personal</h1>
            <p class="page-subtitle">Administra los accesos y roles de tu equipo de trabajo.</p>
        </div>
        <div class="col-sm-6 header-actions">
            <a href="{{ route('users.create') }}" class="btn-create-user">
                <i class="fas fa-user-plus"></i>
                Crear Usuario
            </a>
        </div>
    </div>
@endsection

@section('content')

    {{-- ── Alertas flash ── --}}
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

    {{-- ── Stats ── --}}
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-label">Total Usuarios</div>
                <div class="stat-value">{{ $users->total() }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-shield-halved"></i></div>
            <div>
                <div class="stat-label">Activos</div>
                <div class="stat-value">{{ $users->getCollection()->where('activo', true)->count() }}</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-user-shield"></i></div>
            <div>
                <div class="stat-label">Administrativos</div>
                <div class="stat-value">{{ $users->getCollection()->whereIn('role', ['admin','superadmin'])->count() }}</div>
            </div>
        </div>
    </div>

    {{-- ── Tabla principal ── --}}
    <div class="card-main">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Proyectos</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <a href="{{ route('users.show', $user->id) }}">
                                    <strong>{{ $user->name }}</strong>
                                </a>
                                @if(auth()->id() === $user->id)
                                    <span class="badge badge-secondary ml-2">Tú</span>
                                @endif
                            </td>
                            <td class="td-email">{{ $user->email }}</td>
                            <td>
                                @if($user->role === 'superadmin')
                                    <span class="badge badge-danger">Super Admin</span>
                                @elseif($user->role === 'admin')
                                    <span class="badge badge-warning">Admin</span>
                                @else
                                    <span class="badge badge-info">Usuario</span>
                                @endif
                            </td>
                            <td>
                                @if($user->proyectos->isNotEmpty())
                                    <small>{{ $user->proyectos->pluck('nombre')->join(', ') }}</small>
                                @else
                                    <span style="color:#d1d5db">Acceso a todos los proyectos</span>
                                @endif
                            </td>
                            <td>{{ $user->telefono ?? '—' }}</td>
                            <td>
                                @if($user->activo)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                @if(auth()->id() !== $user->id)
                                    <div class="actions-cell">
                                        @can('edit-user', $user)
                                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-info" title="Editar">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-secondary disabled" title="No puedes editar este usuario" disabled>
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        @endcan

                                        @can('edit-user', $user)
                                            <button class="btn btn-sm btn-warning toggle-active-btn" data-user-id="{{ $user->id }}" title="Cambiar estado">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        @endcan

                                        @can('delete-user', $user)
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan

                                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-secondary" title="Ver detalle" style="background:#f3f4f6!important;color:#6b7280!important;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                @else
                                    <span style="font-size:.78rem;color:var(--color-muted)">Perfil Personal</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    No hay usuarios disponibles.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages() || $users->total() > 0)
            <div class="table-footer">
                <span class="table-count">
                    Mostrando {{ $users->firstItem() }}–{{ $users->lastItem() }} de {{ $users->total() }} usuarios
                </span>
                @if($users->hasPages())
                    {{ $users->links() }}
                @endif
            </div>
        @endif
    </div>

    <div class="bottom-row">
        <div class="card-permisos">
            <div class="card-permisos-header">
                <div class="permisos-icon"><i class="fas fa-circle-info"></i></div>
                <h5>Permisos por Rol</h5>
            </div>
            <p>Los roles de Super Admin tienen acceso total. Los Admin pueden gestionar varias áreas con determinados permisos. Los Usuarios tendrán acceso limitado a ciertas funcionalidades.</p>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.querySelectorAll('.toggle-active-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userId;
                if (!confirm('¿Cambiar el estado de este usuario?')) return;

                fetch(`/users/${userId}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cambiar el estado del usuario.');
                });
            });
        });
    </script>
@endsection