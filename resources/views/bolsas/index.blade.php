@extends('adminlte::page')

@section('title', $titulo ?? 'Bolsas Globales - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/bolsas.css'])
    <style>
        .bolsa-toolbar {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
        }
        .bolsa-search-box {
            flex: 1 1 320px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 6px 12px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .bolsa-search-box:focus-within {
            border-color: #1f74dd;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(31, 116, 221, 0.12);
        }
        .bolsa-search-box input {
            border: none;
            background: transparent;
            width: 100%;
            font-size: 0.92rem;
            color: #0f172a;
            outline: none;
        }
        .bolsa-filter-pills {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .bolsa-filter-check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #475569;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            user-select: none;
            margin: 0;
            transition: all 0.15s ease;
        }
        .bolsa-filter-check:hover {
            border-color: #94a3b8;
        }
        .bolsa-filter-check input {
            accent-color: #1f74dd;
            width: 15px;
            height: 15px;
            cursor: pointer;
        }
        .bolsa-filter-check.is-active-open {
            background: #eff6ff;
            border-color: #60a5fa;
            color: #1d4ed8;
        }
        .bolsa-filter-check.is-active-closed {
            background: #ecfdf5;
            border-color: #34d399;
            color: #047857;
        }
    </style>
@endsection

@section('content')
    @php
        $verAbiertas = $verAbiertas ?? true;
        $verCerradas = $verCerradas ?? true;
        $buscar = $buscar ?? '';
    @endphp

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

        {{-- BARRA DE BÚSQUEDA Y FILTRO PERSISTENTE DE MODALIDAD --}}
        <form method="GET" action="{{ route('bolsas.index') }}" id="bolsas-filter-form" class="bolsa-toolbar">
            <input type="hidden" name="filter_applied" value="1">

            <div class="bolsa-search-box">
                <i class="fas fa-search text-muted" aria-hidden="true"></i>
                <input
                    type="search"
                    name="buscar"
                    value="{{ $buscar }}"
                    placeholder="Buscar por cliente, nº referencia, tipo/grupo, importe o fecha (dd/mm/aaaa)..."
                    aria-label="Buscar bolsas"
                >
                @if($buscar !== '')
                    <a href="{{ route('bolsas.index', ['filter_applied' => 1, 'ver_abiertas' => $verAbiertas ? 1 : 0, 'ver_cerradas' => $verCerradas ? 1 : 0]) }}"
                       class="text-muted" title="Limpiar texto de búsqueda" style="text-decoration: none;">
                        <i class="fas fa-times-circle"></i>
                    </a>
                @endif
            </div>

            <div class="bolsa-filter-pills">
                <span class="text-muted small font-weight-bold mr-1">
                    <i class="fas fa-sliders-h mr-1"></i> Mostrar:
                </span>

                <label class="bolsa-filter-check {{ $verAbiertas ? 'is-active-open' : '' }}" title="Tu selección se guarda automáticamente">
                    <input type="checkbox" name="ver_abiertas" value="1" class="js-auto-filter" {{ $verAbiertas ? 'checked' : '' }}>
                    <i class="fas fa-infinity"></i> Bolsas Abiertas
                </label>

                <label class="bolsa-filter-check {{ $verCerradas ? 'is-active-closed' : '' }}" title="Tu selección se guarda automáticamente">
                    <input type="checkbox" name="ver_cerradas" value="1" class="js-auto-filter" {{ $verCerradas ? 'checked' : '' }}>
                    <i class="fas fa-lock"></i> Bolsas con Tope / Cerradas
                </label>

                <button type="submit" class="bolsa-btn bolsa-btn--primary bolsa-btn--sm">
                    <i class="fas fa-search"></i> Filtrar
                </button>

                @if($buscar !== '' || !$verAbiertas || !$verCerradas)
                    <a href="{{ route('bolsas.index', ['reset_filters' => 1]) }}" class="bolsa-btn bolsa-btn--soft bolsa-btn--sm" title="Restablecer todos los filtros">
                        <i class="fas fa-undo"></i> Ver todo
                    </a>
                @endif
            </div>
        </form>

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
                    $esAbierta = $topeInicial <= 0;

                    // Desglose real a partir de los albaranes de las partidas de esta bolsa
                    $usadoFacturado = $bolsa->totalFacturado();
                    $comprometido = $bolsa->totalComprometido();
                    $totalAlbaranes = round($usadoFacturado + $comprometido, 2);
                    $disponible = $bolsa->saldoDisponible();

                    if ($esAbierta) {
                        // BOLSA ABIERTA / LIBRE:
                        // Rosco: Usado en Rojo (#ef4444) y Comprometido en Naranja (#f59e0b)
                        if ($totalAlbaranes > 0) {
                            $pctRojo = min(100, round(($usadoFacturado / $totalAlbaranes) * 100, 2));
                            $gradientCss = "conic-gradient(#ef4444 0% {$pctRojo}%, #f59e0b {$pctRojo}% 100%)";
                        } else {
                            $gradientCss = "conic-gradient(#e2e8f0 0% 100%)";
                        }
                        $centerValue = $totalAlbaranes;
                        $centerLabel = 'Usado + Compr.';
                        $centerColor = $comprometido > 0 && $usadoFacturado <= 0 ? '#d97706' : '#1e293b';
                    } else {
                        // BOLSA CON TOPE:
                        // Rosco: Usado (albaranes de partidas) en Rojo (#ef4444) y Disponible en Verde (#10b981)
                        $pctUsado = $topeInicial > 0 ? min(100, round(($totalAlbaranes / $topeInicial) * 100, 2)) : 100;
                        $gradientCss = "conic-gradient(#ef4444 0% {$pctUsado}%, #10b981 {$pctUsado}% 100%)";
                        $centerValue = $disponible;
                        $centerLabel = 'Disponible';
                        $centerColor = $disponible > 0 ? '#10b981' : '#ef4444';
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
                        <p class="bolsa-card__ref">
                            {{ $bolsa->numero ?: 'Bolsa #' . $bolsa->id }}
                            @if($bolsa->fecha)
                                <small class="text-muted ml-1">· {{ optional($bolsa->fecha)->format('d/m/Y') }}</small>
                            @endif
                        </p>
                    </div>

                    <div class="bolsa-ring">
                        <div class="bolsa-ring__chart" style="background: {{ $gradientCss }};">
                            <div class="bolsa-ring__inner">
                                <span class="bolsa-ring__value" style="color: {{ $centerColor }}">
                                    {{ number_format($centerValue, 0, ',', '.') }}<small>€</small>
                                </span>
                                <span class="bolsa-ring__label">{{ $centerLabel }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bolsa-stats">
                        @if($esAbierta)
                            <div class="bolsa-stat" style="background-color: #fef2f2; border-color: #fecaca;">
                                <span class="bolsa-stat__label" style="color: #dc2626;">
                                    <i class="fas fa-check-circle mr-1"></i> Usado (Fact.)
                                </span>
                                <p class="bolsa-stat__value" style="color: #b91c1c;">
                                    {{ number_format($usadoFacturado, 2, ',', '.') }} €
                                </p>
                            </div>
                            <div class="bolsa-stat" style="background-color: #fffbeb; border-color: #fde68a;">
                                <span class="bolsa-stat__label" style="color: #d97706;">
                                    <i class="fas fa-clock mr-1"></i> Comprometido
                                </span>
                                <p class="bolsa-stat__value" style="color: #b45309;">
                                    {{ number_format($comprometido, 2, ',', '.') }} €
                                </p>
                            </div>
                            <div class="bolsa-stat bolsa-stat--wide" style="padding: 6px 10px; text-align: center;">
                                <span style="font-size: 0.75rem; color: #0369a1; font-weight: 700;">
                                    <i class="fas fa-unlock-alt mr-1" aria-hidden="true"></i> Bolsa Abierta · Total: {{ number_format($totalAlbaranes, 2, ',', '.') }} €
                                </span>
                            </div>
                        @else
                            <div class="bolsa-stat">
                                <span class="bolsa-stat__label">Tope bolsa</span>
                                <p class="bolsa-stat__value">{{ number_format($topeInicial, 2, ',', '.') }} €</p>
                            </div>
                            <div class="bolsa-stat" style="background-color: #fef2f2; border-color: #fecaca;">
                                <span class="bolsa-stat__label" style="color: #dc2626;">Usado (Albaranes)</span>
                                <p class="bolsa-stat__value" style="color: #b91c1c;">{{ number_format($totalAlbaranes, 2, ',', '.') }} €</p>
                            </div>
                            <div class="bolsa-stat bolsa-stat--wide" style="background-color: #ecfdf5; border-color: #a7f3d0; display: flex; justify-content: space-between; align-items: center; padding: 8px 12px;">
                                <span class="bolsa-stat__label" style="color: #059669; margin: 0;">
                                    <i class="fas fa-wallet mr-1"></i> Disponible real
                                </span>
                                <strong style="color: #047857; font-size: 0.95rem;">
                                    {{ number_format($disponible, 2, ',', '.') }} €
                                </strong>
                            </div>
                            @php
                                $pendienteFacturarBolsa = max(0, round($topeInicial - $usadoFacturado, 2));
                            @endphp
                            <div class="bolsa-stat bolsa-stat--wide" style="background-color: #fffbeb; border-color: #fde68a; padding: 6px 10px; text-align: center; line-height: 1.4;">
                                <small style="color: #b45309; font-weight: 700; font-size: 0.74rem; display: block;">
                                    Del usado: {{ number_format($usadoFacturado, 2, ',', '.') }} € facturado
                                </small>
                                <small style="color: #b45309; font-weight: 700; font-size: 0.74rem; display: block;">
                                    · {{ number_format($pendienteFacturarBolsa, 2, ',', '.') }} € pendiente de facturar
                                </small>
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
                    <h4>No se encontraron bolsas globales</h4>
                    <p>Prueba a cambiar los filtros de búsqueda o crea una nueva bolsa global.</p>
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

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.getElementById('bolsas-filter-form');
            const checkboxes = document.querySelectorAll('.js-auto-filter');

            checkboxes.forEach(function (chk) {
                chk.addEventListener('change', function () {
                    // Evita que el usuario desmarque ambos a la vez dejando todo vacío:
                    // si desmarca el único que quedaba activo, activamos automáticamente el otro.
                    const marcados = Array.from(checkboxes).filter(c => c.checked);
                    if (marcados.length === 0) {
                        checkboxes.forEach(c => {
                            if (c !== chk) c.checked = true;
                        });
                    }
                    if (filterForm) {
                        filterForm.submit();
                    }
                });
            });
        });
    </script>
@endsection