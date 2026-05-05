@extends('adminlte::page')

@section('title', 'Editar Ficha')

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
                <h1>Editar Ficha: {{ $user->name }} {{ $user->apellido }}</h1>
                <p>Actualiza la información personal y equipamiento de protección individual (EPIS)</p>
            </div>

            <div class="profile-hero-actions">
                <a href="{{ route('personal.show', $user->id) }}" class="profile-action profile-action--soft">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <button form="personal-edit-form" class="profile-action profile-action--primary">
                    <i class="fas fa-save"></i>
                    Guardar Cambios
                </button>
            </div>
        </header>

        <form id="personal-edit-form" action="{{ route('personal.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

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
                                    <h2>{{ $user->name }} {{ $user->apellido }}</h2>
                                    <p>{{ strtoupper($user->departamento ?: 'Sin departamento') }}</p>
                                </div>

                                <div class="profile-metadata">
                                    <div>
                                        <span>ID EMPLEADO</span>
                                        <strong>AL-{{ str_pad((string) $user->id, 3, '0', STR_PAD_LEFT) }}</strong>
                                    </div>
                                    <div>
                                        <span>REGISTRADO DESDE</span>
                                        <strong>{{ optional($user->created_at)->format('d M Y') }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Información Personal -->
                            <div style="flex: 1;">
                                <div class="profile-edit-form-group">
                                    <label for="name">Nombre</label>
                                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" value="{{ old('apellido', $user->apellido) }}">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="dni_nie">DNI / NIE</label>
                                    <input type="text" id="dni_nie" name="dni_nie" value="{{ old('dni_nie', $user->dni_nie) }}" disabled>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" disabled>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="departamento">Departamento</label>
                                    <input type="text" id="departamento" name="departamento" value="{{ old('departamento', $user->departamento) }}">
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $user->telefono ?? '') }}" placeholder="Ej. 600 000 000">
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
                                            <option value="{{ $value }}" @selected(old('camiseta', $user->camiseta) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="chaqueta">Chaqueta</label>
                                    <select id="chaqueta" name="chaqueta">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('chaqueta', $user->chaqueta) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="sudadera">Sudadera</label>
                                    <select id="sudadera" name="sudadera">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', '2XL' => '2XL', '3XL' => '3XL', '4XL' => '4XL'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('sudadera', $user->sudadera) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="pantalon">Pantalón</label>
                                    <select id="pantalon" name="pantalon">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['36' => '36', '38' => '38', '40' => '40', '42' => '42', '44' => '44', '46' => '46', '48' => '48', '50' => '50'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('pantalon', $user->pantalon) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="calzado">Calzado Seguridad</label>
                                    <select id="calzado" name="calzado">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['36' => '36', '37' => '37', '38' => '38', '39' => '39', '40' => '40', '41' => '41', '42' => '42', '43' => '43', '44' => '44', '45' => '45', '46' => '46'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('calzado', $user->calzado) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="guantes">Guantes</label>
                                    <select id="guantes" name="guantes">
                                        <option value="">Selecciona talla</option>
                                        @foreach(['6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('guantes', $user->guantes) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="profile-edit-form-group">
                                    <label for="casco">Casco</label>
                                    <select id="casco" name="casco">
                                        <option value="">Selecciona talla</option>
                                        <option value="Estándar" @selected(old('casco', $user->casco) === 'Estándar')>Estándar</option>
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
                                <textarea id="descripcion" name="descripcion" placeholder="Añade información adicional, notas especiales, o comentarios...">{{ old('descripcion', $user->descripcion ?? '') }}</textarea>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </form>
    </section>
@endsection
