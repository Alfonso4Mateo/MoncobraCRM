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
                    <strong>No se pudo guardar el albarán.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('albaranes.update', $albaran) }}" method="POST" class="layout-grid-form" id="form-albaran">
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
                            
                            <!-- Agrupamos OT y Pedido Cliente en las 2 columnas restantes de la fila -->
                            <div class="span-2" style="display: flex; flex-direction: row; gap: 1rem;">
                                <!-- Caja de OT (25% del espacio) -->
                                <div class="field" style="flex: 0 0 25%; margin-bottom: 0;">
                                    <label for="ot">CC (Centro de Coste)</label>
                                    <input type="text" id="ot" name="ot" value="{{ old('ot', $albaran->ot ?? '') }}" list="cc_frecuentes" autocomplete="off" style="width: 100%;">
                                    <datalist id="cc_frecuentes">
                                        @foreach($centrosCoste ?? [] as $cc)
                                            <option value="{{ $cc->etiqueta_completa }}">
                                        @endforeach
                                    </datalist>
                                </div>

                                @php
                                    // Recuperamos las bolsas que ya tiene guardadas el albarán
                                    $pedidosVinculados = old('pedidos_cliente', $albaran->pedidosClientes->pluck('numero_pedido')->toArray());
                                    // Fallback para albaranes antiguos
                                    if (empty($pedidosVinculados) && !empty($albaran->pedido_cliente)) {
                                        $pedidosVinculados = [$albaran->pedido_cliente];
                                    }
                                @endphp
                                
                                <!-- Caja de Pedido Cliente (75% del espacio restante) -->
                                <div class="field" style="flex: 1; margin-bottom: 0;">
                                    <label for="pedido_cliente">Pedido cliente</label>
                                    <select id="pedido_cliente" name="pedidos_cliente[]" multiple="multiple" data-placeholder="Selecciona una o varias partidas / pedidos..." style="width: 100%;">
                                        @foreach ($pedidosClientes as $pedido)
                                            @php
                                                $clienteNombre = $pedido->cliente?->empresa_nombre ?: 'Sin cliente';
                                                $otTexto = $pedido->ot ? ' | OT ' . $pedido->ot : '';
                                                $consumidoTotal = $pedido->importeConsumido($albaran->id);
                                                
                                                // Desglose de facturado en firme y pendiente de facturar (comprometido en albarán)
                                                $facturadoFirme = method_exists($pedido, 'importeFacturado')
                                                    ? $pedido->importeFacturado($albaran->id)
                                                    : 0.0;
                                                
                                                $pendienteComprometido = max(0.0, round($consumidoTotal - $facturadoFirme, 2));

                                                $esBolsaGlobalAbierta = $pedido->bolsaGlobal && (float) $pedido->bolsaGlobal->total <= 0;
                                                $esAbierta = $pedido->esPartidaAbierta() || $esBolsaGlobalAbierta;
                                                
                                                $disponible = $pedido->pendienteFacturar($albaran->id);

                                                // Detectamos si la partida con techo ya no tiene saldo libre disponible
                                                $sinSaldoDisponible = !$esAbierta && $disponible <= 0.001 && $consumidoTotal > 0.001;

                                                $estadoVisual = $pedido->estado ?: 'pendiente';
                                                if (!$esAbierta && $estadoVisual === 'comprometido') {
                                                    $estadoVisual = $consumidoTotal > 0 ? 'facturado_parcial' : 'pendiente';
                                                }
                                                $estadoTag = strtoupper(str_replace('_', ' ', $estadoVisual));

                                                if ($pedido->bolsa) {
                                                    $macroRef = $pedido->bolsaGlobal ? ($pedido->bolsaGlobal->numero ?: '#' . $pedido->bolsaGlobal->id) : 'Suelta';
                                                    $partidaRef = $pedido->numero_pedido ?: 'Sin número';
                                                    
                                                    // Información según la modalidad de bolsa
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

                            <div class="field span-2">
                                <label for="titulo">Título</label>
                                <input type="text" id="titulo" name="titulo" value="{{ old('titulo', $albaran->titulo ?? '') }}">
                            </div>
                        </div>
                    </section>

                    <section class="card-block">
                        <h2>ARTÍCULOS</h2>
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
                            @php
                                $estadosAlbaran = [
                                    'pendiente' => 'Pendiente', 'recibido' => 'Recibido',
                                    'facturado' => 'Facturado', 'facturado_parcial' => 'Facturado parcial',
                                    'cancelado' => 'Cancelado',
                                ];
                            @endphp
                            <select id="estado" name="estado">
                                @foreach ($estadosAlbaran as $valor => $label)
                                    <option value="{{ $valor }}" @selected($currentEstado === $valor)>{{ $label }}</option>
                                @endforeach
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
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
    
    @vite(['resources/css/albaranes-edit.css'])
    
    <style>
        .ts-control {
            border: 1px solid #d8e1ec !important;
            border-radius: 0.5rem !important;
            background: #f8fbff !important;
            min-height: 2.15rem !important;
        }
    </style>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    @vite(['resources/js/albaranes-form.js'])
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formularios = document.querySelectorAll('form');
            formularios.forEach(form => {
                form.addEventListener('submit', function (e) {
                    const inputsConError = this.querySelectorAll('input[style*="border-color: rgb(192, 57, 43)"], input[style*="border-color: #c0392b"]');
                    if (inputsConError.length > 0) {
                        e.preventDefault();
                        alert("Por favor, corrige los importes o cantidades marcados en rojo antes de guardar. Hay líneas que superan el saldo disponible.");
                        return false;
                    }
                    
                    const btnSubmit = this.querySelector('button[type="submit"]');
                    if (btnSubmit) {
                        if (!this.checkValidity()) return;
                        
                        btnSubmit.disabled = true;
                        btnSubmit.style.pointerEvents = 'none';
                        
                        const originalWidth = btnSubmit.offsetWidth;
                        if(originalWidth > 0) btnSubmit.style.width = originalWidth + 'px';
                        
                        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando';
                    }
                });
            });
        });
    </script>
@endsection