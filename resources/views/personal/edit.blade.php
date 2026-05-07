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
                                    <p>{{ strtoupper($personal->departamento ?: 'Sin departamento') }}</p>
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
                                    <input type="text" id="name" name="name" value="{{ old('name', $personal->name) }}">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" value="{{ old('apellido', $personal->apellido) }}">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="dni_nie">DNI / NIE</label>
                                    <input type="text" id="dni_nie" name="dni_nie" value="{{ old('dni_nie', $personal->dni_nie) }}">
                                </div>
                
                                <div class="profile-edit-form-group">
                                    <label for="departamento">Departamento</label>
                                    <input type="text" id="departamento" name="departamento" value="{{ old('departamento', $personal->departamento) }}">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $personal->telefono ?? '') }}" placeholder="Ej. 600 000 000">
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
                                    <select id="camiseta" name="camiseta">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('camiseta', $personal->camiseta) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="chaqueta">Chaqueta</label>
                                    <select id="chaqueta" name="chaqueta">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('chaqueta', $personal->chaqueta) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="sudadera">Sudadera</label>
                                    <select id="sudadera" name="sudadera">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('sudadera', $personal->sudadera) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="pantalon">Pantalón</label>
                                    <select id="pantalon" name="pantalon">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['36' => '36', '38' => '38', '40' => '40', '42' => '42', '44' => '44', '46' => '46', '48' => '48', '50' => '50'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('pantalon', $personal->pantalon) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="calzado">Calzado Seguridad</label>
                                    <select id="calzado" name="calzado">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['36' => '36', '37' => '37', '38' => '38', '39' => '39', '40' => '40', '41' => '41', '42' => '42', '43' => '43', '44' => '44', '45' => '45', '46' => '46'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('calzado', $personal->calzado) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="guantes">Guantes</label>
                                    <select id="guantes" name="guantes">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('guantes', $personal->guantes) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="casco">Casco</label>
                                    <select id="casco" name="casco">
                                        <option value="">Selecciona talla</option>
                                        <option value="Estándar" @selected(old('casco', $personal->casco) === 'Estándar')>Estándar</option>
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
                                <textarea id="descripcion" name="descripcion" placeholder="Añade información adicional, notas especiales, o comentarios...">{{ old('descripcion', $personal->descripcion ?? '') }}</textarea>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </form>
    </section>
@endsection
