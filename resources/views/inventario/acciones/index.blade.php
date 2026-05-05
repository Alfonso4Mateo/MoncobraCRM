@extends('adminlte::page')

@section('title', 'Registro de Acciones de inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-acciones.css'])
@endsection

@section('content')
<div class="actions-container">
    <nav class="actions-breadcrumb">
        <a href="{{ route('inventario.index') }}">INVENTARIO</a>
        <span>&rsaquo;</span>
        <span class="current">{{ strtoupper($tab) }}</span>
    </nav>
    
    <div class="actions-header">
        <h1>Registro de Acciones de inventario</h1>
    </div>

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
                <input type="text" name="solicitante" placeholder="Filtrar por Solicitante (ej:Javier Pozo)..." value="{{ request('solicitante') }}">
            </div>
            
            <div class="date-filters">
                <label>Desde: <input type="date" name="desde" value="{{ request('desde') }}"></label>
                <label>Hasta: <input type="date" name="hasta" value="{{ request('hasta') }}"></label>
            </div>
            
            <button type="submit" class="btn-filtrar">
                <i class="fas fa-search"></i> Filtrar
            </button>
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
                    <th>SOLICITANTE</th>
                    <th>PROYECTO/OT</th>
                    <th>ALMACÉN ORIGEN</th>
                    @if($tab === 'traslados' || $tab === 'logs')
                        <th>ALMACÉN ACTUAL</th>
                    @endif
                    @if($tab === 'logs')
                        <th>ESTADO</th>
                    @endif
                    <th>ACCIONES</th>
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
                            <span class="ot-badge">{{ $registro->ot ?: '—' }}</span>
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
                            <div class="row-actions">
                                <a
                                    class="row-action"
                                    href="{{ route('inventario.acciones.show', ['tipo' => $registro->tipo, 'id' => $registro->id, 'tab' => $tab]) }}"
                                    title="Ver registro"
                                >
                                    <i class="far fa-eye"></i>
                                </a>

                                @if(($registro->estado ?? 'aceptado') === 'aceptado')
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
                        <td colspan="{{ ($tab === 'logs' ? 7 : 6) + (($tab === 'traslados' || $tab === 'logs') ? 1 : 0) + ($tab === 'logs' ? 1 : 0) }}">
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
@endsection
