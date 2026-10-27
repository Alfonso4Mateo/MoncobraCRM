@extends('adminlte::page')

@section('title', 'Albarán ' . ($albaran->numero ?? '') . ' - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        .albaran-ui {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
            padding-bottom: 2rem;
        }

        /* --- HEADER & ACCIONES --- */
        .albaran-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .albaran-eyebrow {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 800;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        .albaran-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .albaran-title-wrap h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .albaran-badge-status {
            padding: 5px 12px;
            font-size: 0.75rem;
            font-weight: 800;
            border-radius: 6px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .albaran-badge-status--pendiente { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .albaran-badge-status--facturado { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .albaran-badge-status--facturado_parcial { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        .albaran-badge-status--recibido { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .albaran-badge-status--cancelado { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* --- BOTONES UNIFICADOS CON EL ESTILO DEL BOTÓN EDITAR --- */
        .albaran-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action-base {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            font-size: 0.88rem;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none !important;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            border: 1px solid transparent;
            line-height: 1.35;
        }

        .btn-action-base:hover {
            transform: translateY(-1px);
        }

        /* Estilo idéntico al botón primario Editar */
        .btn-action-base--primary {
            background-color: #1f74dd;
            border-color: #1f74dd;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(31, 116, 221, 0.25);
        }
        .btn-action-base--primary:hover {
            background-color: #155cb4;
            border-color: #155cb4;
            box-shadow: 0 4px 8px rgba(31, 116, 221, 0.35);
        }

        /* Variante secundaria (Listado) */
        .btn-action-base--secondary {
            background-color: #ffffff;
            border-color: #cbd5e1;
            color: #475569 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .btn-action-base--secondary:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
            color: #1e293b !important;
        }

        /* Variante Visor PDF */
        .btn-action-base--info {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8 !important;
            box-shadow: 0 1px 3px rgba(29, 78, 216, 0.08);
        }
        .btn-action-base--info:hover {
            background-color: #dbeafe;
            border-color: #93c5fd;
            color: #1e40af !important;
        }

        /* Variante Descarga PDF */
        .btn-action-base--danger {
            background-color: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c !important;
            box-shadow: 0 1px 3px rgba(185, 28, 28, 0.08);
        }
        .btn-action-base--danger:hover {
            background-color: #fee2e2;
            border-color: #fca5a5;
            color: #991b1b !important;
        }

        /* --- CARDS MODERNAS --- */
        .modern-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -2px rgba(0,0,0,0.02);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .modern-card-header {
            padding: 14px 20px;
            background: #fafafa;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modern-card-header h2 {
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            color: #475569;
            text-transform: uppercase;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modern-card-body {
            padding: 20px;
        }

        /* --- CAMPOS METADATOS --- */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
        }
        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .meta-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .meta-value {
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
        }
        .pill-tag {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            font-size: 0.8rem;
            border-radius: 6px;
            width: fit-content;
        }

        /* --- PARTIDAS Y BOLSAS CAPSULE --- */
        .partidas-deck {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 12px;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
        }
        .partida-card-modern {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .partida-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }
        .partida-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .partida-chip-this {
            background: #dbeafe;
            color: #1e40af;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 6px;
        }
        .partida-submeta {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            font-size: 0.78rem;
            background: #ffffff;
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
        }
        .submeta-pill {
            font-weight: 700;
        }
        .submeta-pill--ok { color: #15803d; }
        .submeta-pill--warn { color: #b45309; }
        .submeta-pill--blue { color: #2563eb; }

        /* --- TABLA MODERNA --- */
        .modern-table-wrap {
            overflow-x: auto;
        }
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .modern-table th {
            background: #fafafa;
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .modern-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .modern-table tbody tr:hover {
            background: #f8fafc;
        }
        .modern-table .td-pos {
            color: #94a3b8;
            font-weight: 700;
            font-size: 0.8rem;
        }
        .modern-table .td-desc {
            font-weight: 600;
            color: #0f172a;
        }
        .unit-chip {
            background: #f1f5f9;
            color: #64748b;
            padding: 2px 7px;
            border-radius: 5px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        /* --- FOOTER TOTAL --- */
        .table-total-row {
            background: #f8fafc;
            display: flex;
            justify-content: flex-end;
            align-items: baseline;
            gap: 16px;
            padding: 16px 20px;
            border-top: 2px solid #e2e8f0;
        }
        .table-total-label {
            font-size: 0.85rem;
            font-weight: 800;
            color: #64748b;
            letter-spacing: 0.05em;
        }
        .table-total-val {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1f74dd;
            letter-spacing: -0.02em;
        }
    </style>
@endsection

@section('content')
    @php
        $estadoValido = strtolower((string) $albaran->estado);
        $estado = in_array($estadoValido, ['pendiente', 'recibido', 'facturado', 'facturado_parcial', 'cancelado'], true)
            ? $estadoValido
            : 'pendiente';

        $lineas = collect((array) $albaran->lista_articulos)->filter(function ($l) {
            return is_array($l) && (!empty($l['descripcion']) || !empty($l['articulo']) || !empty($l['total']));
        })->values();

        $pedidosVinculados = $albaran->pedidosClientes ?? collect();
    @endphp

    <div class="albaran-ui">
        {{-- CABECERA MODERNA --}}
        <header class="albaran-header">
            <div>
                <div class="albaran-eyebrow">Albaranes &middot; Detalle de documento</div>
                <div class="albaran-title-wrap">
                    <h1>{{ $albaran->numero ?: 'Albarán #' . $albaran->id }}</h1>
                    <span class="albaran-badge-status albaran-badge-status--{{ $estado }}">
                        {{ strtoupper(str_replace('_', ' ', $estado)) }}
                    </span>
                </div>
            </div>

            <div class="albaran-actions">
                <a href="{{ route('albaranes.index') }}" class="btn-action-base btn-action-base--secondary">
                    <i class="fas fa-arrow-left"></i> Listado
                </a>

                @can('albaranes.download')
                    <a href="{{ route('albaranes.preview', $albaran) }}" class="btn-action-base btn-action-base--info">
                        <i class="fas fa-search-plus"></i> Visor PDF
                    </a>
                    <a href="{{ route('albaranes.pdf', $albaran) }}" class="btn-action-base btn-action-base--danger" target="_blank">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </a>
                @endcan

                @can('albaranes.manage')
                    @if($estado !== 'facturado')
                        <a href="{{ route('albaranes.edit', $albaran) }}" class="btn-action-base btn-action-base--primary">
                            <i class="fas fa-pen"></i> Editar
                        </a>
                    @endif
                @endcan
            </div>
        </header>

        {{-- BLOQUE 1: INFORMACIÓN DEL DOCUMENTO --}}
        <section class="modern-card">
            <div class="modern-card-header">
                <h2><i class="fas fa-file-invoice text-primary"></i> Información del Documento</h2>
            </div>
            <div class="modern-card-body">
                <div class="meta-grid">
                    <div class="meta-item">
                        <span class="meta-label">Cliente</span>
                        <span class="meta-value" style="font-size: 1.05rem;">{{ $albaran->cliente->empresa_nombre ?? 'Sin cliente' }}</span>
                    </div>

                    <div class="meta-item">
                        <span class="meta-label">Fecha emisión</span>
                        <span class="meta-value">{{ optional($albaran->fecha)->format('d/m/Y') ?: '-' }}</span>
                    </div>

                    <div class="meta-item">
                        <span class="meta-label">Centro de Coste (CC / OT)</span>
                        <span class="pill-tag">{{ $albaran->ot ?: 'Sin CC' }}</span>
                    </div>

                    <div class="meta-item">
                        <span class="meta-label">Título / Identificador</span>
                        <span class="meta-value" style="font-weight: 500;">{{ $albaran->titulo ?: '-' }}</span>
                    </div>
                </div>

                {{-- BOLSAS Y PARTIDAS CON TARJETAS RECONSTRUIDAS --}}
                @if($pedidosVinculados->isNotEmpty())
                    <div class="partidas-deck">
                        @foreach($pedidosVinculados as $p)
                            @php
                                $esPartidaBolsa = (bool) $p->bolsa;
                                $macroRef = $p->bolsaGlobal ? ($p->bolsaGlobal->numero ?: '#' . $p->bolsaGlobal->id) : null;
                                $impPivot = isset($p->pivot) ? (float) ($p->pivot->importe_imputado ?? $p->pivot->importe ?? 0) : (float) $albaran->total;

                                $esBolsaGlobalAbierta = $p->bolsaGlobal && (float) $p->bolsaGlobal->total <= 0;
                                $esAbierta = $p->esPartidaAbierta() || $esBolsaGlobalAbierta;

                                $consumidoTotal = $p->importeConsumido();
                                $facturadoFirme = method_exists($p, 'importeFacturado') ? $p->importeFacturado() : 0.0;
                                $pendienteComprometido = max(0.0, round($consumidoTotal - $facturadoFirme, 2));
                                $disponible = $p->pendienteFacturar();
                            @endphp

                            <article class="partida-card-modern">
                                <div class="partida-head">
                                    <div class="partida-name">
                                        <i class="fas fa-boxes text-primary"></i>
                                        <span>{{ $p->numero_pedido }}</span>
                                        @if($esPartidaBolsa && $macroRef)
                                            <a href="{{ route('bolsas.show', $p->bolsaGlobal->id) }}" class="unit-chip" style="text-decoration: none;" title="Ver bolsa global">
                                                Bolsa: {{ $macroRef }}
                                            </a>
                                        @endif
                                    </div>
                                    <span class="partida-chip-this">
                                        Este albarán: {{ number_format($impPivot, 2, ',', '.') }} €
                                    </span>
                                </div>

                                <div class="partida-submeta">
                                    @if($esAbierta)
                                        <span class="submeta-pill submeta-pill--warn">🔓 Partida Abierta</span>
                                        <span class="text-muted">·</span>
                                        <span>Usado: <strong>{{ number_format($consumidoTotal, 2, ',', '.') }} €</strong></span>
                                        <span class="text-muted">·</span>
                                        <span class="submeta-pill submeta-pill--warn">Comprometido: {{ number_format($pendienteComprometido, 2, ',', '.') }} €</span>
                                    @else
                                        <span class="submeta-pill submeta-pill--ok">Facturado: {{ number_format($facturadoFirme, 2, ',', '.') }} €</span>
                                        <span class="text-muted">·</span>
                                        <span class="submeta-pill submeta-pill--warn">Pendiente: {{ number_format($pendienteComprometido, 2, ',', '.') }} €</span>
                                        <span class="text-muted">·</span>
                                        <span class="submeta-pill submeta-pill--blue">Disp: {{ number_format($disponible, 2, ',', '.') }} €</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- BLOQUE 2: LÍNEAS DE ARTÍCULOS Y TRABAJOS --}}
        <section class="modern-card">
            <div class="modern-card-header">
                <h2><i class="fas fa-list text-primary"></i> Líneas de Artículos y Trabajos</h2>
                <span class="unit-chip" style="background: #ffffff; border: 1px solid #e2e8f0;">
                    {{ $lineas->count() }} {{ $lineas->count() === 1 ? 'línea' : 'líneas' }}
                </span>
            </div>
            
            <div class="modern-table-wrap">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">#</th>
                            <th>Descripción</th>
                            <th style="width: 120px; text-align: right;">Cantidad</th>
                            <th style="width: 80px; text-align: center;">Ud.</th>
                            <th style="width: 140px; text-align: right;">P. Unitario</th>
                            <th style="width: 110px; text-align: right;">Margen</th>
                            <th style="width: 160px; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lineas as $idx => $linea)
                            @php
                                $desc = $linea['descripcion'] ?? $linea['articulo'] ?? '-';
                                $cant = (float) ($linea['cantidad'] ?? 0);
                                $med = $linea['medida'] ?? ($linea['unidad'] ?? 'und');
                                $precio = (float) ($linea['precio_unitario'] ?? ($linea['precio'] ?? 0));
                                $margen = (float) ($linea['margen'] ?? 0);
                                $tot = (float) ($linea['total'] ?? 0);
                            @endphp
                            <tr>
                                <td class="td-pos" style="text-align: center;">{{ sprintf('%02d', $idx + 1) }}</td>
                                <td class="td-desc" style="white-space: pre-wrap;">{{ $desc }}</td>
                                <td style="text-align: right; font-weight: 600;">{{ number_format($cant, 2, ',', '.') }}</td>
                                <td style="text-align: center;"><span class="unit-chip">{{ $med }}</span></td>
                                <td style="text-align: right;">{{ number_format($precio, 2, ',', '.') }} €</td>
                                <td style="text-align: right; color: #64748b;">{{ number_format($margen, 2, ',', '.') }} %</td>
                                <td style="text-align: right; font-weight: 800; color: #0f172a;">{{ number_format($tot, 2, ',', '.') }} €</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: #94a3b8;">
                                    No constan artículos registrados en este albarán.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-total-row">
                <span class="table-total-label">TOTAL ALBARÁN</span>
                <span class="table-total-val">{{ number_format((float) ($albaran->total ?? 0), 2, ',', '.') }} €</span>
            </div>
        </section>

        {{-- CHATTER DE AUDITORÍA --}}
        @include('partials.document-activity-chatter', ['activities' => $activities ?? collect()])
    </div>
@endsection