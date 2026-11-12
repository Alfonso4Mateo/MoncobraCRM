@extends('adminlte::page')

@section('title', 'Documento de Salida EPI - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Contenedor estilo visor PDF */
        .erp-doc-viewer {
            font-family: 'Manrope', sans-serif;
            background: #e2e8f0;
            min-height: calc(100vh - 60px);
            padding: 1.5rem 1rem 3rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Barra de acciones superior */
        .erp-doc-toolbar {
            width: 100%;
            max-width: 210mm;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 0.85rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        }
        .erp-doc-toolbar__title h1 {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f2747;
            margin: 0;
        }
        .erp-doc-toolbar__title p {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            margin: 0.1rem 0 0 0;
        }
        .erp-doc-toolbar__actions {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .btn-doc-action {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none !important;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155 !important;
        }
        .btn-doc-action:hover {
            background: #f8fafc;
            color: #0f2747 !important;
            border-color: #94a3b8;
        }
        .btn-doc-action--primary {
            background: #173e67;
            border-color: #173e67;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(23, 62, 103, 0.2);
        }
        .btn-doc-action--primary:hover {
            background: #0f2747;
            color: #ffffff !important;
        }

        /* Hoja A4 Oficial equilibrada verticalmente */
        .a4-sheet {
            width: 100%;
            max-width: 210mm;
            min-height: 297mm;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
            padding: 12mm 14mm 14mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #0f172a;
            box-sizing: border-box;
        }

        /* 1. Cabecera 3 columnas */
        .a4-header {
            display: grid;
            grid-template-columns: 165px 1fr 165px;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 2px solid #0f2747;
            margin-bottom: 16px;
        }
        .a4-logo img {
            max-height: 50px;
            width: auto;
            display: block;
        }
        .a4-title {
            text-align: center;
            line-height: 1.4;
        }
        .a4-title strong {
            display: block;
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f2747;
            letter-spacing: 0.03em;
        }
        .a4-title span {
            display: block;
            font-size: 0.84rem;
            font-weight: 800;
            color: #334155;
        }
        .a4-service {
            text-align: right;
            font-size: 0.75rem;
            font-weight: 800;
            color: #0f2747;
            letter-spacing: 0.04em;
        }

        /* 2. Filas de Metadatos más amplias */
        .a4-meta-row {
            display: grid;
            grid-template-columns: 1fr 230px;
            gap: 22px;
            margin-bottom: 14px;
        }
        .a4-meta-field {
            display: flex;
            align-items: baseline;
            border-bottom: 1.5px solid #334155;
            padding: 4px 4px 6px;
            gap: 10px;
        }
        .a4-meta-field label {
            font-size: 0.76rem;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            margin: 0;
            white-space: nowrap;
        }
        .a4-meta-field .a4-meta-val {
            font-size: 0.94rem;
            font-weight: 700;
            color: #0f172a;
            flex: 1;
            min-height: 22px;
        }

        /* 3. Tabla ampliada a 14 filas con celdas más altas */
        .a4-table-wrap {
            margin-top: 6px;
            margin-bottom: 16px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .a4-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #1e293b;
            table-layout: fixed;
            flex-grow: 1;
        }
        .a4-table thead th {
            background: #f1f5f9;
            color: #0f2747;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 10px 12px;
            border: 1px solid #1e293b;
            text-align: left;
        }
        .a4-table thead th:first-child {
            width: 58%;
        }
        .a4-table thead th:last-child {
            width: 42%;
        }
        .a4-table tbody td {
            border: 1px solid #475569;
            padding: 8px 12px;
            height: 38px;
            vertical-align: middle;
            font-size: 0.9rem;
            font-weight: 600;
            color: #0f172a;
        }
        .a4-table tbody td.td-qty {
            text-align: center;
            font-weight: 800;
            font-size: 0.95rem;
        }

        /* 4. Caja de Observaciones más amplia y baja */
        .a4-obs-box {
            border: 1.5px solid #1e293b;
            padding: 10px 12px;
            min-height: 115px;
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
        }
        .a4-obs-box label {
            font-size: 0.75rem;
            font-weight: 800;
            color: #0f2747;
            text-transform: uppercase;
            margin: 0 0 6px 0;
        }
        .a4-obs-content {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e293b;
            white-space: pre-wrap;
            line-height: 1.45;
            flex: 1;
        }

        /* 5. Nota legal y Firmas al pie */
        .a4-footer-block {
            margin-top: 4px;
        }
        .a4-legal {
            font-size: 0.74rem;
            color: #475569;
            font-weight: 600;
            margin-bottom: 24mm;
        }
        .a4-signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 35mm;
            padding-top: 4px;
        }
        .a4-sign-box {
            border-top: 1.5px solid #1e293b;
            padding-top: 8px;
            text-align: center;
            font-size: 0.82rem;
            font-weight: 800;
            color: #0f2747;
        }

        /* Reglas de Impresión A4 */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            body, .wrapper, .content-wrapper, .erp-doc-viewer {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print, .main-header, .main-sidebar, .main-footer, .erp-doc-toolbar {
                display: none !important;
            }
            .a4-sheet {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                min-height: 275mm !important;
                padding: 0 !important;
            }
            .a4-table thead th {
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $delegacion = $documento['delegacion'] ?? '';
        $delegacion = $delegacion !== '' ? $delegacion : ($delegacionPrefill ?? '');
        $fecha = $documento['fecha'] ?? ($salida->fecha?->format('d/m/Y') ?? '');
        $trabajador = $documento['trabajador'] ?? ($salida->solicitante ?? '');
        $ficha = $documento['ficha'] ?? '';
        $observaciones = $documento['observaciones'] ?? '';
        $totalFilas = max(14, count($lineasDocumento ?? []));
    @endphp

    <section class="erp-doc-viewer">
        {{-- BARRA SUPERIOR DE ACCIONES --}}
        <header class="erp-doc-toolbar no-print">
            <div class="erp-doc-toolbar__title">
                <h1><i class="fas fa-file-signature text-primary mr-1"></i> Acta de Entrega de EPI / Material</h1>
                <p>Documento {{ $salida->numero_salida }} · Emitido el {{ $salida->fecha?->format('d/m/Y H:i') ?? $fecha }}</p>
            </div>

            <div class="erp-doc-toolbar__actions">
                <a href="{{ route('inventario.index') }}" class="btn-doc-action">
                    <i class="fas fa-arrow-left"></i> Volver a Inventario
                </a>
                <a href="{{ route('inventario.acciones.index', ['tab' => 'salidas']) }}" class="btn-doc-action">
                    <i class="fas fa-clipboard-list"></i> Historial de Salidas
                </a>
                <button type="button" class="btn-doc-action btn-doc-action--primary" data-print-doc>
                    <i class="fas fa-print"></i> Imprimir / Guardar PDF
                </button>
            </div>
        </header>

        {{-- FOLIO A4 COMPLETO --}}
        <article class="a4-sheet">
            <div>
                <div class="a4-header">
                    <div class="a4-logo">
                        <img src="{{ asset('images/moncobra-1l.png') }}?v={{ @filemtime(public_path('images/moncobra-1l.png')) }}" alt="Moncobra">
                    </div>
                    <div class="a4-title">
                        <strong>EQUIPO DE PROTECCION PERSONAL (EPI'S)</strong>
                        <span>(CONTROL DE ENTREGA/REPOSICION)</span>
                    </div>
                    <div class="a4-service">SERVICIO DE PREVENCION</div>
                </div>

                <div class="a4-meta-row">
                    <div class="a4-meta-field">
                        <label>DELEGACION:</label>
                        <span class="a4-meta-val">{{ $delegacion }}</span>
                    </div>
                    <div class="a4-meta-field">
                        <label>FECHA:</label>
                        <span class="a4-meta-val">{{ $fecha }}</span>
                    </div>
                </div>

                <div class="a4-meta-row">
                    <div class="a4-meta-field">
                        <label>TRABAJADOR (NOMBRE Y APELLIDOS):</label>
                        <span class="a4-meta-val">{{ $trabajador }}</span>
                    </div>
                    <div class="a4-meta-field">
                        <label>FICHA N:</label>
                        <span class="a4-meta-val">{{ $ficha }}</span>
                    </div>
                </div>
            </div>

            <div class="a4-table-wrap">
                <table class="a4-table">
                    <thead>
                        <tr>
                            <th>ARTICULO</th>
                            <th>REPOSICIONES / NUEVAS NECESIDADES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < $totalFilas; $i++)
                            @php
                                $linea = $lineasDocumento[$i] ?? null;
                            @endphp
                            <tr>
                                <td>{{ $linea['articulo'] ?? '' }}</td>
                                <td class="td-qty">{{ $linea['cantidad'] ?? '' }}</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div class="a4-footer-block">
                <div class="a4-obs-box">
                    <label>OBSERVACIONES:</label>
                    <div class="a4-obs-content">{{ $observaciones }}</div>
                </div>

                <div class="a4-legal">
                    De conformidad con la normativa vigente, este registro documenta la entrega y reposición de equipos de protección individual.
                </div>

                <div class="a4-signatures">
                    <div class="a4-sign-box">Firma del Responsable</div>
                    <div class="a4-sign-box">Firma del Trabajador</div>
                </div>
            </div>
        </article>
    </section>
@endsection

@section('js')
    <script>
        (function () {
            const printButton = document.querySelector('[data-print-doc]');
            if (printButton) {
                printButton.addEventListener('click', () => window.print());
            }
        })();
    </script>
@endsection