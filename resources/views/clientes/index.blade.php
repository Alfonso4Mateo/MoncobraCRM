@extends('adminlte::page')

@section('title', 'Gestión de Clientes - MoncobraCRM')

@section('content')
    <section class="clientes-ui">
        @if (session('success'))
            <div class="clientes-success" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                {{ session('success') }}
            </div>
        @endif

        <header class="clientes-header">
            <div>
                <h1>Gestión de Clientes</h1>
                <p>Administración y control de la cartera de empresas industriales.</p>
            </div>
            <div class="clientes-header-actions">
                <button type="button" class="clientes-icon-btn" aria-label="Notificaciones">
                    <i class="fas fa-bell"></i>
                </button>
                <button type="button" class="clientes-icon-btn" aria-label="Configuración">
                    <i class="fas fa-cog"></i>
                </button>
                <a href="{{ route('clientes.create') }}" class="clientes-add-btn">
                    Añadir Cliente
                    <i class="fas fa-plus"></i>
                </a>
            </div>
        </header>

        <article class="clientes-card">
            <form method="GET" action="{{ route('clientes.index') }}" class="clientes-search-row">
                <div class="clientes-search-box">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input
                        type="text"
                        name="buscar"
                        value="{{ $buscar }}"
                        placeholder="Filtrar por nombre, CIF, sector o localidad..."
                        aria-label="Buscar clientes"
                    >
                </div>
                <input type="hidden" name="estado" value="{{ $estado }}">
                <button type="submit" class="clientes-search-btn">Ejecutar Búsqueda</button>
            </form>

            <div class="clientes-tabs-row">
                <nav class="clientes-tabs" aria-label="Estados de clientes">
                    <a href="{{ route('clientes.index', ['estado' => 'todos', 'buscar' => $buscar]) }}" class="{{ $estado === 'todos' ? 'is-active' : '' }}">Todos los Clientes</a>
                    <a href="{{ route('clientes.index', ['estado' => 'favoritos', 'buscar' => $buscar]) }}" class="{{ $estado === 'favoritos' ? 'is-active' : '' }}">Favoritos</a>
                </nav>

            </div>

            <div class="table-responsive clientes-table-wrapper">
                <table class="table clientes-table">
                    <thead>
                        <tr>
                            <th>Nombre del Cliente Industrial</th>
                            <th>CIF / Ident.</th>
                            <th>Sede Principal</th>
                            <th>Persona de Contacto</th>
                            <th>OTS Asignadas</th>
                            <th class="text-right">Gestión</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clientes as $cliente)
                            @php
                                $otsActivas = (int) $cliente->albaranes_count + (int) $cliente->presupuestos_count + (int) $cliente->pedidos_clientes_count;
                                $contacto = trim((string) $cliente->email);
                                $contactoEtiqueta = 'Correo';

                                if ($contacto === '') {
                                    $contacto = trim((string) $cliente->telefono);
                                    $contactoEtiqueta = 'Teléfono';
                                }

                                if ($contacto === '') {
                                    $contacto = trim((string) $cliente->persona_contacto);
                                    $contactoEtiqueta = 'Persona de contacto';
                                }

                                if ($contacto === '') {
                                    $contacto = 'Sin asignar';
                                    $contactoEtiqueta = null;
                                }

                                $favoritoActivo = (bool) $cliente->favorito;
                            @endphp
                            <tr>
                                <td>
                                    <div class="cliente-main">
                                        <div>
                                            <a href="{{ route('clientes.show', $cliente->id) }}" class="cliente-name-link">
                                                <div class="cliente-name">{{ $cliente->empresa_nombre }}</div>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="cliente-cif">{{ $cliente->cif_nif }}</td>
                                <td class="cliente-location">{{ $cliente->localidad ?: 'Sin localidad' }}</td>
                                <td>
                                    @if ($contactoEtiqueta)
                                        <div class="cliente-contact-label">{{ $contactoEtiqueta }}</div>
                                    @endif
                                    <div class="cliente-contact">{{ $contacto }}</div>
                                </td>
                                <td>
                                    <span class="cliente-ots">{{ $otsActivas }}</span>
                                </td>
                                <td>
                                    <div class="cliente-actions">
                                        <form action="{{ route('clientes.favorito.toggle', $cliente->id) }}" method="POST" class="cliente-favorite-form">
                                            @csrf
                                            <input type="hidden" name="estado" value="{{ $estado }}">
                                            <input type="hidden" name="buscar" value="{{ $buscar }}">
                                            <button
                                                type="submit"
                                                class="cliente-favorite-btn {{ $favoritoActivo ? 'is-favorito' : '' }}"
                                                title="{{ $favoritoActivo ? 'Quitar de favoritos' : 'Marcar como favorito' }}"
                                                aria-label="{{ $favoritoActivo ? 'Quitar de favoritos' : 'Marcar como favorito' }}"
                                            >
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('clientes.edit', $cliente->id) }}" class="cliente-action-icon" title="Editar cliente" aria-label="Editar cliente">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="{{ route('clientes.show', $cliente->id) }}" class="cliente-expediente-btn">
                                            <i class="fas fa-history"></i>
                                            Expediente
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    No hay clientes registrados para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="clientes-footer">
                <p>
                    Mostrando registros del {{ $clientes->firstItem() ?? 0 }} al {{ $clientes->lastItem() ?? 0 }} de un total de {{ number_format($clientes->total(), 0, ',', '.') }} clientes registrados
                </p>

                @if ($clientes->hasPages())
                    <div class="clientes-pagination">
                        {{ $clientes->onEachSide(1)->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </footer>
        </article>
    </section>
@endsection

@section('css')
    @vite(['resources/css/clientes-index.css'])
@endsection
