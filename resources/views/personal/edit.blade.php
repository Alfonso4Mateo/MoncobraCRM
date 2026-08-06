@extends('adminlte::page')

@section('title', ($isCreate ?? false) ? 'Crear Trabajador' : 'Editar Ficha')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/personal-show.css'])
    <style>
        .profile-edit-form-group {
            margin-bottom: 16px;
        }

        .profile-edit-form-group label {
            display: block;
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: #8a98ab;
            margin-bottom: 8px;
        }

        .profile-edit-form-group input,
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

        .profile-edit-form-group input:focus,
        .profile-edit-form-group select:focus,
        .profile-edit-form-group textarea:focus {
            outline: none;
            border-color: var(--profile-primary);
            box-shadow: 0 0 0 3px rgba(23, 62, 103, .08);
        }

        .profile-edit-form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .profile-edit-form-group .is-invalid {
            border-color: #e11d48;
            background: #fff1f2;
        }

        .profile-edit-form-group .is-invalid:focus {
            border-color: #be123c;
            box-shadow: 0 0 0 3px rgba(225, 29, 72, .18);
        }

        .profile-edit-inline {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-edit-inline input {
            flex: 1;
        }

        .profile-edit-today-btn {
            border: 1px solid var(--profile-line);
            background: #f7f9fc;
            color: var(--profile-ink);
            font-weight: 700;
            font-size: .8rem;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all .18s ease;
            white-space: nowrap;
        }

        .profile-edit-today-btn:hover {
            border-color: var(--profile-primary);
            color: var(--profile-primary);
            box-shadow: 0 4px 10px rgba(23, 62, 103, .12);
        }

        .profile-alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: .9rem;
            font-weight: 600;
            border: 1px solid transparent;
            margin-bottom: 16px;
        }

        .profile-alert-error {
            background: #fff1f2;
            color: #b91c1c;
            border-color: #f5c2c7;
        }

        .profile-alert-error ul {
            margin: 8px 0 0;
            padding-left: 18px;
            font-weight: 600;
        }

        .profile-edit-two-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 768px) {
            .profile-edit-two-cols {
                grid-template-columns: 1fr;
            }
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
                                    <h2>{{ $personal->name }} {{ $personal->apellido }}</h2>
                                    @php
                                        $deptosActuales = is_string($personal->departamento) ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento) : (array) $personal->departamento;
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
                                    <input type="text" id="name" name="name" value="{{ old('name', $personal->name) }}" class="@error('name') is-invalid @enderror">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" value="{{ old('apellido', $personal->apellido) }}" class="@error('apellido') is-invalid @enderror">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="dni_nie">DNI / NIE</label>
                                    <input type="text" id="dni_nie" name="dni_nie" value="{{ old('dni_nie', $personal->dni_nie) }}" class="@error('dni_nie') is-invalid @enderror">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="id_rrhh">ID de RRHH</label>
                                    <input type="text" id="id_rrhh" name="id_rrhh" maxlength="10" value="{{ old('id_rrhh', $personal->id_rrhh ?? '') }}" placeholder="Ej. A1B2C3D4E5" class="@error('id_rrhh') is-invalid @enderror">
                                </div>
                
                                <div class="profile-edit-form-group">
                                    <!-- Contenedor flex para alinear la etiqueta y los botones de añadir/quitar departamento -->
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <label for="departamento" style="margin-bottom: 0;">Departamento</label>
                                        <div style="display: flex; gap: 8px;">
                                            <button type="button" id="btn-add-depto" title="Añadir departamento" style="border: 1px solid var(--profile-line); background: #f7f9fc; color: var(--profile-ink); border-radius: 4px; padding: 2px 8px; cursor: pointer;">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button type="button" id="btn-del-depto" title="Eliminar seleccionado" style="border: 1px solid #f5c2c7; background: #fff1f2; color: #b91c1c; border-radius: 4px; padding: 2px 8px; cursor: pointer;">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <select id="departamento" name="departamento[]" multiple class="@error('departamento') is-invalid @enderror" style="height: 140px;">
                                        @php
                                            // Normalizamos el dato guardado en el trabajador para asegurar que siempre sea un array
                                            $deptosActuales = is_string($personal->departamento) ? json_decode($personal->departamento, true) ?? explode(',', $personal->departamento) : (array) $personal->departamento;
                                            
                                            // old() recupera los seleccionados si la validación falla
                                            $deptosSeleccionados = old('departamento', $deptosActuales);
                                        @endphp

                                        <!-- Ahora iteramos sobre la variable $departamentos que nos ha mandado el Controlador -->
                                        @foreach($departamentos as $depto)
                                            <option value="{{ $depto->nombre }}" @selected(in_array($depto->nombre, $deptosSeleccionados ?: []))>
                                                {{ strtoupper($depto->nombre) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small style="font-size: 0.75rem; color: #8a98ab; margin-top: 4px; display: block;">Mantén pulsado Ctrl (Windows) o Cmd (Mac) para seleccionar varios.</small>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $personal->telefono ?? '') }}" placeholder="Ej. 600 000 000" class="@error('telefono') is-invalid @enderror">
                                </div>

                                <div class="profile-edit-two-cols">
                                    <div class="profile-edit-form-group">
                                        <label for="ultima_revision_medica">Última revisión médica</label>
                                        <div class="profile-edit-inline">
                                            <input type="date" id="ultima_revision_medica" name="ultima_revision_medica" value="{{ old('ultima_revision_medica', optional($personal->ultima_revision_medica)->format('Y-m-d')) }}" class="@error('ultima_revision_medica') is-invalid @enderror">
                                            <button type="button" id="set-ultima-hoy" class="profile-edit-today-btn">Hoy</button>
                                        </div>
                                    </div>

                                    <div class="profile-edit-form-group">
                                        <label for="proxima_revision_medica">Próxima revisión médica</label>
                                        <input type="date" id="proxima_revision_medica" name="proxima_revision_medica" value="{{ old('proxima_revision_medica', optional($personal->proxima_revision_medica)->format('Y-m-d')) }}" class="@error('proxima_revision_medica') is-invalid @enderror">
                                    </div>

                                    <div class="profile-edit-form-group">
                                        <label for="ultima_graduacion">Última graduación</label>
                                        <div class="profile-edit-inline">
                                            <input type="date" id="ultima_graduacion" name="ultima_graduacion" value="{{ old('ultima_graduacion', optional($personal->ultima_graduacion)->format('Y-m-d')) }}" class="@error('ultima_graduacion') is-invalid @enderror">
                                            <button type="button" id="set-graduacion-hoy" class="profile-edit-today-btn">Hoy</button>
                                        </div>
                                    </div>

                                    <div class="profile-edit-form-group">
                                        <label for="proxima_graduacion">Próxima graduación</label>
                                        <input type="date" id="proxima_graduacion" name="proxima_graduacion" value="{{ old('proxima_graduacion', optional($personal->proxima_graduacion)->format('Y-m-d')) }}" class="@error('proxima_graduacion') is-invalid @enderror">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Panel Tallas y Equipamiento -->
                    <article class="profile-card">
                        <div class="profile-card__header">
                            <div>
                                <h3><i class="fas fa-ruler-combined"></i> Tallas y Equipamiento</h3>
                                <p>Medidas corporales para la asignación de uniforme industrial</p>
                            </div>
                        </div>

                        <div class="profile-card__body" style="padding: 20px;">
                            <div class="profile-edit-two-cols">
                                <div class="profile-edit-form-group">
                                    <label for="camiseta">Camiseta</label>
                                    <select id="camiseta" name="camiseta" class="@error('camiseta') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['2XS' => '2XS','XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL', '5XL' => '5XL', '6XL' => '6XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('camiseta', $personal->camiseta) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="chaqueta">Chaqueta</label>
                                    <select id="chaqueta" name="chaqueta" class="@error('chaqueta') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['2XS' => '2XS','XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL', '5XL' => '5XL', '6XL' => '6XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('chaqueta', $personal->chaqueta) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="sudadera">Sudadera</label>
                                    <select id="sudadera" name="sudadera" class="@error('sudadera') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['2XS' => '2XS','XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL', '5XL' => '5XL', '6XL' => '6XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('sudadera', $personal->sudadera) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="pantalon">Pantalón</label>
                                    <select id="pantalon" name="pantalon" class="@error('pantalon') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['30' => '30','32' => '32','34' => '34','36' => '36', '38' => '38', '40' => '40', '42' => '42', '44' => '44', '46' => '46', '48' => '48', '50' => '50','52' => '52','54' => '54','56' => '56','58' => '58','60' => '60','62' => '62','64' => '64','66' => '66','68' => '68','70' => '70'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('pantalon', $personal->pantalon) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="calzado">Calzado Seguridad</label>
                                    <select id="calzado" name="calzado" class="@error('calzado') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['34' => '34','35' => '35','36' => '36', '37' => '37', '38' => '38', '39' => '39', '40' => '40', '41' => '41', '42' => '42', '43' => '43', '44' => '44', '45' => '45', '46' => '46', '47' => '47', '48' => '48', '49' => '49', '50' => '50'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('calzado', $personal->calzado) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="guantes">Guantes</label>
                                    <select id="guantes" name="guantes" class="@error('guantes') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['5' => '5','6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11' ,'12' => '12'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('guantes', $personal->guantes) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="casco">Casco</label>
                                    <select id="casco" name="casco" class="@error('casco') is-invalid @enderror">
                                        <option value="">Selecciona talla</option>
                                        <option value="Estándar" @selected(old('casco', $personal->casco) === 'Estándar')>Estándar</option>
                                    </select>
                                </div>
                                <div class="profile-edit-form-group">
                                    <label for="gafas">Gafas</label>
                                    <select id="gafas" name="gafas" class="@error('gafas') is-invalid @enderror">
                                        <option value="">Selecciona tipo</option>
                                        @foreach(['Graduadas' => 'Graduadas', 'Protección' => 'Protección'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('gafas', $personal->gafas) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </article>

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
    </section>
@endsection

@section('js')
    <script>
        // Lógica de fechas
        (function() {
            // Elementos de Revisión Médica
            const btnHoyMedica = document.getElementById('set-ultima-hoy');
            const ultimaMedicaInput = document.getElementById('ultima_revision_medica');
            const proximaMedicaInput = document.getElementById('proxima_revision_medica');
            
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

            const setNextFrom = (date, targetInput) => {
                if (!date || !targetInput) {
                    if (targetInput) targetInput.value = '';
                    return;
                }
                const next = new Date(date);
                next.setMonth(next.getMonth() + 12); // Añade 1 año por defecto
                targetInput.value = formatDate(next);
            };

            // Eventos para Revisión Médica
            if (btnHoyMedica && ultimaMedicaInput) {
                btnHoyMedica.addEventListener('click', function() {
                    const today = new Date();
                    ultimaMedicaInput.value = formatDate(today);
                    setNextFrom(today, proximaMedicaInput);
                });

                ultimaMedicaInput.addEventListener('input', function() {
                    setNextFrom(parseDate(ultimaMedicaInput.value), proximaMedicaInput);
                });
            }

            // Eventos para Graduación
            if (btnHoyGrad && ultimaGradInput) {
                btnHoyGrad.addEventListener('click', function() {
                    const today = new Date();
                    ultimaGradInput.value = formatDate(today);
                    setNextFrom(today, proximaGradInput);
                });

                ultimaGradInput.addEventListener('input', function() {
                    setNextFrom(parseDate(ultimaGradInput.value), proximaGradInput);
                });
            }
        })();

        // LÓGICA PARA LOS BOTONES DE DEPARTAMENTO (AJAX)
        (function() {
            const btnAddDepto = document.getElementById('btn-add-depto');
            const btnDelDepto = document.getElementById('btn-del-depto');
            const selectDepto = document.getElementById('departamento');

            // Acción del botón "+"
            if (btnAddDepto) {
                btnAddDepto.addEventListener('click', async function() {
                    const nombre = prompt('Escribe el nombre del nuevo departamento:');
                    if (!nombre || nombre.trim() === '') return;

                    try {
                        const response = await fetch('{{ route("departamentos.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}' // Medida de seguridad obligatoria en Laravel
                            },
                            body: JSON.stringify({ nombre: nombre.trim() })
                        });

                        const data = await response.json();
                        
                        if (response.ok && data.success) {
                            // Si Laravel dice OK, creamos la opción y la añadimos al HTML visualmente
                            const option = document.createElement('option');
                            option.value = data.departamento.nombre;
                            option.text = data.departamento.nombre.toUpperCase();
                            selectDepto.appendChild(option);
                            
                            // Seleccionamos automáticamente el que acabamos de crear
                            option.selected = true;
                        } else {
                            alert('Error: Es posible que el departamento ya exista o haya un problema de validación.');
                        }
                    } catch (error) {
                        alert('Error de conexión al crear el departamento.');
                        console.error(error);
                    }
                });
            }

            // Acción del botón "-"
            if (btnDelDepto) {
                btnDelDepto.addEventListener('click', async function() {
                    const selectedOptions = Array.from(selectDepto.selectedOptions);
                    
                    if (selectedOptions.length === 0) {
                        alert('Selecciona en la lista el departamento que quieres eliminar.');
                        return;
                    }

                    const confirmacion = confirm('¿Eliminar los departamentos seleccionados de la base de datos?\n\n(Nota: No se borrarán de los trabajadores que ya lo tengan asignado).');
                    if (!confirmacion) return;

                    // Recorremos todos los que haya marcado y los borramos uno a uno
                    for (const option of selectedOptions) {
                        try {
                            const response = await fetch(`/departamentos/${option.value}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            });

                            if (response.ok) {
                                option.remove(); // Lo borramos del HTML visualmente
                            } else {
                                alert(`Error al intentar borrar el departamento: ${option.value}`);
                            }
                        } catch (error) {
                            console.error('Error al borrar', error);
                            alert('Hubo un error de conexión al intentar borrar el departamento.');
                        }
                    }
                });
            }
        })();
    </script>
@endsection