@extends('adminlte::page')

@section('title', $titulo ?? 'Bolsas Globales - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/bolsas.css'])
@endsection

@section('content')
    <section class="bolsa-ui">
        <header class="bolsa-hero">
            <div>
                <div class="bolsa-eyebrow">{{ $breadcrumb ?? 'Gestión de bolsas' }}</div>
                <h1>{{ $titulo ?? 'Gestión de Bolsas Globales' }}</h1>
                <p>Controla el saldo contratado por cliente y las partidas que lo consumen.</p>
            </div>
            <div class="bolsa-hero-actions">
                <a href="{{ route('pedidos-clientes.index') }}" class="bolsa-btn bolsa-btn--soft">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Pedidos
                </a>
                @can('pedidos.manage')
                    <a href="{{ route('bolsas.create') }}" class="bolsa-btn bolsa-btn--primary">
                        <i class="fas fa-plus" aria-hidden="true"></i> Nueva Bolsa Global
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
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i> {{ session('error') }}
            </div>
        @endif

        <div class="bolsa-grid">
            @forelse($bolsas as $bolsa)
                @php
                    $topeInicial = (float) $bolsa->total;
                    $acumulado = $bolsa->totalAcumulado();
                    $esAbierta = $topeInicial <= 0;

                    if ($esAbierta) {
                        $colorHex = '#3b82f6';
                        $pctGrafico = 100;
                    } else {
                        $pctConsumido = $topeInicial > 0 ? ($acumulado / $topeInicial) * 100 : 100;
                        $pctGrafico = min(100, $pctConsumido);

                        if ($pctConsumido < 70) $colorHex = '#10b981';
                        elseif ($pctConsumido < 95) $colorHex = '#f59e0b';
                        else $colorHex = '#ef4444';
                    }
                @endphp

                <article class="bolsa-card">
                    <div class="bolsa-card__head">
                        <span class="bolsa-chip {{ $esAbierta ? 'bolsa-chip--open' : '' }}">
                            <i class="fas {{ $esAbierta ? 'fa-infinity' : 'fa-layer-group' }}" aria-hidden="true"></i>
                            {{ mb_strtoupper($bolsa->tipo_bolsa) }}
                        </span>
                        <h3 class="bolsa-card__client" title="{{ $bolsa->cliente->empresa_nombre ?? 'Sin cliente' }}">
                            {{ $bolsa->cliente->empresa_nombre ?? 'Sin cliente' }}
                        </h3>
                        <p class="bolsa-card__ref">{{ $bolsa->numero ?: 'Bolsa #' . $bolsa->id }}</p>
                    </div>

                    <div class="bolsa-ring">
                        <div class="bolsa-ring__chart" style="background: conic-gradient({{ $colorHex }} {{ $pctGrafico }}%, #f1f5f9 0);">
                            <div class="bolsa-ring__inner">
                                <span class="bolsa-ring__value" style="color: {{ $colorHex }}">{{ number_format($acumulado, 0, ',', '.') }}<small>€</small></span>
                                <span class="bolsa-ring__label">Acumulado</span>
                            </div>
                        </div>
                    </div>

                    <div class="bolsa-stats">
                        @if($esAbierta)
                            <div class="bolsa-stat bolsa-stat--wide">
                                <span class="bolsa-stat__label" style="color: #0284c7;">Modalidad</span>
                                <p class="bolsa-stat__value" style="color: #0369a1;"><i class="fas fa-unlock-alt mr-1" aria-hidden="true"></i> Bolsa Abierta (sin tope)</p>
                            </div>
                        @else
                            <div class="bolsa-stat">
                                <span class="bolsa-stat__label">Tope contratado</span>
                                <p class="bolsa-stat__value">{{ number_format($topeInicial, 2, ',', '.') }} €</p>
                            </div>
                            <div class="bolsa-stat" style="background-color: {{ $colorHex }}15; border-color: {{ $colorHex }}40;">
                                <span class="bolsa-stat__label" style="color: {{ $colorHex }};">Saldo libre</span>
                                <p class="bolsa-stat__value" style="color: {{ $colorHex }};">{{ number_format($bolsa->saldoDisponible(), 2, ',', '.') }} €</p>
                            </div>
                        @endif
                    </div>

                    <div class="bolsa-card__actions">
                        <a href="{{ route('bolsas.show', $bolsa) }}" class="bolsa-btn bolsa-btn--ghost bolsa-btn--grow">
                            <i class="fas fa-eye" aria-hidden="true"></i> Ver partidas
                        </a>
                        @can('pedidos.manage')
                            <a href="{{ route('bolsas.edit', $bolsa) }}" class="bolsa-btn bolsa-btn--soft bolsa-btn--icon" title="Editar bolsa" aria-label="Editar bolsa">
                                <i class="fas fa-pen"></i>
                            </a>
                        @endcan

                        @if($bolsa->partidas_count === 0)
                            @can('pedidos.delete')
                                <form action="{{ route('bolsas.destroy', $bolsa) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta bolsa global? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bolsa-btn bolsa-btn--danger bolsa-btn--icon" title="Eliminar bolsa vacía" aria-label="Eliminar bolsa vacía">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </article>
            @empty
                <div class="bolsa-empty">
                    <i class="fas fa-box-open" aria-hidden="true"></i>
                    <h4>No hay bolsas globales registradas</h4>
                    <p>Crea la primera para empezar a controlar saldos por cliente.</p>
                    @can('pedidos.manage')
                        <a href="{{ route('bolsas.create') }}" class="bolsa-btn bolsa-btn--primary">
                            <i class="fas fa-plus" aria-hidden="true"></i> Nueva Bolsa Global
                        </a>
                    @endcan
                </div>
            @endforelse
        </div>

        @if($bolsas->hasPages())
            <div class="bolsa-pagination">{{ $bolsas->links('pagination::bootstrap-4') }}</div>
        @endif
    </section>
@endsection