@extends('adminlte::page')

@section('title', 'Gestión de Clases - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/clases-index.css'])
@endsection

@section('content')
    <section class="clases-ui">
        @if (session('success'))
            <div class="clases-success" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="clases-error" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                {{ session('error') }}
            </div>
        @endif

        <header class="clases-header">
            <div>
                <h1>Gestión de Clases</h1>
                <p>Administración de categorías para clasificar los items del inventario.</p>
            </div>
            <div class="clases-header-actions">
                <a href="{{ route('clases.create') }}" class="clases-add-btn">
                    Nueva Clase
                    <i class="fas fa-plus"></i>
                </a>
            </div>
        </header>

        <article class="clases-card">
            @if($clases->count() > 0)
                <div class="table-responsive clases-table-wrapper">
                    <table class="table clases-table">
                        <thead>
                            <tr>
                                <th>Nombre de Clase</th>
                                <th>Items Asociados</th>
                                <th>Fecha de Creación</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clases as $clase)
                                <tr>
                                    <td>
                                        <strong>{{ $clase->nombre }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $clase->inventarios_count }}</span>
                                    </td>
                                    <td>
                                        {{ $clase->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('clases.edit', $clase->id) }}" class="btn btn-secondary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('clases.destroy', $clase->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de que desea eliminar esta clase?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="clases-alert clases-alert-info">
                    <i class="fas fa-info-circle"></i>
                    No hay clases registradas. <a href="{{ route('clases.create') }}">Crear una nueva clase</a>
                </div>
            @endif
        </article>
    </section>
@endsection
