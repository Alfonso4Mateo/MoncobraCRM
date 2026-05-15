<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Cambiado el título a Albarán -->
    <title>Albarán {{ $albaran->numero ?? '' }}</title>
    <style>
        /* MÁRGENES FÍSICOS DE LA PÁGINA */
        @page { size: A4; margin: 15mm; }
        
        /* RESET BÁSICO */
        html, body { margin: 20px; padding: 20px; font-family: Arial, Helvetica, sans-serif; color: #17385d; }
        
        /* EL BODY ACTÚA COMO CONTENEDOR RELATIVO PARA EL PIE */
        body {
            position: relative;
            /* Dejamos un margen inferior inmenso para que la tabla nunca pise el total */
            padding-bottom: 180px; 
            box-sizing: border-box;
            min-height: 100%;
        }

        /* ESTILOS DE TEXTO Y CAJAS */
        .muted { color: #6b7b8f; font-size: 12px; }
        .box-header { background: #2a6fb0; color: #fff; padding: 6px 8px; font-weight: 800; font-size: 13px; }
        .box-body { background: #f0f6ff; padding: 8px; border: 3px solid #2a6fb0; border-top: none; }
        
        .client-labels { background: #174a89; color: #fff; padding: 10px 8px; font-weight: 800; font-size: 13px; }
        .client-content { background: #fff; padding: 10px 8px; border: 3px solid #2a6fb0; border-left: 1px solid #dbeaf9; }
        
        .meta-header { background: #2a6fb0; color: #fff; padding: 6px; font-weight: 800; font-size: 12px; text-align: center; border: 1px solid #2a6fb0; }
        .meta-body { background: #fff; padding: 10px; font-weight: bold; text-align: center; border: 1px solid #2a6fb0; border-top: none; }

        /* TABLA DE ARTÍCULOS */
        .doc-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; table-layout: fixed; }
        .doc-table th { background: #2a6fb0; color: #fff; padding: 8px; text-align: left; }
        .doc-table td { border: 1px solid #7db0e4; padding: 8px; vertical-align: top; word-wrap: break-word; }

        /* BLOQUE DE TOTALES ANCLADO AL FONDO */
        .summary-block {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
        }
        .total-box { background: #2a6fb0; color: #fff; padding: 4px 18px; border-radius: 5px; margin-top: 4px; display: inline-block; font-size: 14px;}
        .footer-box { border: 1px solid #223f5a; padding: 8px; background: #fff; font-size: 13px;}
    </style>
</head>
<body>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
        <tr>
            <td width="50%" valign="top">
                <img src="{{ public_path('images/moncobra-1l.png') }}" alt="logo" style="max-height: 80px; max-width: 250px;">
            </td>
            <td width="70%" align="right" valign="top">
                <img src="{{ public_path('images/aenor.png') }}" alt="aenor" style="max-height: 80px; margin-left: 5px;">
                <img src="{{ public_path('images/aenor2.png') }}" alt="aenor2" style="max-height: 80px; margin-left: 5px;">
                <img src="{{ public_path('images/aenor3.jpg') }}" alt="aenor3" style="max-height: 80px; margin-left: 5px;">
                <img src="{{ public_path('images/eqa.png') }}" alt="eqa" style="max-height: 80px; margin-left: 5px;">
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
        <tr>
            <td width="48%" valign="top">
                <div class="box-header">Moncobra, S.A.</div>
                <div class="box-body">
                    <strong>Eufrates 44</strong><br>
                    <strong>Sevilla</strong><br>
                    <strong>41020 Sevilla</strong><br>
                    <strong>A78990413</strong>
                </div>
            </td>
            
            <td width="4%"></td>
            
            <td width="48%" valign="top">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="30%" class="client-labels" valign="top">
                            <div style="margin-bottom: 8px;">Empresa</div>
                            <div style="margin-bottom: 8px;">Dirección</div>
                            <div>CIF</div>
                        </td>
                        <td width="70%" class="client-content" valign="top">
                            <!-- Cambiado $presupuesto por $albaran -->
                            <div style="font-weight:bold; margin-bottom: 8px;">{{ optional($albaran->cliente)->empresa_nombre }}</div>
                            <div class="muted" style="margin-bottom: 8px;">{{ optional($albaran->cliente)->direccion ?? '' }}</div>
                            <div style="font-weight:bold;">{{ optional($albaran->cliente)->cif ?? '' }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
        <tr>
            <td width="32%" valign="top">
                <div class="meta-header">DOCUMENTO</div>
                <!-- Cambiado $presupuesto por $albaran -->
                <div class="meta-body">{{ $albaran->documento }}</div>
            </td>
            <td width="2%"></td>
            <td width="32%" valign="top">
                <div class="meta-header">NÚMERO</div>
                <!-- Cambiado $presupuesto por $albaran -->
                <div class="meta-body">{{ $albaran->numero }}</div>
            </td>
            <td width="2%"></td>
            <td width="32%" valign="top">
                <div class="meta-header">FECHA</div>
                <!-- Cambiado $presupuesto por $albaran -->
                <div class="meta-body">{{ optional($albaran->fecha)->format('d/m/Y') ?? $albaran->fecha }}</div>
            </td>
        </tr>
    </table>

    @php
        // Cambiado $presupuesto por $albaran
        $lineasValidas = collect((array) $albaran->lista_articulos)->filter(function ($line) {
            if (!is_array($line)) { return false; }
            $descripcion = trim((string) ($line['descripcion'] ?? ''));
            $articulo = trim((string) ($line['articulo'] ?? ''));
            $cantidad = (float) ($line['cantidad'] ?? 0);
            $precioUnitario = (float) ($line['precio_unitario'] ?? ($line['precio'] ?? 0));
            $total = (float) ($line['total'] ?? 0);
            return $descripcion !== '' || $articulo !== '' || $cantidad > 0 || $precioUnitario > 0 || $total > 0;
        })->values();
    @endphp

    @if ($lineasValidas->isNotEmpty())
    <table class="doc-table">
        <thead>
            <tr>
                <th width="6%">Pos.</th>
                <th width="54%">Descripción</th>
                <th width="10%" style="text-align:right;">Cant.</th>
                <th width="15%" style="text-align:right;">Precio</th>
                <th width="15%" style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lineasValidas as $i => $line)
                @php
                    $cantidad = (float) ($line['cantidad'] ?? 0);
                    $precioUnitario = (float) ($line['precio_unitario'] ?? ($line['precio'] ?? 0));
                    $margen = (float) ($line['margen'] ?? 0);
                    $precioConMargen = isset($line['precio_con_margen']) ? (float) $line['precio_con_margen'] : ($precioUnitario * (1 + ($margen / 100)));
                    $medida = $line['medida'] ?? ($line['unidad'] ?? null);
                    $medida = is_string($medida) ? trim($medida) : $medida;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line['descripcion'] ?? $line['articulo'] ?? '' }}</td>
                    <td align="right">{{ number_format($cantidad, 2, ',', '.') }}{{ $medida ? ' ' . e($medida) : '' }}</td>
                    <td align="right">{{ number_format($precioConMargen, 2, ',', '.') }}</td>
                    <td align="right">{{ number_format($line['total'] ?? 0, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="summary-block">
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
            <tr>
                <td align="right">
                     <div style="display: inline-block; text-align: right; width: 160px;">
                         <strong style="color: #6b7b8f; font-size: 13px;">Total:</strong><br>
                        <div class="total-box" style="display: block; text-align: center;">
                            <!-- Cambiado $presupuesto por $albaran -->
                            {{ number_format((float) $albaran->total ?? 0, 2, ',', '.') }} €
                         </div>
                     </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>