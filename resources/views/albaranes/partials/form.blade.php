@php
    $formAction = route('albaranes.store');
    $currentEstado = old('estado', 'pendiente');

    $lineasIniciales = [];
    $lineasDesdeOld = old('lineas_json');

    if (is_string($lineasDesdeOld) && trim($lineasDesdeOld) !== '') {
        $decodedOld = json_decode($lineasDesdeOld, true);
        if (is_array($decodedOld)) {
            $lineasIniciales = $decodedOld;
        }
    }

    $lineasIniciales = collect($lineasIniciales)
        ->filter(fn ($linea) => is_array($linea) && trim((string) ($linea['descripcion'] ?? '')) !== '')
        ->map(function (array $linea) {
            $descripcion = trim((string) ($linea['descripcion'] ?? ''));
            $cantidad = round(max(0, (float) ($linea['cantidad'] ?? 0)), 2);
            $precio = round(max(0, (float) ($linea['precio'] ?? 0)), 2);

            return [
                'descripcion' => $descripcion,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'total' => round($cantidad * $precio, 2),
            ];
        })
        ->values()
        ->all();

    $lineasJsonInicial = json_encode($lineasIniciales, JSON_UNESCAPED_UNICODE);
@endphp

<section class="albaran-form-ui" data-albaran-form data-initial-lineas="{{ e($lineasJsonInicial) }}">
    <header class="albaran-form-topbar">
        <nav class="albaran-breadcrumbs" aria-label="breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <span>/</span>
            <a href="{{ route('albaranes.index') }}">Albaranes</a>
            <span>/</span>
            <strong>Crear Albaran Cliente</strong>
        </nav>
    </header>

    <section class="albaran-headline">
        <h1>Crear Albaran Cliente</h1>
    </section>

    @if (session('error'))
        <div class="albaran-alert albaran-alert-error">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="albaran-alert albaran-alert-error">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <div>
                <strong>No se pudo guardar el albaran.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" class="albaran-form-layout" novalidate>
        @csrf

        <div class="albaran-main-col">
            <article class="albaran-card">
                <h2>INFORMACION DEL DOCUMENTO</h2>

                <div class="albaran-grid cols-3">
                    <div class="field-group">
                        <label for="documento">Documento</label>
                        <input type="text" id="documento" name="documento" value="{{ old('documento') }}" required>
                    </div>

                    <div class="field-group">
                        <label for="numero">Numero</label>
                        <input type="text" id="numero" name="numero" value="{{ old('numero') }}" required>
                    </div>

                    <div class="field-group">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" value="{{ old('fecha') }}" required>
                    </div>

                    <div class="field-group">
                        <label for="cliente_id">Cliente</label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">Selecciona cliente...</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected((string) old('cliente_id', request('cliente_id')) === (string) $cliente->id)>
                                    {{ $cliente->empresa_nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="ot">OT</label>
                        <input type="text" id="ot" name="ot" value="{{ old('ot', request('ot')) }}">
                    </div>

                    <div class="field-group">
                        <label for="pedido_cliente">Pedido cliente</label>
                        <input type="text" id="pedido_cliente" name="pedido_cliente" value="{{ old('pedido_cliente', request('pedido_cliente')) }}">
                    </div>

                    <div class="field-group col-span-2">
                        <label for="titulo">Titulo</label>
                        <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}">
                    </div>

                    <div class="field-group">
                        <label for="archivo_pdf">PDF del albaran</label>
                        <input type="file" id="archivo_pdf" name="archivo_pdf" accept="application/pdf">
                    </div>
                </div>
            </article>

            <article class="albaran-card">
                <h2>ARTICULOS</h2>
                <div class="linea-input-row">
                    <div class="field-group flex-2">
                        <label for="linea_descripcion">Descripcion</label>
                        <textarea id="linea_descripcion" placeholder="Escriba el nombre del articulo..."></textarea>
                    </div>
                    <div class="field-group flex-1">
                        <label for="linea_cantidad">Cantidad</label>
                        <input type="number" id="linea_cantidad" value="1" min="0" step="0.01">
                    </div>
                    <div class="field-group flex-1">
                        <label for="linea_precio">Precio</label>
                        <input type="number" id="linea_precio" value="0" min="0" step="0.01">
                    </div>
                    <button type="button" class="btn-add-linea" id="btnAddLinea">
                        <i class="fas fa-plus"></i>
                        Agregar
                    </button>
                </div>
            </article>

            <article class="albaran-card albaran-lineas-card">
                <div class="table-responsive">
                    <table class="table lineas-table">
                        <thead>
                            <tr>
                                <th>Linea</th>
                                <th>Descripcion</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Total</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody id="lineasBody">
                            <tr>
                                <td colspan="6" class="lineas-empty">No hay lineas añadidas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="albaran-card albaran-bottom-bar">
                <input type="hidden" name="estado" value="pendiente">

                <div class="albaran-total-box">
                    <span>TOTAL ALBARAN</span>
                    <strong id="albaranTotalValue">0 €</strong>
                </div>
            </article>
        </div>

        <aside class="albaran-side-col">
            <div class="side-card actions-row">
                <button type="button" id="btnEditLinea" class="side-btn side-btn-neutral" disabled>
                    <i class="far fa-edit"></i>
                    Editar
                </button>
                <button type="button" id="btnDeleteLinea" class="side-btn side-btn-danger" disabled>
                    <i class="far fa-trash-alt"></i>
                    Eliminar
                </button>
            </div>

            <div class="side-card actions-row">
                <button type="submit" class="side-btn side-btn-primary">
                    <i class="far fa-save"></i>
                    Guardar
                </button>
                <a href="{{ route('albaranes.index') }}" class="side-btn side-btn-neutral">
                    <i class="fas fa-sign-out-alt"></i>
                    Salir
                </a>
            </div>
        </aside>

        <input type="hidden" id="lineasJson" name="lineas_json" value="{{ old('lineas_json', $lineasJsonInicial) }}">
    </form>
</section>
