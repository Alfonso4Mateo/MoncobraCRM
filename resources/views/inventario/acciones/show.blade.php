@extends('adminlte::page')

@section('title', 'Detalle de Registro - Inventario')

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
        .btn-erp-navy { display: inline-flex; align-items: center; gap: 0.5rem; background: #173e67; border: 1px solid #173e67; color: #fff !important; font-weight: 800; font-size: 0.85rem; padding: 0.6rem 1.15rem; border-radius: 10px; text-decoration: none !important; cursor: pointer; transition: all 0.15s; box-shadow: 0 3px 6px rgba(23, 62, 103, 0.18); }
        .btn-erp-navy:hover { background: #0f2747; color: #fff !important; }
    </style>
@endsection

@section('content')
<section class="container-fluid kardex-page">
    @php
        $defaultTab = $tipo === 'salida' ? 'salidas' : ($tipo === 'entrada' ? 'entradas' : 'traslados');
        $backTab = (string) request()->query('tab', $defaultTab);
    @endphp

    <header class="kardex-head">
        <div>
            <span class="kardex-tag">MÓDULO DE INVENTARIO · AUDITORÍA</span>
            <h1 class="kardex-title">Detalle del Registro</h1>
            <p class="kardex-subtitle">Consulta de líneas, cantidades y trazabilidad del movimiento en el Kardex.</p>
        </div>
        <div class="d-flex align-items-center" style="gap: 0.65rem;">
            <a href="{{ route('inventario.acciones.index', ['tab' => $backTab]) }}" class="btn-erp-back">
                <i class="fas fa-arrow-left"></i> Volver al listado
            </a>
            @if($tipo === 'salida' && ($registro->estado ?? 'aceptado') === 'aceptado')
                <a href="{{ route('inventario.salida.documento', $registro->id) }}" class="btn-erp-navy">
                    <i class="fas fa-print"></i> Imprimir Documento de Salida
                </a>
            @endif
        </div>
    </header>

    <div class="actions-container" style="padding: 0; background: transparent; box-shadow: none;">
        <div class="actions-content">
            <div class="actions-stats">
                <p>
                    <strong>{{ mb_strtoupper($tipo) }}</strong>
                    · {{ $registro->fecha?->format('d M Y, H:i') ?? '—' }}
                    · Nº: {{ $tipo === 'salida' ? $registro->numero_salida : ($tipo === 'entrada' ? $registro->numero_entrada : $registro->numero_traslado) }}
                </p>
            </div>

            <div class="row g-3 mb-4">
                @foreach([
                    'Solicitante' => $registro->solicitante ?: '—',
                    'OT' => $registro->ot ?: '—',
                    ($tipo === 'entrada' ? 'Almacén destino' : 'Almacén origen') => $registro->almacen_origen ?: '—',
                    'Estado' => mb_strtoupper((string) ($registro->estado ?? '—')),
                ] as $label => $value)
                    <div class="col-md-3"><div class="card card-body h-100 shadow-sm border-0"><small class="text-muted text-uppercase font-weight-bold" style="font-size: 0.72rem;">{{ $label }}</small><strong class="mt-1" style="color: #0f2747;">{{ $value }}</strong></div></div>
                @endforeach
            </div>

            @if($registro->observaciones ?? false)
                <div class="alert alert-light border shadow-sm mb-4" style="border-radius: 10px;"><strong>Observaciones:</strong> {{ $registro->observaciones }}</div>
            @endif

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
                        <th class="text-right">CANTIDAD</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $index => $item)
                        @php 
                            $data = (array) $item; 
                            $esObsoleta = !empty($data['obsoleto']) || (!empty($data['activo']) && $data['activo'] === false);
                        @endphp
                        <tr>
                            <td><strong>#{{ $index + 1 }}</strong></td>
                            <td>
                                <strong style="color: #173e67;">{{ $data['codigo'] ?? '—' }}</strong>
                                @if($esObsoleta)
                                    <span class="badge ml-1" style="font-size: 0.68rem; font-weight: 800; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;">
                                        <i class="fas fa-history mr-1"></i> OBSOLETO
                                    </span>
                                @endif
                            </td>
                            <td>
                                {{ $data['descripcion'] ?? '—' }}
                                @if($esObsoleta)
                                    <small class="d-block text-muted" style="font-size: 0.75rem;">
                                        * Variante sustituida por una nueva matriz dimensional. Conservada para integridad del Kardex.
                                    </small>
                                @endif
                            </td>
                            @if($tipo === 'traslado')
                                <td>{{ $data['almacen_origen'] ?? '—' }}</td>
                                <td>{{ $data['almacen_actual'] ?? '—' }}</td>
                            @endif
                            <td class="text-right"><strong style="font-size: 0.95rem;">{{ isset($data['cantidad']) ? number_format((int) $data['cantidad'], 0, ',', '.') : '—' }} uds.</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tipo === 'traslado' ? 6 : 5 }}" class="text-center py-4 text-muted font-weight-bold">
                                No hay items en este registro.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="height: 18px;"></div>

            <div class="row-actions" style="justify-content: flex-start; gap: 0.75rem;">
                <a class="btn-erp-back" href="{{ route('inventario.acciones.index', ['tab' => $backTab]) }}" title="Volver">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al listado
                </a>

                @if(($registro->estado ?? 'aceptado') === 'aceptado' && $isAdmin)
                    <form
                        action="{{ route('inventario.acciones.cancel', ['tipo' => $tipo, 'id' => $registro->id, 'tab' => $backTab]) }}"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('¿Cancelar este registro? Se revertirá el inventario.')"
                    >
                        @csrf
                        <button class="btn btn-outline-danger font-weight-bold" type="button" onclick="if(confirm('¿Cancelar este registro? Se revertirá el inventario.')) this.form.submit();" style="border-radius: 10px; padding: 0.6rem 1rem;">
                            <i class="far fa-trash-alt mr-1"></i> Cancelar registro y revertir stock
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection