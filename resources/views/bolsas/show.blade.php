@extends('adminlte::page')

@section('title', $titulo ?? 'Detalle de Bolsa - MoncobraCRM')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <p class="text-muted mb-0" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">
                {{ $breadcrumb ?? 'Inicio / Bolsas / Detalle' }}
            </p>
            <h1 class="m-0 text-dark font-weight-bold">
                <i class="fas fa-briefcase text-primary mr-2"></i> {{ $titulo ?? 'Detalle de Bolsa' }}
            </h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('bolsas.index') }}" class="btn btn-outline-secondary font-weight-bold mr-2">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
            
            @if($bolsa->partidas->isEmpty())
                <form action="{{ route('bolsas.destroy', $bolsa) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que quieres borrar esta bolsa global vacía?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger font-weight-bold mr-2">
                        <i class="fas fa-trash-alt mr-1"></i> Eliminar
                    </button>
                </form>
            @endif

            <a href="{{ route('pedidos-clientes.create', ['cliente_id' => $bolsa->id_cliente, 'bolsa_id' => $bolsa->id, 'bolsa' => 1]) }}" class="btn btn-primary font-weight-bold">
                <i class="fas fa-plus mr-1"></i> Nueva Partida
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success shadow-sm" style="border-radius: 8px; font-weight: bold;">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif

    <!-- KPIs de Saldo Dinámicos -->
    @php
        $esAbierta = $bolsa->total <= 0;
        $acumulado = $bolsa->totalAcumulado();
    @endphp

    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-white shadow-sm" style="border-radius: 12px; border-bottom: 4px solid #1f74dd;">
                <div class="inner">
                    <h3 style="color: #17385d;">{{ number_format($acumulado, 4, ',', '.') }} €</h3>
                    <p class="text-muted font-weight-bold mb-0">TOTAL ACUMULADO</p>
                </div>
                <div class="icon"><i class="fas fa-layer-group text-primary" style="opacity: 0.15;"></i></div>
            </div>
        </div>

        @if($esAbierta)
            <div class="col-md-8">
                <div class="small-box bg-white shadow-sm d-flex align-items-center px-4" style="border-radius: 12px; border-bottom: 4px solid #3b82f6; height: calc(100% - 1rem);">
                    <div class="mr-4"><i class="fas fa-unlock-alt fa-3x text-primary" style="opacity: 0.2;"></i></div>
                    <div>
                        <h4 style="color: #1d4ed8; font-weight: 800; margin: 0;">Bolsa Abierta (Facturación Continua)</h4>
                        <p class="text-muted mb-0">Esta bolsa no tiene tope inicial. Puedes seguir vinculando partidas indefinidamente hasta que decidas cerrarla.</p>
                    </div>
                </div>
            </div>
        @else
            <div class="col-md-4">
                <div class="small-box bg-white shadow-sm" style="border-radius: 12px; border-bottom: 4px solid #64748b;">
                    <div class="inner">
                        <h3 style="color: #475569;">{{ number_format($bolsa->total, 4, ',', '.') }} €</h3>
                        <p class="text-muted font-weight-bold mb-0">TOPE INICIAL (LÍMITE)</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-contract text-secondary" style="opacity: 0.15;"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-white shadow-sm" style="border-radius: 12px; border-bottom: 4px solid #10b981;">
                    <div class="inner">
                        <h3 style="color: #059669;">{{ number_format($bolsa->saldoDisponible(), 4, ',', '.') }} €</h3>
                        <p class="text-muted font-weight-bold mb-0">SALDO DISPONIBLE</p>
                    </div>
                    <div class="icon"><i class="fas fa-piggy-bank text-success" style="opacity: 0.15;"></i></div>
                </div>
            </div>
        @endif
    </div>

    <div class="row">
        <!-- Información General -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm" style="border-radius: 12px; border: none; height: 100%;">
                <div class="card-header bg-white" style="border-bottom: 2px solid #f4f6f9; border-radius: 12px 12px 0 0;">
                    <h3 class="card-title font-weight-bold text-secondary" style="font-size: 1.1rem;">
                        <i class="fas fa-info-circle text-primary mr-1"></i> Datos de la Bolsa
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0 text-muted">
                            <strong>Cliente:</strong> <span class="text-dark float-right font-weight-bold">{{ $bolsa->cliente->empresa_nombre ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item px-0 text-muted">
                            <strong>Nº Referencia:</strong> <span class="text-dark float-right">{{ $bolsa->numero ?: 'Sin referencia' }}</span>
                        </li>
                        <li class="list-group-item px-0 text-muted">
                            <strong>Tipo / Grupo:</strong> <span class="badge badge-info float-right" style="font-size: 0.9rem;">{{ $bolsa->tipo_bolsa }}</span>
                        </li>
                        <li class="list-group-item px-0 text-muted">
                            <strong>Fecha de Alta:</strong> <span class="text-dark float-right">{{ optional(\Carbon\Carbon::parse($bolsa->fecha))->format('d/m/Y') }}</span>
                        </li>
                    </ul>
                    
                    @if(isset($bolsasFungibles) && $bolsasFungibles->count() > 1)
                        <div class="mt-4 p-3 rounded" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <strong style="color: #475569; font-size: 0.85rem; text-transform: uppercase;">Otras bolsas de tipo "{{ $bolsa->tipo_bolsa }}"</strong>
                            <p class="text-muted mb-2" style="font-size: 0.8rem;">Estas bolsas comparten el mismo grupo y cliente. Puedes reutilizar sus saldos sobrantes entre ellas.</p>
                            <div class="mt-2">
                                @foreach($bolsasFungibles as $fungible)
                                    @if($fungible->id !== $bolsa->id)
                                        <a href="{{ route('bolsas.show', $fungible->id) }}" class="badge badge-secondary mr-1 mb-1 p-2" style="font-size: 0.8rem; text-decoration: none;">
                                            {{ $fungible->numero ?: '#'.$fungible->id }} ({{ number_format($fungible->total, 0, ',', '.') }}€)
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Partidas (Pedidos) Vinculadas -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm" style="border-radius: 12px; border: none; height: 100%;">
                <div class="card-header bg-white" style="border-bottom: 2px solid #f4f6f9; border-radius: 12px 12px 0 0;">
                    <h3 class="card-title font-weight-bold text-secondary" style="font-size: 1.1rem;">
                        <i class="fas fa-list text-primary mr-1"></i> Partidas (Pedidos) Vinculadas
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background-color: #f8fafc; color: #475569;">
                                <tr>
                                    <th class="border-top-0">Nº Pedido</th>
                                    <th class="border-top-0">Fecha</th>
                                    <th class="border-top-0">CC / OT</th>
                                    <th class="border-top-0 text-right">Total Pedido</th>
                                    <th class="border-top-0 text-center">Estado</th>
                                    <th class="border-top-0 text-center">Albaranes</th>
                                    <th class="border-top-0 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bolsa->partidas as $partida)
                                    @php
                                        // Albaranes de esta partida con lo que REALMENTE se le ha
                                        // imputado (Bolsa V3: AlbaranLineaPartida), no el pivote V1.
                                        $albaranesPartida = \App\Models\AlbaranLineaPartida::where('pedido_id', $partida->id)
                                            ->selectRaw('albaran_id, SUM(importe) as importe_total')
                                            ->groupBy('albaran_id')
                                            ->with('albaran:id,numero,fecha,estado,total')
                                            ->get()
                                            ->filter(fn ($fila) => $fila->albaran !== null)
                                            ->sortByDesc(fn ($fila) => optional($fila->albaran->fecha)->timestamp)
                                            ->values();
                                    @endphp
                                    <tr style="cursor: pointer;" data-toggle="collapse" data-target="#partida-albaranes-{{ $partida->id }}" aria-expanded="false">
                                        <td class="align-middle font-weight-bold" style="color: #1f74dd;">
                                            {{ $partida->numero_pedido }}
                                        </td>
                                        <td class="align-middle text-muted">
                                            {{ optional(\Carbon\Carbon::parse($partida->fecha_pedido))->format('d/m/Y') }}
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge" style="background-color: #e2e8f0; color: #475569; font-size: 0.85rem;">{{ $partida->ot ?: 'Sin CC' }}</span>
                                        </td>
                                        <td class="align-middle text-right font-weight-bold text-dark">
                                            {{ number_format((float)$partida->total, 2, ',', '.') }} €
                                        </td>
                                        <td class="align-middle text-center">
                                            @php
                                                $estadoClass = match($partida->estado) {
                                                    'facturado' => 'badge-success',
                                                    'facturado_parcial' => 'badge-warning',
                                                    default => 'badge-secondary',
                                                };
                                                $estadoLabel = str_replace('_', ' ', $partida->estado ?: 'pendiente');
                                            @endphp
                                            <span class="badge {{ $estadoClass }} text-uppercase px-2 py-1">{{ $estadoLabel }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <button type="button" class="btn btn-sm btn-light border font-weight-bold" style="color: #1f74dd;">
                                                <i class="fas fa-chevron-down mr-1" style="font-size: 0.7rem;"></i>
                                                {{ $albaranesPartida->count() }} {{ \Illuminate\Support\Str::plural('albarán', $albaranesPartida->count()) }}
                                            </button>
                                        </td>
                                        <td class="align-middle text-center" onclick="event.stopPropagation();">
                                            <div class="d-flex justify-content-center" style="gap: 6px;">
                                                <a href="{{ route('pedidos-clientes.albaranes', $partida->id) }}" class="btn btn-sm btn-light text-primary border" title="Ver desglose y albaranes de esta partida">
                                                    <i class="fas fa-eye"></i> Detalle
                                                </a>
                                                
                                                @can('pedidos.manage')
                                                <form action="{{ route('bolsas.quitar-partida', ['bolsa' => $bolsa->id, 'pedido' => $partida->id]) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres desvincular esta partida de la bolsa global? El pedido pasará a ser una bolsa suelta.');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-light text-danger border" title="Desvincular de esta bolsa global">
                                                        <i class="fas fa-unlink"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                                <form action="{{ route('pedidos-clientes.edit', $partida->id) }}" method="GET">
                                                    <button type="submit" class="btn btn-sm btn-light text-secondary border" title="Editar esta partida">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="partida-albaranes-{{ $partida->id }}">
                                        <td colspan="7" class="p-0" style="border-top: none;">
                                            <div class="p-3" style="background-color: #f8fafc;">
                                                @if($albaranesPartida->isEmpty())
                                                    <p class="text-muted mb-0" style="font-size: 0.9rem;">
                                                        <i class="fas fa-info-circle mr-1"></i> Esta partida todavía no tiene ningún albarán.
                                                    </p>
                                                @else
                                                    <table class="table table-sm table-borderless mb-0" style="background: transparent;">
                                                        <thead>
                                                            <tr style="font-size: 0.75rem; text-transform: uppercase; color: #64748b;">
                                                                <th>Número</th>
                                                                <th>Fecha</th>
                                                                <th class="text-right">Importe imputado</th>
                                                                <th class="text-center">Estado</th>
                                                                <th class="text-center">Acciones</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($albaranesPartida as $fila)
                                                                <tr>
                                                                    <td class="font-weight-bold" style="color: #1f74dd;">{{ $fila->albaran->numero }}</td>
                                                                    <td class="text-muted">{{ optional($fila->albaran->fecha)->format('d/m/Y') ?: '-' }}</td>
                                                                    <td class="text-right font-weight-bold">{{ number_format((float) $fila->importe_total, 4, ',', '.') }} €</td>
                                                                    <td class="text-center">
                                                                        @php
                                                                            $estadoAlbClass = match($fila->albaran->estado) {
                                                                                'entregado' => 'badge-success',
                                                                                'recibido' => 'badge-info',
                                                                                default => 'badge-secondary',
                                                                            };
                                                                        @endphp
                                                                        <span class="badge {{ $estadoAlbClass }} text-uppercase">{{ $fila->albaran->estado ?: 'pendiente' }}</span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <a href="{{ route('albaranes.show', $fila->albaran_id) }}" class="btn btn-sm btn-light border" title="Ver detalle del albarán">
                                                                            <i class="fas fa-eye"></i> Ver detalle
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-folder-open mb-3" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                            <h5>Esta bolsa global aún no tiene partidas vinculadas</h5>
                                            <p class="mb-0">Crea un "Pedido Bolsa" para este cliente y asígnalo a esta bolsa global para ir consumiendo el saldo.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection