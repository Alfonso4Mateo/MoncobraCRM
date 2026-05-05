@extends('adminlte::page')

@section('title', 'Detalle de Registro - Inventario')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-acciones.css'])
@endsection

@section('content')
<div class="actions-container">
    @php
        $defaultTab = $tipo === 'salida' ? 'salidas' : ($tipo === 'entrada' ? 'entradas' : 'traslados');
        $backTab = (string) request()->query('tab', $defaultTab);
    @endphp
    <nav class="actions-breadcrumb">
        <a href="{{ route('inventario.index') }}">INVENTARIO</a>
        <span>&rsaquo;</span>
        <a href="{{ route('inventario.acciones.index', ['tab' => $backTab]) }}">REGISTRO</a>
        <span>&rsaquo;</span>
        <span class="current">DETALLE</span>
    </nav>

    <div class="actions-header">
        <h1>Detalle del registro</h1>
    </div>

    <div class="actions-content">
        <div class="actions-stats">
            <p>
                <strong>{{ mb_strtoupper($tipo) }}</strong>
                · {{ $registro->fecha?->format('d M Y, H:i') ?? '—' }}
                · Nº: {{ $tipo === 'salida' ? $registro->numero_salida : ($tipo === 'entrada' ? $registro->numero_entrada : $registro->numero_traslado) }}
            </p>
        </div>

        <table class="actions-table" aria-label="Detalle del registro">
            <thead>
                <tr>
                    <th>CAMPO</th>
                    <th>VALOR</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Solicitante</strong></td>
                    <td>{{ $registro->solicitante ?: '—' }}</td>
                </tr>
                <tr>
                    <td><strong>OT</strong></td>
                    <td>{{ $registro->ot ?: '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Almacén origen</strong></td>
                    <td>{{ $registro->almacen_origen ?: '—' }}</td>
                </tr>
                @if($tipo === 'traslado')
                    <tr>
                        <td><strong>Almacén actual</strong></td>
                        <td>{{ $registro->almacen_actual ?: '—' }}</td>
                    </tr>
                @endif
                <tr>
                    <td><strong>Estado</strong></td>
                    <td>
                        @php
                            $estado = strtolower((string) ($registro->estado ?? ''));
                            $estadoTexto = mb_strtoupper((string) ($registro->estado ?? ''));
                        @endphp
                        <span class="status-badge {{ $estado }}">{{ $estadoTexto ?: '—' }}</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="height: 16px;"></div>

        <table class="actions-table" aria-label="Items del registro">
            <thead>
                <tr>
                    <th>ITEM</th>
                    <th>CÓDIGO</th>
                    <th>DESCRIPCIÓN</th>
                    @if($tipo === 'traslado')
                        <th>ALMACÉN ORIGEN</th>
                        <th>ALMACÉN ACTUAL</th>
                    @endif
                    <th>CANTIDAD</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                    @php $data = (array) $item; @endphp
                    <tr>
                        <td><strong>#{{ $index + 1 }}</strong></td>
                        <td>{{ $data['codigo'] ?? '—' }}</td>
                        <td>{{ $data['descripcion'] ?? '—' }}</td>
                        @if($tipo === 'traslado')
                            <td>{{ $data['almacen_origen'] ?? '—' }}</td>
                            <td>{{ $data['almacen_actual'] ?? '—' }}</td>
                        @endif
                        <td>{{ isset($data['cantidad']) ? number_format((int) $data['cantidad'], 0, ',', '.') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $tipo === 'traslado' ? 6 : 5 }}">No hay items en este registro.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="height: 18px;"></div>

        <div class="row-actions">
            <a class="row-action" href="{{ route('inventario.acciones.index', ['tab' => $backTab]) }}" title="Volver">
                <i class="fas fa-arrow-left"></i>
            </a>

            @if(($registro->estado ?? 'aceptado') === 'aceptado')
                <form
                    action="{{ route('inventario.acciones.cancel', ['tipo' => $tipo, 'id' => $registro->id, 'tab' => $backTab]) }}"
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
    </div>
</div>
@endsection
