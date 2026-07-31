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
        
        /* EL BODY TIENE UN MARGEN DE SEGURIDAD PARA EL PIE */
        body {
            position: relative;
            padding-bottom: 80px; 
            box-sizing: border-box;
            min-height: 100%;
        }

        /* ESTILOS DE TEXTO Y CAJAS COMUNES */
        .muted { color: #6b7b8f; font-size: 12px; }
        
        /* CLASES COMPARTIDAS PARA AMBOS RECUADROS (MONCOBRA Y CLIENTE) */
        .box-header-cell { background: #2a6fb0; color: #fff; padding: 6px 8px; font-weight: 800; font-size: 13px; }
        .box-body-cell { background: #f0f6ff; padding: 8px; font-size: 13px; color: #17385d; line-height: 1.4; }
        
        /* BLOQUES SECUNDARIOS (DOCUMENTO, NÚMERO, FECHA) */
        .meta-header { background: #2a6fb0; color: #fff; padding: 6px; font-weight: 800; font-size: 12px; text-align: center; border: 1px solid #2a6fb0; }
        .meta-body { background: #fff; padding: 10px; font-weight: bold; text-align: center; border: 1px solid #2a6fb0; border-top: none; }

        /* TABLA DE ARTÍCULOS - FORMATO NORMAL (MÁS LEÍBLE Y ESPACIOSO) */
        .doc-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            font-size: 12px; 
            border-bottom: 1px solid #7db0e4; 
        }
        .doc-table th { background: #2a6fb0; color: #fff; padding: 8px 10px; text-align: left; }
        .doc-table td { 
            border-left: 1px solid #7db0e4; 
            border-right: 1px solid #7db0e4; 
            border-top: none;
            border-bottom: none;
            padding: 8px 10px; 
            vertical-align: top; 
            word-wrap: break-word; 
        }

        /* CLASE DINÁMICA COMPACTA (SE ACTIVA SOLO SI HAY MUCHO TEXTO) */
        .doc-table.compact {
            margin-top: 15px;
            font-size: 10.5px; 
        }
        .doc-table.compact th {
            padding: 5px 6px;
        }
        .doc-table.compact td {
            padding: 4px 6px; 
        }

        .evitar-salto {
            white-space: nowrap;
        }

        /* BLOQUE DEL TOTAL */
        .total-block {
            width: 100%;
            margin-top: 10px;
        }
        .total-box { background: #2a6fb0; color: #fff; padding: 6px 16px; border-radius: 5px; display: inline-block; font-size: 14px; white-space: nowrap; text-align: center; }
        .total-inline { display: inline; white-space: nowrap; font-weight: 700; }
        
        /* BLOQUE DE VALIDEZ Y EXCLUSIONES */
        .footer-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
        }
        .footer-table {
            width: 100%;
            table-layout: fixed; 
        }
        .footer-box { 
            border: 1px solid #223f5a; 
            padding: 8px; 
            background: #fff; 
            font-size: 13px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
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

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
        <tr>
            <!-- TABLA IZQUIERDA (MONCOBRA) -->
            <td width="48%" valign="top">
                <table width="100%" cellpadding="0" cellspacing="0" style="border: 3px solid #2a6fb0; background: #f0f6ff; height: 125px;">
                    <tr>
                        <td class="box-header-cell" valign="middle" style="height: 1px;">Moncobra, S.A.</td>
                    </tr>
                    <tr>
                        <td class="box-body-cell" valign="top">
                            <strong>Eufrates 44</strong><br>
                            <strong>Sevilla</strong><br>
                            <strong>41020 Sevilla</strong><br>
                            <strong>A78990413</strong>
                        </td>
                    </tr>
                </table>
            </td>
            
            <td width="4%"></td>
            
            <!-- TABLA DERECHA (CLIENTE) -->
            <td width="48%" valign="top">
                <table width="100%" cellpadding="0" cellspacing="0" style="border: 3px solid #2a6fb0; background: #f0f6ff; height: 125px;">
                    <tr>
                        <td class="box-header-cell" valign="middle" style="height: 1px;">{{ optional($presupuesto->cliente)->empresa_nombre ?? 'Cliente' }}</td>
                    </tr>
                    <tr>
                        <td class="box-body-cell" valign="top">
                            <strong>{{ optional($presupuesto->cliente)->direccion ?? '' }}</strong><br>
                            <strong>{{ optional($presupuesto->cliente)->provincia ?? '' }}</strong><br>
                            <strong>{{ optional($presupuesto->cliente)->codigo_postal ?? '' }} {{ optional($presupuesto->cliente)->localidad ?? '' }}</strong><br>
                            <strong>{{ optional($presupuesto->cliente)->cif_nif ?? '' }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
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

        // --- LÓGICA DE COMPRESIÓN DINÁMICA ---
        $usarFormatoCompacto = false;
        $totalLineasVisuales = 0;

        foreach($lineasValidas as $line) {
            $texto = trim((string) ($line['descripcion'] ?? $line['articulo'] ?? ''));
            $longitud = strlen($texto);
            $saltosLinea = substr_count($texto, "\n");

            // Estimamos el espacio visual consumido
            $lineasEstimadas = $saltosLinea + ceil($longitud / 80) + 1;
            $totalLineasVisuales += $lineasEstimadas;

            // Si hay mucho texto, activamos el formato compacto
            if ($longitud > 250 || $totalLineasVisuales > 15) {
                $usarFormatoCompacto = true;
                break; 
            }
        }
    @endphp

    @if ($lineasValidas->isNotEmpty())
    <!-- SE APLICA LA CLASE DINÁMICA A LA TABLA -->
    <table class="doc-table {{ $usarFormatoCompacto ? 'compact' : '' }}">
        <thead>
            <tr>
                <th width="5%">Pos.</th>
                <th width="55%">Descripción</th>
                <th width="8%" style="text-align:right;">Cant.</th>
                <th width="7%" style="text-align:center;">Ud.</th>
                <th width="12%" style="text-align:right;">Precio</th>
                <th width="13%" style="text-align:right;">Total</th>
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

                    // --- LIMPIEZA DE SÍMBOLOS '&' ---
                    $textoDesc = $line['descripcion'] ?? $line['articulo'] ?? '';
                    
                    // 1. Borrar agrupaciones de múltiples &&&&
                    $textoLimpio = preg_replace('/&{2,}/', '', $textoDesc);
                    
                    // 2. Borrar un '&' suelto en una línea, pero RESPETANDO el salto de línea.
                    $textoLimpio = preg_replace('/^\s*&\s*$/m', '', $textoLimpio);
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td style="white-space: pre-wrap;">{!! nl2br(e($textoLimpio)) !!}</td>
                    <td align="right" class="evitar-salto">{{ number_format($cantidad, 2, ',', '.') }}</td>
                    <td align="center">{{ $medida ? e($medida) : '' }}</td>
                    <td align="right" class="evitar-salto">{{ number_format($precioConMargen, 2, ',', '.') }} €</td>
                    <td align="right" class="evitar-salto">{{ number_format($line['total'] ?? 0, 2, ',', '.') }} €</td>
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