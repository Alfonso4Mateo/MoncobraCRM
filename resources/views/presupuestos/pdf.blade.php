<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Presupuesto {{ $presupuesto->numero ?? '' }}</title>
    <style>
        /* MÁRGENES FÍSICOS DE LA PÁGINA */
        @page { size: A4; margin: 15mm; }
        
        /* RESET BÁSICO */
        html, body { margin: 20px; padding: 20px; font-family: Arial, Helvetica, sans-serif; color: #17385d; }
        
        /* EL BODY VUELVE A TENER SU MARGEN DE SEGURIDAD PARA EL PIE */
        body {
            position: relative;
            /* Si vas a escribir textos muy grandes en el pie, puedes subir este valor (ej. 150px) */
            padding-bottom: 120px; 
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
        .doc-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            font-size: 12px; 
            table-layout: fixed;
            border-bottom: 1px solid #7db0e4; 
        }
        .doc-table th { background: #2a6fb0; color: #fff; padding: 8px; text-align: left; }
        .doc-table td { 
            border-left: 1px solid #7db0e4; 
            border-right: 1px solid #7db0e4; 
            border-top: none;
            border-bottom: none;
            padding: 8px; 
            vertical-align: top; 
            word-wrap: break-word; 
        }

        /* BLOQUE DEL TOTAL (Flujo natural debajo de la tabla) */
        .total-block {
            width: 100%;
            margin-top: 10px;
        }
        .total-box { background: #2a6fb0; color: #fff; padding: 6px 16px; border-radius: 5px; display: inline-block; font-size: 14px; white-space: nowrap; text-align: center; }
        .total-inline { display: inline; white-space: nowrap; font-weight: 700; }
        
        /* BLOQUE DE VALIDEZ Y EXCLUSIONES (Anclado al fondo) */
        .footer-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
        }
        /* NUEVA CLASE: Fuerza a la tabla del pie a respetar su ancho */
        .footer-table {
            width: 100%;
            table-layout: fixed; 
        }
        .footer-box { 
            border: 1px solid #223f5a; 
            padding: 8px; 
            background: #fff; 
            font-size: 13px;
            /* NUEVAS REGLAS: Obligan al texto a saltar de línea */
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
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
                            <div style="font-weight:bold; margin-bottom: 8px;">{{ optional($presupuesto->cliente)->empresa_nombre }}</div>
                            <div class="muted" style="margin-bottom: 8px;">{{ optional($presupuesto->cliente)->direccion ?? '' }}</div>
                            <div style="font-weight:bold;">{{ optional($presupuesto->cliente)->cif ?? '' }}</div>
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
                <div class="meta-body">{{ $presupuesto->documento }}</div>
            </td>
            <td width="2%"></td>
            <td width="32%" valign="top">
                <div class="meta-header">NÚMERO</div>
                <div class="meta-body">{{ $presupuesto->numero }}</div>
            </td>
            <td width="2%"></td>
            <td width="32%" valign="top">
                <div class="meta-header">FECHA</div>
                <div class="meta-body">{{ optional($presupuesto->fecha)->format('d/m/Y') ?? $presupuesto->fecha }}</div>
            </td>
        </tr>
    </table>

    @php
        $lineasValidas = collect((array) $presupuesto->lista_articulos)->filter(function ($line) {
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
                <th width="44%">Descripción</th>
                <th width="10%" style="text-align:right;">Cant.</th>
                <th width="10%" style="text-align:center;">Ud.</th>
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
                    <td align="right">{{ number_format($cantidad, 2, ',', '.') }}</td>
                    <td align="center">{{ $medida ? e($medida) : '' }}</td>
                    <td align="right">{{ number_format($precioConMargen, 2, ',', '.') }} €</td>
                    <td align="right">{{ number_format($line['total'] ?? 0, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="total-block">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="right">
                    <div class="total-box">
                        <span class="total-inline">Total: {{ number_format((float) $presupuesto->total ?? 0, 2, ',', '.') }} €</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-bottom">
        <table class="footer-table" cellpadding="0" cellspacing="0">
            <tr>
                <td width="48%" class="footer-box" valign="top">
                    <strong>Validez oferta:</strong><br>
                    <span class="muted" style="display:block; margin-top:4px;">{{ $presupuesto->validez_oferta ?? '30 días' }}</span>
                </td>
                <td width="4%"></td>
                <td width="48%" class="footer-box" valign="top">
                    <strong>Exclusiones:</strong><br>
                    <span class="muted" style="display:block; margin-top:4px;">{{ $presupuesto->exclusiones ?? 'Cualquier concepto no descrito en la oferta' }}</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>