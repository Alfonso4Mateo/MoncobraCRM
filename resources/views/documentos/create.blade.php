@extends('adminlte::page')

@section('title', 'Cargar Documento - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/documentos-create.css'])
@endsection

@section('content')
    <section class="docs-upload-page">
        <header class="docs-upload-hero">
            <div>
                <div class="docs-upload-crumbs">
                    <span>GESTION DE ARCHIVOS</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>CARGAR DOCUMENTOS</span>
                </div>
                <h1>Cargar Nuevo Documento</h1>
                <p>Centraliza nuevos registros industriales y asociarlos a su proyecto y cliente.</p>
            </div>
                <a href="{{ route('documentos.index') }}" class="docs-upload-cancel btn-back">
                    <i class="fas fa-arrow-left"></i> Volver atrás
                </a>
        </header>

        @if ($errors->any())
            <div class="docs-status-banner docs-status-banner--error">
                <strong>No se pudo cargar el documento.</strong>
                <div>Revisa los campos marcados y vuelve a intentarlo.</div>
            </div>
        @endif

        @if(session('status'))
            <div class="docs-status-banner docs-status-banner--success">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('documentos.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="docs-upload-layout">
                <div class="docs-upload-main">
                    <div class="docs-type-selector" role="tablist">
                        <select name="tipo" class="type-chip docs-type-select">
                            @foreach($tiposCarga as $key => $t)
                                <option value="{{ $key }}" @selected(old('tipo', 'albaranes') === $key)>{{ $t['label'] }}</option>
                            @endforeach
                        </select>

                        <section class="docs-dropzone">
                            <div class="docs-dropzone-icon">
                                <i class="fas fa-cloud-arrow-up"></i>
                            </div>
                            <h2>Arrastra y suelta tus archivos aqui</h2>
                            <p>o haz clic para seleccionar uno o varios documentos desde tu equipo.</p>
                            <input id="documentos" type="file" name="documentos[]" accept=".pdf,.jpg,.jpeg,.png,.xml" class="docs-file-input" multiple required>
                            <label for="documentos" class="docs-dropzone-btn">Seleccionar archivos</label>
                            <span class="docs-dropzone-note">Soporta PDF, JPG, PNG, XML. Si los documentos son de tipos distintos, súbelos por separado.</span>
                            @error('documentos') <div class="text-danger">{{ $message }}</div> @enderror
                            @error('documentos.*') <div class="text-danger">{{ $message }}</div> @enderror
                        </section>
                    </div>

                    <section id="docs-detected" class="docs-detected" aria-live="polite" style="display:none;">
                        <header>
                            <h4>Datos detectados</h4>
                            <p class="muted">Revisa, edita y aplica los datos detectados automáticamente desde el PDF. Estos campos se enviarán al guardar.</p>
                        </header>
                        <div class="docs-detected-list">
                            <div class="docs-detected-row" data-field="tipo"><strong>Tipo:</strong> <span class="value">—</span></div>
                            <div class="docs-detected-row" data-field="fecha"><strong>Fecha:</strong> <span class="value">—</span></div>
                            <div class="docs-detected-row" data-field="numero"><strong>Número:</strong> <span class="value">—</span></div>
                            <div class="docs-detected-row" data-field="ot"><strong>OT:</strong> <span class="value">—</span></div>
                            <div class="docs-detected-row" data-field="cliente"><strong>Cliente:</strong> <span class="value">—</span></div>
                        </div>

                        <div class="docs-detected-meta" style="margin-top:12px; display:grid; gap:8px;">
                            <label for="fecha_documento">Fecha de documento</label>
                            <input id="fecha_documento" name="fecha_documento" type="date" value="{{ old('fecha_documento') }}">

                            <label for="numero_documento">Nº de documento</label>
                            <input id="numero_documento" name="numero_documento" type="text" placeholder="Ej: ALB-2025-001" value="{{ old('numero_documento') }}">

                            <label for="ot_documento">OT asociada</label>
                            <input id="ot_documento" name="ot_documento" type="text" placeholder="Orden de trabajo" value="{{ old('ot_documento') }}">

                            <label for="cliente_documento">Proveedor / Cliente</label>
                            <input id="cliente_documento" name="cliente_documento" type="text" placeholder="Buscar entidad..." value="{{ old('cliente_documento') }}">

                            <label for="nombre_trabajador">Nombre del trabajador (RRHH)</label>
                            <input id="nombre_trabajador" name="nombre_trabajador" type="text" placeholder="Ej: Alfonso Mateo">

                            <label for="id_rrhh">ID RRHH</label>
                            <input id="id_rrhh" name="id_rrhh" type="text" placeholder="Ej: 104852">
                        </div>

                        <div class="docs-detected-actions">
                            <button id="discard-detected" type="button" class="docs-upload-cancel">Descartar</button>
                            <button type="submit" class="docs-upload-submit" style="background:linear-gradient(135deg,var(--docs-primary),var(--docs-primary-dark));">Cargar Documento</button>
                        </div>

                        <pre id="detected-raw" style="display:none; white-space:pre-wrap; margin-top:10px; background:#f8fafc; padding:8px; border-radius:6px; border:1px solid #e6eef6; max-height:180px; overflow:auto;"> </pre>
                    </section>
                </div>
            </div>
        </form>

        <section class="docs-upload-assistant">
            <div class="assistant-icon">
                <i class="fas fa-wand-magic-sparkles"></i>
            </div>
            <div>
                <h4>Asistente de clasificación</h4>
                <p>La detección automática se aplica arriba. Revisa los datos detectados, edítalos si es necesario y luego guarda.</p>
            </div>
        </section>
    </section>
@endsection

@section('js')
<script src="/js/pdf.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.getElementById('documentos');
        const dropzone = document.querySelector('.docs-dropzone');
        const dropzoneText = dropzone.querySelector('h2');
        const dropzoneP = dropzone.querySelector('p');
        const tipoSelect = document.querySelector('select[name="tipo"]');
        const fechaInput = document.getElementById('fecha_documento');
        const numeroInput = document.getElementById('numero_documento');
        const otInput = document.getElementById('ot_documento');
        const clienteInput = document.getElementById('cliente_documento');
        const detectedPanel = document.getElementById('docs-detected');

        // Estado de datos detectados para el panel
        let detectedData = null;

        if (window.pdfjsLib) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = '/js/pdf.worker.min.js';
        } else {
            console.warn('pdfjsLib no está disponible — la detección de PDF no funcionará.');
            const dropzoneP = document.querySelector('.docs-dropzone p');
            if (dropzoneP) dropzoneP.textContent = 'La librería pdf.js no pudo cargarse — la extracción automática no estará disponible. Si el problema persiste, descarga y sirve pdf.js localmente desde tu servidor.';
        }

        // 1. Reaccionar cuando el usuario hace clic y elige archivos
        fileInput.addEventListener('change', function() {
            updateFileNames(this.files);
            if (this.files.length > 0) {
                autoClassifySelectedFiles(this.files);
            }
        });

        // 2. Efectos visuales al arrastrar archivos por encima
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = 'var(--docs-primary)';
            dropzone.style.background = '#eef2f8';
        });

        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#bfd0e4';
            dropzone.style.background = 'linear-gradient(180deg, #ffffff 0%, #f8fbff 100%)';
        });

        // 3. Reaccionar al soltar el archivo
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.borderColor = '#bfd0e4';
            dropzone.style.background = 'linear-gradient(180deg, #ffffff 0%, #f8fbff 100%)';

            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files; // Traspasamos los archivos al input oculto
                updateFileNames(e.dataTransfer.files);
                autoClassifySelectedFiles(e.dataTransfer.files);
            }
        });

        async function autoClassifySelectedFiles(files) {
            const pdfFile = Array.from(files).find((file) => file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'));

            if (!pdfFile || !window.pdfjsLib) {
                return;
            }

            dropzoneP.textContent = 'Analizando el PDF en el navegador...';

            try {
                const text = await extractPdfText(pdfFile);
                const extracted = inferMetadataFromText(text, pdfFile.name);
                console.debug('PDF extraido (primeros 1000 chars):', (text || '').slice(0,1000));
                const rawEl = document.getElementById('detected-raw');
                if (rawEl) { rawEl.textContent = text || '(sin texto extraido)'; rawEl.style.display = 'block'; }

                if (extracted.tipo && tipoSelect && Array.from(tipoSelect.options).some((option) => option.value === extracted.tipo)) {
                    tipoSelect.value = extracted.tipo;
                }

                if (extracted.fecha && fechaInput && !fechaInput.value) {
                    fechaInput.value = extracted.fecha;
                }

                if (extracted.numero && numeroInput && !numeroInput.value) {
                    numeroInput.value = extracted.numero;
                }

                if (extracted.ot && otInput && !otInput.value) {
                    otInput.value = extracted.ot;
                }

                if (extracted.cliente && clienteInput && !clienteInput.value) {
                    clienteInput.value = extracted.cliente;
                }

                const labels = [];
                if (extracted.fecha) labels.push('fecha');
                if (extracted.numero) labels.push('numero');
                if (extracted.ot) labels.push('OT');
                if (extracted.cliente) labels.push('cliente');

                if (labels.length > 0) {
                    dropzoneP.textContent = `Clasificado automáticamente: ${labels.join(', ')}.`;
                } else {
                    dropzoneP.textContent = 'PDF cargado, pero no se detectaron datos claros.';
                }

                // Populate detected-data panel and show controls
                detectedData = extracted;
                populateDetectedPanel(extracted);
                if (detectedPanel) detectedPanel.style.display = 'block';
            } catch (error) {
                console.error('No se pudo leer el PDF:', error);
                dropzoneP.textContent = 'PDF cargado, pero no se pudo leer el contenido automáticamente.';
            }
        }

        async function extractPdfText(file) {
            const arrayBuffer = await file.arrayBuffer();
            const loadingTask = window.pdfjsLib.getDocument({ data: arrayBuffer });
            const pdf = await loadingTask.promise;
            const pageTexts = [];

            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
                const page = await pdf.getPage(pageNumber);
                const textContent = await page.getTextContent();
                const pageText = textContent.items.map((item) => item.str).join(' ');
                pageTexts.push(pageText);
            }

            return pageTexts.join('\n');
        }

        function inferMetadataFromText(text, fileName) {
            const normalized = `${fileName}\n${text}`;
            const cleaned = normalized.replace(/\s+/g, ' ').trim();

            // Helper: try to parse useful tokens from filename
            function parseFromFileName(name) {
                const low = name.toLowerCase();
                const out = {};
                // date patterns in filename
                const f1 = name.match(/(\d{4})[._-](\d{2})[._-](\d{2})/);
                if (f1) out.fecha = `${f1[1]}-${f1[2]}-${f1[3]}`;
                const f2 = name.match(/(\d{2})[._-](\d{2})[._-](\d{4})/);
                if (f2) out.fecha = `${f2[3]}-${f2[2]}-${f2[1]}`;
                const num = name.match(/\b(ALB|ALBARAN|DOC|PRE|PED|SAL|ENT|TRS)[-_ ]?\d{1,6}\b/i);
                if (num) out.numero = num[0];
                return out;
            }

            // date detection (support many separators)
            const fecha = findFirstMatch(cleaned, [
                /\b(\d{2})[\/\.\-](\d{2})[\/\.\-](\d{4})\b/,
                /\b(\d{4})[\/\.\-](\d{2})[\/\.\-](\d{2})\b/,
                /\b(\d{2})[\/\.\-](\d{2})[\/\.\-](\d{2})\b/,
            ], (match, groups) => {
                if (groups[0].length === 4) return `${groups[0]}-${groups[1]}-${groups[2]}`;
                // two-digit year -> assume 20xx
                if (groups[2] && groups[2].length === 2) return `20${groups[2]}-${groups[1]}-${groups[0]}`;
                return `${groups[2]}-${groups[1]}-${groups[0]}`;
            });

            const numero = findFirstMatch(cleaned, [
                /\b(?:N[ºo°]?\s*(?:de\s)?\s*)?(?:doc(?:umento)?|albar[aá]n|presupuesto|pedido|salida|entrada|traslado)\s*[:#\-]?\s*([A-Z0-9][A-Z0-9\-\/._]{0,})\b/i,
                /\b([A-Z]{2,6}-\d{1,6}-?\d{0,6})\b/i,
                /\b(ALB|ALBARAN|DOC|PRE|PED|SAL|ENT|TRS)[-_ ]?(\d{1,6})\b/i,
                /\balbar[aá]n[-_ ]?(\d{1,6})\b/i,
            ], (match, groups) => {
                // If pattern captured prefix+number separately, normalize to PREFIX-NUM
                if (groups && groups.length === 2 && groups[0] && groups[1]) {
                    const pref = groups[0].toUpperCase();
                    const num = groups[1];
                    const short = pref.startsWith('ALB') ? 'ALB' : pref;
                    return `${short}-${num}`;
                }

                // If single-group matches, return the captured token uppercased
                if (groups && groups.length >= 1 && groups[0]) {
                    return String(groups[0]).toUpperCase();
                }

                // Fallback: full match
                return match[0].toUpperCase();
            });

            const ot = findFirstMatch(cleaned, [
                /\b(?:OT|O\.T\.)\s*[:#\-]?\s*([A-Z0-9][A-Z0-9\-\/._]{1,})\b/i,
                /\bOT\s*(\d{3,8})\b/i,
            ], (_, groups) => `OT ${groups[0]}`);

            const cliente = findFirstMatch(cleaned, [
                /\b(?:cliente|proveedor)\s*[:\-]\s*([A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚÑ0-9 ,.'\-]{2,80}?) (?=\s{2,}|\s+(?:OT|N[ºo°]?|Documento|Fecha|Presupuesto|Albar[aá]n|Pedido)\b|$)/i,
                /\b(?:cliente|proveedor)\s*[:\-]\s*([A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚÑ0-9 ,.'\-]{2,80})/i,
                /\b([A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚÑ0-9 &,.\-]{4,50})\b/g
            ], (_, groups) => groups[0].trim());

            const tipo = inferTipoFromText(cleaned);

            // fallback to filename parsing for missing fields
            const fromName = parseFromFileName(fileName || '');
            return {
                fecha: fecha || fromName.fecha || null,
                numero: numero || fromName.numero || null,
                ot: ot || null,
                cliente: cliente || null,
                tipo: tipo || null,
            };
        }

        function inferTipoFromText(text) {
            const lower = text.toLowerCase();

            if (lower.includes('presupuesto')) {
                return 'presupuestos';
            }

            if (lower.includes('albar') || lower.includes('albaran')) {
                return 'albaranes';
            }

            if (lower.includes('pedido')) {
                return 'pedidos';
            }

            if (lower.includes('salida') || lower.includes('material')) {
                return 'salidas';
            }

            if (lower.includes('entrada')) {
                return 'entradas';
            }

            if (lower.includes('traslado')) {
                return 'traslados';
            }

            return null;
        }

        function findFirstMatch(text, patterns, resolver) {
            for (const pattern of patterns) {
                const match = text.match(pattern);
                if (match) {
                    const groups = match.slice(1).filter(Boolean);
                    return resolver(match, groups);
                }
            }

            return null;
        }

        // Función para cambiar el texto de la interfaz
        function updateFileNames(files) {
            if (files.length === 1) {
                dropzoneText.textContent = files[0].name;
                dropzoneP.textContent = 'Archivo listo para subir. Haz clic en "Cargar Documento".';
            } else if (files.length > 1) {
                dropzoneText.textContent = files.length + ' archivos seleccionados';
                dropzoneP.textContent = 'Archivos listos para subir. Haz clic en "Cargar Documento".';
            } else {
                dropzoneText.textContent = 'Arrastra y suelta tus archivos aqui';
                dropzoneP.textContent = 'o haz clic para seleccionar uno o varios documentos desde tu equipo.';
            }
        }

        // Mostrar valores detectados en el panel
        function populateDetectedPanel(data) {
            if (!detectedPanel) return;
            const rows = detectedPanel.querySelectorAll('.docs-detected-row');
            rows.forEach(row => {
                const field = row.getAttribute('data-field');
                const span = row.querySelector('.value');
                if (field === 'tipo') span.textContent = data.tipo || '—';
                if (field === 'fecha') span.textContent = data.fecha || '—';
                if (field === 'numero') span.textContent = data.numero || '—';
                if (field === 'ot') span.textContent = data.ot || '—';
                if (field === 'cliente') span.textContent = data.cliente || '—';
            });
            // Also populate the editable inputs so user can directly submit
            try {
                if (data.fecha && fechaInput) fechaInput.value = data.fecha;
                if (data.numero && numeroInput) numeroInput.value = data.numero;
                if (data.ot && otInput) otInput.value = data.ot;
                if (data.cliente && clienteInput) clienteInput.value = data.cliente;
            } catch (e) {
                // ignore
            }
        }
        function discardDetected() {
            detectedData = null;
            if (!detectedPanel) return;
            const rows = detectedPanel.querySelectorAll('.docs-detected-row .value');
            rows.forEach(s => s.textContent = '—');
            detectedPanel.style.display = 'none';
        }
        // Only wire discard button (submit button is the form submit)
        if (detectedPanel) {
            const discardBtn = document.getElementById('discard-detected');
            if (discardBtn) discardBtn.addEventListener('click', function(e){ e.preventDefault(); discardDetected(); });
        }

        const camposRrhh = document.getElementById('campos_rrhh');
            tipoSelect.addEventListener('change', function() {
                if(this.value === 'certificados') {
                    camposRrhh.style.display = 'grid';
                } else {
                    camposRrhh.style.display = 'none';
                    document.getElementById('nombre_trabajador').value = '';
                    document.getElementById('id_rrhh').value = '';
                    }
                });
    });
</script>
@endsection