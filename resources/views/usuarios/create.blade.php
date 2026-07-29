@extends('adminlte::page')

@section('title', 'Añadir Trabajador')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/usuarios-create.css'])
@endsection

@section('content_header')
    <div class="usuarios-create-hero">
        <div class="usuarios-create-hero__copy">
            <div class="usuarios-create-crumbs">GESTIÓN DE PERSONAL <span>•</span> AÑADIR TRABAJADOR</div>
            <h1>Registro de nuevo usuario</h1>
            <p>Complete el expediente técnico y de acceso del trabajador. Los campos marcados con asterisco son obligatorios.</p>
        </div>
    </div>
@endsection

@section('content')
    @php
        // Genera una sugerencia base para el correo usando el DNI
        $sugerenciaCorreo = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', old('dni_nie', '')));
        $emailSugerido = $sugerenciaCorreo ? $sugerenciaCorreo . '@empresa.com' : '';
    @endphp

    <div class="usuarios-create-page">
        @if ($errors->any())
            <div class="usuarios-create-alert usuarios-create-alert--danger">
                <i class="fas fa-triangle-exclamation"></i>
                <div>
                    <strong>Revisa el formulario.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('users.store') }}" method="POST" class="usuarios-create-grid">
            @csrf

            <section class="usuarios-create-main">
                <article class="usuarios-panel">
                    <header class="usuarios-panel__header">
                        <i class="fas fa-user"></i>
                        <h2>1. Datos personales y Acceso</h2>
                    </header>

                    <div class="usuarios-panel__body usuarios-panel__body--grid">
                        <div class="field-group">
                            <label for="name">Nombre *</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Ej. Juan" required>
                        </div>

                        <div class="field-group">
                            <label for="apellido">Apellido *</label>
                            <input type="text" id="apellido" name="apellido" value="{{ old('apellido') }}" placeholder="Ej. García López" required>
                        </div>

                        <div class="field-group">
                            <label for="dni_nie">DNI / NIE *</label>
                            <input type="text" id="dni_nie" name="dni_nie" value="{{ old('dni_nie') }}" placeholder="12345678X" required>
                        </div>

                        <div class="field-group">
                            <label for="departamento">Departamento</label>
                            <select id="departamento" name="departamento">
                                @php($departamentos = ['' => 'Selecciona departamento', 'Producción' => 'Producción', 'Logística' => 'Logística', 'Mantenimiento' => 'Mantenimiento', 'Calidad' => 'Calidad', 'Administración' => 'Administración'])
                                @foreach($departamentos as $value => $label)
                                    <option value="{{ $value }}" @selected(old('departamento') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-group">
                            <label for="email">Correo Electrónico *</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $emailSugerido) }}" placeholder="Ej. usuario@empresa.com" required>
                        </div>

                        <div class="field-group">
                            <label for="role">Rol en el Sistema *</label>
                            <select id="role" name="role" required>
                                <option value="user" @selected(old('role') === 'user')>Usuario</option>
                                @if(auth()->user()->role === 'superadmin' || auth()->user()->role === 'admin')
                                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                                @endif
                                @if(auth()->user()->role === 'superadmin')
                                    <option value="superadmin" @selected(old('role') === 'superadmin')>Super Admin</option>
                                @endif
                            </select>
                        </div>
                        
                        {{-- NOTA INFORMATIVA SOBRE LA CONTRASEÑA --}}
                        <div class="field-group field-group--full" style="background-color: #f8f9fa; border-left: 4px solid #0dcaf0; padding: 15px; margin-top: 10px; border-radius: 4px;">
                            <p style="margin: 0; font-size: 0.9rem; color: #495057;">
                                <i class="fas fa-envelope-open-text" style="color: #0dcaf0; margin-right: 8px;"></i>
                                <strong>Configuración de contraseña:</strong> Al guardar el trabajador, se le enviará automáticamente un correo electrónico a la dirección proporcionada con un enlace seguro para que configure su propia contraseña.
                            </p>
                        </div>

                        <div class="field-group field-group--full mt-3">
                            <label>Tipo de personal</label>
                            <div class="choice-grid">
                                <label class="choice-card">
                                    <input type="radio" name="tipo_personal" value="indefinido" @checked(old('tipo_personal', 'indefinido') === 'indefinido')>
                                    <span>Indefinido</span>
                                </label>
                                <label class="choice-card">
                                    <input type="radio" name="tipo_personal" value="temporal" @checked(old('tipo_personal') === 'temporal')>
                                    <span>Temporal / ETT</span>
                                </label>
                            </div>
                        </div>

                        <div class="field-group field-group--full" id="proyectos-container" style="margin-top: 1rem;">
                            <label>Asignación de Proyectos</label>
                            <p style="font-size: 0.85rem; color: #6c757d; margin-bottom: 10px;">
                                Selecciona los proyectos a los que tendrá acceso este trabajador.
                            </p>
                            
                            <div class="choice-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                                @foreach($proyectos as $proyecto)
                                    <label class="choice-card" style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                                        <input 
                                            type="checkbox" 
                                            name="proyecto_ids[]" 
                                            value="{{ $proyecto->id }}" 
                                            class="proyecto-checkbox"
                                            @checked(is_array(old('proyecto_ids')) && in_array($proyecto->id, old('proyecto_ids')))
                                        >
                                        <span>{{ $proyecto->nombre }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <small id="superadmin-note" style="display: none; color: #0dcaf0; margin-top: 10px; font-weight: 600;">
                                <i class="fas fa-info-circle"></i> Los usuarios Super Admin tienen acceso total a todos los proyectos automáticamente.
                            </small>
                        </div>
                    </div>
                </article>
                <article class="usuarios-panel usuarios-panel--full">
                    <header class="usuarios-panel__header">
                        <i class="fas fa-address-card"></i>
                        <h2>2. Observaciones</h2>
                    </header>
                    <div class="usuarios-panel__body">
                        <div class="field-group">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" value="{{ old('telefono') }}" placeholder="Ej. 600 000 000">
                        </div>

                        <div class="field-group field-group--full">
                            <label for="descripcion">Observaciones</label>
                            <textarea id="descripcion" name="descripcion" rows="4" placeholder="Información adicional sobre el trabajador...">{{ old('descripcion') }}</textarea>
                        </div>

                        <div class="field-group field-group--full">
                            <label class="switch-field" for="activo">
                                <input type="checkbox" id="activo" name="activo" value="1" @checked(old('activo', true))>
                                <span>Trabajador activo</span>
                            </label>
                        </div>
                    </div>
                </article>
            </section>

            <aside class="usuarios-create-sidebar">
                <div class="usuarios-create-actions">
                    <a href="{{ route('users.index') }}" class="btn-secondary-action">Cancelar</a>
                    <button type="submit" class="btn-primary-action">Guardar Trabajador</button>
                </div>
            </aside>
        </form>
    </div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const checkboxes = document.querySelectorAll('.proyecto-checkbox');
        const superadminNote = document.getElementById('superadmin-note');

        function toggleProyectosState() {
            const isSuperAdmin = roleSelect.value === 'superadmin';

            checkboxes.forEach(checkbox => {
                if (isSuperAdmin) {
                    checkbox.checked = true;    // Los marcamos todos
                    checkbox.disabled = true;   // Los bloqueamos visualmente
                } else {
                    checkbox.disabled = false;  // Los desbloqueamos para user/admin
                }
            });

            // Mostramos u ocultamos el mensaje de aviso
            superadminNote.style.display = isSuperAdmin ? 'block' : 'none';
        }

        // Ejecutar cuando se cambia el desplegable
        if (roleSelect) {
            roleSelect.addEventListener('change', toggleProyectosState);
            // Ejecutar también al cargar la página (por si hay un old('role') tras un error de validación)
            toggleProyectosState();
        }
    });
</script>
@endsection