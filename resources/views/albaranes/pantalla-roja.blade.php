@extends('adminlte::page')

@section('title', 'Editar Albarán Cliente - MoncobraCRM')

@section('content')
    @php
        $currentEstado = old('estado', $albaran->estado ?? 'pendiente');
    @endphp

    <section class="albaran-red-ui" data-albaran-form data-initial-lineas='@json($albaran->lista_articulos ?? [])'>
        <header class="albaran-red-topbar">
            <div>
                <p class="breadcrumbs">Inicio <span>/</span> Editar Albarán Cliente</p>
                <h1>Editar Albarán Cliente</h1>
            </div>

            <div class="top-actions">
                @can('albaranes.download')
                    <button type="button" class="icon-btn" title="PDF">
                        <i class="far fa-file-pdf"></i>
                    </button>
                    <button type="button" class="icon-btn" title="Imprimir">
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

        <form action="{{ route('albaranes.pantalla-roja.update', $albaran) }}" method="POST" class="layout-grid-form">
            @csrf
            @method('PUT')

            <div class="layout-grid">
                <main class="main-col">
                    <section class="card-block">
                        <h2>INFORMACIÓN DEL DOCUMENTO</h2>
                        <div class="form-grid cols-3">
                            <div class="field">
                                <label for="documento">Documento</label>
                                <input type="text" id="documento" name="documento" value="{{ old('documento', $albaran->documento ?? '') }}">
                            </div>
                            <div class="field">
                                <label for="numero">Número</label>
                                <input type="text" id="numero" name="numero" value="{{ old('numero', $albaran->numero ?? '') }}">
                            </div>
                            <div class="field">
                                <label for="fecha">Fecha</label>
                                <input type="date" id="fecha" name="fecha" value="{{ old('fecha', optional($albaran->fecha)->format('Y-m-d')) }}">
                            </div>

                            <div class="field">
                                <label for="cliente_id">Cliente</label>
                                <select id="cliente_id" name="cliente_id">
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
                                <input type="text" id="pedido_cliente" name="pedido_cliente" value="{{ old('pedido_cliente', $albaran->pedido_cliente ?? '') }}">
                            </div>

                            <div class="field span-2">
                                <label for="titulo">Título</label>
                                <input type="text" id="titulo" name="titulo" value="{{ old('titulo', $albaran->titulo ?? '') }}">
                            </div>
                            <div class="field">
                                <label>Número referencia bolsa</label>
                                <input type="text" value="" readonly>
                            </div>
                        </div>
                    </section>

                    <section class="card-block">
                        <h2>ARTÍCULOS</h2>
                        <div class="line-row">
                            <div class="field flex-3">
                                <label for="linea_descripcion">Descripción</label>
                                <textarea id="linea_descripcion" placeholder="Escriba el nombre del artículo..."></textarea>
                            </div>
                            <div class="field flex-1">
                                <label for="linea_cantidad">Cantidad</label>
                                <input type="number" id="linea_cantidad" value="1" min="0" step="0.01">
                            </div>
                            <div class="field flex-1">
                                <label for="linea_medida">Medida</label>
                                <input type="text" id="linea_medida" placeholder="u, kg, m...">
                            </div>
                            <div class="field flex-1">
                                <label for="linea_precio">P. unitario</label>
                                <input type="number" id="linea_precio" value="0.00" min="0" step="0.01">
                            </div>
                            <div class="field flex-1">
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
    @vite(['resources/css/albaranes-form.css'])
    <style>
        .content-wrapper {
            background: #f2f4f7;
        }

        .albaran-red-ui {
            color: #1f2d40;
            font-family: "Segoe UI", "Source Sans Pro", sans-serif;
            padding-bottom: 1rem;
        }

        .albaran-red-topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .breadcrumbs {
            margin: 0;
            color: #7587a0;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .breadcrumbs span {
            margin: 0 0.35rem;
        }

        .albaran-red-topbar h1 {
            margin: 0.35rem 0 0;
            font-size: 2.05rem;
            font-weight: 800;
            color: #1c2d44;
        }

        .top-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .check-wrap {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border: 1px solid #d4deec;
            border-radius: 0.55rem;
            background: #ffffff;
            padding: 0.45rem 0.55rem;
            color: #516681;
            font-size: 0.78rem;
            margin: 0;
        }

        .icon-btn {
            width: 2rem;
            height: 2rem;
            border: 1px solid #d4deec;
            border-radius: 0.55rem;
            background: #ffffff;
            color: #677f9d;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 145px;
            gap: 1rem;
            align-items: start;
        }

        .layout-grid-form {
            display: block;
        }

        .card-block {
            border: 1px solid #d9e1ec;
            background: #ffffff;
            border-radius: 0.8rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .card-block h2 {
            margin: 0 0 0.8rem;
            color: #5f728c;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .form-grid {
            display: grid;
            gap: 0.7rem;
        }

        .cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .span-2 {
            grid-column: span 2;
        }

        .field {
            min-width: 0;
        }

        .field label {
            display: block;
            margin: 0 0 0.35rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: #61758f;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            height: 2.15rem;
            border: 1px solid #d8e1ec;
            border-radius: 0.5rem;
            background: #f8fbff;
            padding: 0 0.55rem;
            color: #334a65;
            font-size: 0.84rem;
        }

        .field textarea {
            min-height: 2.8rem;
            height: auto;
            padding: 0.45rem 0.55rem;
            line-height: 1.35;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            resize: vertical;
        }

        #linea_descripcion {
            min-height: 3rem;
            max-height: 12rem;
        }

        .line-row {
            display: flex;
            align-items: flex-end;
            gap: 0.7rem;
        }

        .flex-3 {
            flex: 3;
        }

        .flex-1 {
            flex: 1;
        }

        .add-btn {
            height: 2.15rem;
            border: 1px solid #2a7fe6;
            background: #2a7fe6;
            color: #ffffff;
            border-radius: 0.5rem;
            padding: 0 0.9rem;
            font-weight: 700;
            font-size: 0.84rem;
        }

        .empty-table {
            min-height: 140px;
        }

        .bottom-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .estado-field {
            width: min(250px, 100%);
        }

        .total-box {
            text-align: right;
            color: #607692;
        }

        .total-box span {
            display: block;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .total-box strong {
            font-size: 2rem;
            line-height: 1;
            color: #1e7ade;
        }

        .side-card {
            border-top: 1px solid #d8e1ec;
            padding-top: 0.9rem;
            margin-bottom: 1rem;
            display: grid;
            gap: 0.45rem;
        }

        .side-btn {
            width: 100%;
            height: 2.35rem;
            border: 1px solid #d8e1ec;
            border-radius: 0.55rem;
            font-size: 0.84rem;
            font-weight: 700;
            background: #ffffff;
            color: #516681;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.42rem;
            text-decoration: none;
        }

        .side-btn.primary {
            border-color: #1f74dd;
            background: #1f74dd;
            color: #ffffff;
        }

        .side-btn.danger {
            color: #df4a4a;
        }

        .link-btn:hover,
        .side-btn:hover {
            text-decoration: none;
        }

        .albaran-alert {
            margin-bottom: 1rem;
            border-radius: 0.75rem;
            padding: 0.7rem 0.9rem;
            font-size: 0.88rem;
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
        }

        .albaran-alert-success {
            border: 1px solid #b7e6cb;
            background: #ebf9f1;
            color: #247a48;
            font-weight: 700;
        }

        .albaran-alert-error {
            border: 1px solid #f2c6c6;
            background: #fff3f3;
            color: #9f2c2c;
            font-weight: 700;
        }

        @media (max-width: 1200px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }

            .side-col {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1rem;
            }

            .side-card {
                margin: 0;
            }
        }

        @media (max-width: 900px) {
            .cols-3 {
                grid-template-columns: 1fr;
            }

            .span-2 {
                grid-column: span 1;
            }

            .line-row {
                flex-direction: column;
                align-items: stretch;
            }

            .bottom-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .total-box {
                text-align: left;
            }

            .side-col {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('js')
    @vite(['resources/js/albaranes-form.js'])
@endsection