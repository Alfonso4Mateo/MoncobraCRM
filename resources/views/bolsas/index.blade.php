@extends('adminlte::page')

@section('title', $titulo ?? 'Bolsas Globales - MoncobraCRM')

@section('css')
    <style>
        .bolsa-card { border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: all 0.3s ease; background: #ffffff; }
        .bolsa-card:hover { transform: translateY(-5px); box-shadow: 0 12px 20px rgba(0,0,0,0.1); }
        .bolsa-card-header { padding: 1.25rem 1.25rem 0; text-align: center; }
        
        .rosco-wrap { position: relative; width: 150px; height: 150px; margin: 1.5rem auto; }
        .rosco-chart { width: 100%; height: 100%; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .rosco-inner { width: 115px; height: 115px; background: #ffffff; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: inset 0 2px 6px rgba(0,0,0,0.06); }
        .rosco-value { font-size: 1.35rem; font-weight: 900; color: #1e293b; line-height: 1.2; text-align: center; }
        .rosco-label { font-size: 0.65rem; color: #64748b; text-transform: uppercase; font-weight: 800; margin-top: 2px; letter-spacing: 0.5px; }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 0 1.25rem 1rem; }
        .stat-box { background: #f8fafc; padding: 10px; border-radius: 8px; text-align: center; }
        .stat-title { font-size: 0.65rem; color: #64748b; text-transform: uppercase; font-weight: 800; margin-bottom: 4px; display: block; }
        .stat-amount { font-size: 0.95rem; font-weight: 800; color: #334155; margin: 0; }
    </style>
@endsection

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <p class="text-muted mb-0" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">{{ $breadcrumb ?? 'Inicio / Bolsas' }}</p>
            <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-briefcase text-warning mr-2"></i> {{ $titulo ?? 'Gestión de Bolsas Globales' }}</h1>
        </div>
        <a href="{{ route('bolsas.create') }}" class="btn btn-primary font-weight-bold shadow-sm" style="border-radius: 8px;"><i class="fas fa-plus mr-1"></i> Nueva Bolsa Global</a>
    </div>
@endsection

@section('content')
    <div class="row">
        @forelse($bolsas as $bolsa)
            @php
                $topeInicial = (float) $bolsa->total;
                $acumulado = $bolsa->totalAcumulado();
                $esAbierta = $topeInicial <= 0;
                
                if ($esAbierta) {
                    $colorHex = '#3b82f6'; // Azul corporativo para bolsas infinitas
                    $pctGrafico = 100; // Rosco completo
                } else {
                    $pctConsumido = $topeInicial > 0 ? ($acumulado / $topeInicial) * 100 : 100;
                    $pctGrafico = min(100, $pctConsumido); // Llenado según el consumo
                    
                    if ($pctConsumido < 70) $colorHex = '#10b981'; // Verde (Sano)
                    elseif ($pctConsumido < 95) $colorHex = '#f59e0b'; // Naranja (Aviso)
                    else $colorHex = '#ef4444'; // Rojo (Peligro/Excedido)
                }
            @endphp
            
            <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
                <div class="card bolsa-card h-100 d-flex flex-column">
                    <div class="bolsa-card-header">
                        <span class="badge" style="background-color: {{ $esAbierta ? '#dbeafe' : '#e2e8f0' }}; color: {{ $esAbierta ? '#1d4ed8' : '#475569' }}; font-size: 0.75rem; letter-spacing: 0.5px;">
                            <i class="fas {{ $esAbierta ? 'fa-infinity' : 'fa-layer-group' }} mr-1"></i> {{ mb_strtoupper($bolsa->tipo_bolsa) }}
                        </span>
                        <h4 class="font-weight-bold mt-3 mb-1" style="color: #0f172a; font-size: 1.15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $bolsa->cliente->empresa_nombre ?? 'Sin cliente' }}">
                            {{ $bolsa->cliente->empresa_nombre ?? 'Sin cliente' }}
                        </h4>
                        <p class="text-muted mb-0" style="font-size: 0.85rem;">{{ $bolsa->numero ?: 'Bolsa #' . $bolsa->id }}</p>
                    </div>

                    <!-- El Rosco -->
                    <div class="rosco-wrap">
                        <div class="rosco-chart" style="background: conic-gradient({{ $colorHex }} {{ $pctGrafico }}%, #f1f5f9 0);">
                            <div class="rosco-inner">
                                <span class="rosco-value" style="color: {{ $colorHex }}">{{ number_format($acumulado, 0, ',', '.') }}<small>€</small></span>
                                <span class="rosco-label">ACUMULADO</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cifras al detalle -->
                    <div class="stats-grid mt-auto">
                        @if($esAbierta)
                            <div class="stat-box" style="grid-column: span 2; background-color: #f0f9ff; border: 1px dashed #bae6fd;">
                                <span class="stat-title" style="color: #0284c7;">Modalidad</span>
                                <p class="stat-amount" style="color: #0369a1;"><i class="fas fa-unlock-alt mr-1"></i> Bolsa Abierta (Sin Tope)</p>
                            </div>
                        @else
                            <div class="stat-box">
                                <span class="stat-title">Tope Contratado</span>
                                <p class="stat-amount">{{ number_format($topeInicial, 2, ',', '.') }} €</p>
                            </div>
                            <div class="stat-box" style="background-color: {{ $colorHex }}15;">
                                <span class="stat-title" style="color: {{ $colorHex }};">Saldo Libre</span>
                                <p class="stat-amount" style="color: {{ $colorHex }};">{{ number_format($bolsa->saldoDisponible(), 2, ',', '.') }} €</p>
                            </div>
                        @endif
                    </div>

                    <div class="card-footer bg-white border-top-0 p-3 mt-auto" style="border-radius: 0 0 16px 16px; display: flex; gap: 8px;">
                        <a href="{{ route('bolsas.show', $bolsa) }}" class="btn font-weight-bold m-0" style="background-color: #f8fafc; border: 1px solid #cbd5e1; color: #1e293b; border-radius: 8px; flex: 1;">
                            <i class="fas fa-eye text-primary mr-1"></i> Ver Partidas
                        </a>
                        
                        {{-- Solo permitimos borrar si la bolsa no tiene partidas vinculadas --}}
                        @if($bolsa->partidas_count === 0)
                            <form action="{{ route('bolsas.destroy', $bolsa) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta bolsa global? Esta acción no se puede deshacer.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn font-weight-bold" style="background-color: #fef2f2; border: 1px solid #fecaca; color: #ef4444; border-radius: 8px; padding: 6px 14px;" title="Eliminar bolsa vacía">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 bg-white shadow-sm" style="border-radius: 16px;">
                    <i class="fas fa-box-open mb-3" style="font-size: 3rem; color: #cbd5e1;"></i>
                    <h4 class="text-secondary font-weight-bold">No hay bolsas globales registradas</h4>
                    <a href="{{ route('bolsas.create') }}" class="btn btn-primary font-weight-bold mt-3" style="border-radius: 8px;"><i class="fas fa-plus mr-1"></i> Nueva Bolsa Global</a>
                </div>
            </div>
        @endforelse
    </div>
    @if($bolsas->hasPages()) <div class="d-flex justify-content-center mt-3">{{ $bolsas->links('pagination::bootstrap-4') }}</div> @endif
@endsection