@extends('adminlte::page')

@section('title', 'Pedido de Cliente - MoncobraCRM')

@section('header-title')
    <i class="fas fa-file-invoice"></i> Pedido de Cliente
@endsection

@section('content')
    <section class="pedido-cliente-wrap">
        <header class="pedido-cliente-head">
            <div>
                <h1>{{ $pedidoCliente->numero_pedido }}</h1>
                <p>
                    Fecha: {{ optional($pedidoCliente->fecha_pedido)->format('d/m/Y') ?: '-' }}
                    | OT: {{ $pedidoCliente->ot ?: 'Sin OT' }}
                </p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="{{ route('albaranes.index') }}" class="pedido-back-btn">Volver a albaranes</a>
            </div>
        </header>

        <article class="pedido-cliente-card">
            @php
                $bolsaTexto = trim((string) ($pedidoCliente->bolsa_texto ?? ''));
            @endphp
            <div class="pedido-grid">
                <div>
                    <span class="label">Cliente</span>
                    <p>{{ $pedidoCliente->cliente?->empresa_nombre ?: 'Sin cliente' }}</p>
                </div>
                <div>
                    <span class="label">Codigo Pedido</span>
                    <p>{{ $pedidoCliente->numero_pedido }}</p>
                </div>
            </div>

            <div class="pedido-articulos">
                <h2>Lineas del Pedido</h2>
                @php
                    $lineas = is_array($pedidoCliente->lista_articulos) ? $pedidoCliente->lista_articulos : [];
                @endphp

                @if ($lineas === [])
                    @if ($bolsaTexto !== '')
                        <p>{{ $bolsaTexto }}</p>
                    @else
                        <p class="empty">Este pedido no tiene lineas asociadas.</p>
                    @endif
                @else
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Articulo</th>
                                    <th>Descripcion</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lineas as $linea)
                                    <tr>
                                        <td>{{ $linea['articulo'] ?? '-' }}</td>
                                        <td>{{ $linea['descripcion'] ?? '-' }}</td>
                                        <td>{{ isset($linea['cantidad']) ? number_format((float) $linea['cantidad'], 2, ',', '.') : '-' }}</td>
                                        <td>
                                            {{ isset($linea['precio_unitario']) ? number_format((float) $linea['precio_unitario'], 2, ',', '.') . '€' : '-' }}
                                        </td>
                                        <td>{{ isset($linea['total']) ? number_format((float) $linea['total'], 2, ',', '.') . '€' : '-' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    style="background-color: #2a6fb0; border-color: #2a6fb0;"
                                                    data-toggle="modal" 
                                                    data-target="#modalEditarDescripcion"
                                                    data-index="{{ $loop->index }}"
                                                    data-descripcion="{{ $linea['descripcion'] ?? '' }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>
    </section>

    <!-- Modal para Editar la Descripción -->
    <div class="modal fade" id="modalEditarDescripcion" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('pedidos-clientes.actualizar-descripcion', $pedidoCliente) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <input type="hidden" name="linea_index" id="edit_linea_index" value="">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel">Editar Descripción de la Línea</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_descripcion">Nueva Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" style="background-color: #2a6fb0; border-color: #2a6fb0;">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @vite(['resources/css/pedidos-show-cliente.css'])
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $('#modalEditarDescripcion').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget); 
                
                var index = button.data('index');
                var descripcion = button.data('descripcion');
                
                var modal = $(this);
                modal.find('#edit_linea_index').val(index);
                modal.find('#edit_descripcion').val(descripcion);
            });
        });
    </script>
@endsection