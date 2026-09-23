@include('herramientas.partials.architectural-ledger')
@push('css')
<style>
.asset-form-ux{--ux-navy:#002442;--ux-ink:#191c1e;--ux-bg:#f7f9fb;--ux-low:#eef2f5;--ux-muted:#687681;background:var(--ux-bg);padding:4px 0 20px}.asset-form-ux .ux-progress{display:flex;align-items:center;gap:10px;margin:0 0 16px;padding:0 2px}.asset-form-ux .ux-progress-line{height:2px;background:#dce4e9;flex:1}.asset-form-ux .ux-step-tab{display:flex;align-items:center;gap:8px;background:transparent;border:0;color:#7b8991;font-size:11px;font-weight:800;padding:0;cursor:pointer;white-space:nowrap}.asset-form-ux .ux-step-tab span{display:grid;place-items:center;width:26px;height:26px;border-radius:50%;background:#e0e6ea;color:#61717c}.asset-form-ux .ux-step-tab.is-active{color:var(--ux-navy)}.asset-form-ux .ux-step-tab.is-active span{background:var(--ux-navy);color:#fff}.asset-form-ux .ux-step-tab.is-done span{background:#dff3e9;color:#08734e}.asset-form-ux .ux-step-panel{display:none;background:#fff;box-shadow:0 8px 32px #191c1e0a;padding:22px 24px 8px}.asset-form-ux .ux-step-panel.is-active{display:block;animation:ux-in .18s ease-out}@keyframes ux-in{from{opacity:.4;transform:translateY(4px)}to{opacity:1;transform:none}}.asset-form-ux .ux-step-panel>h2{font-size:18px;font-weight:800;color:var(--ux-ink);margin:0 0 4px}.asset-form-ux .ux-step-panel>p{font-size:12px;color:var(--ux-muted);margin:0 0 18px}.asset-form-ux .ux-step-actions{display:flex;justify-content:space-between;align-items:center;background:#fff;padding:14px 24px;margin-top:1px;box-shadow:0 8px 32px #191c1e0a}.asset-form-ux .ux-step-actions button{border:0;border-radius:5px;padding:9px 15px;font-size:12px;font-weight:800;cursor:pointer}.asset-form-ux .ux-next{background:var(--ux-navy);color:#fff}.asset-form-ux .ux-back{background:#e8edf1;color:#284252}.asset-form-ux .ux-save{display:none;background:linear-gradient(110deg,#002442,#155785);color:#fff}.asset-form-ux .ux-step-actions.is-last .ux-next{display:none}.asset-form-ux .ux-step-actions.is-last .ux-save{display:inline-block}.asset-form-ux .ux-counter{font-size:11px;color:var(--ux-muted)}.asset-form-ux .ux-required{font-size:10px;color:#87949b;margin-left:auto}.asset-form-ux .ux-error{background:#fff0ef;color:#a32328;padding:10px 12px;font-size:12px;margin-bottom:14px}.asset-form-ux .ux-error ul{margin:0;padding-left:18px}@media(max-width:640px){.asset-form-ux .ux-step-tab{font-size:0}.asset-form-ux .ux-step-tab span{font-size:11px}.asset-form-ux .ux-step-panel{padding:18px 15px 5px}.asset-form-ux .ux-step-actions{padding:12px 15px}}
</style>
<style>
.asset-form-ux .asset-smart-form > .card-footer{display:none}
</style>
@endpush

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.asset-smart-form').forEach(function (form) {
        const root = form.closest('.asset-form-ux');
        const headers = Array.from(form.querySelectorAll('.it-section, .plant-section, .met-section, .catalog-section'));
        if (!root || !headers.length) return;

        const panels = headers.map(function (header, index) {
            const panel = document.createElement('section');
            panel.className = 'ux-step-panel';
            const title = header.textContent.trim();
            const description = ['Identifica el activo y define su estado operativo.', 'Añade solo las características que tengas disponibles.', 'Completa la custodia, documentación y seguimiento.'][index] || 'Revisa la información antes de guardar.';
            panel.innerHTML = '<h2>' + title + '</h2><p>' + description + '</p>';
            header.parentNode.insertBefore(panel, header);
            header.remove();
            let next = panel.nextSibling;
            while (next && !next.matches?.('.it-section, .plant-section, .met-section, .catalog-section, .card-footer')) {
                const current = next;
                next = next.nextSibling;
                panel.appendChild(current);
            }
            return panel;
        });

        const progress = document.createElement('nav');
        progress.className = 'ux-progress';
        panels.forEach(function (panel, index) {
            const tab = document.createElement('button');
            tab.type = 'button';
            tab.className = 'ux-step-tab';
            tab.innerHTML = '<span>' + (index + 1) + '</span><b>' + panel.querySelector('h2').textContent + '</b>';
            tab.addEventListener('click', function () { activate(index); });
            progress.appendChild(tab);
            if (index < panels.length - 1) {
                const line = document.createElement('i');
                line.className = 'ux-progress-line';
                progress.appendChild(line);
            }
        });
        form.parentNode.insertBefore(progress, form);

        const controls = document.createElement('div');
        controls.className = 'ux-step-actions';
        controls.innerHTML = '<span class="ux-counter"></span><span class="ux-required">* Campos obligatorios</span><button type="button" class="ux-back"><i class="fas fa-arrow-left"></i> Anterior</button><button type="button" class="ux-next">Siguiente <i class="fas fa-arrow-right"></i></button><button type="submit" class="ux-save"><i class="fas fa-save"></i> Guardar activo</button>';
        form.appendChild(controls);
        const tabs = Array.from(progress.querySelectorAll('.ux-step-tab'));
        let active = 0;

        function activate(index) {
            active = Math.max(0, Math.min(index, panels.length - 1));
            panels.forEach(function (panel, panelIndex) { panel.classList.toggle('is-active', panelIndex === active); });
            tabs.forEach(function (tab, tabIndex) { tab.classList.toggle('is-active', tabIndex === active); tab.classList.toggle('is-done', tabIndex < active); });
            controls.querySelector('.ux-counter').textContent = 'Paso ' + (active + 1) + ' de ' + panels.length;
            controls.querySelector('.ux-back').style.visibility = active === 0 ? 'hidden' : 'visible';
            controls.classList.toggle('is-last', active === panels.length - 1);
            window.scrollTo({ top: root.offsetTop - 20, behavior: 'smooth' });
        }
        function validStep() {
            const fields = Array.from(panels[active].querySelectorAll('input, select, textarea'));
            for (const field of fields) {
                if (!field.checkValidity()) { field.reportValidity(); return false; }
            }
            return true;
        }
        controls.querySelector('.ux-next').addEventListener('click', function () { if (validStep()) activate(active + 1); });
        controls.querySelector('.ux-back').addEventListener('click', function () { activate(active - 1); });
        activate(0);
    });
});
</script>
@endpush
