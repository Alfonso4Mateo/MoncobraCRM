@extends('adminlte::page')

@section('title', ($isCreate ?? false) ? 'Crear Trabajador' : 'Editar Ficha')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @vite(['resources/css/personal-show.css'])
    
    <style>
        /* =========================================================
           1. ESTILOS GENERALES DEL FORMULARIO
           ========================================================= */
        .profile-edit-form-group { margin-bottom: 16px; }

        .profile-edit-form-group label {
            display: block;
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: #8a98ab;
            margin-bottom: 8px;
        }

        /* La regla general que EXCLUYE a Select2 */
        .profile-edit-form-group input:not(.select2-search__field),
        .profile-edit-form-group select,
        .profile-edit-form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            font-family: var(--profile-font);
            font-size: .9rem;
            color: var(--profile-ink);
            background: #fff;
            transition: border-color .18s ease;
        }

        .profile-edit-form-group input:not(.select2-search__field):focus,
        .profile-edit-form-group select:focus,
        .profile-edit-form-group textarea:focus {
            outline: none;
            border-color: var(--profile-primary);
            box-shadow: 0 0 0 3px rgba(23, 62, 103, .08);
        }

        .profile-edit-form-group textarea {
            resize: vertical;
            min-height: 10px;
        }

        .profile-edit-form-group .is-invalid { border-color: #e11d48; background: #fff1f2; }
        .profile-edit-form-group .is-invalid:focus { border-color: #be123c; box-shadow: 0 0 0 3px rgba(225, 29, 72, .18); }

        .profile-edit-two-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* =========================================================
           2. MAQUILLAJE SELECT2 (Limpio, sin conflictos)
           ========================================================= */
        /* Contenedor principal: Fuerza el 100% del grid */
        .select2-container {
            width: 100% !important;
        }

        /* La caja visual (Caja blanca) */
        .select2-container--default .select2-selection--multiple {
            background-color: #fff;
            border: 1px solid var(--profile-line);
            border-radius: 8px;
            padding: 4px 8px;
            min-height: 44px;
            cursor: text;
        }

        /* Foco al hacer clic en la caja blanca */
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--profile-primary);
            box-shadow: 0 0 0 3px rgba(23, 62, 103, 0.08);
        }

        /* Contenedor Flexbox interno (donde viven las píldoras y el cursor) */
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        /* Diseño de las píldoras azules */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
            border-radius: 6px;
            padding: 4px 10px;
            margin: 0;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            flex-direction: row-reverse;
            align-items: center;
            gap: 8px;
        }

        /* La cruz "X" de las píldoras */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #60a5fa;
            border: none;
            background: transparent;
            padding: 0;
            margin: 0;
            font-weight: bold;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #ef4444;
        }

        /* =========================================================
           3. LA CIRUGÍA DEL CURSOR (Esto arregla los saltos)
           ========================================================= */
        /* Quitamos el float nativo de Select2 para que use Flexbox */
        .select2-container--default .select2-selection--multiple .select2-search--inline {
            float: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
        }

        /* EL INPUT: Limpiamos los estilos para que JS controle la anchura al teclear */
        .select2-container--default .select2-search--inline .select2-search__field {
            margin: 0;
            padding: 0;
            height: 26px;
            line-height: 26px;
            border: none;
            box-shadow: none;
            background: transparent;
            font-family: inherit;
            color: var(--profile-ink);
            outline: none;
            /* PROHIBIDO PONER WIDTH AQUI. SELECT2 LO CALCULA SOLO. */
        }

        /* =========================================================
           4. MODALES Y DESPLEGABLE
           ========================================================= */
        .select2-dropdown {
            border: 1px solid #cbd5e1;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 600;
        }

        /* Lista del Modal de Gestión */
        .depto-list-item {
            display: flex; justify-content: space-between; align-items: center; 
            padding: 10px 14px; border-bottom: 1px solid var(--profile-line); transition: background 0.2s;
        }
        .depto-list-item:hover { background: #f1f5f9; }
        .depto-list-item:last-child { border-bottom: none; }
        .depto-btn-delete { 
            color: #ef4444; background: #fee2e2; border: none; cursor: pointer; 
            width: 28px; height: 28px; border-radius: 6px; 
            display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; 
        }
        .depto-btn-delete:hover { background: #ef4444; color: #fff; transform: scale(1.05); }

        @media (max-width: 768px) {
            .profile-edit-two-cols { grid-template-columns: 1fr; }
        }
    </style>
@endsection

@section('content')
    <section class="profile-page">
        <header class="profile-hero">
            <div>
                <div class="profile-crumbs">GESTIÓN DE PERSONAL <span>•</span> EDITAR TRABAJADOR</div>
                <h1>
                    @if($isCreate ?? false)
                        Crear Trabajador
                    @else
                        Editar Ficha: {{ $personal->name }} {{ $personal->apellido }}
                    @endif
                </h1>
                <p>Actualiza la información personal y equipamiento de protección individual (EPIS)</p>
            </div>

            <div class="profile-hero-actions">
                <a href="{{ ($isCreate ?? false) ? route('personal.index') : route('personal.show', $personal->id) }}" class="profile-action profile-action--soft">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <button form="personal-edit-form" class="profile-action profile-action--primary">
                    <i class="fas fa-save"></i>
                    Guardar Cambios
                </button>
            </div>
        </header>

        @if ($errors->any())
            <div class="profile-alert profile-alert-error">
                Hay errores en el formulario. Revisa los campos marcados:
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="personal-edit-form" action="{{ ($isCreate ?? false) ? route('personal.store') : route('personal.update', $personal->id) }}" method="POST">
            @csrf
            @if(!($isCreate ?? false))
                @method('PUT')
            @endif

            <div class="profile-grid">
                <section class="profile-main">
                    <!-- Panel Perfil Principal -->
                    <article class="profile-card profile-card--main-sidebar">
                        <div class="profile-main-row">
                            <div class="profile-main-left">
                                <div class="profile-avatar-wrap">
                                    <div class="profile-avatar">
                                        <i class="fas fa-hard-hat"></i>
                                    </div>
                                </div>

                                <div class="profile-name-block">
                                    <h2>{{ $personal->name ?? 'Nuevo' }} {{ $personal->apellido ?? 'Trabajador' }}</h2>
                                    @php
                                        $deptosActuales = isset($personal) && is_string($personal->departamento) ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento) : (array) ($personal->departamento ?? []);
                                    @endphp
                                    <p>{{ !empty($deptosActuales) ? strtoupper(implode(', ', $deptosActuales)) : 'SIN DEPARTAMENTO' }}</p>
                                </div>

                                <div class="profile-metadata">
                                    <div>
                                        <span>ID EMPLEADO</span>
                                        <strong>
                                            @if($isCreate ?? false)
                                                —
                                            @else
                                                AL-{{ str_pad((string) $personal->id, 3, '0', STR_PAD_LEFT) }}
                                            @endif
                                        </strong>
                                    </div>
                                    <div>
                                        <span>REGISTRADO DESDE</span>
                                        <strong>{{ optional($personal->created_at)->format('d M Y') ?: '—' }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Información Personal -->
                            <div style="flex: 1;">
                                <div class="profile-edit-form-group">
                                    <label for="name">Nombre</label>
                                    <input type="text" id="name" name="name" value="{{ old('name', $personal->name ?? '') }}" class="@error('name') is-invalid @enderror">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" value="{{ old('apellido', $personal->apellido ?? '') }}" class="@error('apellido') is-invalid @enderror">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="dni_nie">DNI / NIE</label>
                                    <input type="text" id="dni_nie" name="dni_nie" value="{{ old('dni_nie', $personal->dni_nie ?? '') }}" class="@error('dni_nie') is-invalid @enderror">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="id_rrhh">ID de RRHH</label>
                                    <input type="text" id="id_rrhh" name="id_rrhh" maxlength="10" value="{{ old('id_rrhh', $personal->id_rrhh ?? '') }}" placeholder="Ej. A1B2C3D4E5" class="@error('id_rrhh') is-invalid @enderror">
                                </div>
                
                                <div class="profile-edit-form-group">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <label for="departamento" style="margin-bottom: 0;">Departamento</label>
                                        @can('personal.acciones')
                                        <button type="button" id="btn-manage-deptos" style="border: 1px solid var(--profile-line); background: #f7f9fc; color: var(--profile-primary); border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 0.8rem; font-weight: 800; transition: all 0.2s;">
                                            <i class="fas fa-cog"></i> Gestionar
                                        </button>
                                        @endcan
                                    </div>

                                    <select id="departamento" name="departamento[]" multiple class="@error('departamento') is-invalid @enderror" style="width: 100%; display: none;">
                                        @php
                                            $deptosSeleccionados = old('departamento', $deptosActuales);
                                        @endphp
                                        @foreach($departamentos as $depto)
                                            <option value="{{ $depto->nombre }}" @selected(in_array($depto->nombre, $deptosSeleccionados ?: []))>
                                                {{ strtoupper($depto->nombre) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- 1. PUESTO LABORAL (Línea completa independiente) -->
                                <div class="profile-edit-form-group">
                                    <label for="puesto_trabajo_id">Puesto Laboral (PRL)</label>
                                    <select id="puesto_trabajo_id" name="puesto_trabajo_id" class="@error('puesto_trabajo_id') is-invalid @enderror">
                                        <option value="" data-meses="12">Selecciona un puesto...</option>
                                        @foreach($puestosTrabajoCatalogo as $pt)
                                            <option value="{{ $pt->id }}" 
                                                    data-meses="{{ $pt->periodicidad_meses ?? 12 }}"
                                                    @selected(old('puesto_trabajo_id', $personal->puesto_trabajo_id) == $pt->id)>
                                                {{ $pt->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- 2. TELÉFONO Y CORREO (Estrictamente 2 columnas) -->
                                <div class="profile-edit-two-cols">
                                    <div class="profile-edit-form-group">
                                        <label for="telefono">Teléfono</label>
                                        <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $personal->telefono ?? '') }}" placeholder="Ej. 600 000 000" class="@error('telefono') is-invalid @enderror">
                                    </div>

                                    <div class="profile-edit-form-group">
                                        <label for="correo">Correo electrónico</label>
                                        <input type="email" id="correo" name="correo" value="{{ old('correo', $personal->correo ?? '') }}" placeholder="Ej. tecnico@moncobra.com" class="@error('correo') is-invalid @enderror">
                                    </div>
                                </div>

                            @can('personal.medico')
                                <!-- 3. CAJA MÉDICA (Contenedor externo limpio al 100% de ancho) -->
                                <div style="width: 100%; box-sizing: border-box; padding: 24px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; margin-top: 24px; grid-column: 1 / -1;">
                                    
                                    <div style="margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px dashed #bbf7d0;">
                                        <h4 style="font-size: 0.85rem; font-weight: 800; color: #166534; margin: 0; letter-spacing: 0.05em;">
                                            <i class="fas fa-notes-medical"></i> DATOS MÉDICOS Y VIGILANCIA DE LA SALUD
                                        </h4>
                                    </div>
                                    
                                    <div class="profile-edit-two-cols">
                                        <!-- Última Revisión -->
                                        <div class="profile-edit-form-group">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                <label for="ultima_revision_medica" style="margin-bottom: 0;">Última Revisión</label>
                                                <button type="button" id="btn-hoy-ultima" class="profile-edit-today-btn">Poner hoy</button>
                                            </div>
                                            <input type="date" id="ultima_revision_medica" name="ultima_revision_medica" value="{{ old('ultima_revision_medica', optional($personal->ultima_revision_medica)->format('Y-m-d')) }}" class="@error('ultima_revision_medica') is-invalid @enderror">
                                        </div>

                                        <!-- Próxima Revisión -->
                                        <div class="profile-edit-form-group">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                <label for="proxima_revision_medica" style="margin-bottom: 0;">Próxima Revisión</label>
                                                <button type="button" id="btn-hoy-proxima" class="profile-edit-today-btn">Poner hoy</button>
                                            </div>
                                            <input type="date" id="proxima_revision_medica" name="proxima_revision_medica" value="{{ old('proxima_revision_medica', optional($personal->proxima_revision_medica)->format('Y-m-d')) }}" class="@error('proxima_revision_medica') is-invalid @enderror">
                                        </div>

                                        <!-- Última Graduación -->
                                        <div class="profile-edit-form-group">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                <label for="ultima_graduacion" style="margin-bottom: 0;">Última graduación</label>
                                                <button type="button" id="set-graduacion-hoy" class="profile-edit-today-btn">Poner hoy</button>
                                            </div>
                                            <input type="date" id="ultima_graduacion" name="ultima_graduacion" value="{{ old('ultima_graduacion', optional($personal->ultima_graduacion ?? null)->format('Y-m-d')) }}" class="@error('ultima_graduacion') is-invalid @enderror">
                                        </div>

                                        <!-- Próxima Graduación -->
                                        <div class="profile-edit-form-group js-graduacion-field">
                                            <label for="proxima_graduacion" style="margin-bottom: 8px;">Próxima graduación</label>
                                            <input type="date" id="proxima_graduacion" name="proxima_graduacion" value="{{ old('proxima_graduacion', optional($personal->proxima_graduacion ?? null)->format('Y-m-d')) }}" class="@error('proxima_graduacion') is-invalid @enderror">
                                        </div>

                                        <!-- Reconocido en -->
                                        <div class="profile-edit-form-group js-graduacion-field">
                                            <label for="reconocido_en" style="margin-bottom: 8px;">Reconocido en:</label>
                                            <input type="text" id="reconocido_en" name="reconocido_en" value="{{ old('reconocido_en', $personal->reconocido_en ?? '') }}" class="@error('reconocido_en') is-invalid @enderror">
                                        </div>

                                        <!-- Graduado en -->
                                        <div class="profile-edit-form-group js-graduacion-field">
                                            <label for="graduado_en" style="margin-bottom: 8px;">Graduado en:</label>
                                            <input type="text" id="graduado_en" name="graduado_en" value="{{ old('graduado_en', $personal->graduado_en ?? '') }}" class="@error('graduado_en') is-invalid @enderror">
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="profile-alert" style="background: #f8fafc; border: 1px dashed #cbd5e1; color: #64748b; margin-top: 20px;">
                                    <i class="fas fa-lock"></i> No tienes permisos para gestionar los datos médicos y de vigilancia de la salud.
                                </div>
                            @endcan
                            </div>
                        </div>
                    </article>

                @can('personal.tallas')
                    <!-- Panel Tallas y Equipamiento -->
                    <article class="profile-card">
                        <div class="profile-card__header">
                            <div>
                                <h3><i class="fas fa-ruler-combined"></i> Tallas y Equipamiento</h3>
                                <p>Medidas corporales para la asignación de uniforme industrial</p>
                            </div>
                        </div>

                        <div class="profile-card__body" style="padding: 20px">
                            <label for="sin_tallas" style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                <input type="hidden" name="sin_tallas" value="0">
                                <input type="checkbox" id="sin_tallas" name="sin_tallas" value="1" @checked(old('sin_tallas', $personal->sin_tallas ?? false))>
                                <span>Sin tallas asignadas</span>
                            </label>
                        </div>

                        <div class="profile-card__body" style="padding: 20px;">
                            <div class="profile-edit-two-cols">
                                <div class="profile-edit-form-group">
                                    <label for="camiseta">Camiseta</label>
                                    <select id="camiseta" name="camiseta" class="@error('camiseta') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['Sin necesidad' => 'Sin necesidad','2XS' => '2XS','XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL', '5XL' => '5XL', '6XL' => '6XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('camiseta', $personal->camiseta ?? '') == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="chaqueta">Chaqueta</label>
                                    <select id="chaqueta" name="chaqueta" class="@error('chaqueta') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['Sin necesidad' => 'Sin necesidad','2XS' => '2XS','XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL', '5XL' => '5XL', '6XL' => '6XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('chaqueta', $personal->chaqueta ?? '') == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="sudadera">Sudadera</label>
                                    <select id="sudadera" name="sudadera" class="@error('sudadera') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['Sin necesidad' => 'Sin necesidad','2XS' => '2XS','XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL', '5XL' => '5XL', '6XL' => '6XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('sudadera', $personal->sudadera ?? '') == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="pantalon">Pantalón</label>
                                    <select id="pantalon" name="pantalon" class="@error('pantalon') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['Sin necesidad' => 'Sin necesidad','30' => '30','32' => '32','34' => '34','36' => '36', '38' => '38', '40' => '40', '42' => '42', '44' => '44', '46' => '46', '48' => '48', '50' => '50','52' => '52','54' => '54','56' => '56','58' => '58','60' => '60','62' => '62','64' => '64','66' => '66','68' => '68','70' => '70'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('pantalon', $personal->pantalon ?? '') == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="calzado">Calzado Seguridad</label>
                                    <select id="calzado" name="calzado" class="@error('calzado') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['Sin necesidad' => 'Sin necesidad','34' => '34','35' => '35','36' => '36', '37' => '37', '38' => '38', '39' => '39', '40' => '40', '41' => '41', '42' => '42', '43' => '43', '44' => '44', '45' => '45', '46' => '46', '47' => '47', '48' => '48', '49' => '49', '50' => '50'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('calzado', $personal->calzado ?? '') == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="guantes">Guantes</label>
                                    <select id="guantes" name="guantes" class="@error('guantes') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['Sin necesidad' => 'Sin necesidad','5' => '5','6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11' ,'12' => '12'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('guantes', $personal->guantes ?? '') == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="casco">Casco</label>
                                    <select id="casco" name="casco" class="@error('casco') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['Sin necesidad' => 'Sin necesidad','Estándar' => 'Estándar'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('casco', $personal->casco ?? '') == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="profile-edit-form-group">
                                    <label for="gafas">Gafas</label>
                                    <select id="gafas" name="gafas" class="@error('gafas') is-invalid @enderror">
                                        <option value="">Selecciona tipo</option>
                                        @foreach(['Sin necesidad' => 'Sin necesidad','Graduadas' => 'Graduadas', 'Protección' => 'Protección'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('gafas', $personal->gafas ?? '') == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </article>
                @endcan

                    <!-- Panel Observaciones -->
                    <article class="profile-card">
                        <div class="profile-card__header">
                            <div>
                                <h3><i class="fas fa-clipboard-list"></i> Observaciones</h3>
                                <p>Notas y comentarios adicionales sobre el trabajador</p>
                            </div>
                        </div>

                        <div class="profile-card__body" style="padding: 20px;">
                            <div class="profile-edit-form-group">
                                <label for="descripcion">Observaciones</label>
                                <textarea id="descripcion" name="descripcion" placeholder="Añade información adicional, notas especiales, o comentarios..." class="@error('descripcion') is-invalid @enderror">{{ old('descripcion', $personal->descripcion ?? '') }}</textarea>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </form>

            <!-- MODAL GESTIÓN DE DEPARTAMENTOS -->
        <div class="profile-modal" id="manage-deptos-modal" aria-hidden="true" role="dialog">
            <div class="profile-modal__panel" style="max-width: 480px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--profile-line); padding-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--profile-ink);"><i class="fas fa-sitemap" style="color: var(--profile-primary); margin-right: 8px;"></i> Gestionar Departamentos</h3>
                    <button type="button" data-close-manage-deptos style="background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer;"><i class="fas fa-times"></i></button>
                </div>

                <!-- Formulario añadir -->
                <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                    <input type="text" id="new-depto-name" placeholder="Añadir nuevo departamento..." style="flex: 1; padding: 10px 14px; border: 1px solid var(--profile-line); border-radius: 8px; outline: none; font-family: inherit; font-size: 0.9rem;">
                    <button type="button" id="btn-add-depto-ajax" style="background: var(--profile-primary); color: #fff; border: none; border-radius: 8px; padding: 0 16px; font-weight: 800; cursor: pointer; transition: all 0.2s;">Añadir</button>
                </div>

                <!-- Lista existente -->
                <div style="max-height: 280px; overflow-y: auto; border: 1px solid var(--profile-line); border-radius: 8px; background: #fff;">
                    <ul id="manage-deptos-list" style="list-style: none; padding: 0; margin: 0;">
                        <!-- Se llena con JS -->
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
<!-- SCRIPT OFICIAL SELECT2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // INICIALIZAR SELECT2 (Anti-Fantasmas Definitivo)
        $(document).ready(function() {
            $('#departamento').select2({
                placeholder: "Busca o selecciona departamentos...",
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se ha encontrado el departamento";
                    }
                },
                // LA MAGIA: Si la opción ya está seleccionada, no la renderiza en el menú
                templateResult: function(option) {
                    if (option.element && option.element.selected) {
                        return null; 
                    }
                    return option.text;
                }
            });
        });
    </script>
    
    <script>
        // Lógica de fechas
        (function() {
            // Elementos de Revisión Médica
            const btnHoyUltima = document.getElementById('btn-hoy-ultima'); // ID CORREGIDO
            const ultimaMedicaInput = document.getElementById('ultima_revision_medica');
            const proximaMedicaInput = document.getElementById('proxima_revision_medica');
            
            const btnHoyProxima = document.getElementById('btn-hoy-proxima');

            // Elementos de Graduación (gafas)
            const btnHoyGrad = document.getElementById('set-graduacion-hoy');
            const ultimaGradInput = document.getElementById('ultima_graduacion');
            const proximaGradInput = document.getElementById('proxima_graduacion');

            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            const parseDate = (value) => {
                if (!value) return null;
                const parts = value.split('-').map(Number);
                if (parts.length !== 3 || parts.some(Number.isNaN)) return null;
                return new Date(parts[0], parts[1] - 1, parts[2]);
            };

            const setNextFrom = (date, targetInput, meses = 12) => {
                if (!date || !targetInput) {
                    if (targetInput) targetInput.value = '';
                    return;
                }
                const next = new Date(date);
                next.setMonth(next.getMonth() + meses); 
                targetInput.value = formatDate(next);
            };

            const getMesesPuesto = () => {
                const selectPuesto = document.getElementById('puesto_trabajo_id');
                let minMeses = 12; // Valor seguro por defecto

                if (selectPuesto && selectPuesto.selectedIndex >= 0) {
                    const option = selectPuesto.options[selectPuesto.selectedIndex];
                    const meses = parseInt(option.getAttribute('data-meses'));
                    if (!isNaN(meses)) {
                        minMeses = meses;
                    }
                }
                return minMeses;
            };

            // 1. Botón "Poner hoy" para ÚLTIMA Revisión Médica
            if (btnHoyUltima && ultimaMedicaInput) {
                btnHoyUltima.addEventListener('click', function() {
                    const today = new Date();
                    ultimaMedicaInput.value = formatDate(today);
                    // Calcula la próxima revisión automáticamente según el puesto
                    setNextFrom(today, proximaMedicaInput, getMesesPuesto());
                });

                ultimaMedicaInput.addEventListener('input', function() {
                    setNextFrom(parseDate(ultimaMedicaInput.value), proximaMedicaInput, getMesesPuesto());
                });
            }

            // Si cambia el puesto laboral, recalcular próxima revisión médica automáticamente
            const selectPuesto = document.getElementById('puesto_trabajo_id');
            if (selectPuesto && ultimaMedicaInput && proximaMedicaInput) {
                selectPuesto.addEventListener('change', function() {
                    if (ultimaMedicaInput.value) {
                        setNextFrom(parseDate(ultimaMedicaInput.value), proximaMedicaInput, getMesesPuesto());
                    }
                });
            }

            // 2. Botón "Poner hoy" para PRÓXIMA Revisión Médica
            if (btnHoyProxima && proximaMedicaInput) {
                btnHoyProxima.addEventListener('click', function() {
                    const today = new Date();
                    proximaMedicaInput.value = formatDate(today);
                });
            }

            // 3. Botón "Poner hoy" para ÚLTIMA Graduación
            if (btnHoyGrad && ultimaGradInput) {
                btnHoyGrad.addEventListener('click', function() {
                    const today = new Date();
                    ultimaGradInput.value = formatDate(today);
                    // Calcula la próxima graduación (+12 meses por defecto)
                    setNextFrom(today, proximaGradInput);
                });

                ultimaGradInput.addEventListener('input', function() {
                    setNextFrom(parseDate(ultimaGradInput.value), proximaGradInput);
                });
            }
        })();

        // LÓGICA DEL MODAL DE GESTIÓN DE DEPARTAMENTOS
        (function() {
            const btnManage = document.getElementById('btn-manage-deptos');
            const modalManage = document.getElementById('manage-deptos-modal');
            if (!btnManage || !modalManage) return;

            const btnCloseList = modalManage.querySelectorAll('[data-close-manage-deptos]');
            const deptoList = document.getElementById('manage-deptos-list');
            const inputNewDepto = document.getElementById('new-depto-name');
            const btnAddDeptoAjax = document.getElementById('btn-add-depto-ajax');
            const selectDepto = document.getElementById('departamento');

            // Pinta la lista leyendo el <select> invisible
            const renderList = () => {
                deptoList.innerHTML = '';
                const options = Array.from(selectDepto.options).filter(opt => opt.value !== '');
                
                if(options.length === 0) {
                    deptoList.innerHTML = '<li style="padding: 16px; text-align: center; color: #94a3b8; font-size: 0.9rem;">No hay departamentos. Crea uno arriba.</li>';
                    return;
                }

                options.forEach(opt => {
                    const li = document.createElement('li');
                    li.className = 'depto-list-item';
                    li.innerHTML = `
                        <span style="font-size: 0.9rem; font-weight: 700; color: var(--profile-ink);">${opt.text}</span>
                        <button type="button" class="depto-btn-delete" data-val="${opt.value}" title="Eliminar definitivamente"><i class="fas fa-trash"></i></button>
                    `;
                    deptoList.appendChild(li);
                });
            };

            const openModal = () => {
                renderList();
                modalManage.classList.add('is-open');
                modalManage.setAttribute('aria-hidden', 'false');
            };

            const closeModal = () => {
                modalManage.classList.remove('is-open');
                modalManage.setAttribute('aria-hidden', 'true');
                inputNewDepto.value = ''; // Limpiar el input al salir
            };

            btnManage.addEventListener('click', openModal);
            btnCloseList.forEach(btn => btn.addEventListener('click', closeModal));

            // AÑADIR AJAX
            btnAddDeptoAjax.addEventListener('click', async () => {
                const nombre = inputNewDepto.value.trim();
                if(!nombre) return;
                
                btnAddDeptoAjax.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btnAddDeptoAjax.disabled = true;

                try {
                    const response = await fetch('{{ route("departamentos.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ nombre })
                    });
                    const data = await response.json();
                    
                    if(response.ok && data.success) {
                        // Inyectar en el select y avisar a Select2
                        const option = document.createElement('option');
                        option.value = data.departamento.nombre;
                        option.text = data.departamento.nombre.toUpperCase();
                        selectDepto.appendChild(option);
                        $('#departamento').trigger('change');
                        
                        inputNewDepto.value = '';
                        renderList(); // Repintar la lista en vivo
                    } else {
                        alert('El departamento ya existe o el nombre no es válido.');
                    }
                } catch(e) {
                    alert('Error de conexión al crear.');
                } finally {
                    btnAddDeptoAjax.innerHTML = 'Añadir';
                    btnAddDeptoAjax.disabled = false;
                }
            });

            // BORRAR AJAX (Delegación de eventos)
            deptoList.addEventListener('click', async (e) => {
                const btn = e.target.closest('.depto-btn-delete');
                if(!btn) return;

                const val = btn.getAttribute('data-val');
                if(!confirm(`¿Seguro que deseas eliminar definitivamente "${val}" de la base de datos?`)) return;

                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                try {
                    const response = await fetch(`/departamentos/${val}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    
                    if(response.ok) {
                        // Buscarlo en el select y aniquilarlo
                        const opt = Array.from(selectDepto.options).find(o => o.value === val);
                        if(opt) {
                            opt.selected = false; // Desmarcarlo si estaba puesto
                            opt.remove();
                        }
                        $('#departamento').trigger('change'); // Refrescar UI Select2
                        renderList(); // Repintar modal
                    } else {
                        alert('Error del servidor al intentar borrar.');
                        renderList();
                    }
                } catch(err) {
                    alert('Error de conexión al borrar.');
                    renderList();
                }
            });
        })();
    </script>
@endsection