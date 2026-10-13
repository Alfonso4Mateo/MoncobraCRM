<!-- ESTILOS DEL MODAL DE CLASES -->
<style>
    .clase-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); }
    .clase-modal-overlay.show { display: flex; }
    .clase-modal-dialog { background: #fff; width: 100%; max-width: 450px; padding: 24px; box-shadow: 0 16px 45px rgba(0,36,66,0.2); border-radius: 8px; display: flex; flex-direction: column; max-height: 85vh; }
    .clase-list { flex: 1; overflow-y: auto; margin: 15px 0; border: 1px solid #edf0f2; border-radius: 4px; padding: 0; list-style: none; background: #fafbfc; }
    .clase-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border-bottom: 1px solid #edf0f2; font-size: 12px; color: #273740; font-weight: 700; }
    .clase-item-btn { background: none; border: none; color: #c92328; cursor: pointer; padding: 4px 8px; transition: 0.2s; border-radius: 4px; }
    .clase-item-btn:hover { background: #fee1df; }
</style>

<div id="modal-clase" class="clase-modal-overlay" onclick="cerrarModalClase()">
    <div class="clase-modal-dialog" onclick="event.stopPropagation()">
        <h2 style="font-size: 18px; margin-bottom: 5px; color: #191c1e; font-weight: 800;">Catálogo de Clases</h2>
        <p style="font-size: 12px; color: #687681; margin-bottom: 15px;">Añade o elimina clasificaciones.</p>
        
        <div id="clase-error" class="alert alert-danger" style="display:none; font-size:12px; font-weight:bold; padding:8px; background:#fee1df; color:#c92328; border:0; border-radius: 4px; margin-bottom: 15px;"></div>

        <div style="display: flex; gap: 8px;">
            <input type="text" id="nombre_clase" class="form-control" placeholder="Nombre de la nueva clase..." style="flex: 1; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; height: 38px;">
            <button type="button" class="btn text-white" style="background:#002442; font-size:12px; font-weight:bold; height: 38px; padding: 0 16px;" id="btn-guardar-clase" onclick="guardarClaseAjax()">
                <i class="fas fa-plus"></i> Añadir
            </button>
        </div>

        <ul class="clase-list" id="lista-clases">
            @foreach($clasesCatalogo ?? [] as $cls)
                <li class="clase-item" id="cls-item-{{ $cls->id }}">
                    <span><i class="fas fa-tag mr-2" style="color:#87949b;"></i>{{ $cls->nombre }}</span>
                    <button type="button" class="clase-item-btn" onclick="eliminarClaseAjax({{ $cls->id }})" title="Eliminar del catálogo"><i class="fas fa-trash"></i></button>
                </li>
            @endforeach
        </ul>

        <div style="display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-light" style="font-size:12px; font-weight:bold; padding: 8px 16px;" onclick="cerrarModalClase()">Cerrar panel</button>
        </div>
    </div>
</div>

<script>
    function abrirModalClase() { document.getElementById('modal-clase').classList.add('show'); setTimeout(() => document.getElementById('nombre_clase').focus(), 100); }
    function cerrarModalClase() { document.getElementById('modal-clase').classList.remove('show'); document.getElementById('nombre_clase').value = ''; document.getElementById('clase-error').style.display = 'none'; }

    function guardarClaseAjax() {
        let nombre = document.getElementById('nombre_clase').value;
        let errorDiv = document.getElementById('clase-error');
        let btn = document.getElementById('btn-guardar-clase');

        if(nombre.trim() === '') { errorDiv.innerText = 'Debes escribir un nombre.'; errorDiv.style.display = 'block'; return; }

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true;

        fetch('{{ route("clases_herramientas.ajax") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ nombre: nombre })
        }).then(response => response.json().then(data => ({ status: response.status, body: data }))).then(res => {
            if (res.status === 200 || res.status === 201) {
                // Guardamos el NOMBRE en el value
                let select = document.getElementById('clase_select');
                select.add(new Option(res.body.nombre, res.body.nombre, false, true));

                let lista = document.getElementById('lista-clases');
                let li = document.createElement('li');
                li.className = 'clase-item'; li.id = 'cls-item-' + res.body.id;
                li.innerHTML = `<span><i class="fas fa-tag mr-2" style="color:#87949b;"></i>${res.body.nombre}</span><button type="button" class="clase-item-btn" onclick="eliminarClaseAjax(${res.body.id})"><i class="fas fa-trash"></i></button>`;
                lista.appendChild(li); lista.scrollTop = lista.scrollHeight;

                document.getElementById('nombre_clase').value = ''; errorDiv.style.display = 'none';
            } else {
                errorDiv.innerText = res.body.message || 'Error al guardar.'; errorDiv.style.display = 'block';
            }
        }).finally(() => { btn.innerHTML = '<i class="fas fa-plus"></i> Añadir'; btn.disabled = false; });
    }

    function eliminarClaseAjax(id) {
        if(!confirm('¿Estás seguro de eliminar esta clase del catálogo?')) return;
        fetch(`/clases-herramientas/ajax/${id}`, { method: 'DELETE', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
        .then(response => {
            if(response.ok) document.getElementById('cls-item-' + id).remove();
            else alert('Hubo un problema al intentar eliminar la clase.');
        });
    }
</script>