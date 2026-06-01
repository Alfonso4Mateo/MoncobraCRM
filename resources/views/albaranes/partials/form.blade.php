@php
    $formAction = route('albaranes.store');
    $currentEstado = old('estado', 'pendiente');
    $pedidoContext = $pedidoContext ?? null;
    $pedidoBolsa = (bool) ($pedidoBolsa ?? false);
    $pedidoModoRestringido = (bool) ($pedidoModoRestringido ?? false);
    $pedidoPendienteFacturar = $pedidoPendienteFacturar ?? null;
    // Si venimos de la vista de edición y el albarán tiene pedidos asociados,
    // usaremos el primero como contexto para que la UI se adapte correctamente.
    if ($pedidoContext === null && isset($albaran)) {
        $firstPedido = null;
        if (method_exists($albaran, 'pedidosClientes')) {
            $firstPedido = $albaran->pedidosClientes->first();
        }

        if ($firstPedido) {
            $pedidoContext = $firstPedido;
            $pedidoBolsa = (bool) ($firstPedido->bolsa ?? false);
            $pedidoModoRestringido = false;
        }
    }
    $lineasDesdeController = $lineasIniciales ?? [];
    $pedidoDefaults = $pedidoDefaults ?? [];
    $pedidosClientes = $pedidosClientes ?? collect();
    $pedidoMode = $pedidoContext !== null;

    $clienteIdDefault = old('cliente_id', $pedidoDefaults['cliente_id'] ?? $pedidoContext?->id_cliente ?? '');
    $pedidoClienteDefault = old('pedido_cliente', $pedidoDefaults['pedido_cliente'] ?? $pedidoContext?->numero_pedido ?? '');
    $otDefault = old('ot', $pedidoDefaults['ot'] ?? $pedidoContext?->ot ?? '');

    $lineasIniciales = [];
    $lineasDesdeOld = old('lineas_json');

    if (is_string($lineasDesdeOld) && trim($lineasDesdeOld) !== '') {
        $decodedOld = json_decode($lineasDesdeOld, true);
        if (is_array($decodedOld)) {
            $lineasIniciales = $decodedOld;
        }
    }

    if ($lineasIniciales === [] && is_array($lineasDesdeController) && $lineasDesdeController !== []) {
        $lineasIniciales = $lineasDesdeController;
    }

    $lineasIniciales = collect($lineasIniciales)
        ->filter(fn ($linea) => is_array($linea) && trim((string) ($linea['descripcion'] ?? '')) !== '')
        ->map(function (array $linea) {
            $articuloId = isset($linea['articulo_id']) ? (int) $linea['articulo_id'] : null;
            $articulo = trim((string) ($linea['articulo'] ?? ''));
            $descripcion = trim((string) ($linea['descripcion'] ?? ''));
            $cantidad = round(max(0, (float) ($linea['cantidad'] ?? 0)), 2);
            $medida = trim((string) ($linea['medida'] ?? ($linea['unidad'] ?? '')));
            $precioUnitario = round(max(0, (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0))), 2);
            $margen = round(max(0, (float) ($linea['margen'] ?? 0)), 2);

            $total = $cantidad * $precioUnitario * (1 + ($margen / 100));

            return [
                'articulo_id' => $articuloId,
                'articulo' => $articulo,
                'descripcion' => $descripcion,
                'cantidad' => $cantidad,
                'medida' => $medida,
                'precio_unitario' => $precioUnitario,
                'margen' => $margen,
                'total' => round($total, 2),
            ];
        })
        ->values()
        ->all();

    $lineasJsonInicial = json_encode($lineasIniciales, JSON_UNESCAPED_UNICODE);
@endphp

<section class="albaran-form-ui" data-albaran-form data-form-mode="create" data-pedido-mode="{{ $pedidoMode ? '1' : '0' }}" data-pedido-bolsa="{{ $pedidoBolsa ? '1' : '0' }}" data-initial-lineas="{{ e($lineasJsonInicial) }}">
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
                        <input type="text" id="documento" name="documento" value="Albarán" readonly>
                    </div>

                    <div class="field-group">
                        <label for="numero">Numero</label>
                        <input type="text" id="numero" name="numero" value="{{ old('numero', $numeroAlbaranAuto ?? '') }}">
                        <small class="pdf-help">Puedes cambiarlo manualmente. Si lo dejas vacío, se usará el siguiente número automático.</small>
                    </div>

                    <div class="field-group">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" value="{{ old('fecha', now()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="field-group">
                        <label for="cliente_id">Cliente</label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">Selecciona cliente...</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected((string) $clienteIdDefault === (string) $cliente->id)>
                                    {{ $cliente->empresa_nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="ot">OT</label>
                        <input type="text" id="ot" name="ot" value="{{ $otDefault }}">
                    </div>

                    <div class="field-group">
                        <label for="pedido_cliente">Pedido cliente</label>
                        <select id="pedido_cliente" name="pedido_cliente" data-placeholder="Busca por número, cliente u OT...">
                            <option value="">Selecciona pedido...</option>
                            @foreach ($pedidosClientes as $pedido)
                                @php
                                    $pedidoLabel = trim(
                                        ($pedido->numero_pedido ?: 'Pedido sin número') . ' | ' .
                                        ($pedido->cliente?->empresa_nombre ?: 'Sin cliente') .
                                        ($pedido->ot ? ' | OT ' . $pedido->ot : '')
                                    );
                                @endphp
                                <option
                                    value="{{ $pedido->numero_pedido }}"
                                    data-pedido-id="{{ $pedido->id }}"
                                    data-cliente-id="{{ $pedido->id_cliente }}"
                                    data-ot="{{ $pedido->ot }}"
                                    @selected((string) $pedidoClienteDefault === (string) $pedido->numero_pedido)
                                >
                                    {{ $pedidoLabel }}
                                </option>
                            @endforeach
                        </select>
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
                @if ($pedidoMode && !$pedidoBolsa)
                    <div class="albaran-selection-note">
                        <i class="fas fa-circle-info" aria-hidden="true"></i>
                        Marca los artículos que quieres incluir en este albarán. Los no marcados quedarán fuera del documento.
                    </div>
                @elseif ($pedidoBolsa)
                    <div class="albaran-selection-note albaran-selection-note--warning">
                        <i class="fas fa-circle-info" aria-hidden="true"></i>
                        Este pedido es bolsa: puedes añadir las líneas que necesites, pero no podrás superar <strong>{{ number_format((float) ($pedidoPendienteFacturar ?? 0), 2, ',', '.') }} €</strong> pendientes por facturar.
                    </div>
                    <div class="linea-input-row">
                        <div class="field-group flex-2">
                            <label for="linea_descripcion">Descripcion</label>
                            <textarea id="linea_descripcion" placeholder="Escriba el nombre del articulo..."></textarea>
                        </div>
                        <div class="field-group flex-1">
                            <label for="linea_cantidad">Cantidad</label>
                            <input type="number" id="linea_cantidad" value="1" min="0" max="10000000" step="0.01">
                        </div>
                        <div class="field-group flex-1">
                            <label for="linea_medida">Medida</label>
                            <input type="text" id="linea_medida" placeholder="u, kg, m...">
                        </div>
                        <div class="field-group flex-1">
                            <label for="linea_precio">P. unitario</label>
                            <input type="number" id="linea_precio" value="0" min="0" max="10000000" step="0.01">
                        </div>
                        <div class="field-group flex-1">
                            <label for="linea_margen">Margen (%)</label>
                            <input type="number" id="linea_margen" value="0" min="0" max="1000" step="0.01">
                        </div>
                        <button type="button" class="btn-add-linea" id="btnAddLinea">
                            <i class="fas fa-plus"></i>
                            Agregar
                        </button>
                    </div>
                @else
                    <div class="linea-input-row">
                        <div class="field-group flex-2">
                            <label for="linea_descripcion">Descripcion</label>
                            <textarea id="linea_descripcion" placeholder="Escriba el nombre del articulo..."></textarea>
                        </div>
                        <div class="field-group flex-1">
                            <label for="linea_cantidad">Cantidad</label>
                            <input type="number" id="linea_cantidad" value="1" min="0" max="10000000" step="0.01">
                        </div>
                        <div class="field-group flex-1">
                            <label for="linea_medida">Medida</label>
                            <input type="text" id="linea_medida" placeholder="u, kg, m...">
                        </div>
                        <div class="field-group flex-1">
                            <label for="linea_precio">P. unitario</label>
                            <input type="number" id="linea_precio" value="0" min="0" max="10000000" step="0.01">
                        </div>
                        <div class="field-group flex-1">
                            <label for="linea_margen">Margen (%)</label>
                            <input type="number" id="linea_margen" value="0" min="0" max="1000" step="0.01">
                        </div>
                        <button type="button" class="btn-add-linea" id="btnAddLinea">
                            <i class="fas fa-plus"></i>
                            Agregar
                        </button>
                    </div>
                @endif
            </article>

            <article class="albaran-card albaran-lineas-card">
                <div class="table-responsive">
                    <table class="table lineas-table">
                        <thead>
                            <tr>
                                @if ($pedidoMode && !$pedidoBolsa)
                                    <th>Línea</th>
                                    <th>Descripcion</th>
                                    <th>Cantidad</th>
                                    <th>Medida</th>
                                    <th>P. unitario</th>
                                    <th>Margen</th>
                                    <th>Total</th>
                                    <th style="width: 7rem">Incluir</th>
                                @else
                                    <th>Línea</th>
                                    <th>Descripcion</th>
                                    <th>Cantidad</th>
                                    <th>Medida</th>
                                    <th>P. unitario</th>
                                    <th>Margen</th>
                                    <th>Total</th>
                                    <th>Accion</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="lineasBody">
                            <tr>
                                <td colspan="8" class="lineas-empty">No hay lineas añadidas.</td>
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
            @unless ($pedidoMode)
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
            @endunless

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
