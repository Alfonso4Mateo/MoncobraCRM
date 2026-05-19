<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido {{ $pedido->numero_pedido ?? '' }}</title>
    <style>
        /* MÁRGENES FÍSICOS DE LA PÁGINA */
        @page { size: A4; margin: 15mm; }
        
        /* RESET BÁSICO */
        html, body { margin: 20px; padding: 20px; font-family: Arial, Helvetica, sans-serif; color: #17385d; }
        
        /* EL BODY EN FLUJO NATURAL */
        body { box-sizing: border-box; }

        /* ESTILOS DE TEXTO Y CAJAS */
        .muted { color: #6b7b8f; font-size: 12px; }
        .box-header { background: #2a6fb0; color: #fff; padding: 6px 8px; font-weight: 800; font-size: 13px; }
        .box-body { background: #f0f6ff; padding: 8px; border: 3px solid #2a6fb0; border-top: none; }
        
        .client-labels { background: #174a89; color: #fff; padding: 10px 8px; font-weight: 800; font-size: 13px; }
        .client-content { background: #fff; padding: 10px 8px; border: 3px solid #2a6fb0; border-left: 1px solid #dbeaf9; }
        
        .meta-header { background: #2a6fb0; color: #fff; padding: 6px; font-weight: 800; font-size: 12px; text-align: center; border: 1px solid #2a6fb0; }
        .meta-body { background: #fff; padding: 10px; font-weight: bold; text-align: center; border: 1px solid #2a6fb0; border-top: none; }

        /* TABLA DE ARTÍCULOS SIN LÍNEAS HORIZONTALES */
        .doc-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            font-size: 12px; 
            table-layout: fixed;
            border-bottom: 1px solid #7db0e4; /* Cierra la tabla por abajo */
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
        .total-box { background: #2a6fb0; color: #fff; padding: 4px 18px; border-radius: 5px; margin-top: 4px; display: inline-block; font-size: 14px;}
    </style>
</head>
<body>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
        <tr>
            <td width="50%" valign="top">
                <img src="{{ public_path('images/logo_h100.png') }}" alt="logo" style="max-height: 80px; max-width: 250px;">
            </td>
            <td width="50%" align="right" valign="top">
                <img src="{{ public_path('images/aenor.png') }}" alt="aenor" style="max-height: 80px; margin-left: 5px;">
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
                            <div style="margin-bottom: 8px;">Cliente</div>
                            <div style="margin-bottom: 8px;">Dirección</div>
                            <div>CIF</div>
                        </td>
                        <td width="70%" class="client-content" valign="top">
                            <div style="font-weight:bold; margin-bottom: 8px;">{{ optional($pedido->cliente)->empresa_nombre }}</div>
                            <div class="muted" style="margin-bottom: 8px;">{{ optional($pedido->cliente)->direccion ?? '' }}</div>
                            <div style="font-weight:bold;">{{ optional($pedido->cliente)->cif ?? '' }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
        <tr>
            <td width="23.5%" valign="top">
                <div class="meta-header">DOCUMENTO</div>
                <div class="meta-body">Pedido</div>
            </td>
            
            <td width="2%"></td> <td width="23.5%" valign="top">
                <div class="meta-header">NÚMERO</div>
                    <div class="meta-body">{{ $pedido->numero_pedido ?? $pedido->numero ?? '' }}</div>
            </td>
            
            <td width="2%"></td> <td width="23.5%" valign="top">
                <div class="meta-header">FECHA</div>
                    <div class="meta-body">{{ optional($pedido->fecha_pedido)->format('d/m/Y') ?? optional($pedido->fecha)->format('d/m/Y') ?? ($pedido->fecha_pedido ?? $pedido->fecha ?? '') }}</div>
            </td>

            <td width="2%"></td> <td width="23.5%" valign="top">
                <div class="meta-header">Nº DE PEDIDO</div>
                <div class="meta-body">{{ $pedido->numero_pedido ?? '' }}</div>
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
                    $precio = (float) ($line['precio_unitario'] ?? ($line['precio'] ?? 0));
                    $totalLinea = (float) ($line['total'] ?? 0);
                    $medida = $line['medida'] ?? ($line['unidad'] ?? null);
                    $medida = is_string($medida) ? trim($medida) : $medida;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line['descripcion'] ?? $line['articulo'] ?? '' }}</td>
                    <td align="right">{{ number_format($cantidad, 2, ',', '.') }}</td>
                    <td align="center">{{ $medida ? e($medida) : '' }}</td>
                    <td align="right">{{ number_format($precio, 2, ',', '.') }} €</td>
                    <td align="right">{{ number_format($totalLinea, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="total-block">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="right">
                     <div style="display: inline-block; text-align: right; width: 160px;">
                         <strong style="color: #6b7b8f; font-size: 13px;">Total:</strong><br>
                        <div class="total-box" style="display: block; text-align: center;">
                            {{ number_format((float) $pedido->total ?? 0, 2, ',', '.') }} €
                         </div>
                     </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>