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

@section('content_header')
    <div class="albaran-preview-toolbar">
        <div>
            <h1>Previsualizar albarán {{ $albaran->numero ?: '' }}</h1>
            <p>Revisa el documento antes de imprimirlo o descargarlo.</p>
        </div>

        <div class="albaran-preview-actions">
            <a href="{{ $pdfUrl }}" class="albaran-preview-btn albaran-preview-btn--solid" target="_blank" rel="noopener">
                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                Abrir PDF
            </a>
            <a href="{{ $downloadUrl }}" class="albaran-preview-btn" target="_blank" rel="noopener">
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
            src="{{ $pdfUrl }}"
            title="Vista previa del albarán"
        ></iframe>
    </section>

    <script>
        function printPreviewPdf() {
            const frame = document.getElementById('albaran-preview-frame');
            if (!frame || !frame.contentWindow) {
                window.open(@json($pdfUrl), '_blank', 'noopener');
                return;
            }

            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch (error) {
                window.open(@json($pdfUrl), '_blank', 'noopener');
            }
        }
    </script>
@endsection
