@extends('adminlte::page')

@section('title', 'Previsualizar pedido - MoncobraCRM')

@section('css')
    <style>
        .pedido-preview-shell { display:flex; flex-direction:column; gap:16px }
        .pedido-preview-toolbar { display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; padding:16px 18px; background:linear-gradient(135deg,#17385d,#2a6fb0); color:#fff; border-radius:16px }
        .pedido-preview-toolbar h1{margin:0;font-size:1.5rem;font-weight:800}
        .pedido-preview-actions{display:flex;gap:10px}
        .pedido-preview-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;font-weight:700;text-decoration:none;border:1px solid rgba(255,255,255,0.22);color:#fff;background:rgba(255,255,255,0.12)}
        .pedido-preview-btn:hover{transform:translateY(-1px);background:rgba(255,255,255,0.2)}
        .pedido-preview-btn--solid{background:#fff;color:#17385d;border-color:#fff}
        .pedido-preview-frame{width:100%;min-height:88vh;border:1px solid #d8e1ec;border-radius:16px;background:#fff}
        .pedido-preview-note{margin:0;padding:0 4px;color:#5f6d80;font-size:0.95rem}
    </style>
@endsection

@section('content_header')
    <div class="pedido-preview-toolbar">
        <div>
            <h1>Previsualizar pedido {{ $pedido->numero_pedido ?: '' }}</h1>
            <p>Revisa el documento antes de imprimirlo o descargarlo.</p>
        </div>

        <div class="pedido-preview-actions">
            <a href="{{ $pdfUrl }}" class="pedido-preview-btn pedido-preview-btn--solid" target="_blank" rel="noopener">
                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                Abrir PDF
            </a>
            <a href="{{ $downloadUrl }}" class="pedido-preview-btn" target="_blank" rel="noopener">
                <i class="fas fa-download" aria-hidden="true"></i>
                Descargar
            </a>
            <button type="button" class="pedido-preview-btn" onclick="printPreviewPdf()">
                <i class="fas fa-print" aria-hidden="true"></i>
                Imprimir
            </button>
        </div>
    </div>
@endsection

@section('content')
    <section class="pedido-preview-shell">
        <p class="pedido-preview-note">Si tu navegador bloquea la impresión directa del visor, usa "Abrir PDF" y luego imprime desde la pestaña del documento.</p>

        <iframe id="pedido-preview-frame" class="pedido-preview-frame" src="{{ $pdfUrl }}" title="Vista previa del pedido"></iframe>
    </section>

    <script>
        function printPreviewPdf() {
            const frame = document.getElementById('pedido-preview-frame');
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
