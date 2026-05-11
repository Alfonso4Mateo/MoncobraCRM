<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Presupuesto {{ $presupuesto->numero ?? '' }}</title>
    <style>
        /* Basic reset for PDF */
        @page { size: A4; margin: 12mm }
        html,body{margin:0;padding:0;font-family: Arial, Helvetica, sans-serif;color:#17385d}
        .page{position:relative;width:100%;min-height:297mm;box-sizing:border-box;padding:0;background:#fff}
        .sheet{width:100%;box-sizing:border-box;padding:12mm}
        .header{display:flex;align-items:flex-start;gap:12px}
        .logo{width:180px}
        .company-box{border:3px solid #2a6fb0;padding:8px;background:#f0f6ff}
        .company-box small{display:block;color:#0f3560;font-weight:700}
        .client-box{border:3px solid #2a6fb0;padding:8px;background:#f0f6ff}
        .client-box small{display:block;color:#0f3560;font-weight:700}
        /* Certifications positioned absolutely at top-right (row) */
        .certs{position:absolute;top:12mm;right:12mm;display:flex;flex-direction:row;gap:8px;align-items:center}
        .cert-img{max-height:100px;width:auto;display:block;max-width:280px}
        .cert-img--wide{max-height:100px;width:auto;max-width:280px}
        .logo-img{max-height:90px;width:auto;display:block;max-width:280px}

        /* new boxed styles for company and client */
        .box-header{background:#2a6fb0;color:#fff;padding:6px 8px;font-weight:800;font-size:13px}
        .box-body{background:#f0f6ff;padding:8px;border-left:3px solid #2a6fb0}
        .client-box-inner{display:flex;align-items:stretch}
        .client-labels{background:#174a89;color:#fff;padding:12px 8px;font-weight:800;display:flex;flex-direction:column;justify-content:flex-start;gap:18px;text-align:left}
        .client-labels div{writing-mode:horizontal-tb}
        .client-content{padding:8px;border-left:1px solid #dbeaf9;background:#fff;flex:1}

        /* Ensure panels don't touch sheet edges */
        .sheet .company-box, .sheet .client-box, .sheet .doc-meta-cell {
            padding:10px;box-sizing:border-box;
        }
        /* panel sizes (adjusted for new boxed style) */
        .company-box{width:260px;max-width:260px;flex:0 0 260px}
        .client-box{width:260px;max-width:260px;flex:0 0 260px}
        /* panels and meta rows align with sheet padding (same left/right margins as header) */
        .sheet .panels-row{padding:0;display:table;table-layout:fixed;width:auto;margin-top:20px;border-collapse:separate;border-spacing:20px 0;margin-left:12mm;margin-right:12mm}
        .sheet .doc-meta-row{padding:0;display:table;table-layout:fixed;width:auto;margin-top:12px;border-collapse:separate;border-spacing:8px 0}
        .header-row{display:flex;justify-content:flex-start;align-items:flex-start}
        .header-right-certs{display:flex;align-items:flex-start}
        .panel-left{display:table-cell;width:260px;min-width:260px;max-width:260px;vertical-align:top;box-sizing:border-box}
        .panel-right{display:table-cell;width:260px;min-width:260px;max-width:260px;vertical-align:top;box-sizing:border-box}
        .doc-meta-row{display:table;width:auto;table-layout:fixed}
        .doc-meta-cell{display:table-cell;width:195px;min-width:195px;max-width:195px;border:1px solid #2a6fb0;vertical-align:top;box-sizing:border-box}
        .doc-meta-label{background:#2a6fb0;color:#fff;padding:6px;font-weight:800;font-size:12px}
        .doc-meta-value{padding:8px;background:#fff}
        .doc-table{display:table;width:auto;min-width:0;border-collapse:collapse;margin-top:12px;table-layout:fixed;font-size:12px;box-sizing:border-box;margin-right:auto}
        .doc-table th{background:#2a6fb0;color:#fff;padding:8px;text-align:left;box-sizing:border-box}
        .doc-table td{border:1px solid #7db0e4;padding:8px;vertical-align:top;word-break:break-word;overflow-wrap:anywhere;box-sizing:border-box}
        .doc-table th.pos{width:5%}
        .doc-table th.desc{width:55%}
        .doc-table th.cant{width:10%;text-align:right}
        .doc-table th.precio{width:15%;text-align:right}
        .doc-table th.total{width:15%;text-align:right}
        /* keep explicit max sizes for images to avoid being scaled by parent widths */
        .logo-img, .cert-img, .cert-img--wide { height:auto }
        .content{padding:0 12mm;box-sizing:border-box}
        .items{margin:12px 0 0}
        .total-row{display:block;width:300px;margin:8px 0 0 auto;clear:both;text-align:left}
        .total-box{background:#2a6fb0;color:#fff;padding:8px 24px;border-radius:5px}
        .footer-boxes{margin:36px 0 0px 4mm;display:block;width:300px;gap:12px}
        .footer-box{border:1px solid #223f5a;padding:8px;background:#fff;flex:2}
        .muted{color:#6b7b8f;font-size:12px}
        .page-number{position:fixed;left:50%;top:45%;transform:translateX(-50%);font-size:84px;color:rgba(15,35,86,0.08)}
        @media print{html,body{width:210mm;height:297mm}}
    </style>
</head>
<body>
    <div class="page">
        <div class="sheet">
        <!-- Top row: logo left, certifications right -->
        <div class="header-row">
            <div class="logo">
                <img src="{{ public_path('images/logo_h100.png') }}" alt="logo" class="logo-img">
            </div>

            <div class="header-right-certs">
                <!-- Certifications stacked top-right -->
                <div class="certs">
                    <img src="{{ public_path('images/aenor.png') }}" alt="aenor" class="cert-img">
                    <img src="{{ public_path('images/aenor2.png') }}" alt="aenor2" class="cert-img">
                    <!-- aenor3 is a JPG per your note -->
                    <img src="{{ public_path('images/aenor3.jpg') }}" alt="aenor3" class="cert-img cert-img--wide">
                    <img src="{{ public_path('images/eqa.png') }}" alt="eqa" class="cert-img">
                </div>
            </div>
        </div>

        <!-- Two fiscal panels: left = Moncobra, right = Cliente (side by side) -->
        <div class="panels-row">
            <div class="company-box panel-left">
                <div class="box-header">Moncobra, S.A.</div>
                <div class="box-body">
                    <div>Eufrates 44</div>
                    <div>Sevilla</div>
                    <div>41020 Sevilla</div>
                    <div>A78990413</div>
                </div>
            </div>

            <div class="client-box panel-right">
                <div class="client-box-inner">
                    <div class="client-labels">
                        <div>Empresa</div>
                        <div>Dirección</div>
                        <div>CIF</div>
                    </div>
                    <div class="client-content">
                        <div class="client-name">{{ optional($presupuesto->cliente)->empresa_nombre }}</div>
                        <div class="muted" style="margin-top:6px">{{ optional($presupuesto->cliente)->direccion ?? '' }}</div>
                        <div style="margin-top:8px"><strong>CIF</strong> {{ optional($presupuesto->cliente)->cif ?? '' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content area aligned to sheet padding -->
        <div class="content">

        <!-- Document meta row (Documento / N
a / Fecha) -->
        <div class="doc-meta-row">
            <div class="doc-meta-cell">
                <div class="doc-meta-label">DOCUMENTO</div>
                <div class="doc-meta-value">{{ $presupuesto->documento }}</div>
            </div>
            <div class="doc-meta-cell">
                <div class="doc-meta-label">NÚMERO</div>
                <div class="doc-meta-value">{{ $presupuesto->numero }}</div>
            </div>
            <div class="doc-meta-cell">
                <div class="doc-meta-label">FECHA</div>
                <div class="doc-meta-value">{{ optional($presupuesto->fecha)->format('d/m/Y') ?? $presupuesto->fecha }}</div>
            </div>
        </div>

        <div class="items">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width:40px">Pos.</th>
                        <th>Descripción</th>
                        <th style="width:80px;text-align:right">Cant.</th>
                        <th style="width:120px;text-align:right">Precio</th>
                        <th style="width:120px;text-align:right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $articulos = (array) $presupuesto->lista_articulos;
                        $lineasMostradas = 0;
                    @endphp
                    @foreach((array) $presupuesto->lista_articulos as $i => $line)
                        <tr>
                            <td style="vertical-align:top">{{ $i + 1 }}</td>
                            <td>{{ $line['descripcion'] ?? $line['articulo'] ?? '' }}</td>
                            <td style="text-align:right">{{ $line['cantidad'] ?? '' }}</td>
                            <td style="text-align:right">{{ number_format($line['precio_unitario'] ?? 0, 2, ',', '.') }}</td>
                            <td style="text-align:right">{{ number_format($line['total'] ?? 0, 2, ',', '.') }}</td>
                        </tr>
                        @php $lineasMostradas++; @endphp
                    @endforeach
                    @for($i = $lineasMostradas; $i < 10; $i++)
                        <tr>
                            <td style="vertical-align:top">&nbsp;</td>
                            <td>&nbsp;</td>
                            <td style="text-align:right">&nbsp;</td>
                            <td style="text-align:right">&nbsp;</td>
                            <td style="text-align:right">&nbsp;</td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <div class="total-row">
            <div style="display:inline-block;min-width:260px;text-align:left">
                <div class="muted">Total:</div>
                <div class="total-box">{{ number_format((float) $presupuesto->total ?? 0, 2, ',', '.') }} €</div>
            </div>
        </div>

        <div class="footer-boxes">
            <div class="footer-box">
                <strong>Validez oferta:</strong>
                <div class="muted">30 días</div>
            </div>
            <div class="footer-box">
                <strong>Exclusiones:</strong>
                <div class="muted">Cualquier concepto no descrito en la oferta</div>
            </div>
        </div>

        </div> <!-- /.content -->

        <div class="page-number">Página 1</div>
    </div>
</body>
</html>
