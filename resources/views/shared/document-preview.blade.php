@php
    $previewPrefix = $previewPrefix ?? 'document';
    $heading = $heading ?? 'Previsualizar documento';
    $subheading = $subheading ?? 'Revisa el documento antes de imprimirlo o descargarlo.';
    $note = $note ?? 'Si tu navegador bloquea la impresión directa del visor, usa "Abrir PDF" y luego imprime desde la pestaña del documento.';
    $pdfUrl = $pdfUrl ?? '#';
    $downloadUrl = $downloadUrl ?? $pdfUrl;
    $iframeId = $iframeId ?? $previewPrefix . '-preview-frame';
    $iframeTitle = $iframeTitle ?? 'Vista previa del documento';
    $openLabel = $openLabel ?? 'Abrir PDF';
    $downloadLabel = $downloadLabel ?? 'Descargar';
    $printLabel = $printLabel ?? 'Imprimir';
    $showDefaultActions = $showDefaultActions ?? true;
    $toolbarExtras = $toolbarExtras ?? '';
    $afterContent = $afterContent ?? '';
@endphp

@section('css')
    <style>
        .{{ $previewPrefix }}-preview-shell {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .{{ $previewPrefix }}-preview-toolbar {
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

        .{{ $previewPrefix }}-preview-toolbar h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
        }

        .{{ $previewPrefix }}-preview-toolbar p {
            margin: 4px 0 0;
            opacity: 0.9;
        }

        .{{ $previewPrefix }}-preview-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .{{ $previewPrefix }}-preview-btn {
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

        .{{ $previewPrefix }}-preview-btn:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .{{ $previewPrefix }}-preview-btn--solid {
            background: #fff;
            color: #17385d;
            border-color: #fff;
        }

        .{{ $previewPrefix }}-preview-btn--solid:hover {
            color: #17385d;
            background: #f4f8fc;
        }

        .{{ $previewPrefix }}-preview-frame {
            width: 100%;
            min-height: 88vh;
            border: 1px solid #d8e1ec;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(16, 34, 53, 0.08);
        }

        .{{ $previewPrefix }}-preview-note {
            margin: 0;
            padding: 0 4px;
            color: #5f6d80;
            font-size: 0.95rem;
        }
    </style>
@endsection

@section('content_header')
    <div class="{{ $previewPrefix }}-preview-toolbar">
        <div>
            <h1>{{ $heading }}</h1>
            <p>{{ $subheading }}</p>
        </div>

        <div class="{{ $previewPrefix }}-preview-actions">
            {!! $toolbarExtras !!}
            @if ($showDefaultActions)
                <a href="{{ $pdfUrl }}" class="{{ $previewPrefix }}-preview-btn {{ $previewPrefix }}-preview-btn--solid" target="_blank" rel="noopener">
                    <i class="fas fa-file-pdf" aria-hidden="true"></i>
                    {{ $openLabel }}
                </a>
                <a href="{{ $downloadUrl }}" class="{{ $previewPrefix }}-preview-btn" target="_blank" rel="noopener">
                    <i class="fas fa-download" aria-hidden="true"></i>
                    {{ $downloadLabel }}
                </a>
                <button type="button" class="{{ $previewPrefix }}-preview-btn" onclick="printPreviewPdf()">
                    <i class="fas fa-print" aria-hidden="true"></i>
                    {{ $printLabel }}
                </button>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <section class="{{ $previewPrefix }}-preview-shell">
        <p class="{{ $previewPrefix }}-preview-note">{{ $note }}</p>

        <iframe
            id="{{ $iframeId }}"
            class="{{ $previewPrefix }}-preview-frame"
            src="{{ $pdfUrl }}"
            title="{{ $iframeTitle }}"
        ></iframe>
    </section>

    {!! $afterContent !!}

    <script>
        function printPreviewPdf() {
            const frame = document.getElementById(@json($iframeId));
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