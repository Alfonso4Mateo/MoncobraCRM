@extends('adminlte::page')

@section('title', 'Centro de Informes de Inventario - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/inventario-acciones.css'])
    <style>
        .kardex-page { font-family: 'Manrope', sans-serif; padding: 1.25rem 0.5rem; color: #191c1e; }
        .kardex-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .kardex-tag { display: inline-block; background: #e0f2fe; color: #0369a1; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.25rem 0.65rem; border-radius: 999px; margin-bottom: 0.35rem; }
        .kardex-title { font-size: 1.7rem; font-weight: 800; color: #0f2747; margin: 0; letter-spacing: -0.02em; }
        .kardex-subtitle { color: #64748b; font-size: 0.9rem; margin: 0.2rem 0 0 0; }

        .btn-erp-back { display: inline-flex; align-items: center; gap: 0.5rem; background: #fff; border: 1px solid #cbd5e1; color: #173e67; font-weight: 800; font-size: 0.85rem; padding: 0.6rem 1.15rem; border-radius: 10px; text-decoration: none !important; transition: all 0.15s; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
        .btn-erp-back:hover { background: #f8fafc; border-color: #94a3b8; color: #0f2747; }

        .btn-erp-csv { display: inline-flex; align-items: center; gap: 0.55rem; background: linear-gradient(135deg, #002442 0%, #173e67 100%); border: none; color: #fff !important; font-weight: 800; font-size: 0.86rem; padding: 0.65rem 1.25rem; border-radius: 10px; cursor: pointer; transition: all 0.18s; box-shadow: 0 4px 12px rgba(0, 36, 66, 0.18); text-decoration: none !important; }
        .btn-erp-csv:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 36, 66, 0.26); color: #fff !important; }

        .report-filter-select { height: 40px; border: 1px solid #d5dfec; border-radius: 8px; padding: 0 0.75rem; font-weight: 700; color: #0f2747; background: #f8fafc; font-size: 0.84rem; }
        .cache-banner { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 0.9rem 1.25rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .cache-banner__text { color: #0c4a6e; font-size: 0.86rem; font-weight: 600; margin: 0; }
        .cache-banner__badge { background: #0284c7; color: #fff; font-size: 0.68rem; font-weight: 800; padding: 0.2rem 0.55rem; border-radius: 6px; text-transform: uppercase; margin-right: 0.5rem; }

        .history-section { margin-top: 2.25rem; background: #ffffff; border-radius: 14px; padding: 1.5rem; box-shadow: 0 8px 32px rgba(25,28,30,0.04); border: 1px solid #e2e8f0; }
        .history-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid #f1f5f9; }
        .type-pill { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; padding: 0.25rem 0.65rem; border-radius: 6px; letter-spacing: 0.04em; }
        .type-pill.salidas { background: #fef2f2; color: #b91c1c; }
        .type-pill.entradas { background: #ecfdf5; color: #047857; }
        .type-pill.traslados { background: #eff6ff; color: #1d4ed8; }
        .type-pill.stock { background: #f3e8ff; color: #6d28d9; }
    </style>
@endsection

@section('content')
<section class="container-fluid kardex-page">
    <header class="kardex-head">
        <div>
            <span class="kardex-tag">MÓDULO DE INVENTARIO · CENTRO DE EXPORTACIÓN</span>
            <h1 class="kardex-title">Gestión de Informes de Inventario</h1>
            <p class="kardex-subtitle">Configura, previsualiza y exporta informes en CSV o consulta los reportes generados hoy por el equipo.</p>
        </div>
        <div class="d-flex align-items-center" style="gap: 0.65rem;">
            <a href="{{ route('inventario.index') }}" class="btn-erp-back">
                <i class="fas fa-arrow-left"></i> Volver al Inventario
            </a>
        </div>
    </header>

    <div class="actions-container" style="padding: 0; background: transparent; box-shadow: none;">
        {{-- PESTAÑAS DE LOS 4 INFORMES --}}
        <div class="actions-tabs">
            <a href="{{ route('inventario.informes.index', ['tab' => 'salidas']) }}" class="actions-tab {{ $tab === 'salidas' ? 'active' : '' }}">
                <i class="fas fa-arrow-up mr-1"></i> 1. Salidas de Inventario / EPI
            </a>
            <a href="{{ route('inventario.informes.index', ['tab' => 'entradas']) }}" class="actions-tab {{ $tab === 'entradas' ? 'active' : '' }}">
                <i class="fas fa-arrow-down mr-1"></i> 2. Entradas de Material
            </a>
            <a href="{{ route('inventario.informes.index', ['tab' => 'traslados']) }}" class="actions-tab {{ $tab === 'traslados' ? 'active' : '' }}">
                <i class="fas fa-exchange-alt mr-1"></i> 3. Traslados entre Almacenes
            </a>
            <a href="{{ route('inventario.informes.index', ['tab' => 'stock']) }}" class="actions-tab {{ $tab === 'stock' ? 'active' : '' }}">
                <i class="fas fa-boxes mr-1"></i> 4. Estado Actual de Stock Real
            </a>
        </div>

        {{-- TARJETA DE CONFIGURACIÓN Y FILTROS DEL INFORME --}}
        <div class="actions-filter-card">
            <form class="actions-filter-form" method="GET" action="{{ route('inventario.informes.index') }}" style="flex-wrap: wrap;">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div class="search-input-wrapper" style="min-width: 240px;">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" placeholder="{{ $tab === 'stock' ? 'Buscar por SKU, ref. proveedor o artículo...' : 'Buscar por trabajador, proveedor, SKU...' }}" value="{{ $buscar }}">
                </div>

                {{-- Selector opcional de Clase (EPI, Herramientas, etc.) --}}
                <select name="clase_id" class="report-filter-select">
                    <option value="">Todas las clases (EPI, Herramienta...)</option>
                    @foreach($clases as $id => $nombreClase)
                        <option value="{{ $id }}" @selected((string)$claseId === (string)$id)>{{ $nombreClase }}</option>
                    @endforeach
                </select>

                {{-- Selector opcional de Almacén --}}
                <select name="almacen_id" class="report-filter-select">
                    <option value="">Todos los almacenes</option>
                    @foreach($almacenes as $alm)
                        <option value="{{ $alm->id }}" @selected((string)$almacenId === (string)$alm->id)>{{ $alm->nombre }}</option>
                    @endforeach
                </select>

                {{-- Filtro de fechas solo en Salidas, Entradas y Traslados --}}
                @if($tab !== 'stock')
                    <div class="date-filters">
                        <label>Desde: <input type="date" name="desde" value="{{ $desde }}"></label>
                        <label>Hasta: <input type="date" name="hasta" value="{{ $hasta }}"></label>
                    </div>
                @endif

                <button type="submit" class="btn-filtrar">
                    <i class="fas fa-filter"></i> Aplicar vista previa
                </button>

                @if(request()->hasAny(['q', 'clase_id', 'almacen_id', 'desde', 'hasta']))
                    <a href="{{ route('inventario.informes.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">Limpiar</a>
                @endif
            </form>
        </div>

        {{-- AVISO INTELIGENTE SI EL INFORME YA FUE GENERADO HOY Y EL STOCK NO HA CAMBIADO --}}
        @if($informeExistenteHoy)
            <div class="cache-banner">
                <p class="cache-banner__text">
                    <span class="cache-banner__badge"><i class="fas fa-bolt mr-1"></i> Informe ya disponible hoy</span>
                    <strong>{{ $informeExistenteHoy->solicitante_nombre }}</strong> ya generó este mismo informe hoy a las <strong>{{ $informeExistenteHoy->created_at->format('H:i') }}</strong> y el stock/Kardex no ha cambiado desde entonces ({{ $informeExistenteHoy->total_filas }} filas).
                </p>
                <div class="d-flex align-items-center" style="gap: 0.5rem;">
                    <a href="{{ route('inventario.informes.descargar', $informeExistenteHoy->id) }}" class="btn-erp-csv" style="padding: 0.45rem 0.95rem; font-size: 0.8rem;">
                        <i class="fas fa-download"></i> Usar el de {{ $informeExistenteHoy->solicitante_nombre }}
                    </a>
                </div>
            </div>
        @endif

        {{-- PREVISUALIZACIÓN DEL INFORME Y BOTÓN DE EXPORTACIÓN --}}
        <div class="actions-content">
            <div class="actions-stats d-flex justify-content-between align-items-center flex-wrap" style="gap: 1rem; margin-bottom: 1rem;">
                <p class="mb-0">
                    Vista previa del informe: <strong>{{ number_format($filasInforme->count(), 0, ',', '.') }} filas</strong> listas para exportar.
                </p>

                <form method="POST" action="{{ route('inventario.informes.exportar') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="desde" value="{{ $desde }}">
                    <input type="hidden" name="hasta" value="{{ $hasta }}">
                    <input type="hidden" name="clase_id" value="{{ $claseId }}">
                    <input type="hidden" name="almacen_id" value="{{ $almacenId }}">
                    <input type="hidden" name="q" value="{{ $buscar }}">

                    <button type="submit" class="btn-erp-csv">
                        <i class="fas fa-file-csv"></i>
                        {{ $informeExistenteHoy ? 'Descargar CSV (Snapshot del día)' : 'Generar y Descargar CSV' }}
                    </button>
                </form>
            </div>

            <div class="table-responsive" style="max-height: 420px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
                <table class="actions-table mb-0">
                    <thead style="position: sticky; top: 0; z-index: 5; background: #f8fafc;">
                        @if($tab === 'stock')
                            <tr>
                                <th>Nº REFERENCIA PROVEEDOR</th>
                                <th>SKU INTERNO</th>
                                <th>TIPO DE ARTÍCULO</th>
                                <th>VARIANTES (ORDEN SKU)</th>
                                <th>TALLA</th>
                                <th class="text-right">CANTIDAD</th>
                            </tr>
                        @elseif($tab === 'traslados')
                            <tr>
                                <th>NOMBRE SOLICITANTE</th>
                                <th>FECHA DE TRASLADO</th>
                                <th>ALMACÉN QUE SALE</th>
                                <th>ALMACÉN QUE ENTRA</th>
                                <th>TIPO DE ARTÍCULO</th>
                                <th>REFERENCIA DEL ARTÍCULO</th>
                                <th>TALLA</th>
                                <th class="text-right">CANTIDAD</th>
                            </tr>
                        @elseif($tab === 'entradas')
                            <tr>
                                <th>NOMBRE DE PROVEEDOR</th>
                                <th>FECHA DE ENTRADA</th>
                                <th>ALMACÉN QUE RECIBE</th>
                                <th>TIPO DE ARTÍCULO</th>
                                <th>REFERENCIA DEL ARTÍCULO</th>
                                <th>TALLA</th>
                                <th class="text-right">CANTIDAD</th>
                            </tr>
                        @else
                            <tr>
                                <th>NOMBRE TRABAJADOR</th>
                                <th>FECHA DE SALIDA</th>
                                <th>TIPO DE ARTÍCULO</th>
                                <th>REFERENCIA DEL ARTÍCULO</th>
                                <th>TALLA</th>
                                <th class="text-right">CANTIDAD</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($filasInforme->take(100) as $fila)
                            @if($tab === 'stock')
                                <tr>
                                    <td><span class="ot-badge">{{ $fila['referencia_proveedor'] }}</span></td>
                                    <td><strong style="color: #0f2747;">{{ $fila['sku_interno'] }}</strong></td>
                                    <td>{{ $fila['tipo_articulo'] }}</td>
                                    <td><strong style="color: #173e67; font-size: 0.82rem;">{{ $fila['variantes'] }}</strong></td>
                                    <td>{{ $fila['talla'] }}</td>
                                    <td class="text-right"><strong>{{ number_format($fila['cantidad'], 0, ',', '.') }} uds.</strong></td>
                                </tr>
                            @elseif($tab === 'traslados')
                                <tr>
                                    <td><strong>{{ $fila['sujeto'] }}</strong></td>
                                    <td>{{ $fila['fecha'] }}</td>
                                    <td><span class="ot-badge" style="background: #fef2f2; color: #b91c1c;">{{ $fila['almacen_origen'] }}</span></td>
                                    <td><span class="ot-badge" style="background: #ecfdf5; color: #047857;">{{ $fila['almacen_destino'] }}</span></td>
                                    <td>{{ $fila['tipo_articulo'] }}</td>
                                    <td><span class="ot-badge">{{ $fila['referencia_articulo'] }}</span></td>
                                    <td>{{ $fila['talla'] }}</td>
                                    <td class="text-right"><strong>{{ number_format($fila['cantidad'], 0, ',', '.') }} uds.</strong></td>
                                </tr>
                            @elseif($tab === 'entradas')
                                <tr>
                                    <td><strong>{{ $fila['sujeto'] }}</strong></td>
                                    <td>{{ $fila['fecha'] }}</td>
                                    <td><span class="ot-badge" style="background: #ecfdf5; color: #047857;">{{ $fila['almacen_destino'] }}</span></td>
                                    <td>{{ $fila['tipo_articulo'] }}</td>
                                    <td><span class="ot-badge">{{ $fila['referencia_articulo'] }}</span></td>
                                    <td>{{ $fila['talla'] }}</td>
                                    <td class="text-right"><strong>{{ number_format($fila['cantidad'], 0, ',', '.') }} uds.</strong></td>
                                </tr>
                            @else
                                <tr>
                                    <td><strong>{{ $fila['sujeto'] }}</strong></td>
                                    <td>{{ $fila['fecha'] }}</td>
                                    <td>{{ $fila['tipo_articulo'] }}</td>
                                    <td><span class="ot-badge">{{ $fila['referencia_articulo'] }}</span></td>
                                    <td>{{ $fila['talla'] }}</td>
                                    <td class="text-right"><strong>{{ number_format($fila['cantidad'], 0, ',', '.') }} uds.</strong></td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $tab === 'traslados' ? 8 : ($tab === 'entradas' ? 7 : 6) }}" class="text-center py-4 text-muted font-weight-bold">
                                    No hay datos que coincidan con los filtros actuales para este informe.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($filasInforme->count() > 100)
                <small class="text-muted d-block mt-2 font-weight-bold">
                    * Mostrando las primeras 100 filas en pantalla por rendimiento. El archivo CSV descargará las {{ $filasInforme->count() }} filas completas.
                </small>
            @endif
        </div>
    </div>

    {{-- SECCIÓN INFERIOR: HISTORIAL DE INFORMES GENERADOS POR EL EQUIPO CON BUSCADOR --}}
    <div class="history-section">
        <div class="history-head">
            <div>
                <h2 style="font-size: 1.2rem; font-weight: 800; color: #0f2747; margin: 0;">
                    <i class="fas fa-history mr-1" style="color: #0369a1;"></i> Registro de Informes Generados
                </h2>
                <p style="font-size: 0.82rem; color: #64748b; margin: 0.2rem 0 0 0;">
                    Consulta o reutiliza los informes solicitados por cualquier compañero sin necesidad de recalcularlos.
                </p>
            </div>

            <form method="GET" action="{{ route('inventario.informes.index') }}" class="d-flex align-items-center flex-wrap" style="gap: 0.5rem;">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="text" name="historial_q" value="{{ $buscarHistorial }}" placeholder="Buscar por solicitante (ej. Rocío)..." class="report-filter-select" style="min-width: 220px; background: #fff;">
                <input type="date" name="historial_fecha" value="{{ $fechaHistorial }}" class="report-filter-select" style="background: #fff;">
                <button type="submit" class="btn-filtrar" style="height: 40px;">
                    <i class="fas fa-search"></i> Buscar
                </button>
                @if($buscarHistorial !== '' || !empty($fechaHistorial))
                    <a href="{{ route('inventario.informes.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                @endif
            </form>
        </div>

        <table class="actions-table">
            <thead>
                <tr>
                    <th>FECHA DE SOLICITUD</th>
                    <th>NOMBRE DEL SOLICITANTE</th>
                    <th>TIPO DE INFORME</th>
                    <th>PARÁMETROS / RANGO</th>
                    <th>FILAS</th>
                    <th class="text-right">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($historialInformes as $itemHist)
                    <tr>
                        <td><strong>{{ $itemHist->created_at->format('d/m/Y H:i') }}</strong></td>
                        <td>
                            <div class="solicitante-info">
                                <div class="avatar">{{ mb_strtoupper(mb_substr($itemHist->solicitante_nombre, 0, 2)) }}</div>
                                {{ $itemHist->solicitante_nombre }}
                            </div>
                        </td>
                        <td>
                            <span class="type-pill {{ $itemHist->tipo_informe }}">
                                {{ match($itemHist->tipo_informe) {
                                    'salidas' => 'Salidas / EPI',
                                    'entradas' => 'Entradas Material',
                                    'traslados' => 'Traslados Almacén',
                                    'stock' => 'Stock Actual',
                                    default => $itemHist->tipo_informe
                                } }}
                            </span>
                        </td>
                        <td>
                            @php $f = $itemHist->filtros ?? []; @endphp
                            @if(!empty($f['desde']) || !empty($f['hasta']))
                                <span class="ot-badge">{{ $f['desde'] ?? 'Inicio' }} → {{ $f['hasta'] ?? 'Hoy' }}</span>
                            @else
                                <span class="text-muted" style="font-size: 0.8rem;">Todo el histórico / Stock del día</span>
                            @endif
                        </td>
                        <td><strong>{{ $itemHist->total_filas }} filas</strong></td>
                        <td class="text-right">
                            <div class="row-actions" style="justify-content: flex-end; gap: 0.4rem;">
                                <button type="button"
                                        class="row-action btn-ver-preview-hist"
                                        title="Ver datos de este informe en pantalla"
                                        data-nombre="{{ $itemHist->archivo_nombre }}"
                                        data-solicitante="{{ $itemHist->solicitante_nombre }}"
                                        data-fecha="{{ $itemHist->created_at->format('d/m/Y H:i') }}"
                                        data-filas='@json($itemHist->datos_preview ?? [])'>
                                    <i class="far fa-eye"></i>
                                </button>
                                <a href="{{ route('inventario.informes.descargar', $itemHist->id) }}" class="row-action" title="Descargar este CSV directamente">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted font-weight-bold">
                            Aún no se han registrado informes generados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($historialInformes->hasPages())
            <div class="actions-pagination mt-3">
                {{ $historialInformes->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
</section>

{{-- MODAL PARA VER UN INFORME DEL HISTORIAL SIN DESCARGARLO --}}
<div class="modal fade" id="modalPreviewInformeHist" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <h5 class="modal-title font-weight-bold" style="color: #0f2747;" id="modalHistTitle">Vista del Informe</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <table class="actions-table mb-0" id="tablaPreviewModalHist">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-ver-preview-hist').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const filas = JSON.parse(this.getAttribute('data-filas') || '[]');
            const solicitante = this.getAttribute('data-solicitante');
            const fecha = this.getAttribute('data-fecha');
            document.getElementById('modalHistTitle').textContent = `Informe generado por ${solicitante} (${fecha})`;

            const thead = document.querySelector('#tablaPreviewModalHist thead');
            const tbody = document.querySelector('#tablaPreviewModalHist tbody');
            thead.innerHTML = '';
            tbody.innerHTML = '';

            if (filas.length === 0) {
                tbody.innerHTML = '<tr><td class="text-center p-4 text-muted">Sin filas registradas en la vista previa.</td></tr>';
            } else {
                const headers = Object.keys(filas[0]);
                thead.innerHTML = '<tr>' + headers.map(h => `<th>${h.replace(/_/g, ' ').toUpperCase()}</th>`).join('') + '</tr>';
                tbody.innerHTML = filas.map(row => {
                    return '<tr>' + headers.map(h => `<td>${row[h] ?? '—'}</td>`).join('') + '</tr>';
                }).join('');
            }

            if (window.jQuery && $('#modalPreviewInformeHist').modal) {
                $('#modalPreviewInformeHist').modal('show');
            }
        });
    });
});
</script>
@endsection