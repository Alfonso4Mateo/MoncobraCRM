<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido {{ $pedido->numero_pedido ?? '' }}</title>
    <style>
        @page { size: A4; margin: 12mm }
        html,body{margin:0;padding:0;font-family: Arial, Helvetica, sans-serif;color:#17385d}
        .page{position:relative;width:100%;min-height:297mm;box-sizing:border-box;padding:0;background:#fff}
        .sheet{width:100%;box-sizing:border-box;padding:12mm}
        .header-row{display:flex;justify-content:flex-start;align-items:flex-start}
        .logo{width:180px}
        .logo-img{max-height:90px;width:auto;display:block;max-width:280px}
        .certs{position:absolute;top:12mm;right:12mm;display:flex;flex-direction:row;gap:8px;align-items:center}
        .cert-img{max-height:100px;width:auto;display:block;max-width:280px}
        .company-box{border:3px solid #2a6fb0;padding:8px;background:#f0f6ff;width:260px;max-width:260px;display:inline-block;vertical-align:top}
        .client-box{border:3px solid #2a6fb0;padding:8px;background:#f0f6ff;width:260px;max-width:260px;display:inline-block;vertical-align:top}
        .box-header{background:#2a6fb0;color:#fff;padding:6px 8px;font-weight:800;font-size:13px}
        .box-body{background:#f0f6ff;padding:8px;border-left:3px solid #2a6fb0}
        .panels-row{padding:0;display:table;table-layout:fixed;width:auto;margin-top:20px;border-collapse:separate;border-spacing:20px 0;margin-left:12mm;margin-right:12mm}
        .doc-meta-row{display:table;width:auto;table-layout:fixed;margin-top:12px;border-collapse:separate;border-spacing:8px 0}
        .doc-meta-cell{display:table-cell;width:195px;min-width:195px;max-width:195px;border:1px solid #2a6fb0;vertical-align:top;box-sizing:border-box}
        .doc-meta-label{background:#2a6fb0;color:#fff;padding:6px;font-weight:800;font-size:12px}
        .doc-meta-value{padding:8px;background:#fff}
        .content{padding:0 12mm;box-sizing:border-box}
        .doc-table{display:table;width:auto;min-width:0;border-collapse:collapse;margin-top:12px;table-layout:fixed;font-size:12px;box-sizing:border-box;margin-right:auto}
        .doc-table th{background:#2a6fb0;color:#fff;padding:8px;text-align:left;box-sizing:border-box}
        .doc-table td{border:1px solid #7db0e4;padding:8px;vertical-align:top;word-break:break-word;overflow-wrap:anywhere;box-sizing:border-box}
        .items{margin:12px 0 0}
        .items-empty-spacer{height:360px;display:block}
        .summary-block{page-break-inside:avoid;break-inside:avoid}
        .total-row{display:block;width:300px;margin:8px 0 0 auto;clear:both;text-align:left}
        .total-box{background:#2a6fb0;color:#fff;padding:8px 24px;border-radius:5px}
        .footer-boxes{margin:36px 0 0px 4mm;display:block;width:300px;gap:12px}
        .footer-box{border:1px solid #223f5a;padding:8px;background:#fff;flex:2}
        .muted{color:#6b7b8f;font-size:12px}
    </style>
</head>
<body>
    <div class="page">
        <div class="sheet">
            <div class="header-row">
                <div class="logo">
                    <img src="{{ public_path('images/logo_h100.png') }}" alt="logo" class="logo-img">
                </div>
                <div class="certs">
                    <img src="{{ public_path('images/aenor.png') }}" alt="aenor" class="cert-img">
                </div>
            </div>

            <div class="panels-row">
                <div class="company-box">
                    <div class="box-header">Moncobra, S.A.</div>
                    <div class="box-body">
                        <div>Eufrates 44</div>
                        <div>Sevilla</div>
                        <div>41020 Sevilla</div>
                        <div>A78990413</div>
                    </div>
                </div>

                <div class="client-box">
                    <div class="box-header">Cliente</div>
                    <div class="box-body">
                        <div>{{ optional($pedido->cliente)->empresa_nombre }}</div>
                        <div class="muted">{{ optional($pedido->cliente)->direccion ?? '' }}</div>
                        <div><strong>CIF</strong> {{ optional($pedido->cliente)->cif ?? '' }}</div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="doc-meta-row">
                    <div class="doc-meta-cell">
                        <div class="doc-meta-label">DOCUMENTO</div>
                        <div class="doc-meta-value">Pedido</div>
                    </div>
                    <div class="doc-meta-cell">
                        <div class="doc-meta-label">NÚMERO</div>
                        <div class="doc-meta-value">{{ $pedido->numero_pedido }}</div>
                    </div>
                    <div class="doc-meta-cell">
                        <div class="doc-meta-label">FECHA</div>
                        <div class="doc-meta-value">{{ optional($pedido->fecha_pedido)->format('d/m/Y') ?? $pedido->fecha_pedido }}</div>
                    </div>
                </div>

                @php
                    $lineasValidas = collect((array) $pedido->lista_articulos)
                        ->filter(function ($line) {
                            if (!is_array($line)) return false;
                            $descripcion = trim((string) ($line['descripcion'] ?? ''));
                            $cantidad = (float) ($line['cantidad'] ?? 0);
                            $precio = (float) ($line['precio_unitario'] ?? ($line['precio'] ?? 0));
                            return $descripcion !== '' || $cantidad > 0 || $precio > 0;
                        })->values();
                @endphp

                <div class="items">
                    @if ($lineasValidas->isNotEmpty())
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
                            @foreach($lineasValidas as $i => $line)
                            @php
                                $cantidad = (float) ($line['cantidad'] ?? 0);
                                $precio = (float) ($line['precio_unitario'] ?? ($line['precio'] ?? 0));
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $line['descripcion'] ?? $line['articulo'] ?? '' }}</td>
                                <td style="text-align:right">{{ number_format($cantidad, 2, ',', '.') }}</td>
                                <td style="text-align:right">{{ number_format($precio, 2, ',', '.') }}</td>
                                <td style="text-align:right">{{ number_format($line['total'] ?? 0, 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="items-empty-spacer"></div>
                    @endif
                </div>
            </div>

            <div class="summary-block">
                <div class="total-row">
                    <div class="muted">Total:</div>
                    <div class="total-box">{{ number_format((float) $pedido->total ?? 0, 2, ',', '.') }} €</div>
                </div>
            </div>

            <div class="page-number">Página 1</div>
        </div>
    </div>
</body>
</html>
