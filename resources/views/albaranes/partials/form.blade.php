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
    $pedidosVinculados = old('pedidos_cliente', []);
    if (empty($pedidosVinculados)) {
        $defaultNum = old('pedido_cliente', $pedidoDefaults['pedido_cliente'] ?? $pedidoContext?->numero_pedido ?? '');
        if ($defaultNum !== '') {
            $pedidosVinculados = [$defaultNum];
        }
    }
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

<section class="albaran-form-ui" data-albaran-form data-form-mode="create" data-pedido-mode="0" data-pedido-bolsa="{{ $pedidoBolsa ? '1' : '0' }}" data-initial-lineas="{{ e($lineasJsonInicial) }}">
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

    @if (session('warning'))
            <div class="albaranes-alert-warning" style="background-color: #fffbeb; color: #b45309; border: 1px solid #fcd34d; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                {{ session('warning') }}
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

                    <!-- Agrupamos OT y Pedido Cliente en las 2 columnas restantes de la fila -->
                    <div class="col-span-2" style="display: flex; flex-direction: row; gap: 1rem;">
                        <!-- Caja de OT (25% del espacio) -->
                        <div class="field-group" style="flex: 0 0 25%; margin-bottom: 0;">
                            <label for="ot">OT</label>
                            <input type="text" id="ot" name="ot" value="{{ $otDefault }}" list="cc_frecuentes" autocomplete="off" style="width: 100%;">
                            <datalist id="cc_frecuentes">
                                @foreach($centrosCoste ?? [] as $cc)
                                    <option value="{{ $cc->etiqueta_completa }}">
                                @endforeach
                            </datalist>
                        </div>

                        <!-- Caja de Pedido Cliente (75% del espacio restante) -->
                        <div class="field-group" style="flex: 1; margin-bottom: 0;">
                            <label for="pedido_cliente">Pedido cliente</label>
                            <select id="pedido_cliente" name="pedidos_cliente[]" multiple="multiple" data-placeholder="Selecciona una o varias bolsas..." style="width: 100%;">
                                <option value="">Selecciona pedido...</option>
                                @foreach ($pedidosClientes as $pedido)
                                    @php
                                        $clienteNombre = $pedido->cliente?->empresa_nombre ?: 'Sin cliente';
                                        $otTexto = $pedido->ot ? ' | OT ' . $pedido->ot : '';
                                        
                                        if ($pedido->bolsa) {
                                            $macroRef = $pedido->bolsaGlobal ? ($pedido->bolsaGlobal->numero ?: $pedido->bolsaGlobal->id) : 'Suelta';
                                            $partidaRef = $pedido->numero_pedido ?: 'Sin número';
                                            
                                            $pedidoLabel = "📦 Bolsa {$macroRef} | Partida {$partidaRef} | {$clienteNombre}{$otTexto}";
                                        } else {
                                            $pedidoRef = $pedido->numero_pedido ?: 'Pedido sin número';
                                            
                                            $pedidoLabel = "{$pedidoRef} | {$clienteNombre}{$otTexto}";
                                        }
                                    @endphp
                                    <option
                                        value="{{ $pedido->numero_pedido }}"
                                        data-pedido-id="{{ $pedido->id }}"
                                        data-cliente-id="{{ $pedido->id_cliente }}"
                                        data-ot="{{ $pedido->ot }}"
                                        @selected(is_array($pedidosVinculados) && in_array($pedido->numero_pedido, $pedidosVinculados))
                                    >
                                        {{ $pedidoLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="field-group col-span-2">
                        <label for="titulo">Titulo</label>
                        <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}">
                    </div>
                </div>
            </article>

            <article class="albaran-card">
                <h2>ARTICULOS</h2>
                
                @if ($pedidoBolsa)
                    <div class="albaran-selection-note albaran-selection-note--warning">
                        <i class="fas fa-circle-info" aria-hidden="true"></i>
                        Este pedido es bolsa: puedes añadir o modificar las líneas que necesites libremente.
                    </div>
                @endif

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
                        <input type="text" id="linea_medida" value="und" placeholder="u, kg, m...">
                    </div>
                    <div class="field-group flex-1">
                        <label for="linea_precio">P. unitario</label>
                        <input type="number" id="linea_precio" value="0.00" min="0" max="10000000" step="0.01">
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
            </article>

            <article class="albaran-card albaran-lineas-card">
                <div class="table-responsive">
                    <table class="table lineas-table">
                        <thead>
                            <tr>
                                <th>Línea</th>
                                <th>Descripcion</th>
                                <th>Cantidad</th>
                                <th>Medida</th>
                                <th>P. unitario</th>
                                <th>Margen</th>
                                <th>Total</th>
                                <th>Accion</th>
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