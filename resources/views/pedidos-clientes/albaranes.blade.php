@extends('adminlte::page')

@section('title', 'Albaranes del Pedido - MoncobraCRM')

@section('css')
    @vite(['resources/css/pedidos-clientes-index.css'])
    <style>
        .drilldown-link {
            color: #1f74dd;
            font-weight: 800;
            cursor: pointer;
            text-decoration: underline dashed;
            text-underline-offset: 4px;
            padding: 2px 6px;
            transition: all 0.2s;
            border-radius: 4px;
        }
        .drilldown-link:hover {
            color: #165ab0;
            background-color: #eff6ff;
            text-decoration: underline solid;
        }
        
        .modal-desglose .modal-header {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        .modal-desglose .modal-title {
            color: #1e293b;
            font-weight: 800;
            font-size: 1.15rem;
        }
        .modal-desglose .table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
@endsection

@section('content_header')
    <div class="pedidos-clientes-header">
        <div class="pedidos-clientes-header__copy">
            <h1>Albaranes del Pedido</h1>
            <p>Gestión y seguimiento de albaranes asociados.</p>
        </div>

        <div class="pedidos-clientes-header__actions">
            <a href="{{ route('pedidos-clientes.index') }}" class="pedidos-clientes-action-btn pedidos-clientes-action-btn--secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Volver
            </a>
            
            @can('albaranes.manage')
                @php
                    $pedidoFacturado = (string) ($pedidoCliente->estado ?? '') === 'facturado';
                    $crearAlbaranUrl = route('albaranes.create', ['pedido_id' => $pedidoCliente->id, 'pedido_cliente' => $pedidoCliente->numero_pedido, 'cliente_id' => $pedidoCliente->id_cliente, 'ot' => $pedidoCliente->ot]);
                @endphp
                @if ($pedidoFacturado)
                    <span
                        class="pedidos-clientes-create-btn pedidos-clientes-create-btn--disabled"
                        aria-disabled="true"
                        title="Este pedido ya está facturado"
                    >
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        Agregar Albarán
                    </span>
                @else
                    <a
                        href="{{ $crearAlbaranUrl }}"
                        class="pedidos-clientes-create-btn"
                    >
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        Agregar Albarán
                    </a>
                @endif
            @endcan
            @can('pedidos.manage')
                @if ($pedidoFacturado)
                    <form action="{{ route('pedidos-clientes.estado.update', $pedidoCliente) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Reabrir esta partida? Volverá a estado COMPROMETIDO y aparecerá de nuevo en el selector de albaranes.');">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="{{ $albaranes->total() > 0 ? 'comprometido' : 'pendiente' }}">
                        <button type="submit" class="pedidos-clientes-action-btn" style="background-color: #f59e0b; color: #fff; border: none; font-weight: 700;">
                            <i class="fas fa-lock-open" aria-hidden="true"></i> Reabrir Partida
                        </button>
                    </form>
                @elseif ($pedidoCliente->bolsa)
                    <form action="{{ route('pedidos-clientes.estado.update', $pedidoCliente) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Cerrar y marcar como FACTURADA esta partida? Desaparecerá del selector de nuevos albaranes.');">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="facturado">
                        <button type="submit" class="pedidos-clientes-action-btn" style="background-color: #10b981; color: #fff; border: none; font-weight: 700;">
                            <i class="fas fa-check-circle" aria-hidden="true"></i> Cerrar / Facturar Partida
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>
@endsection

@section('content')
    <section class="pedidos-clientes-shell">
        
        <!-- WIDGET GLOBAL DEL CLIENTE -->
        @if(isset($resumenBolsasCliente) && !empty($resumenBolsasCliente['bolsas']))
            <article class="pedidos-clientes-card" style="background: #fef3c7; border: 1px solid #f59e0b; border-left: 4px solid #d97706; padding: 16px; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 14px;">
                    <div style="font-size: 2.2rem; color: #d97706;"><i class="fas fa-boxes"></i></div>
                    <div>
                        <h3 style="margin: 0; color: #92400e; font-size: 1.1rem; font-weight: 800;">Otras bolsas activas de {{ $pedidoCliente->cliente?->empresa_nombre ?? 'este cliente' }}</h3>
                        <p style="margin: 4px 0 0 0; color: #b45309; font-size: 0.95rem;">
                            El sistema detecta <strong>{{ $resumenBolsasCliente['cantidad'] }} bolsa(s) más</strong> activa(s) para este cliente. Cada bolsa es independiente: comprueba cuál corresponde antes de vincular un albarán.
                        </p>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    @foreach ($resumenBolsasCliente['bolsas'] as $bolsa)
                        <div style="background: #fffbeb; padding: 10px 14px; border-radius: 8px; border: 1px solid #fcd34d;">
                            <span style="display: block; font-size: 0.72rem; color: #b45309; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px;">
                                {{ $bolsa['numero_pedido'] ?: 'Sin número' }}{{ $bolsa['ot'] ? ' · OT ' . $bolsa['ot'] : '' }}
                            </span>
                            <strong style="display: block; font-size: 1.25rem; color: #92400e; line-height: 1.3;">
                                {{ number_format($bolsa['saldo_disponible'], 2, ',', '.') }} € libres
                            </strong>
                            <span style="font-size: 0.78rem; color: #92400e;">de {{ number_format($bolsa['total'], 2, ',', '.') }} € totales</span>
                        </div>
                    @endforeach
                </div>
            </article>
        @endif

        <!-- Datos Principales del Pedido -->
        <article class="pedidos-clientes-card albaran-pedido-info">
            <header class="albaran-info-header">
                <h3>Información del Pedido</h3>
            </header>
            <div class="albaran-info-grid">
                <div class="albaran-info-item">
                    <span class="albaran-info-label">Número de Pedido</span>
                    <strong class="albaran-info-value">{{ $pedidoCliente->numero_pedido }}</strong>
                </div>
                <div class="albaran-info-item">
                    <span class="albaran-info-label">Cliente</span>
                    <strong class="albaran-info-value">{{ $pedidoCliente->cliente?->empresa_nombre ?? 'Sin cliente' }}</strong>
                </div>
                <div class="albaran-info-item">
                    <span class="albaran-info-label">CC (Centro de Coste)</span>
                    <strong class="albaran-info-value">{{ $pedidoCliente->ot ?: '—' }}</strong>
                </div>
                <div class="albaran-info-item">
                    <span class="albaran-info-label">Fecha del Pedido</span>
                    <strong class="albaran-info-value">{{ optional($pedidoCliente->fecha_pedido)->format('d/m/Y H:i') ?: '—' }}</strong>
                </div>
            </div>
        </article>

        <!-- Resumen de Pagos -->
        <article class="pedidos-clientes-card albaran-resumen-pagos">
            <header class="albaran-info-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Resumen de Pagos</h3>
            </header>
            <div class="albaran-pagos-grid">
                <div class="albaran-pago-item">
                    <div class="albaran-pago-info">
                        <span class="albaran-pago-label">{{ $pedidoCliente->esPartidaAbierta() ? 'Modalidad de Partida' : 'Techo / Total del Pedido' }}</span>
                        <strong class="albaran-pago-amount">
                            @if($pedidoCliente->esPartidaAbierta())
                                <span style="color: #0369a1; font-size: 1.15rem;"><i class="fas fa-unlock-alt mr-1"></i> Partida Abierta (Sin techo)</span>
                            @else
                                €{{ number_format((float) $totalPedido, 2, ',', '.') }}
                            @endif
                        </strong>
                    </div>
                </div>
                <div class="albaran-pago-item albaran-pago-item--paid">
                    <div class="albaran-pago-info">
                        <span class="albaran-pago-label">Importe Comprometido / Facturado</span>
                        <strong class="albaran-pago-amount">€{{ number_format((float) $totalFacturado, 2, ',', '.') }}</strong>
                    </div>
                </div>
                <div class="albaran-pago-item albaran-pago-item--pending">
                    <div class="albaran-pago-info">
                        <span class="albaran-pago-label">{{ $pedidoCliente->esPartidaAbierta() ? 'Estado Actual' : 'Saldo Disponible' }}</span>
                        <strong class="albaran-pago-amount">
                            @if($pedidoCliente->esPartidaAbierta())
                                {{ strtoupper(str_replace('_', ' ', $pedidoCliente->estado ?: 'pendiente')) }}
                            @else
                                €{{ number_format((float) ($pendienteFacturar ?? 0), 2, ',', '.') }}
                            @endif
                        </strong>
                    </div>
                </div>
                <div class="albaran-pago-item albaran-pago-item--count">
                    <div class="albaran-pago-info">
                        <span class="albaran-pago-label">Total de Albaranes</span>
                        <strong class="albaran-pago-amount">{{ $albaranes->total() }}</strong>
                    </div>
                </div>
            </div>

            <!-- Barra de Progreso de Pago -->
            <div class="albaran-pago-progress">
                @php
                    $porcentajePagado = $totalPedido > 0 ? ($totalFacturado / $totalPedido) * 100 : 0;
                @endphp
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: {{ $porcentajePagado }}%"></div>
                </div>
                <span class="progress-text">{{ number_format($porcentajePagado, 1, ',', '.') }}% Facturado</span>
            </div>
        </article>

        <!-- ========================================== -->
        <!-- DESGLOSE DE CONSUMO CON DRILL-DOWN (BOLSA) -->
        <!-- ========================================== -->
        @php
            $esBolsa = (bool) ($pedidoCliente->bolsa ?? false);
            $lineasBolsa = [];

            if ($esBolsa) {
                try {
                    if (!$pedidoCliente->lineasBolsa()->exists() && !empty($pedidoCliente->lista_articulos)) {
                        (new \App\Services\LineaBolsaService())->sincronizarLineasDePartida(
                            $pedidoCliente,
                            is_array($pedidoCliente->lista_articulos) ? $pedidoCliente->lista_articulos : []
                        );
                        $pedidoCliente->unsetRelation('lineasBolsa');
                    }

                    $lineasPartida = $pedidoCliente->lineasBolsa()->get();

                    foreach ($lineasPartida as $linea) {
                        $modoConsumo = $linea->modo_consumo ?? 'cantidad';
                        $medida = $linea->medida ?: 'und';
                        $precioUnitario = (float) $linea->precio_unitario;
                        $margen = (float) $linea->margen;
                        $unitPriceConMargen = $precioUnitario * (1 + ($margen / 100));

                        $totalOriginal = round((float) $linea->pivot->importe_asignado, 2);

                        $cantOriginal = $modoConsumo === 'importe'
                            ? 1.0
                            : ($unitPriceConMargen > 0 ? round($totalOriginal / $unitPriceConMargen, 2) : 0.0);

                        $consumosPartida = \Illuminate\Support\Facades\DB::table('albaran_linea_partida')
                            ->join('albaranes_clientes', 'albaran_linea_partida.albaran_id', '=', 'albaranes_clientes.id')
                            ->where('albaran_linea_partida.linea_id', $linea->id)
                            ->where('albaran_linea_partida.pedido_id', $pedidoCliente->id)
                            ->whereNull('albaranes_clientes.deleted_at')
                            ->whereNotIn('albaranes_clientes.estado', ['cancelado', 'rechazado'])
                            ->select(
                                'albaran_linea_partida.importe',
                                'albaranes_clientes.id as albaran_id',
                                'albaranes_clientes.numero as albaran_numero',
                                'albaranes_clientes.fecha as albaran_fecha'
                            )
                            ->get();

                        $totalConsumido = round((float) $consumosPartida->sum('importe'), 2);

                        $cantConsumida = $modoConsumo === 'importe'
                            ? ($totalOriginal > 0 ? round($cantOriginal * ($totalConsumido / $totalOriginal), 2) : 0.0)
                            : ($unitPriceConMargen > 0 ? round($totalConsumido / $unitPriceConMargen, 2) : 0.0);

                        $detallesConsumo = [];
                        foreach ($consumosPartida as $consumo) {
                            $importeConsumo = (float) $consumo->importe;
                            $cantFisica = $modoConsumo === 'importe'
                                ? ($totalOriginal > 0 ? round($cantOriginal * ($importeConsumo / $totalOriginal), 2) : 0.0)
                                : ($unitPriceConMargen > 0 ? round($importeConsumo / $unitPriceConMargen, 2) : 0.0);

                            $detallesConsumo[] = [
                                'numero' => $consumo->albaran_numero,
                                'fecha' => $consumo->albaran_fecha ? \Carbon\Carbon::parse($consumo->albaran_fecha)->format('d/m/Y') : '-',
                                'cantidad' => $cantFisica,
                                'total' => $importeConsumo,
                                'url' => route('albaranes.show', $consumo->albaran_id)
                            ];
                        }

                        $lineasBolsa[] = [
                            'descripcion' => $linea->descripcion ?: 'Sin descripción',
                            'medida' => $medida,
                            'precio_unitario' => $unitPriceConMargen, // <--- AQUÍ ESTÁ LA CORRECCIÓN
                            'cant_original' => $cantOriginal,
                            'total_original' => $totalOriginal,
                            'cant_consumida' => $cantConsumida,
                            'total_consumido' => $totalConsumido,
                            'cant_pendiente' => max(0, $cantOriginal - $cantConsumida),
                            'total_pendiente' => max(0, $totalOriginal - $totalConsumido),
                            'detalles_json' => json_encode($detallesConsumo)
                        ];
                    }
                } catch (\Exception $e) {
                    // Si falla la DB, ignoramos para no romper la vista
                }
            }
        @endphp

        @if($esBolsa && count($lineasBolsa) > 0)
        <article class="pedidos-clientes-card" style="border-top: 4px solid #3b82f6;">
            <header class="pedidos-clientes-card__header">
                <div>
                    <h3>Desglose de Consumo (Bolsa)</h3>
                    <p class="card-subtitle">Haz clic en los importes consumidos para ver el desglose de albaranes.</p>
                </div>
            </header>

            <div class="card-body p-0">
                <div class="table-responsive pedidos-clientes-table-wrap m-0">
                    <table class="table pedidos-clientes-table mb-0">
                        <thead style="background-color: #f8fafc;">
                            <tr>
                                <th>Descripción</th>
                                <th class="text-right">P. Unitario</th>
                                <th class="text-right">Cant. Inicial</th>
                                <th class="text-right" style="color: #16a34a;">Cant. Consumida</th>
                                <th class="text-right" style="color: #ea580c;">Cant. Pdte.</th>
                                <th class="text-right">Importe Inicial</th>
                                <th class="text-right" style="color: #16a34a;">Imp. Consumido</th>
                                <th class="text-right" style="color: #ea580c;">Imp. Pdte.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lineasBolsa as $lb)
                                <tr>
                                    <td>{{ $lb['descripcion'] }}</td>
                                    <td class="text-right">€{{ number_format($lb['precio_unitario'], 2, ',', '.') }}</td>
                                    
                                    <!-- Cantidades -->
                                    <td class="text-right">{{ number_format($lb['cant_original'], 2, ',', '.') }} {{ $lb['medida'] }}</td>
                                    
                                    <td class="text-right">
                                        @if($lb['cant_consumida'] > 0)
                                            <span class="drilldown-link" data-detalles="{{ $lb['detalles_json'] }}" data-descripcion="{{ $lb['descripcion'] }}" data-medida="{{ $lb['medida'] }}" title="Ver albaranes imputados">
                                                {{ number_format($lb['cant_consumida'], 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span style="color: #16a34a; font-weight: bold;">
                                                {{ number_format($lb['cant_consumida'], 2, ',', '.') }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-right" style="color: #ea580c; font-weight: bold;">
                                        {{ number_format($lb['cant_pendiente'], 2, ',', '.') }}
                                    </td>

                                    <!-- Importes -->
                                    <td class="text-right">€{{ number_format($lb['total_original'], 2, ',', '.') }}</td>
                                    
                                    <td class="text-right">
                                        @if($lb['total_consumido'] > 0)
                                            <span class="drilldown-link" data-detalles="{{ $lb['detalles_json'] }}" data-descripcion="{{ $lb['descripcion'] }}" data-medida="{{ $lb['medida'] }}" title="Ver albaranes imputados">
                                                €{{ number_format($lb['total_consumido'], 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span style="color: #16a34a; font-weight: bold;">
                                                €{{ number_format($lb['total_consumido'], 2, ',', '.') }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-right" style="color: #ea580c; font-weight: bold;">
                                        €{{ number_format($lb['total_pendiente'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </article>
        @endif
        <!-- ========================================== -->


        <!-- Listado de Albaranes -->
        <article class="pedidos-clientes-card">
            <header class="pedidos-clientes-card__header">
                <div>
                    <h3>Albaranes Asociados</h3>
                    <p class="card-subtitle">Listado de entregas logísticas vinculadas a este pedido.</p>
                </div>
            </header>

            <div class="card-body p-0">
                <div class="table-responsive pedidos-clientes-table-wrap m-0">
                    <table class="table pedidos-clientes-table mb-0">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th class="text-right">Importe</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($albaranes as $albaran)
                                @php
                                    $estadoAlbaran = (string) ($albaran->estado ?: 'pendiente');
                                    $estadoClass = match($estadoAlbaran) {
                                        'facturado' => 'pedido-chip pedido-chip--paid',
                                        'recibido' => 'pedido-chip pedido-chip--partial',
                                        default => 'pedido-chip pedido-chip--pending',
                                    };
                                @endphp
                                <tr>
                                    <td data-label="Número">
                                        @can('albaranes.view')
                                        <a href="{{ route('albaranes.show', $albaran) }}" class="pedido-code-link">{{ $albaran->numero }}</a>
                                        @else
                                        <span class="pedido-muted">{{ $albaran->numero }}</span>
                                        @endcan
                                    </td>
                                    <td data-label="Fecha">
                                        <span class="pedido-date">{{ optional($albaran->fecha)->format('d M Y') ?: '—' }}</span>
                                    </td>
                                    <td data-label="Estado">
                                        <span class="{{ $estadoClass }}">{{ ucfirst($estadoAlbaran) }}</span>
                                    </td>
                                    <td data-label="Importe" class="text-right">
                                        @php
                                            $totalRealAlbaran = (float) ($albaran->total ?? 0);
                                            
                                            // Si es bolsa, confiamos ciegamente en el pivote
                                            if ($pedidoCliente->bolsa) {
                                                $importeImputado = isset($albaran->pivot) ? (float) $albaran->pivot->importe_imputado : $totalRealAlbaran;
                                            } else {
                                                $importeImputado = (isset($albaran->pivot) && $albaran->pivot->importe_imputado > 0) 
                                                    ? (float) $albaran->pivot->importe_imputado 
                                                    : $totalRealAlbaran;
                                            }
                                        @endphp
                                        
                                        <strong class="pedido-total">€{{ number_format($importeImputado, 2, ',', '.') }}</strong>
                                        
                                        @if($importeImputado < $totalRealAlbaran)
                                            <br>
                                            <small style="color: #64748b; font-size: 0.8em;" title="Total real del albarán: €{{ number_format($totalRealAlbaran, 2, ',', '.') }}">
                                                (de €{{ number_format($totalRealAlbaran, 2, ',', '.') }})
                                            </small>
                                        @endif
                                    </td>
                                    <td data-label="Acciones" class="text-center">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            @can('albaranes.view')
                                            <a href="{{ route('albaranes.show', $albaran) }}" class="pedido-action-btn pedido-action-btn--soft" title="Ver PDF">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </a>
                                            @endcan

                                            @can('albaranes.manage')
                                                @if($estadoAlbaran !== 'facturado')
                                                    <!-- BOTÓN DIRECTO PARA PASAR A FACTURADO -->
                                                    <form action="{{ route('albaranes.estado.update', $albaran) }}" method="POST" onsubmit="return confirm('¿Marcar este albarán como FACTURADO? Se bloqueará y el importe se sumará a la facturación del pedido.');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="facturado">
                                                        <button type="submit" class="pedido-action-btn" style="color: #28a745; border: 1px solid #28a745; background: transparent; padding: 4px 8px; border-radius: 4px;" title="Marcar como Facturado">
                                                            <i class="fas fa-check-double"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <!-- BOTÓN DE DESVINCULAR -->
                                                <form action="{{ route('albaranes.desvincular', $albaran) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres desvincular este albarán DE ESTE PEDIDO? El saldo imputado se liberará.');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="pedido_id" value="{{ $pedidoCliente->id }}">
                                                    
                                                    <button type="submit" class="pedido-action-btn" style="color: #dc3545; border: 1px solid #dc3545; background: transparent; padding: 4px 8px; border-radius: 4px;" title="Desvincular del pedido">
                                                        <i class="fas fa-unlink"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="pedido-empty-state">
                                            <i class="fas fa-file-invoice"></i>
                                            <h4>No hay albaranes asociados</h4>
                                            <p>Puedes crear uno nuevo con el botón "Agregar Albarán".</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($albaranes->hasPages())
                    <div class="pedidos-clientes-pagination p-3">
                        {{ $albaranes->links() }}
                    </div>
                @endif
            </div>
        </article>

        <!-- MODAL DE DESGLOSE (DRILL-DOWN) -->
        <div class="modal fade modal-desglose" id="modalDesgloseLinea" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-list-ul" style="color: #1f74dd; margin-right: 8px;"></i> Desglose de Consumo
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="p-3" style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <strong style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Artículo / Servicio</strong>
                            <div id="modalDesgloseDescripcion" style="color: #0f172a; font-weight: 700; font-size: 1.05rem;"></div>
                        </div>
                        <div class="table-responsive m-0">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Albarán</th>
                                        <th>Fecha</th>
                                        <th class="text-right">Cant. Imputada</th>
                                        <th class="text-right">Importe Imputado</th>
                                    </tr>
                                </thead>
                                <tbody id="modalDesgloseBody">
                                    <!-- Inyectado por JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f8fafc;">
                        <button type="button" class="btn btn-secondary" style="background-color: #e2e8f0; color: #475569; border: none; font-weight: bold;" data-dismiss="modal">Cerrar detalle</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- FIN MODAL -->

    </section>
    @include('partials.document-activity-chatter', ['activities' => $activities ?? collect()])
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Formateadores locales para pintar el desglose en el JS
            const numFormatter = new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const moneyFormatter = new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Capturar clics en los enlaces de Drill-down
            document.querySelectorAll('.drilldown-link').forEach(link => {
                link.addEventListener('click', function() {
                    const detalles = JSON.parse(this.dataset.detalles);
                    const descripcion = this.dataset.descripcion;
                    const medida = this.dataset.medida;

                    // Poblar la cabecera
                    document.getElementById('modalDesgloseDescripcion').textContent = descripcion;

                    // Poblar la tabla
                    const tbody = document.getElementById('modalDesgloseBody');
                    tbody.innerHTML = '';

                    if (detalles.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center" style="color: #64748b; padding: 2rem;">No se encontraron movimientos para esta línea.</td></tr>';
                    } else {
                        let totalCant = 0;
                        let totalImp = 0;
                        
                        detalles.forEach(d => {
                            totalCant += d.cantidad;
                            totalImp += d.total;

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>
                                    <a href="${d.url}" target="_blank" style="color: #1f74dd; font-weight: 800; text-decoration: none;">
                                        ${d.numero} <i class="fas fa-external-link-alt" style="font-size: 0.7em; margin-left: 2px;"></i>
                                    </a>
                                </td>
                                <td style="color: #475569;">${d.fecha}</td>
                                <td class="text-right" style="color: #334155;">${numFormatter.format(d.cantidad)} <small style="color: #94a3b8;">${medida}</small></td>
                                <td class="text-right" style="color: #16a34a; font-weight: 800;">€${moneyFormatter.format(d.total)}</td>
                            `;
                            tbody.appendChild(tr);
                        });

                        // Fila de Total
                        const trTotal = document.createElement('tr');
                        trTotal.style.backgroundColor = '#f1f5f9';
                        trTotal.innerHTML = `
                            <td colspan="2" class="text-right" style="color: #475569; font-weight: 800; font-size: 0.85rem; text-transform: uppercase;">Total Imputado:</td>
                            <td class="text-right" style="color: #0f172a; font-weight: 800;">${numFormatter.format(totalCant)} <small style="color: #64748b;">${medida}</small></td>
                            <td class="text-right" style="color: #16a34a; font-weight: 900;">€${moneyFormatter.format(totalImp)}</td>
                        `;
                        tbody.appendChild(trTotal);
                    }

                    // Abrir el Modal de Bootstrap
                    if (window.$) {$('#modalDesgloseLinea').modal('show');
                    } else {
                        console.error("jQuery o Bootstrap.js no están cargados. El modal no se puede abrir.");
                    }
                });
            });
        });
    </script>
@endsection