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
            <a href="{{ route('albaranes.index') }}" class="pedido-back-btn">Volver a albaranes</a>
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </article>
    </section>
@endsection

@section('css')
    @vite(['resources/css/pedidos-show-cliente.css'])
@endsection
