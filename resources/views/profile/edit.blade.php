@extends('adminlte::page')

@section('title', 'Editar Perfil - MoncobraCRM')

@push('css')
    @vite(['resources/css/show_profile.css'])
@endpush

@section('content')

<div class="profile-wrapper" style="margin: 0 auto; padding: 20px;">
    <div class="profile-banner"></div>
    <div class="profile-card">

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="avatar-row">
                <div class="avatar-wrapper" style="position: relative; cursor: pointer;" onclick="document.getElementById('avatar-input').click();">
                    @if (Auth::user()->avatar)
                        <img src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="avatar-image" id="avatar-preview">
                    @else
                        <div class="avatar-placeholder" id="avatar-preview">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(strstr(Auth::user()->name . ' ', ' '), 1, 1)) }}
                        </div>
                    @endif
                    <div style="position: absolute; bottom: 0; right: 0; background: #2563EB; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 3px solid white; cursor: pointer;">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>

                <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display: none;" onchange="previewAvatar(this)">

                <div class="avatar-actions">
                    <a href="{{ route('profile.show') ?? route('dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </div>

            <div style="margin-top: 1.5rem; margin-bottom: 0.75rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="name" class="form-input-inline @error('name') error @enderror"
                           value="{{ old('name', Auth::user()->name) }}" required placeholder="Nombre" style="width: 100%; font-size: 1.4rem; font-weight: 600; color: #0D1B36; padding: 0.5rem; border: 1px solid transparent; border-bottom: 2px solid #E2EAF4; transition: border-color 0.3s;">
                    @error('name')<div style="color: #DC2626; font-size: 0.875rem;">{{ $message }}</div>@enderror
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="apellido" class="form-input-inline @error('apellido') error @enderror"
                           value="{{ old('apellido', Auth::user()->apellido) }}" required placeholder="Apellidos" style="width: 100%; font-size: 1.4rem; font-weight: 600; color: #0D1B36; padding: 0.5rem; border: 1px solid transparent; border-bottom: 2px solid #E2EAF4; transition: border-color 0.3s;">
                    @error('apellido')<div style="color: #DC2626; font-size: 0.875rem;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <input type="email" name="email" class="form-input-inline @error('email') error @enderror"
                       value="{{ old('email', Auth::user()->email) }}" required style="width: 100%; font-size: 0.95rem; color: #4B5563; padding: 0.5rem;">
                @error('email')<div style="color: #DC2626; font-size: 0.875rem;">{{ $message }}</div>@enderror
            </div>

            <div class="section-divider"></div>

            <div class="section-label">Información de contacto y laboral</div>

            <div class="info-grid" style="margin-bottom: 1rem;">
                <div class="info-item">
                    <div class="info-label">DNI / NIE</div>
                    <input type="text" name="dni_nie" class="form-input-inline @error('dni_nie') error @enderror"
                           value="{{ old('dni_nie', Auth::user()->dni_nie) }}"
                           placeholder="Ej: 12345678X" style="width: 100%; border: 1px solid #E2EAF4; border-radius: 8px; padding: 0.5rem; font-size: 0.95rem;">
                    @error('dni_nie')<div style="color: #DC2626; font-size: 0.875rem;">{{ $message }}</div>@enderror
                </div>

                <div class="info-item">
                    <div class="info-label">Teléfono</div>
                    <input type="tel" name="telefono" class="form-input-inline @error('telefono') error @enderror"
                           value="{{ old('telefono', Auth::user()->telefono) }}"
                           placeholder="Ej: 600 123 456" style="width: 100%; border: 1px solid #E2EAF4; border-radius: 8px; padding: 0.5rem; font-size: 0.95rem;">
                    @error('telefono')<div style="color: #DC2626; font-size: 0.875rem;">{{ $message }}</div>@enderror
                </div>

                <div class="info-item">
                    <div class="info-label">Departamento</div>
                    <select name="departamento" class="form-input-inline @error('departamento') error @enderror" style="width: 100%; border: 1px solid #E2EAF4; border-radius: 8px; padding: 0.5rem; font-size: 0.95rem; background: white;">
                        @php($departamentos = ['' => 'Selecciona departamento', 'Producción' => 'Producción', 'Logística' => 'Logística', 'Mantenimiento' => 'Mantenimiento', 'Calidad' => 'Calidad', 'Administración' => 'Administración'])
                        @foreach($departamentos as $value => $label)
                            <option value="{{ $value }}" @selected(old('departamento', Auth::user()->departamento) == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('departamento')<div style="color: #DC2626; font-size: 0.875rem;">{{ $message }}</div>@enderror
                </div>

                <div class="info-item">
                    <div class="info-label">Tipo de Personal</div>
                    <select name="tipo_personal" class="form-input-inline @error('tipo_personal') error @enderror" style="width: 100%; border: 1px solid #E2EAF4; border-radius: 8px; padding: 0.5rem; font-size: 0.95rem; background: white;">
                        <option value="indefinido" @selected(old('tipo_personal', Auth::user()->tipo_personal) === 'indefinido')>Indefinido</option>
                        <option value="temporal" @selected(old('tipo_personal', Auth::user()->tipo_personal) === 'temporal')>Temporal / ETT</option>
                    </select>
                    @error('tipo_personal')<div style="color: #DC2626; font-size: 0.875rem;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="section-divider"></div>
            <div class="section-label">Observaciones del perfil</div>

            <textarea name="descripcion" class="description-box-input @error('descripcion') error @enderror"
                      placeholder="Breve descripción sobre ti (opcional)"
                      style="width: 100%; border: 1px solid #E2EAF4; border-radius: 8px; padding: 0.75rem; font-size: 0.95rem; resize: vertical; min-height: 80px;">{{ old('descripcion', Auth::user()->descripcion) }}</textarea>
            @error('descripcion')<div style="color: #DC2626; font-size: 0.875rem;">{{ $message }}</div>@enderror

            <div style="margin-top: 1.5rem; padding: 1rem; background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 8px; color: #0369A1; font-size: 0.9rem;">
                <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                <strong>Nota:</strong> Algunos campos como Rol y Proyecto son gestionados exclusivamente por un administrador.
            </div>

        </form>

    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatar-preview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                preview.style.backgroundImage = 'url(' + e.target.result + ')';
                preview.style.backgroundSize = 'cover';
                preview.style.backgroundPosition = 'center';
                preview.textContent = '';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

@endsection