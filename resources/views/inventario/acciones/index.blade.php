@extends('adminlte::page')

@section('title', 'Registro de Acciones de Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-acciones.css'])
    <style>
        .kardex-page { font-family: 'Manrope', sans-serif; padding: 1.25rem 0.5rem; }
        .kardex-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .kardex-tag { display: inline-block; background: #e0f2fe; color: #0369a1; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.25rem 0.65rem; border-radius: 999px; margin-bottom: 0.35rem; }
        .kardex-title { font-size: 1.7rem; font-weight: 800; color: #0f2747; margin: 0; }
        .kardex-subtitle { color: #64748b; font-size: 0.9rem; margin: 0.2rem 0 0 0; }
        .btn-erp-back { display: inline-flex; align-items: center; gap: 0.5rem; background: #fff; border: 1px solid #cbd5e1; color: #173e67; font-weight: 800; font-size: 0.85rem; padding: 0.6rem 1.15rem; border-radius: 10px; text-decoration: none !important; transition: all 0.15s; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
        .btn-erp-back:hover { background: #f8fafc; border-color: #94a3b8; color: #0f2747; }
    </style>
@endsection

@section('content')
<section class="container-fluid kardex-page">
    <header class="kardex-head">
        <div>
            <span class="kardex-tag">MÓDULO DE INVENTARIO · AUDITORÍA</span>
            <h1 class="kardex-title">Registro de Acciones de Inventario</h1>
            <p class="kardex-subtitle">Historial completo de salidas, entradas, traslados y trazabilidad del Kardex.</p>
        </div>
        <div>
            <a href="{{ route('inventario.index') }}" class="btn-erp-back">
                <i class="fas fa-arrow-left"></i> Volver al Inventario
            </a>
        </div>
    </header>

    <div class="actions-container" style="padding: 0; background: transparent; box-shadow: none;">
        <div class="actions-tabs">
            <a href="{{ route('inventario.acciones.index', ['tab' => 'salidas']) }}" class="actions-tab {{ $tab == 'salidas' ? 'active' : '' }}">Registro de Salidas</a>
            <a href="{{ route('inventario.acciones.index', ['tab' => 'entradas']) }}" class="actions-tab {{ $tab == 'entradas' ? 'active' : '' }}">Registro de Entradas</a>
            <a href="{{ route('inventario.acciones.index', ['tab' => 'traslados']) }}" class="actions-tab {{ $tab == 'traslados' ? 'active' : '' }}">Registro de Traslados</a>
            
            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'superadmin')
                <a href="{{ route('inventario.acciones.index', ['tab' => 'logs']) }}" class="actions-tab {{ $tab == 'logs' ? 'active' : '' }}">Logs De Registros</a>
            @endif
        </div>

        <div class="actions-filter-card">
            <form class="actions-filter-form" method="GET" action="{{ route('inventario.acciones.index') }}">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="search-input-wrapper">
                    <i class="fas fa-filter"></i>
                    <input type="text" name="solicitante" placeholder="Buscar por solicitante, Nº de registro, OT o albarán..." value="{{ request('solicitante') }}">
                </div>

                <div class="date-filters">
                    <label>Desde: <input type="date" name="desde" value="{{ request('desde') }}"></label>
                    <label>Hasta: <input type="date" name="hasta" value="{{ request('hasta') }}"></label>
                </div>

                <button type="submit" class="btn-filtrar">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                @if(request()->hasAny(['solicitante', 'desde', 'hasta']))
                    <a href="{{ route('inventario.acciones.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="actions-content">
            <div class="actions-stats">
                <p>
                    Mostrando {{ $registros->firstItem() ?? 0 }} - {{ $registros->lastItem() ?? 0 }} de {{ number_format($registros->total(), 0, ',', '.') }} registros
                </p>
            </div>

            <table class="actions-table">
                <thead>
                    <tr>
                        @if($tab === 'logs')
                            <th>TIPO</th>
                        @endif

                        <th>
                            @if($tab === 'salidas')
                                Nº SALIDA
                            @elseif($tab === 'entradas')
                                Nº ENTRADA
                            @elseif($tab === 'traslados')
                                Nº TRASLADO
                            @else
                                Nº REGISTRO
                            @endif
                        </th>
                        <th>FECHA</th>
                        <th>{{ $tab === 'entradas' ? 'RECEPTOR / SOLICITANTE' : 'SOLICITANTE' }}</th>
                        <th>{{ $tab === 'entradas' ? 'ALBARÁN / REF.' : 'PROYECTO/OT' }}</th>
                        <th>
                            @if($tab === 'entradas') ALMACÉN DESTINO
                            @else ALMACÉN ORIGEN
                            @endif
                        </th>
                        @if($tab === 'traslados' || $tab === 'logs')
                            <th>ALMACÉN ACTUAL</th>
                        @endif
                        @if($tab === 'logs')
                            <th>ESTADO</th>
                        @endif
                        <th class="text-right">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registros as $registro)
                        <tr>
                            @if($tab === 'logs')
                                <td><strong>{{ mb_strtoupper($registro->tipo ?? '') }}</strong></td>
                            @endif

                            <td><strong>{{ $registro->numero ?? '—' }}</strong></td>
                            <td>{{ $registro->fecha?->format('d M Y, H:i') ?? '—' }}</td>
                            <td>
                                <div class="solicitante-info">
                                    <div class="avatar">{{ $registro->iniciales ?? '—' }}</div>
                                    {{ $registro->solicitante ?: '—' }}
                                </div>
                            </td>
                            <td>
                                <span class="ot-badge">{{ $tab === 'entradas' ? ($registro->documento_referencia ?: '—') : ($registro->ot ?: '—') }}</span>
                            </td>
                            <td>{{ $registro->almacen_origen ?: '—' }}</td>

                            @if($tab === 'traslados' || $tab === 'logs')
                                <td>{{ $registro->almacen_actual ?: '—' }}</td>
                            @endif

                            @if($tab === 'logs')
                                <td>
                                    @php
                                        $estado = strtolower((string) ($registro->estado ?? ''));
                                        $estadoTexto = mb_strtoupper((string) ($registro->estado ?? ''));
                                    @endphp
                                    <span class="status-badge {{ $estado }}">{{ $estadoTexto ?: '—' }}</span>
                                </td>
                            @endif
                            <td class="text-right">
                                <div class="row-actions" style="justify-content: flex-end;">
                                    <a
                                        class="row-action"
                                        href="{{ route('inventario.acciones.show', ['tipo' => $registro->tipo, 'id' => $registro->id, 'tab' => $tab]) }}"
                                        title="Ver registro"
                                    >
                                        <i class="far fa-eye"></i>
                                    </a>
                                    @php
                                        $esAceptado = strtolower((string) ($registro->estado ?? 'aceptado')) === 'aceptado';
                                    @endphp
                                    @if(($registro->tipo ?? '') === 'salida' && $esAceptado)
                                        <a class="row-action" href="{{ route('inventario.salida.documento', $registro->id) }}" title="Imprimir comprobante EPI">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    @endif

                                    @if($esAceptado && $isAdmin)
                                        <form
                                            action="{{ route('inventario.acciones.cancel', ['tipo' => $registro->tipo, 'id' => $registro->id, 'tab' => $tab]) }}"
                                            method="POST"
                                            class="row-action-form"
                                            onsubmit="return confirm('¿Cancelar este registro? Se revertirá el inventario.')"
                                        >
                                            @csrf
                                            <button class="row-action row-action-danger" type="submit" title="Cancelar registro">
                                                <i class="far fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($tab === 'logs' ? 7 : 6) + (($tab === 'traslados' || $tab === 'logs') ? 1 : 0) + ($tab === 'logs' ? 1 : 0) }}" class="text-center py-4 text-muted font-weight-bold">
                                No hay registros para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($registros->hasPages())
                <div class="actions-pagination">
                    {{ $registros->onEachSide(1)->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection