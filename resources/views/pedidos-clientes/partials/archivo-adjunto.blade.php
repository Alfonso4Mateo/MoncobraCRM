{{--
    Input de archivo adjunto del pedido (create / edit).
    Variables opcionales: $urlActual (string|null) -> URL del documento ya guardado.
--}}
<div class="pedido-file {{ !empty($urlActual) ? 'has-current' : '' }}" id="pedido-file">
    <label for="archivo_documento" class="pedido-file__drop">
        <span class="pedido-file__icon"><i class="fas fa-cloud-upload-alt" aria-hidden="true"></i></span>
        <span class="pedido-file__text">
            <strong>{{ !empty($urlActual) ? 'Reemplazar documento del cliente' : 'Adjuntar documento del cliente' }}</strong>
            <small>PDF o imagen · máx. 10 MB · haz clic o arrastra aquí</small>
        </span>
        <input type="file" name="archivo_documento" id="archivo_documento"
               class="d-none" accept=".pdf,.jpg,.jpeg,.png,.webp">
    </label>

    <div class="pedido-file__selected" hidden>
        <span class="badge badge-success px-2 py-1" style="white-space: nowrap; flex-shrink: 0;">
            <i class="fas fa-check mr-1" aria-hidden="true"></i> Archivo listo para guardar
        </span>
        <span class="pedido-file__name"></span>
        <div class="pedido-file__meta">
            <span class="pedido-file__size"></span>
            <button type="button" class="pedido-file__clear" aria-label="Quitar archivo" title="Quitar archivo">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <small class="pedido-file__note" hidden>Se guardará al pulsar guardar. Límite real del servidor: {{ ini_get('upload_max_filesize') }}.</small>
    <small class="pedido-file__error" hidden></small>
    @error('archivo_documento')
        <small class="text-danger font-weight-bold d-block mt-1">{{ $message }}</small>
    @enderror

    @if (!empty($urlActual))
        <div class="pedido-file__current">
            <i class="fas fa-check-circle pedido-file__check" aria-hidden="true"></i>
            <span>Documento adjunto actualmente</span>
            <a href="{{ $urlActual }}" target="_blank" rel="noopener" class="font-weight-bold">Ver</a>
            <label class="pedido-file__remove">
                <input type="checkbox" name="eliminar_archivo" value="1"> Quitar
            </label>
        </div>
    @endif
</div>

<style>
    .pedido-file { display:flex; flex-direction:column; gap:8px; width:100%; }
    .pedido-file__drop { display:flex; align-items:center; gap:14px; margin:0; padding:14px 16px;
        border:2px dashed #cbd5e1; border-radius:12px; background:#f8fafc; cursor:pointer;
        transition:border-color .15s, background .15s; }
    .pedido-file__drop:hover, .pedido-file.is-dragover .pedido-file__drop { border-color:#2563eb; background:#eff6ff; }
    .pedido-file__icon { font-size:1.6rem; color:#2563eb; flex-shrink:0; }
    .pedido-file__text { display:flex; flex-direction:column; min-width:0; }
    .pedido-file__text small, .pedido-file__note { color:#64748b; }
    .pedido-file__selected, .pedido-file__current {
        display:flex;
        align-items:center;
        flex-wrap:wrap;
        gap:8px 12px;
        padding:10px 14px;
        border:1px solid #86efac;
        background:#f0fdf4;
        border-radius:10px;
    }
    .pedido-file__selected[hidden], .pedido-file__note[hidden], .pedido-file__error[hidden] { display:none !important; }
    .pedido-file__check { color:#16a34a; font-size:1.1rem; flex:none; }
    .pedido-file__name {
        flex:1 1 160px;
        min-width:140px;
        max-width:100%;
        font-weight:700;
        color:#065f46;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .pedido-file__meta {
        display:flex;
        align-items:center;
        gap:8px;
        margin-left:auto;
        flex-shrink:0;
    }
    .pedido-file__size { color:#047857; font-weight:600; white-space:nowrap; font-size:.85rem; }
    .pedido-file__clear { border:0; background:none; color:#ef4444; cursor:pointer; padding:4px 6px; line-height:1; border-radius:6px; }
    .pedido-file__clear:hover { background:#fee2e2; }
    .pedido-file__error { color:#dc2626; font-weight:600; }
    .pedido-file__remove { margin:0 0 0 auto; font-size:.85rem; color:#b91c1c; cursor:pointer; display:inline-flex; align-items:center; gap:5px; }
    .pedido-file.has-selection .pedido-file__current { display:none; }
</style>

<script>
    (function () {
        var root = document.getElementById('pedido-file');
        if (!root) return;
        var input = root.querySelector('input[type="file"]');
        var selected = root.querySelector('.pedido-file__selected');
        var nameEl = root.querySelector('.pedido-file__name');
        var sizeEl = root.querySelector('.pedido-file__size');
        var note = root.querySelector('.pedido-file__note');
        var errorEl = root.querySelector('.pedido-file__error');
        var MAX = 10 * 1024 * 1024;
        var OK = /\.(pdf|jpe?g|png|webp)$/i;
        var fmt = function (b) {
            return b < 1048576 ? Math.max(1, Math.round(b / 1024)) + ' KB' : (b / 1048576).toFixed(1) + ' MB';
        };

        var render = function () {
            var f = input.files[0];
            errorEl.hidden = true;
            if (f && (!OK.test(f.name) || f.size > MAX)) {
                errorEl.textContent = !OK.test(f.name)
                    ? 'Formato no permitido (PDF, JPG, PNG o WEBP).'
                    : 'El archivo supera los 10 MB.';
                errorEl.hidden = false;
                input.value = '';
            }
            var file = input.files[0];
            selected.hidden = !file;
            note.hidden = !file;
            root.classList.toggle('has-selection', !!file);
            if (file) {
                nameEl.textContent = file.name;
                nameEl.title = file.name;
                sizeEl.textContent = fmt(file.size);
            }
        };

        input.addEventListener('change', render);
        root.querySelector('.pedido-file__clear').addEventListener('click', function () {
            input.value = '';
            render();
        });

        ['dragenter', 'dragover'].forEach(function (ev) {
            root.addEventListener(ev, function (e) { e.preventDefault(); root.classList.add('is-dragover'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            root.addEventListener(ev, function (e) { e.preventDefault(); root.classList.remove('is-dragover'); });
        });
        root.addEventListener('drop', function (e) {
            if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; render(); }
        });
    })();
</script>