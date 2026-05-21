@extends('adminlte::page')

@section('title', 'Previsualizar albarán - MoncobraCRM')

@section('css')
    <style>
        .albaran-preview-shell {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .albaran-preview-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            background: linear-gradient(135deg, #17385d, #2a6fb0);
            color: #fff;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(23, 56, 93, 0.18);
        }

        .albaran-preview-toolbar h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
        }

        .albaran-preview-toolbar p {
            margin: 4px 0 0;
            opacity: 0.9;
        }

        .albaran-preview-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .albaran-preview-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
            transition: transform .15s ease, background .15s ease;
        }

        .albaran-preview-btn:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .albaran-preview-btn--solid {
            background: #fff;
            color: #17385d;
            border-color: #fff;
        }

        .albaran-preview-btn--solid:hover {
            color: #17385d;
            background: #f4f8fc;
        }

        .albaran-preview-frame {
            width: 100%;
            min-height: 88vh;
            border: 1px solid #d8e1ec;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(16, 34, 53, 0.08);
        }

        .albaran-preview-note {
            margin: 0;
            padding: 0 4px;
            color: #5f6d80;
            font-size: 0.95rem;
        }
    </style>
@endsection

@php
    $pdfUrlWithPresupuesto = $pdfUrlWithPresupuesto ?? route('albaranes.pdf.file', $albaran);
    $pdfUrlWithoutPresupuesto = $pdfUrlWithoutPresupuesto ?? route('albaranes.pdf.file', $albaran) . '?with_presupuesto=0';
    $downloadUrlWithPresupuesto = $downloadUrlWithPresupuesto ?? route('albaranes.pdf.download', $albaran);
    $downloadUrlWithoutPresupuesto = $downloadUrlWithoutPresupuesto ?? route('albaranes.pdf.download', $albaran) . '?with_presupuesto=0';
@endphp

@section('content_header')
    <div class="albaran-preview-toolbar">
        <div>
            <h1>Previsualizar albarán {{ $albaran->numero ?: '' }}</h1>
            <p>Revisa el documento antes de imprimirlo o descargarlo.</p>
        </div>

        <div class="albaran-preview-actions">
            <button type="button" id="btn-with-presupuesto" class="albaran-preview-btn albaran-preview-btn--solid" onclick="setPreview(true)">
                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                Con Valoración
            </button>
            <button type="button" id="btn-without-presupuesto" class="albaran-preview-btn" onclick="setPreview(false)">
                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                Sin Valoración
            </button>

            <a id="open-pdf-link" href="{{ $pdfUrlWithPresupuesto }}" class="albaran-preview-btn" target="_blank" rel="noopener">
                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                Abrir PDF
            </a>
            <a id="download-pdf-link" href="{{ $downloadUrlWithPresupuesto }}" class="albaran-preview-btn" target="_blank" rel="noopener">
                <i class="fas fa-download" aria-hidden="true"></i>
                Descargar
            </a>
            <button type="button" class="albaran-preview-btn" onclick="printPreviewPdf()">
                <i class="fas fa-print" aria-hidden="true"></i>
                Imprimir
            </button>
        </div>
    </div>
@endsection

@section('content')
    <section class="albaran-preview-shell">
        <p class="albaran-preview-note">
            Si tu navegador bloquea la impresión directa del visor, usa "Abrir PDF" y luego imprime desde la pestaña del documento.
        </p>

        <iframe
            id="albaran-preview-frame"
            class="albaran-preview-frame"
            src="{{ $pdfUrlWithPresupuesto }}"
            title="Vista previa del albarán"
        ></iframe>
    </section>

    <script>
        function setPreview(withPresupuesto) {
            const frame = document.getElementById('albaran-preview-frame');
            const openLink = document.getElementById('open-pdf-link');
            const downloadLink = document.getElementById('download-pdf-link');
            const withUrl = @json($pdfUrlWithPresupuesto);
            const withoutUrl = @json($pdfUrlWithoutPresupuesto);
            const withDownload = @json($downloadUrlWithPresupuesto);
            const withoutDownload = @json($downloadUrlWithoutPresupuesto);

            if (withPresupuesto) {
                frame.src = withUrl;
                openLink.href = withUrl;
                downloadLink.href = withDownload;
                document.getElementById('btn-with-presupuesto').classList.add('albaran-preview-btn--solid');
                document.getElementById('btn-without-presupuesto').classList.remove('albaran-preview-btn--solid');
            } else {
                frame.src = withoutUrl;
                openLink.href = withoutUrl;
                downloadLink.href = withoutDownload;
                document.getElementById('btn-with-presupuesto').classList.remove('albaran-preview-btn--solid');
                document.getElementById('btn-without-presupuesto').classList.add('albaran-preview-btn--solid');
            }
        }

        function printPreviewPdf() {
            const frame = document.getElementById('albaran-preview-frame');
            if (!frame || !frame.contentWindow) {
                const openLink = document.getElementById('open-pdf-link');
                window.open(openLink.href, '_blank', 'noopener');
                return;
            }

            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch (error) {
                const openLink = document.getElementById('open-pdf-link');
                window.open(openLink.href, '_blank', 'noopener');
            }
        }
    </script>
@endsection
