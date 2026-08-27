@extends('adminlte::page')

@section('title', 'Editar Albarán Cliente - MoncobraCRM')

@section('content')
    @php
        $currentEstado = old('estado', $albaran->estado ?? 'pendiente');
        $pedidoAsociado = $albaran->pedidosClientes->first();
        $pedidoBolsa = (bool) ($pedidoAsociado?->bolsa ?? false);
        $pedidoMode = $pedidoAsociado !== null;
    @endphp

    <section class="albaran-edit-ui" data-albaran-form data-form-mode="edit" data-pedido-mode="0" data-pedido-bolsa="{{ $pedidoBolsa ? '1' : '0' }}" data-initial-lineas='@json($albaran->lista_articulos ?? [])'>
        <header class="albaran-edit-topbar">
            <div>
                <p class="breadcrumbs">Inicio <span>/</span> Editar Albarán Cliente</p>
                <h1>Editar Albarán Cliente</h1>
            </div>

            <div class="top-actions">
                @can('albaranes.download')
                    <a href="{{ route('albaranes.pdf.file', $albaran) }}" class="icon-btn" title="PDF" target="_blank">
                        <i class="far fa-file-pdf"></i>
                    </a>
                    <button type="button" class="icon-btn" title="Imprimir" onclick="window.print()">
                        <i class="fas fa-print"></i>
                    </button>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="albaran-alert albaran-alert-success">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="albaran-alert albaran-alert-error">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <div>
                    <strong>No se pudo guardar el albarán.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('albaranes.update', $albaran) }}" method="POST" class="layout-grid-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="return_to" value="{{ old('return_to', url()->previous()) }}">

            <div class="layout-grid">
                <main class="main-col">
                    <section class="card-block">
                        <h2>INFORMACIÓN DEL DOCUMENTO</h2>
                        <div class="form-grid cols-3">
                            <div class="field">
                                <label for="documento">Documento</label>
                                <input type="text" id="documento" name="documento" value="{{ old('documento', $albaran->documento ?? '') }}" required>
                            </div>
                            <div class="field">
                                <label for="numero">Número</label>
                                <input type="text" id="numero" name="numero" value="{{ old('numero', $albaran->numero ?? '') }}" required>
                            </div>
                            <div class="field">
                                <label for="fecha">Fecha</label>
                                <input type="date" id="fecha" name="fecha" value="{{ old('fecha', optional($albaran->fecha)->format('Y-m-d')) }}" required>
                            </div>

                            <div class="field">
                                <label for="cliente_id">Cliente</label>
                                <select id="cliente_id" name="cliente_id" required>
                                    <option value="">Selecciona cliente...</option>
                                    @foreach ($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" @selected((string) old('cliente_id', $albaran->cliente_id) === (string) $cliente->id)>
                                            {{ $cliente->empresa_nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="ot">OT</label>
                                <input type="text" id="ot" name="ot" value="{{ old('ot', $albaran->ot ?? '') }}">
                            </div>

                            <div class="field">
                                <label for="pedido_cliente">Pedido cliente</label>
                                <input type="text" id="pedido_cliente" name="pedido_cliente" value="{{ old('pedido_cliente', $albaran->pedido_cliente ?? '') }}" placeholder="Ej: PENDIENTE POR CONFIRMAR o Nº de pedido">
                            </div>

                            <div class="field span-2">
                                <label for="titulo">Título</label>
                                <input type="text" id="titulo" name="titulo" value="{{ old('titulo', $albaran->titulo ?? '') }}">
                            </div>
                        </div>
                    </section>

                    <section class="card-block">
                        <h2>ARTÍCULOS</h2>
                            <div class="albaran-selection-note">
                                <i class="fas fa-circle-info" aria-hidden="true"></i>
                                Este albarán pertenece a un pedido no bolsa, por lo que no se pueden agregar nuevas líneas.
                            </div>
                            <div class="line-row linea-input-row">
                                <div class="field flex-6">
                                    <label for="linea_descripcion">Descripción</label>
                                    <textarea id="linea_descripcion" placeholder="Escriba el nombre del artículo..."></textarea>
                                </div>
                                
                                <div class="field flex-compact">
                                    <label for="linea_cantidad">Cantidad</label>
                                    <input type="number" id="linea_cantidad" value="1" min="0" step="0.01">
                                </div>
                                <div class="field flex-compact">
                                    <label for="linea_medida">Medida</label>
                                    <input type="text" id="linea_medida" placeholder="u, kg, m...">
                                </div>
                                
                                <div class="field flex-compact-lg">
                                    <label for="linea_precio">P. unitario</label>
                                    <input type="number" id="linea_precio" value="0.00" min="0" max="10000000" step="0.01">
                                </div>
                                <div class="field flex-compact">
                                    <label for="linea_margen">Margen (%)</label>
                                    <input type="number" id="linea_margen" value="0" min="0" step="0.01">
                                </div>
                                
                                <button type="button" class="add-btn" id="btnAddLinea">+ Agregar</button>
                            </div>
                    </section>

                    <section class="card-block empty-table">
                        <div class="table-responsive">
                            <table class="table lineas-table">
                                <thead>
                                    <tr>
                                        <th>Línea</th>
                                        <th>Descripción</th>
                                        <th>Cantidad</th>
                                        <th>Medida</th>
                                        <th>P. unitario</th>
                                        <th>Margen</th>
                                        <th>Total</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="lineasBody">
                                    <tr>
                                        <td colspan="9" class="lineas-empty">No hay líneas añadidas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="card-block bottom-bar">
                        <div class="field estado-field">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <option value="pendiente" @selected($currentEstado === 'pendiente')>Pendiente</option>
                                <option value="recibido" @selected($currentEstado === 'recibido')>Recibido</option>
                                <option value="entregado" @selected($currentEstado === 'entregado')>Entregado</option>
                            </select>
                        </div>

                        <div class="total-box">
                            <span>TOTAL ALBARÁN</span>
                            <strong id="albaranTotalValue">{{ number_format((float) ($albaran->total ?? 0), 2, ',', '.') }} €</strong>
                        </div>
                    </section>
                </main>

                <aside class="side-col">
                    <div class="side-card actions-row">
                        <button type="button" id="btnEditLinea" class="side-btn neutral" disabled>
                            <i class="far fa-edit"></i>
                            Editar
                        </button>
                        <button type="button" id="btnDeleteLinea" class="side-btn danger" disabled>
                            <i class="far fa-trash-alt"></i>
                            Eliminar
                        </button>
                    </div>

                    <div class="side-card actions-row">
                        <button type="submit" class="side-btn primary">
                            <i class="far fa-save"></i>
                            Guardar
                        </button>
                        <a href="{{ route('albaranes.index') }}" class="side-btn neutral link-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            Salir
                        </a>
                    </div>
                </aside>
            </div>

            <input type="hidden" id="lineasJson" name="lineas_json" value="{{ old('lineas_json', json_encode($albaran->lista_articulos ?? [], JSON_UNESCAPED_UNICODE)) }}">
        </form>
    </section>
@endsection

@section('css')
    @vite(['resources/css/albaranes-edit.css'])
@endsection

@section('js')
    @vite(['resources/js/albaranes-form.js'])
@endsection