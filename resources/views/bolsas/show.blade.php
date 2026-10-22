@extends('adminlte::page')

@section('title', $titulo ?? 'Detalle de Bolsa - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/bolsas.css'])
@endsection

@section('content')
    @php
        $esAbierta = $bolsa->total <= 0;
        $acumulado = $bolsa->totalAcumulado();
    @endphp

    <section class="bolsa-ui">
        <header class="bolsa-hero">
            <div>
                <div class="bolsa-eyebrow">{{ $breadcrumb ?? 'Bolsas · Detalle' }}</div>
                <h1>{{ $titulo ?? 'Detalle de Bolsa' }}</h1>
                <p>{{ $bolsa->cliente->empresa_nombre ?? 'Sin cliente' }}</p>
            </div>
            <div class="bolsa-hero-actions">
                <a href="{{ route('bolsas.index') }}" class="bolsa-btn bolsa-btn--soft">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
                </a>

                @can('pedidos.delete')
                    @if($bolsa->partidas->isEmpty())
                        <form action="{{ route('bolsas.destroy', $bolsa) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Seguro que quieres borrar esta bolsa global vacía?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bolsa-btn bolsa-btn--danger">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i> Eliminar
                            </button>
                        </form>
                    @endif
                @endcan

                @can('pedidos.manage')
                    <a href="{{ route('bolsas.edit', $bolsa) }}" class="bolsa-btn bolsa-btn--ghost">
                        <i class="fas fa-pen" aria-hidden="true"></i> Editar
                    </a>
                    <a href="{{ route('pedidos-clientes.create', ['cliente_id' => $bolsa->id_cliente, 'bolsa_id' => $bolsa->id, 'bolsa' => 1]) }}" class="bolsa-btn bolsa-btn--primary">
                        <i class="fas fa-plus" aria-hidden="true"></i> Nueva Partida
                    </a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="bolsa-alert bolsa-alert--success" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="bolsa-alert bolsa-alert--error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ session('error') }}
            </div>
        @endif

        {{-- KPIs --}}
        <div class="bolsa-kpis">
            <article class="bolsa-kpi" style="--kpi: #1f74dd;">
                <span class="bolsa-kpi__label">Total acumulado</span>
                <p class="bolsa-kpi__value">{{ number_format($acumulado, 2, ',', '.') }} €</p>
            </article>

            @if($esAbierta)
                <article class="bolsa-kpi bolsa-kpi--wide" style="--kpi: #3b82f6;">
                    <i class="fas fa-unlock-alt" aria-hidden="true"></i>
                    <div>
                        <h4>Bolsa Abierta (facturación continua)</h4>
                        <p>Esta bolsa no tiene tope inicial. Puedes seguir vinculando partidas hasta que decidas cerrarla.</p>
                    </div>
                </article>
            @else
                <article class="bolsa-kpi" style="--kpi: #64748b;">
                    <span class="bolsa-kpi__label">Tope inicial (límite)</span>
                    <p class="bolsa-kpi__value">{{ number_format($bolsa->total, 2, ',', '.') }} €</p>
                </article>
                <article class="bolsa-kpi" style="--kpi: #10b981;">
                    <span class="bolsa-kpi__label">Saldo disponible</span>
                    <p class="bolsa-kpi__value">{{ number_format($bolsa->saldoDisponible(), 2, ',', '.') }} €</p>
                </article>
            @endif
        </div>

        <div class="bolsa-layout">
            {{-- Datos de la bolsa --}}
            <aside class="bolsa-panel">
                <div class="bolsa-panel__head">
                    <div><h2><i class="fas fa-info-circle mr-1" aria-hidden="true"></i> Datos de la bolsa</h2></div>
                </div>

                <ul class="bolsa-info">
                    <li><strong>Cliente</strong> <span>{{ $bolsa->cliente->empresa_nombre ?? 'N/A' }}</span></li>
                    <li><strong>Nº referencia</strong> <span>{{ $bolsa->numero ?: 'Sin referencia' }}</span></li>
                    <li><strong>Tipo / Grupo</strong> <span><span class="bolsa-chip bolsa-chip--info">{{ $bolsa->tipo_bolsa }}</span></span></li>
                    <li><strong>Fecha de alta</strong> <span>{{ optional(\Carbon\Carbon::parse($bolsa->fecha))->format('d/m/Y') }}</span></li>
                </ul>

                @if(isset($bolsasFungibles) && $bolsasFungibles->isNotEmpty())
                    <div class="bolsa-fungibles">
                        <strong>Otras bolsas de tipo "{{ $bolsa->tipo_bolsa }}"</strong>
                        <p>Comparten grupo y cliente. Puedes reutilizar sus saldos sobrantes entre ellas.</p>
                        @foreach($bolsasFungibles as $fungible)
                            @if($fungible->id !== $bolsa->id)
                                <a href="{{ route('bolsas.show', $fungible->id) }}" class="bolsa-chip bolsa-chip--muted" style="text-decoration: none;">
                                    {{ $fungible->numero ?: '#'.$fungible->id }} · {{ number_format($fungible->saldoDisponible(), 2, ',', '.') }} €
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </aside>

            {{-- Partidas --}}
            <section class="bolsa-panel">
                <div class="bolsa-panel__head">
                    <div>
                        <h2><i class="fas fa-list mr-1" aria-hidden="true"></i> Partidas (pedidos) vinculadas</h2>
                        <p>{{ $bolsa->partidas->count() }} {{ \Illuminate\Support\Str::plural('partida', $bolsa->partidas->count()) }} · pulsa una fila para ver sus albaranes.</p>
                    </div>
                </div>

                <div class="bolsa-table-wrap">
                    <table class="bolsa-table">
                        <thead>
                            <tr>
                                <th>Nº Pedido</th>
                                <th>Fecha</th>
                                <th>CC / OT</th>
                                <th class="is-right">Total pedido</th>
                                <th class="is-center">Estado</th>
                                <th class="is-center">Albaranes</th>
                                <th class="is-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bolsa->partidas as $partida)
                                @php
                                    $albaranesPartida = $partida->albaranesEfectivos()->map(function ($albaran) {
                                        return (object) [
                                            'albaran_id' => $albaran->id,
                                            'albaran' => $albaran,
                                            'importe_total' => $albaran->pivot->importe_imputado ?? $albaran->pivot->importe ?? $albaran->total ?? 0,
                                        ];
                                    })->sortByDesc(fn ($fila) => optional($fila->albaran->fecha)->timestamp)->values();

                                    $estadoClass = match($partida->estado) {
                                        'facturado' => 'bolsa-chip--ok',
                                        'facturado_parcial' => 'bolsa-chip--warn',
                                        default => 'bolsa-chip--muted',
                                    };
                                    $estadoLabel = str_replace('_', ' ', $partida->estado ?: 'pendiente');
                                @endphp

                                <tr class="bolsa-row-toggle" data-toggle="collapse" data-target="#partida-albaranes-{{ $partida->id }}" aria-expanded="false">
                                    <td class="bolsa-code">{{ $partida->numero_pedido }}</td>
                                    <td>{{ optional(\Carbon\Carbon::parse($partida->fecha_pedido))->format('d/m/Y') }}</td>
                                    <td><span class="bolsa-chip bolsa-chip--muted">{{ $partida->ot ?: 'Sin CC' }}</span></td>
                                    <td class="is-right"><strong>{{ number_format((float) $partida->total, 2, ',', '.') }} €</strong></td>
                                    <td class="is-center"><span class="bolsa-chip {{ $estadoClass }}">{{ $estadoLabel }}</span></td>
                                    <td class="is-center">
                                        <span class="bolsa-btn bolsa-btn--ghost bolsa-btn--sm">
                                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                            {{ $albaranesPartida->count() }} {{ \Illuminate\Support\Str::plural('albarán', $albaranesPartida->count()) }}
                                        </span>
                                    </td>
                                    <td class="is-center" onclick="event.stopPropagation();">
                                        <div class="bolsa-row-actions">
                                            <a href="{{ route('pedidos-clientes.albaranes', $partida->id) }}" class="bolsa-btn bolsa-btn--ghost bolsa-btn--sm" title="Ver desglose y albaranes de esta partida">
                                                <i class="fas fa-eye" aria-hidden="true"></i> Detalle
                                            </a>

                                            @can('pedidos.manage')
                                                <a href="{{ route('pedidos-clientes.edit', $partida->id) }}" class="bolsa-btn bolsa-btn--soft bolsa-btn--sm bolsa-btn--icon" title="Editar esta partida" aria-label="Editar partida">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('bolsas.quitar-partida', ['bolsa' => $bolsa->id, 'pedido' => $partida->id]) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres desvincular esta partida de la bolsa global? El pedido pasará a ser una bolsa suelta.');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="bolsa-btn bolsa-btn--danger bolsa-btn--sm bolsa-btn--icon" title="Desvincular de esta bolsa global" aria-label="Desvincular partida">
                                                        <i class="fas fa-unlink"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>

                                <tr class="collapse bolsa-subrow" id="partida-albaranes-{{ $partida->id }}">
                                    <td colspan="7">
                                        <div class="bolsa-subtable">
                                            @if($albaranesPartida->isEmpty())
                                                <p class="mb-0 text-muted" style="font-size: .9rem;">
                                                    <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> Esta partida todavía no tiene ningún albarán.
                                                </p>
                                            @else
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th>Número</th>
                                                            <th>Fecha</th>
                                                            <th class="is-right">Importe imputado</th>
                                                            <th class="is-center">Estado</th>
                                                            <th class="is-center">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($albaranesPartida as $fila)
                                                            @php
                                                                $estadoAlbClass = match($fila->albaran->estado) {
                                                                    'facturado' => 'bolsa-chip--ok',
                                                                    'facturado_parcial' => 'bolsa-chip--warn',
                                                                    'recibido', 'entregado' => 'bolsa-chip--info',
                                                                    default => 'bolsa-chip--muted',
                                                                };
                                                            @endphp
                                                            <tr>
                                                                <td class="bolsa-code">{{ $fila->albaran->numero }}</td>
                                                                <td>{{ optional($fila->albaran->fecha)->format('d/m/Y') ?: '-' }}</td>
                                                                <td class="is-right"><strong>{{ number_format((float) $fila->importe_total, 2, ',', '.') }} €</strong></td>
                                                                <td class="is-center"><span class="bolsa-chip {{ $estadoAlbClass }}">{{ str_replace('_', ' ', $fila->albaran->estado ?: 'pendiente') }}</span></td>
                                                                <td class="is-center">
                                                                    <a href="{{ route('albaranes.show', $fila->albaran_id) }}" class="bolsa-btn bolsa-btn--ghost bolsa-btn--sm">
                                                                        <i class="fas fa-eye" aria-hidden="true"></i> Ver detalle
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
                                    <td colspan="7">
                                        <div class="bolsa-empty" style="border: 0; box-shadow: none;">
                                            <i class="fas fa-folder-open" aria-hidden="true"></i>
                                            <h4>Esta bolsa global aún no tiene partidas vinculadas</h4>
                                            <p class="mb-0">Crea un "Pedido Bolsa" para este cliente y asígnalo a esta bolsa para ir consumiendo el saldo.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        @include('partials.document-activity-chatter', ['activities' => $activities ?? collect()])
    </section>
@endsection