@php
    $formAction = route('albaranes.store');
    $currentEstado = old('estado', 'pendiente');
    $pedidoContext = $pedidoContext ?? null;
    $pedidoBolsa = (bool) ($pedidoBolsa ?? false);
    $pedidoModoRestringido = (bool) ($pedidoModoRestringido ?? false);
    $pedidoPendienteFacturar = $pedidoPendienteFacturar ?? null;

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

<section class="albaran-form-ui" data-albaran-form data-form-mode="create" data-pedido-mode="0" data-pedido-bolsa="{{ $pedidoBolsa ? '1' : '0' }}" data-initial-lineas="{{ e($lineasJsonInicial) }}" style="display: flex; flex-direction: column; width: 100%;">
    
    <header class="albaran-form-topbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
        <nav class="albaran-breadcrumbs" aria-label="breadcrumb">
            <a href="{{ route('dashboard') }}">Inicio</a>
            <span>/</span>
            <a href="{{ route('albaranes.index') }}">Albaranes</a>
            <span>/</span>
            <strong>Crear Albarán Cliente</strong>
        </nav>
    </header>

    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" id="form-albaran" style="display: flex; flex-direction: column; width: 100%; gap: 1.25rem;" novalidate>
        @csrf
        <input type="hidden" name="documento" value="Albarán">
        <input type="hidden" id="lineasJson" name="lineas_json" value="{{ old('lineas_json', $lineasJsonInicial) }}">
        <input type="hidden" name="estado" value="pendiente">

        {{-- CABECERA: TÍTULO Y BOTONES DE ACCIÓN --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 4px;">
            <h1 style="margin: 0; font-size: 1.85rem; font-weight: 800; color: #0f172a;">Crear Albarán Cliente</h1>
            
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="{{ route('albaranes.index') }}" class="btn btn-outline-secondary font-weight-bold" style="border-radius: 8px; padding: 7px 18px;">
                    <i class="fas fa-arrow-left mr-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary font-weight-bold" style="background-color: #1f74dd; border-color: #1f74dd; border-radius: 8px; padding: 7px 22px; box-shadow: 0 2px 6px rgba(31, 116, 221, 0.25);">
                    <i class="fas fa-save mr-1"></i> Guardar Albarán
                </button>
            </div>
        </div>

        @if (session('error'))
            <div class="albaran-alert albaran-alert-error">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="albaran-alert albaran-alert-error">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <div>
                    <strong>No se pudo guardar el albarán.</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- BLOQUE 1: INFORMACIÓN DEL DOCUMENTO --}}
        <article class="albaran-card" style="width: 100%; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h2 style="font-size: 0.95rem; font-weight: 800; color: #64748b; letter-spacing: 0.5px; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                INFORMACIÓN DEL DOCUMENTO
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem 1.25rem;">
                
                {{-- Fila 1: Número, Fecha, Cliente --}}
                <div class="field-group" style="margin: 0;">
                    <label for="numero" style="font-weight: 700; font-size: 0.85rem; color: #334155; margin-bottom: 4px;">Número</label>
                    <input type="text" id="numero" name="numero" value="{{ old('numero', $numeroAlbaranAuto ?? '') }}" class="form-control" style="border-radius: 8px;">
                    <small class="text-muted" style="font-size: 0.75rem;">Vacío para correlativo automático.</small>
                </div>

                <div class="field-group" style="margin: 0;">
                    <label for="fecha" style="font-weight: 700; font-size: 0.85rem; color: #334155; margin-bottom: 4px;">Fecha</label>
                    <input type="date" id="fecha" name="fecha" value="{{ old('fecha', now()->format('Y-m-d')) }}" class="form-control" style="border-radius: 8px;" required>
                </div>

                <div class="field-group" style="margin: 0;">
                    <label for="cliente_id" style="font-weight: 700; font-size: 0.85rem; color: #334155; margin-bottom: 4px;">Cliente <span class="text-danger">*</span></label>
                    <select id="cliente_id" name="cliente_id" required>
                        <option value="">Selecciona cliente...</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected((string) $clienteIdDefault === (string) $cliente->id)>
                                {{ $cliente->empresa_nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Fila 2: CC/OT, Título --}}
                <div class="field-group" style="margin: 0;">
                    <label for="ot" style="font-weight: 700; font-size: 0.85rem; color: #334155; margin-bottom: 4px;">CC (Centro de Coste)</label>
                    <input type="text" id="ot" name="ot" value="{{ $otDefault }}" list="cc_frecuentes" autocomplete="off" class="form-control" style="border-radius: 8px;" placeholder="Ej. 365 (Obras)">
                    <datalist id="cc_frecuentes">
                        @foreach($centrosCoste ?? [] as $cc)
                            <option value="{{ $cc->etiqueta_completa }}">
                        @endforeach
                    </datalist>
                </div>

                <div class="field-group" style="margin: 0; grid-column: span 2;">
                    <label for="titulo" style="font-weight: 700; font-size: 0.85rem; color: #334155; margin-bottom: 4px;">Título o descripción breve</label>
                    <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}" class="form-control" style="border-radius: 8px;" placeholder="Opcional. Identificador del trabajo realizado">
                </div>

                {{-- Fila 3: Partidas vinculadas --}}
                <div class="field-group" style="margin: 0; grid-column: 1 / -1;">
                    <label for="pedido_cliente" style="font-weight: 700; font-size: 0.85rem; color: #1e40af; margin-bottom: 4px;">
                        <i class="fas fa-boxes mr-1"></i> Partidas / Pedidos vinculados (Bolsa)
                    </label>
                    <select id="pedido_cliente" name="pedidos_cliente[]" multiple="multiple" data-placeholder="Selecciona una o varias partidas / pedidos..." style="width: 100%;">
                        @foreach ($pedidosClientes as $pedido)
                            @php
                                $clienteNombre = $pedido->cliente?->empresa_nombre ?: 'Sin cliente';
                                $otTexto = $pedido->ot ? ' | OT ' . $pedido->ot : '';
                                $consumidoTotal = $pedido->importeConsumido(isset($albaran) ? $albaran->id : null);
                                
                                // Facturado y comprometido (pendiente)
                                $facturadoFirme = method_exists($pedido, 'importeFacturado')
                                    ? $pedido->importeFacturado(isset($albaran) ? $albaran->id : null)
                                    : 0.0;
                                
                                $pendienteComprometido = max(0.0, round($consumidoTotal - $facturadoFirme, 2));

                                $esBolsaGlobalAbierta = $pedido->bolsaGlobal && (float) $pedido->bolsaGlobal->total <= 0;
                                $esAbierta = $pedido->esPartidaAbierta() || $esBolsaGlobalAbierta;
                                
                                $disponible = $pedido->pendienteFacturar(isset($albaran) ? $albaran->id : null);

                                $sinSaldoDisponible = !$esAbierta && $disponible <= 0.001 && $consumidoTotal > 0.001;

                                $estadoVisual = $pedido->estado ?: 'pendiente';
                                if (!$esAbierta && $estadoVisual === 'comprometido') {
                                    $estadoVisual = $consumidoTotal > 0 ? 'facturado_parcial' : 'pendiente';
                                }
                                $estadoTag = strtoupper(str_replace('_', ' ', $estadoVisual));

                                if ($pedido->bolsa) {
                                    $macroRef = $pedido->bolsaGlobal ? ($pedido->bolsaGlobal->numero ?: '#' . $pedido->bolsaGlobal->id) : 'Suelta';
                                    $partidaRef = $pedido->numero_pedido ?: 'Sin número';
                                    
                                    // Formato de información según si es abierta o cerrada
                                    if ($esAbierta) {
                                        $infoSaldo = "🔓 ABIERTA · Comprometido: " . number_format($consumidoTotal, 2, ',', '.') . " €";
                                    } elseif ($sinSaldoDisponible) {
                                        $infoSaldo = "⚠️ COPADA EN ALBARANES · Facturado: " . number_format($facturadoFirme, 2, ',', '.') . " € · Pendiente: " . number_format($pendienteComprometido, 2, ',', '.') . " € · Disp: 0,00 €";
                                    } else {
                                        $infoSaldo = "Facturado: " . number_format($facturadoFirme, 2, ',', '.') . " € · Pendiente: " . number_format($pendienteComprometido, 2, ',', '.') . " € · Disp: " . number_format($disponible, 2, ',', '.') . " €";
                                    }
                                    
                                    $pedidoLabel = "📦 Bolsa {$macroRef} | Partida {$partidaRef} ({$infoSaldo}) [{$estadoTag}] | {$clienteNombre}{$otTexto}";
                                } else {
                                    $pedidoRef = $pedido->numero_pedido ?: 'Pedido sin número';
                                    $pedidoLabel = "{$pedidoRef} (Facturado: " . number_format($facturadoFirme, 2, ',', '.') . " € · Pendiente: " . number_format($pendienteComprometido, 2, ',', '.') . " €) [{$estadoTag}] | {$clienteNombre}{$otTexto}";
                                }
                            @endphp
                            <option
                                value="{{ $pedido->numero_pedido }}"
                                data-pedido-id="{{ $pedido->id }}"
                                data-cliente-id="{{ $pedido->id_cliente }}"
                                data-ot="{{ $pedido->ot }}"
                                data-bolsa="{{ $pedido->bolsa ? '1' : '0' }}"
                                data-es-abierta="{{ $esAbierta ? '1' : '0' }}"
                                data-comprometido="{{ $consumidoTotal }}"
                                data-facturado="{{ $facturadoFirme }}"
                                data-pendiente="{{ $pendienteComprometido }}"
                                data-disponible="{{ $disponible }}"
                                @selected(is_array($pedidosVinculados) && in_array($pedido->numero_pedido, $pedidosVinculados))
                            >
                                {{ $pedidoLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </article>

        {{-- BLOQUE 2: FORMULARIO DE LÍNEA MANUAL --}}
        <article class="albaran-card" style="width: 100%; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h2 style="font-size: 0.95rem; font-weight: 800; color: #64748b; letter-spacing: 0.5px; margin-bottom: 0.75rem;">
                ARTÍCULOS Y LÍNEAS
            </h2>

            <div class="linea-input-row" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                <div class="field-group" style="flex: 3 1 280px; margin: 0;">
                    <label for="linea_descripcion" style="font-weight: 700; font-size: 0.85rem; color: #334155;">Descripción</label>
                    <textarea id="linea_descripcion" class="form-control" rows="1" placeholder="Escriba el nombre del artículo o trabajo..." style="border-radius: 8px; resize: none;"></textarea>
                </div>
                <div class="field-group" style="flex: 1 1 85px; margin: 0;">
                    <label for="linea_cantidad" style="font-weight: 700; font-size: 0.85rem; color: #334155;">Cantidad</label>
                    <input type="number" id="linea_cantidad" value="1" min="0" step="0.01" class="form-control" style="border-radius: 8px;">
                </div>
                <div class="field-group" style="flex: 1 1 85px; margin: 0;">
                    <label for="linea_medida" style="font-weight: 700; font-size: 0.85rem; color: #334155;">Medida</label>
                    <input type="text" id="linea_medida" value="und" placeholder="u, kg, m..." class="form-control" style="border-radius: 8px;">
                </div>
                <div class="field-group" style="flex: 1 1 110px; margin: 0;">
                    <label for="linea_precio" style="font-weight: 700; font-size: 0.85rem; color: #334155;">P. unitario (€)</label>
                    <input type="number" id="linea_precio" value="0.00" min="0" step="0.01" class="form-control" style="border-radius: 8px;">
                </div>
                <div class="field-group" style="flex: 1 1 85px; margin: 0;">
                    <label for="linea_margen" style="font-weight: 700; font-size: 0.85rem; color: #334155;">Margen (%)</label>
                    <input type="number" id="linea_margen" value="0" min="0" step="0.01" class="form-control" style="border-radius: 8px;">
                </div>
                <button type="button" class="btn btn-success font-weight-bold" id="btnAddLinea" style="border-radius: 8px; padding: 7px 18px; height: 38px;">
                    <i class="fas fa-plus mr-1"></i> Agregar
                </button>
            </div>
        </article>

        {{-- BLOQUE 3: TABLA DE LÍNEAS --}}
        <article class="albaran-card albaran-lineas-card" style="width: 100%; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 0; overflow: hidden;">
            <div class="table-responsive m-0">
                <table class="table lineas-table mb-0">
                    <thead style="background: #f8fafc;">
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
                            <td colspan="8" class="lineas-empty text-center py-4 text-muted">No hay líneas añadidas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>

        {{-- TOTAL INFERIOR --}}
        <article class="albaran-card" style="width: 100%; border-radius: 12px; display: flex; justify-content: flex-end; align-items: center; padding: 14px 20px; background: #ffffff;">
            <div style="text-align: right;">
                <span style="font-size: 0.82rem; font-weight: 800; color: #64748b; letter-spacing: 0.5px; display: block;">TOTAL ALBARÁN</span>
                <strong id="albaranTotalValue" style="font-size: 1.85rem; color: #1f74dd; font-weight: 800;">0,00 €</strong>
            </div>
        </article>

    </form>
</section>