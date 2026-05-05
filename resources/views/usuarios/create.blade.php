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
            <h1>Registro de nuevo personal</h1>
            <p>Complete el expediente técnico del trabajador. Los campos marcados con asterisco son obligatorios para la asignación de EPIs.</p>
        </div>
    </div>
@endsection

@section('content')
    @php
        $sugerenciaCorreo = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', old('dni_nie', '')));
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
                        <h2>1. Datos personales</h2>
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

                        <div class="field-group field-group--full">
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
                    </div>
                </article>

                <article class="usuarios-panel usuarios-panel--wide">
                    <header class="usuarios-panel__header">
                        <i class="fas fa-file-circle-plus"></i>
                        <h2>2. Documentación</h2>
                    </header>

                    <div class="usuarios-panel__body usuarios-doc-grid">
                        <label class="upload-card">
                            <input type="file" hidden disabled>
                            <i class="fas fa-id-card"></i>
                            <strong>DNI escaneado</strong>
                            <span>PDF, JPG o PNG (Max 5MB)</span>
                        </label>

                        <label class="upload-card">
                            <input type="file" hidden disabled>
                            <i class="fas fa-file-contract"></i>
                            <strong>Contrato laboral</strong>
                            <span>PDF firmado (Max 10MB)</span>
                        </label>
                    </div>
                </article>

                <article class="usuarios-panel usuarios-panel--full">
                    <header class="usuarios-panel__header">
                        <i class="fas fa-address-card"></i>
                        <h2>3. Observaciones</h2>
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
                <article class="usuarios-panel">
                    <header class="usuarios-panel__header">
                        <i class="fas fa-shirt"></i>
                        <h2>4. Gestión de tallas</h2>
                    </header>

                    <div class="usuarios-panel__body usuarios-panel__body--stack">
                        <div class="field-group field-group--inline">
                            <label for="camiseta">Camiseta</label>
                            <select id="camiseta" name="camiseta">
                                @foreach(['' => 'Selecciona talla', 'XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('camiseta') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-group field-group--inline">
                            <label for="chaqueta">Chaqueta</label>
                            <select id="chaqueta" name="chaqueta">
                                @foreach(['' => 'Selecciona talla', 'XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('chaqueta') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-group field-group--inline">
                            <label for="sudadera">Sudadera</label>
                            <select id="sudadera" name="sudadera">
                                @foreach(['' => 'Selecciona talla', 'XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('sudadera') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-group field-group--inline">
                            <label for="pantalon">Pantalón</label>
                            <select id="pantalon" name="pantalon">
                                @foreach(['' => 'Selecciona talla', '36' => '36', '38' => '38', '40' => '40', '42' => '42', '44' => '44', '46' => '46', '48' => '48', '50' => '50'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('pantalon') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-group field-group--inline">
                            <label for="calzado">Calzado</label>
                            <select id="calzado" name="calzado">
                                @foreach(['' => 'Selecciona talla', '36' => '36', '37' => '37', '38' => '38', '39' => '39', '40' => '40', '41' => '41', '42' => '42', '43' => '43', '44' => '44', '45' => '45', '46' => '46'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('calzado') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-group field-group--inline">
                            <label for="casco">Casco</label>
                            <select id="casco" name="casco">
                                @foreach(['' => 'Selecciona talla', 'Estándar' => 'Estándar'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('casco') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-group field-group--inline">
                            <label for="guantes">Guantes</label>
                            <select id="guantes" name="guantes">
                                @foreach(['' => 'Selecciona talla', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('guantes') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </article>

                <article class="usuarios-note-card">
                    <i class="fas fa-circle-info"></i>
                    <div>
                        <strong>Nota de seguridad</strong>
                        <p>Las tallas y el expediente se revisan antes de la entrega formal de EPIs.</p>
                    </div>
                </article>

                <div class="usuarios-create-actions">
                    <a href="{{ route('personal.index') }}" class="btn-secondary-action">Cancelar</a>
                    <button type="submit" class="btn-primary-action">Guardar Trabajador</button>
                </div>
            </aside>
        </form>
    </div>
@endsection