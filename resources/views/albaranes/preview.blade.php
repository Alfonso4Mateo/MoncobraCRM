@extends('adminlte::page')

@section('title', 'Previsualizar albarán - MoncobraCRM')

@php
    $pdfUrlWithPresupuesto = $pdfUrlWithPresupuesto ?? route('albaranes.pdf.file', $albaran) . '?with_presupuesto=1';
    $pdfUrlWithoutPresupuesto = $pdfUrlWithoutPresupuesto ?? route('albaranes.pdf.file', $albaran) . '?with_presupuesto=0';
    $downloadUrlWithPresupuesto = $downloadUrlWithPresupuesto ?? route('albaranes.pdf.download', $albaran) . '?with_presupuesto=1';
    $downloadUrlWithoutPresupuesto = $downloadUrlWithoutPresupuesto ?? route('albaranes.pdf.download', $albaran) . '?with_presupuesto=0';
@endphp

@include('shared.document-preview', [
    'previewPrefix' => 'albaran',
    'heading' => 'Previsualizar albarán ' . ($albaran->numero ?: ''),
    'subheading' => 'Revisa el documento antes de imprimirlo o descargarlo.',
    'note' => 'Si tu navegador bloquea la impresión directa del visor, usa "Abrir PDF" y luego imprime desde la pestaña del documento.',
    'pdfUrl' => $pdfUrlWithPresupuesto,
    'downloadUrl' => $downloadUrlWithPresupuesto,
    'iframeId' => 'albaran-preview-frame',
    'iframeTitle' => 'Vista previa del albarán',
    'showDefaultActions' => false,
    'toolbarExtras' => '
        <button type="button" id="btn-with-presupuesto" class="albaran-preview-btn albaran-preview-btn--solid" onclick="setPreview(true)">
            <i class="fas fa-file-pdf" aria-hidden="true"></i>
            Con Valoración
        </button>
        <button type="button" id="btn-without-presupuesto" class="albaran-preview-btn" onclick="setPreview(false)">
            <i class="fas fa-file-pdf" aria-hidden="true"></i>
            Sin Valoración
        </button>
        <a id="open-pdf-link" href="' . e($pdfUrlWithPresupuesto) . '" class="albaran-preview-btn" target="_blank" rel="noopener">
            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
            Abrir PDF
        </a>
        <a id="download-pdf-link" href="' . e($downloadUrlWithPresupuesto) . '" class="albaran-preview-btn" target="_blank" rel="noopener">
            <i class="fas fa-download" aria-hidden="true"></i>
            Descargar
        </a>
    ',
    'afterContent' => '
        <script>
            function setPreview(withPresupuesto) {
                const frame = document.getElementById("albaran-preview-frame");
                const openLink = document.getElementById("open-pdf-link");
                const downloadLink = document.getElementById("download-pdf-link");
                const withUrl = ' . json_encode($pdfUrlWithPresupuesto) . ';
                const withoutUrl = ' . json_encode($pdfUrlWithoutPresupuesto) . ';
                const withDownload = ' . json_encode($downloadUrlWithPresupuesto) . ';
                const withoutDownload = ' . json_encode($downloadUrlWithoutPresupuesto) . ';

                if (withPresupuesto) {
                    frame.src = withUrl;
                    openLink.href = withUrl;
                    downloadLink.href = withDownload;
                    document.getElementById("btn-with-presupuesto").classList.add("albaran-preview-btn--solid");
                    document.getElementById("btn-without-presupuesto").classList.remove("albaran-preview-btn--solid");
                } else {
                    frame.src = withoutUrl;
                    openLink.href = withoutUrl;
                    downloadLink.href = withoutDownload;
                    document.getElementById("btn-with-presupuesto").classList.remove("albaran-preview-btn--solid");
                    document.getElementById("btn-without-presupuesto").classList.add("albaran-preview-btn--solid");
                }
            }
        </script>
    ',
])
