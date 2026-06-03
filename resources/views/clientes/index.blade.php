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
        @if (session('error'))
            <div class="clientes-success" role="alert" style="background:#fee2e2; color:#991b1b; border:1px solid #fecaca;">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                {{ session('error') }}
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
                        placeholder="Filtrar por nombre, CIF o localidad..."
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
                                        <button
                                            type="button"
                                            class="cliente-action-icon"
                                            data-delete-cliente
                                            data-delete-url="{{ route('clientes.destroy', $cliente->id) }}"
                                            data-ots="{{ $otsActivas }}"
                                            aria-label="Eliminar cliente"
                                            title="Eliminar cliente"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
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

        <form id="cliente-delete-form" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>

        <div class="modal fade cliente-delete-modal cliente-delete-modal--blocked" id="clienteDeleteBlockedModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered cliente-delete-modal__dialog" role="document">
                <div class="modal-content cliente-delete-modal__content">
                    <div class="modal-header cliente-delete-modal__header">
                        <div class="cliente-delete-modal__title-wrap">
                            <span class="cliente-delete-modal__icon cliente-delete-modal__icon--blocked">
                                <i class="fas fa-ban" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h5 class="modal-title">No se puede borrar el cliente</h5>
                                <p class="cliente-delete-modal__subtitle">El cliente tiene documentos asociados.</p>
                            </div>
                        </div>
                        <button type="button" class="close cliente-delete-modal__close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body cliente-delete-modal__body">
                        <p class="cliente-delete-modal__message mb-0" id="clienteDeleteBlockedMessage"></p>
                    </div>
                    <div class="modal-footer cliente-delete-modal__footer">
                        <button type="button" class="btn cliente-delete-modal__btn cliente-delete-modal__btn--primary" data-dismiss="modal">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade cliente-delete-modal cliente-delete-modal--confirm" id="clienteDeleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered cliente-delete-modal__dialog" role="document">
                <div class="modal-content cliente-delete-modal__content">
                    <div class="modal-header cliente-delete-modal__header">
                        <div class="cliente-delete-modal__title-wrap">
                            <span class="cliente-delete-modal__icon cliente-delete-modal__icon--danger">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h5 class="modal-title">Eliminar cliente</h5>
                                <p class="cliente-delete-modal__subtitle">Acción irreversible.</p>
                            </div>
                        </div>
                        <button type="button" class="close cliente-delete-modal__close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body cliente-delete-modal__body">
                        <p id="clienteDeleteConfirmMessage" class="cliente-delete-modal__message mb-0"></p>
                    </div>
                    <div class="modal-footer cliente-delete-modal__footer">
                        <button type="button" class="btn cliente-delete-modal__btn cliente-delete-modal__btn--ghost" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn cliente-delete-modal__btn cliente-delete-modal__btn--danger" id="clienteDeleteConfirmButton">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('css')
    @vite(['resources/css/clientes-index.css'])
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForm = document.getElementById('cliente-delete-form');
            const confirmModal = document.getElementById('clienteDeleteConfirmModal');
            const blockedModal = document.getElementById('clienteDeleteBlockedModal');
            const confirmMessage = document.getElementById('clienteDeleteConfirmMessage');
            const blockedMessage = document.getElementById('clienteDeleteBlockedMessage');
            const confirmButton = document.getElementById('clienteDeleteConfirmButton');

            let currentDeleteUrl = '';

            const showModal = (modalElement) => {
                if (!modalElement) {
                    return;
                }

                if (window.$ && typeof window.$(modalElement).modal === 'function') {
                    window.$(modalElement).modal('show');
                    return;
                }

                modalElement.classList.add('show');
                modalElement.style.display = 'block';
                modalElement.setAttribute('aria-modal', 'true');
            };

            document.querySelectorAll('[data-delete-cliente]').forEach((button) => {
                button.addEventListener('click', function () {
                    const deleteUrl = this.getAttribute('data-delete-url') || '';
                    const ots = Number.parseInt(this.getAttribute('data-ots') || '0', 10);

                    currentDeleteUrl = deleteUrl;

                    if (ots > 0) {
                        blockedMessage.textContent = `Este cliente tiene ${ots} documento/s asociado/s y no puede ser borrado.`;
                        showModal(blockedModal);
                        return;
                    }

                    confirmMessage.textContent = '¿Seguro que quieres borrar este cliente?';
                    showModal(confirmModal);
                });
            });

            if (confirmButton) {
                confirmButton.addEventListener('click', function () {
                    if (!currentDeleteUrl || !deleteForm) {
                        return;
                    }

                    deleteForm.setAttribute('action', currentDeleteUrl);
                    deleteForm.submit();
                });
            }
        });
    </script>
@endsection
