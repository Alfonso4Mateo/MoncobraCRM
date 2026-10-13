<!-- ESTILOS DEL MODAL -->
<style>
    .ubicacion-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,36,66,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); }
    .ubicacion-modal-overlay.show { display: flex; }
    .ubicacion-modal-dialog { background: #fff; width: 100%; max-width: 450px; padding: 24px; box-shadow: 0 16px 45px rgba(0,36,66,0.2); border-radius: 8px; display: flex; flex-direction: column; max-height: 85vh; }
    
    .ubicacion-list { flex: 1; overflow-y: auto; margin: 15px 0; border: 1px solid #edf0f2; border-radius: 4px; padding: 0; list-style: none; background: #fafbfc; }
    .ubicacion-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border-bottom: 1px solid #edf0f2; font-size: 12px; color: #273740; font-weight: 700; }
    .ubicacion-item:last-child { border-bottom: none; }
    .ubicacion-item-btn { background: none; border: none; color: #c92328; cursor: pointer; padding: 4px 8px; transition: 0.2s; border-radius: 4px; }
    .ubicacion-item-btn:hover { background: #fee1df; }
    
    .ubicacion-add-group { display: flex; gap: 8px; }
</style>

<!-- MODAL AJAX DE UBICACIÓN -->
<div id="modal-ubicacion" class="ubicacion-modal-overlay" onclick="cerrarModalUbicacion()">
    <div class="ubicacion-modal-dialog" onclick="event.stopPropagation()">
        <h2 style="font-size: 18px; margin-bottom: 5px; color: #191c1e; font-weight: 800;">Gestionar Ubicaciones</h2>
        <p style="font-size: 12px; color: #687681; margin-bottom: 15px;">
            Añade nuevos centros de trabajo o elimina los que ya no uses.
        </p>
        
        <div id="ubicacion-error" class="alert alert-danger" style="display:none; font-size:12px; font-weight:bold; padding:8px; background:#fee1df; color:#c92328; border:0; border-radius: 4px; margin-bottom: 15px;"></div>

        <!-- Zona para añadir -->
        <div class="ubicacion-add-group">
            <input type="text" id="nombre_ubicacion" class="form-control" placeholder="Nombre de la nueva ubicación..." style="flex: 1; border: 0; background: #eef2f5; font-size: 12px; padding: 10px; border-radius: 4px; height: 38px;">
            <button type="button" class="btn text-white" style="background:#002442; font-size:12px; font-weight:bold; height: 38px; padding: 0 16px;" id="btn-guardar-ubicacion" onclick="guardarUbicacionAjax()">
                <i class="fas fa-plus"></i> Añadir
            </button>
        </div>

        <!-- Lista de ubicaciones existentes -->
        <ul class="ubicacion-list" id="lista-ubicaciones">
            @foreach($ubicaciones as $ubi)
                <li class="ubicacion-item" id="ubi-item-{{ $ubi->id }}">
                    <span><i class="fas fa-map-marker-alt mr-2" style="color:#87949b;"></i>{{ $ubi->nombre }}</span>
                    <button type="button" class="ubicacion-item-btn" onclick="eliminarUbicacionAjax({{ $ubi->id }})" title="Eliminar del catálogo"><i class="fas fa-trash"></i></button>
                </li>
            @endforeach
        </ul>

        <div style="display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-light" style="font-size:12px; font-weight:bold; padding: 8px 16px;" onclick="cerrarModalUbicacion()">Cerrar panel</button>
        </div>
    </div>
</div>

<script>
    function abrirModalUbicacion() {
        document.getElementById('modal-ubicacion').classList.add('show');
        setTimeout(() => document.getElementById('nombre_ubicacion').focus(), 100);
    }

    function cerrarModalUbicacion() {
        document.getElementById('modal-ubicacion').classList.remove('show');
        document.getElementById('nombre_ubicacion').value = '';
        document.getElementById('ubicacion-error').style.display = 'none';
    }

    function guardarUbicacionAjax() {
        let nombre = document.getElementById('nombre_ubicacion').value;
        let errorDiv = document.getElementById('ubicacion-error');
        let btn = document.getElementById('btn-guardar-ubicacion');

        if(nombre.trim() === '') {
            errorDiv.innerText = 'Debes escribir un nombre.';
            errorDiv.style.display = 'block';
            return;
        }

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch('{{ route("ubicaciones.ajax") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ nombre: nombre })
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 200 || res.status === 201) {
                // 1. Añadir al selector principal del formulario y dejarlo seleccionado
                let select = document.getElementById('ubicacion_id_select');
                let option = new Option(res.body.nombre, res.body.id, false, true);
                select.add(option);

                // 2. Añadir visualmente a la lista del modal por si quiere seguir gestionando
                let lista = document.getElementById('lista-ubicaciones');
                let li = document.createElement('li');
                li.className = 'ubicacion-item';
                li.id = 'ubi-item-' + res.body.id;
                li.innerHTML = `<span><i class="fas fa-map-marker-alt mr-2" style="color:#87949b;"></i>${res.body.nombre}</span>
                                <button type="button" class="ubicacion-item-btn" onclick="eliminarUbicacionAjax(${res.body.id})"><i class="fas fa-trash"></i></button>`;
                lista.appendChild(li); // Lo ponemos al final de la lista
                lista.scrollTop = lista.scrollHeight; // Hacemos scroll hacia abajo del todo

                // Limpiamos el input y los errores
                document.getElementById('nombre_ubicacion').value = '';
                errorDiv.style.display = 'none';
            } else {
                errorDiv.innerText = res.body.message || 'Error al guardar. Puede que el nombre ya exista.';
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            errorDiv.innerText = 'Error de conexión con el servidor.';
            errorDiv.style.display = 'block';
        })
        .finally(() => {
            btn.innerHTML = '<i class="fas fa-plus"></i> Añadir';
            btn.disabled = false;
        });
    }

    function eliminarUbicacionAjax(id) {
        if(!confirm('¿Estás seguro de eliminar esta ubicación del catálogo? Las herramientas asignadas a ella se quedarán sin ubicación asignada.')) return;
        
        fetch(`/ubicaciones/ajax/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => {
            if(response.ok) {
                // 1. Quitar de la lista del modal
                document.getElementById('ubi-item-' + id).remove();
                
                // 2. Quitar del selector principal del formulario
                let select = document.getElementById('ubicacion_id_select');
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value == id) {
                        select.remove(i);
                        break;
                    }
                }
            } else {
                alert('Hubo un problema al intentar eliminar la ubicación.');
            }
        });
    }
</script>