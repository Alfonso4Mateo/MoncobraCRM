<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha Técnica EPIs - {{ $puesto->nombre }}</title>
    <style>
        /* MÁRGENES NATIVOS SIN TRUCOS */
        @page { margin: 15mm 15mm 40mm 15mm; }
        
        body { 
            margin: 0; 
            padding: 0; 
            font-family: Arial, Helvetica, sans-serif; 
            color: #17385d; 
        }

        /* FOOTER FIJO NATIVO */
        footer {
            position: fixed; 
            bottom: -30mm; /* Se aloja en el margen inferior del @page */
            left: 0; 
            right: 0; 
            height: 25mm; 
        }

        .muted { color: #6b7b8f; font-size: 12px; }
        
        /* TÍTULO CENTRAL */
        .doc-title-wrap { text-align: center; margin: 20px 0; }
        .doc-title { font-size: 17px; font-weight: 900; color: #17385d; margin: 0 0 6px 0; text-transform: uppercase; }
        .doc-subtitle { font-size: 11px; color: #6b7b8f; margin: 0; }

        /* TABLA PRINCIPAL */
        .doc-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
            font-size: 11px; 
            border-bottom: 1px solid #7db0e4; 
        }
        .doc-table th { background: #2a6fb0; color: #fff; padding: 8px; text-align: left; font-size: 10px; text-transform: uppercase; border: 1px solid #2a6fb0; }
        .doc-table td { 
            border-left: 1px solid #7db0e4; 
            border-right: 1px solid #7db0e4; 
            border-bottom: 1px solid #eef3f8;
            padding: 8px; 
            vertical-align: middle; 
            word-wrap: break-word; 
        }

        .doc-table.compact { font-size: 10px; }
        .doc-table.compact th { padding: 5px; }
        .doc-table.compact td { padding: 5px; }

        .epi-cat-tag { display: block; font-size: 9px; color: #6b7b8f; text-transform: uppercase; margin-top: 3px; }
        .epi-dotacion--condicional { color: #b45309; font-weight: bold; }

        /* FIRMAS Y PIE */
        .signatures-table { width: 100%; border-collapse: collapse; margin-top: 0; table-layout: fixed; }
        .sig-box { border: 1px solid #7db0e4; border-top: none; padding: 10px; height: 60px; vertical-align: top; background: #fff; word-wrap: break-word; }
        .sig-box__role { font-size: 9.5px; font-weight: 800; color: #2a6fb0; text-transform: uppercase; margin-bottom: 6px; }
        .sig-box__meta { font-size: 9.5px; color: #17385d; line-height: 1.4; }

        .footer-bottom { 
            width: 100%; 
            font-size: 9px; 
            color: #6b7b8f; 
            border-top: 1px solid #7db0e4; 
            padding-top: 6px; 
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <!-- EL FOOTER SE DECLARA PRIMERO PARA QUE APAREZCA EN TODAS LAS PÁGINAS -->
    <footer>
        <div style="text-align: left; padding-left: 10px; letter-spacing: 0.05em; background: #2a6fb0; color: #fff; padding: 6px 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; border: 1px solid #2a6fb0;">
            VALIDACIÓN OFICIAL E INFORMACIÓN
        </div>
        <table class="signatures-table">
            <tr>
                <td width="49%" class="sig-box">
                    <div class="sig-box__role">SERVICIO PREVENCIÓN SST</div>
                    <div class="sig-box__meta">
                        <strong style="color: #2a6fb0;">{{ $configPdf['sello_prevencion'] }}</strong><br>
                        {{ $configPdf['dpto_prevencion'] }}<br>
                        Norma Ref: {{ $configPdf['codigo'] }}
                    </div>
                </td>
                <td width="2%"></td>
                <td width="49%" class="sig-box">
                    <div class="sig-box__role">RESPONSABLE DE DELEGACIÓN</div>
                    <div class="sig-box__meta">
                        <strong style="color: #2a6fb0;">{{ $configPdf['sello_delegacion'] }}</strong><br>
                        {{ $configPdf['dpto_delegacion'] }}<br>
                        Fecha: {{ $configPdf['fecha_emision'] }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="footer-bottom" cellpadding="0" cellspacing="0">
            <tr>
                <td width="55%"><strong>MONCOBRA, S.A.</strong> • Sistema Documental Integrado SST ({{ $configPdf['marco_prl'] }})</td>
                <td width="45%" align="right"><strong>{{ $configPdf['codigo'] }} {{ $configPdf['revision'] }}</strong> • Generado el {{ now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </footer>

    <!-- CONTENIDO PRINCIPAL -->
    <main>
        @php
            $episList = $puesto->epis ?? collect();
            $usarFormatoCompacto = $episList->count() > 10;
        @endphp

        <!-- LOGOS -->
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
            <tr>
                <td width="50%" valign="top">
                    <img src="{{ public_path('images/moncobra-1l.png') }}" alt="logo" style="max-height: 60px; max-width: 250px;">
                    <div style="font-size: 9.5px; color: #17385d; font-weight: bold; margin-top: 6px;">DPTO. SEGURIDAD Y SALUD EN EL TRABAJO</div>
                    <div style="font-size: 8.5px; color: #6b7b8f; margin-top: 2px;">Dirección de Operaciones y Prevención Mancomunada</div>
                </td>
                <td width="50%" align="right" valign="top">
                    <img src="{{ public_path('images/aenor.png') }}" alt="aenor" style="max-height: 55px; margin-left: 4px;">
                    <img src="{{ public_path('images/aenor2.png') }}" alt="aenor2" style="max-height: 55px; margin-left: 4px;">
                    <img src="{{ public_path('images/aenor3.jpg') }}" alt="aenor3" style="max-height: 55px; margin-left: 4px;">
                    <img src="{{ public_path('images/eqa.png') }}" alt="eqa" style="max-height: 55px; margin-left: 4px;">
                </td>
            </tr>
        </table>

        <!-- BARRA METADATOS (SOLUCIÓN DEFINITIVA: 1 TABLA, 2 FILAS, BORDER-SPACING) -->
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: separate; border-spacing: 8px 0; margin-left: -8px; margin-right: -8px; margin-bottom: 20px;">
            <tr>
                <th style="background: #2a6fb0; color: #fff; padding: 6px; font-size: 9px; font-weight: bold; text-align: center; text-transform: uppercase; border: 1px solid #2a6fb0; border-bottom: none;">CÓDIGO</th>
                <th style="background: #2a6fb0; color: #fff; padding: 6px; font-size: 9px; font-weight: bold; text-align: center; text-transform: uppercase; border: 1px solid #2a6fb0; border-bottom: none;">FECHA EMISIÓN</th>
                <th style="background: #2a6fb0; color: #fff; padding: 6px; font-size: 9px; font-weight: bold; text-align: center; text-transform: uppercase; border: 1px solid #2a6fb0; border-bottom: none;">PUESTO / PERFIL</th>
                <th style="background: #2a6fb0; color: #fff; padding: 6px; font-size: 9px; font-weight: bold; text-align: center; text-transform: uppercase; border: 1px solid #2a6fb0; border-bottom: none;">MARCO PRL</th>
                <th style="background: #2a6fb0; color: #fff; padding: 6px; font-size: 9px; font-weight: bold; text-align: center; text-transform: uppercase; border: 1px solid #2a6fb0; border-bottom: none;">VERSIÓN</th>
            </tr>
            <tr>
                <td style="background: #fff; border: 1px solid #2a6fb0; border-top: none; padding: 10px; font-size: 10px; font-weight: bold; text-align: center; vertical-align: middle; word-wrap: break-word;">{{ $configPdf['codigo'] }}</td>
                <td style="background: #fff; border: 1px solid #2a6fb0; border-top: none; padding: 10px; font-size: 10px; font-weight: bold; text-align: center; vertical-align: middle; word-wrap: break-word;">{{ $configPdf['fecha_emision'] }}</td>
                <td style="background: #fff; border: 1px solid #2a6fb0; border-top: none; padding: 10px; font-size: 10px; font-weight: bold; text-align: center; vertical-align: middle; word-wrap: break-word;">{{ $puesto->nombre }}</td>
                <td style="background: #fff; border: 1px solid #2a6fb0; border-top: none; padding: 10px; font-size: 10px; font-weight: bold; text-align: center; vertical-align: middle; word-wrap: break-word;">{{ $configPdf['marco_prl'] }}</td>
                <td style="background: #fff; border: 1px solid #2a6fb0; border-top: none; padding: 10px; font-size: 10px; font-weight: bold; text-align: center; vertical-align: middle; word-wrap: break-word;">{{ $configPdf['revision'] }}</td>
            </tr>
        </table>

        <!-- TÍTULO CENTRAL -->
        <div class="doc-title-wrap">
            <h1 class="doc-title">EPIS OBLIGATORIOS PARA EL PUESTO DE TRABAJO</h1>
            <p class="doc-subtitle">Norma Moncobra SST • Evaluación de Riesgos Laborales y Protección Individual</p>
        </div>

        <!-- TABLA MATRIZ DE EPIS -->
        <table class="doc-table {{ $usarFormatoCompacto ? 'compact' : '' }}">
            <thead>
                <tr>
                    <th width="6%" style="text-align: center; border-left: none;">Nº</th>
                    <th width="44%">EQUIPO DE PROTECCIÓN INDIVIDUAL (EPI)</th>
                    <th width="28%">NORMA EN / ESPECIFICACIÓN</th>
                    <th width="22%" style="border-right: none;">DOTACIÓN REGLAMENTARIA</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($episList as $index => $epi)
                    @php
                        $cantidad = (int) ($epi->pivot->cantidad ?? 1);
                        $nombreEpi = trim((string) $epi->nombre);
                        $descEpi = trim((string) ($epi->descripcion ?? ''));
                        $textoMinuscula = mb_strtolower($nombreEpi . ' ' . $descEpi);

                        $esCondicional = str_starts_with($nombreEpi, '*')
                            || str_contains($textoMinuscula, 'almacén')
                            || str_contains($textoMinuscula, 'almacen')
                            || str_contains($textoMinuscula, 'condicional')
                            || str_contains($textoMinuscula, 'permiso');
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: bold; color: #6b7b8f;">
                            {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td>
                            <strong>{{ $nombreEpi }}</strong>
                            @if (!empty($epi->categoria))
                                <span class="epi-cat-tag">{{ $epi->categoria }}</span>
                            @endif
                        </td>
                        <td style="font-family: 'Courier New', Courier, monospace; font-weight: bold; font-size: 9px;">
                            {{ $descEpi !== '' ? $descEpi : 'Homologación CE / UNE-EN' }}
                        </td>
                        <td>
                            @if ($esCondicional)
                                <span class="epi-dotacion--condicional">
                                    * Almacén ({{ $cantidad }} {{ $cantidad === 1 ? 'ud.' : 'uds.' }})
                                </span>
                            @else
                                <strong>Permanente diario ({{ $cantidad }} {{ $cantidad === 1 ? 'ud.' : 'uds.' }})</strong>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: #6b7b8f;">
                            No se han asignado Equipos de Protección Individual (EPIs) a este perfil formativo todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>

</body>
</html>