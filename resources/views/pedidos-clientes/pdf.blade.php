<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido {{ $pedido->referencia_manual ?? $pedido->numero_pedido ?? '' }}</title>
    <style>
        /* MÁRGENES FÍSICOS DE LA PÁGINA */
        @page { size: A4; margin: 15mm; }
        
        /* RESET BÁSICO */
        html, body { margin: 20px; padding: 20px; font-family: Arial, Helvetica, sans-serif; color: #17385d; }
        
        /* EL BODY EN FLUJO NATURAL */
        body { box-sizing: border-box; }

        /* ESTILOS DE TEXTO Y CAJAS COMUNES */
        .muted { color: #6b7b8f; font-size: 12px; }
        
        /* CLASES COMPARTIDAS PARA AMBOS RECUADROS (MONCOBRA Y CLIENTE) */
        .box-header-cell { background: #2a6fb0; color: #fff; padding: 6px 8px; font-weight: 800; font-size: 13px; }
        .box-body-cell { background: #f0f6ff; padding: 8px; font-size: 13px; color: #17385d; line-height: 1.4; }
        
        /* BLOQUES SECUNDARIOS (DOCUMENTO, NÚMERO, FECHA) */
        .meta-header { background: #2a6fb0; color: #fff; padding: 6px; font-weight: 800; font-size: 12px; text-align: center; border: 1px solid #2a6fb0; }
        .meta-body { background: #fff; padding: 10px; font-weight: bold; text-align: center; border: 1px solid #2a6fb0; border-top: none; }

        /* TABLA DE ARTÍCULOS SIN LÍNEAS HORIZONTALES (Optimizada en tamaño) */
        .doc-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
            font-size: 10.5px; 
            border-bottom: 1px solid #7db0e4; 
        }
        .doc-table th { background: #2a6fb0; color: #fff; padding: 5px 6px; text-align: left; }
        .doc-table td { 
            border-left: 1px solid #7db0e4; 
            border-right: 1px solid #7db0e4; 
            border-top: none;
            border-bottom: none;
            padding: 5px 6px; 
            vertical-align: top; 
            word-wrap: break-word; 
        }

        .evitar-salto {
            white-space: nowrap;
        }

        /* BLOQUE DEL TOTAL (Flujo natural debajo de la tabla) */
        .total-block {
            width: 100%;
            margin-top: 10px;
        }
        .total-box {
            background: #2a6fb0;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            margin-top: 4px;
            display: inline-flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: center;
            gap: 6px;
            max-width: 100%;
            font-size: 14px;
            line-height: 1.2;
        }
        .total-box__label {
            font-weight: 700;
        }
        .total-box__value {
            font-weight: 700;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
    </style>
</head>
<body>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
        <tr>
            <td width="50%" valign="top">
                <img src="{{ public_path('images/logo_h100.png') }}" alt="logo" style="max-height: 80px; max-width: 250px;">
            </td>
            <td width="50%" align="right" valign="top">
                <img src="{{ public_path('images/aenor.png') }}" alt="aenor" style="max-height: 80px; margin-left: 5px;">
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
        <tr>
            <!-- TABLA IZQUIERDA (MONCOBRA) REESTRUCTURADA -->
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
            
            <!-- TABLA DERECHA (CLIENTE) CLONADA -->
            <td width="48%" valign="top">
                <table width="100%" cellpadding="0" cellspacing="0" style="border: 3px solid #2a6fb0; background: #f0f6ff; height: 125px;">
                    <tr>
                        <td class="box-header-cell" valign="middle" style="height: 1px;">{{ optional($pedido->cliente)->empresa_nombre ?? 'Cliente' }}</td>
                    </tr>
                    <tr>
                        <td class="box-body-cell" valign="top">
                            <strong>{{ optional($pedido->cliente)->direccion ?? '' }}</strong><br>
                            <strong>{{ optional($pedido->cliente)->provincia ?? '' }}</strong><br>
                            <strong>{{ optional($pedido->cliente)->codigo_postal ?? '' }} {{ optional($pedido->cliente)->localidad ?? '' }}</strong><br>
                            <strong>{{ optional($pedido->cliente)->cif_nif ?? '' }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
        <tr>
            <td width="23.5%" valign="top">
                <div class="meta-header">DOCUMENTO</div>
                <div class="meta-body">PEDIDO</div>
            </td>
            
            <td width="2%"></td> <td width="23.5%" valign="top">
                <div class="meta-header">NÚMERO</div>
                    <div class="meta-body">{{ $pedido->numero_pedido ?? 'Sin número' }}</div>
            </td>
            
            <td width="2%"></td> <td width="23.5%" valign="top">
                <div class="meta-header">FECHA</div>
                    <div class="meta-body">{{ optional($pedido->fecha_pedido)->format('d/m/Y') ?? optional($pedido->fecha)->format('d/m/Y') ?? ($pedido->fecha_pedido ?? $pedido->fecha ?? '') }}</div>
            </td>

            <td width="2%"></td> <td width="23.5%" valign="top">
                <div class="meta-header">Nº DE PEDIDO</div>
                <div class="meta-body">{{ $pedido->referencia_manual ?? "Sin pedido-cliente"}}</div>
            </td>
        </tr>
    </table>

    @php
        $lineasValidas = collect((array) $pedido->lista_articulos)->filter(function ($line) {
            if (!is_array($line)) return false;
            $descripcion = trim((string) ($line['descripcion'] ?? ''));
            $cantidad = (float) ($line['cantidad'] ?? 0);
            $precio = (float) ($line['precio_unitario'] ?? ($line['precio'] ?? 0));
            return $descripcion !== '' || $cantidad > 0 || $precio > 0;
        })->values();
        $bolsaTexto = trim((string) ($pedido->bolsa_texto ?? ''));
    @endphp

    @if ($lineasValidas->isNotEmpty() || $bolsaTexto !== '')
    <table class="doc-table">
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
            @if ($lineasValidas->isNotEmpty())
                @foreach($lineasValidas as $i => $line)
                    @php
                        $cantidad = (float) ($line['cantidad'] ?? 0);
                        $precio = (float) ($line['precio_unitario'] ?? ($line['precio'] ?? 0));
                        $totalLinea = (float) ($line['total'] ?? 0);
                        $medida = $line['medida'] ?? ($line['unidad'] ?? null);
                        $medida = is_string($medida) ? trim($medida) : $medida;
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="white-space: pre-wrap;">{!! nl2br(e($line['descripcion'] ?? $line['articulo'] ?? '')) !!}</td>
                        <td align="right" class="evitar-salto">{{ number_format($cantidad, 2, ',', '.') }}</td>
                        <td align="center">{{ $medida ? e($medida) : '' }}</td>
                        <td align="right" class="evitar-salto">{{ number_format($precio, 2, ',', '.') }} €</td>
                        <td align="right" class="evitar-salto">{{ number_format($totalLinea, 2, ',', '.') }} €</td>
                    </tr>
                @endforeach
            @elseif ($bolsaTexto !== '')
                <tr>
                    <td>1</td>
                    <td style="white-space: pre-wrap;">{!! nl2br(e($bolsaTexto)) !!}</td>
                    <td align="right"></td>
                    <td align="center"></td>
                    <td align="right"></td>
                    <td align="right"></td>
                </tr>
            @endif
        </tbody>
    </table>
    @endif

    <div class="total-block">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="right">
                    <div class="total-box">
                        <span class="total-box__label">Total:</span>
                        <span class="total-box__value">{{ number_format((float) $pedido->total ?? 0, 2, ',', '.') }} €</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>