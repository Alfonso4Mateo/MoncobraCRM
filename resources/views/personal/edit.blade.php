@extends('adminlte::page')

@section('title', 'Editar Ficha')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/personal-show.css'])
@endsection

@section('content')
    <section class="profile-page">
        <header class="profile-hero">
            <div>
                <div class="profile-crumbs">GESTIÓN DE PERSONAL <span>•</span> EDITAR TRABAJADOR</div>
                <h1>Editar Ficha: {{ $user->name }} {{ $user->apellido }}</h1>
                <p>Modificación de perfil y equipamiento de protección individual (EPIS)</p>
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

        <div class="container mt-4">
            <form id="personal-edit-form" action="{{ route('personal.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Fila 1: Información Personal + Tallas y Equipamiento -->
                <div class="row gx-4 mb-4">
                    <!-- Columna Izquierda: Información Personal -->
                    <div class="col-lg-6 col-md-6 col-12 mb-3">
                        <article class="profile-card">
                            <div class="profile-card__header">
                                <div>
                                    <h3>Información Personal</h3>
                                    <p>Datos básicos y contacto</p>
                                </div>
                            </div>

                            <div class="profile-card__body" style="padding: 28px;">
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label style="font-weight: 600; margin-bottom: 10px; display: block;">Nombre</label>
                                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" style="padding: 12px; font-size: 15px;">
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label style="font-weight: 600; margin-bottom: 10px; display: block;">Apellido</label>
                                        <input type="text" name="apellido" value="{{ old('apellido', $user->apellido) }}" class="form-control" style="padding: 12px; font-size: 15px;">
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label style="font-weight: 600; margin-bottom: 10px; display: block;">DNI / NIE</label>
                                        <input type="text" name="dni_nie" value="{{ old('dni_nie', $user->dni_nie) }}" class="form-control" style="padding: 12px; font-size: 15px;">
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label style="font-weight: 600; margin-bottom: 10px; display: block;">Departamento</label>
                                        <input type="text" name="departamento" value="{{ old('departamento', $user->departamento) }}" class="form-control" style="padding: 12px; font-size: 15px;">
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- Columna Derecha: Tallas y Equipamiento (Azul Oscuro) -->
                    <div class="col-lg-6 col-md-6 col-12 mb-3">
                        <article class="profile-card profile-card--tallas-dark">
                            <div class="profile-card__header" style="background-color: #1a3a52; color: white;">
                                <div>
                                    <h3 style="color: white; margin-bottom: 4px;"><i class="fas fa-ruler-combined"></i> Tallas y Equipamiento</h3>
                                    <p style="color: #a8c5d9; font-size: 13px;">Definición de medidas corporales para la asignación de uniforme industrial</p>
                                </div>
                            </div>

                            <div class="profile-card__body" style="background-color: #1a3a52; padding: 20px;">
                                <div class="talla-item mb-3">
                                    <label style="color: white; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">
                                        <i class="fas fa-shirt"></i> Camisa / Chaqueta
                                    </label>
                                    <input type="text" name="camiseta" value="{{ old('camiseta', $user->camiseta) }}" class="form-control" placeholder="Ej. L, XL">
                                </div>

                                <div class="talla-item mb-3">
                                    <label style="color: white; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">
                                        <i class="fas fa-ruler-combined"></i> Pantalón
                                    </label>
                                    <input type="text" name="pantalon" value="{{ old('pantalon', $user->pantalon) }}" class="form-control" placeholder="Ej. 32, 34">
                                </div>

                                <div class="talla-item mb-3">
                                    <label style="color: white; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">
                                        <i class="fas fa-shoe-prints"></i> Calzado Seguridad
                                    </label>
                                    <input type="text" name="calzado" value="{{ old('calzado', $user->calzado) }}" class="form-control" placeholder="Ej. 42, 43">
                                </div>

                                <div class="talla-item">
                                    <label style="color: white; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">
                                        <i class="fas fa-hand-paper"></i> Guantes
                                    </label>
                                    <input type="text" name="guantes" value="{{ old('guantes', $user->guantes) }}" class="form-control" placeholder="Ej. M, L">
                                </div>

                                <div style="background-color: rgba(255,193,7,0.2); border-left: 4px solid #ffc107; padding: 12px; border-radius: 4px; margin-top: 16px;">
                                    <p style="color: #ffc107; font-size: 12px; margin: 0;">
                                        <i class="fas fa-info-circle"></i> Las cantidades en estas medidas garantizan una protección adecuada e uniforme personalizado.
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <!-- Fila 2: Contacto de Emergencia -->
                <div class="row gx-4">
                    <div class="col-lg-6 col-md-6 col-12 mb-3">
                        <article class="profile-card">
                            <div class="profile-card__header">
                                <div>
                                    <h3>Contacto de Emergencia</h3>
                                    <p>Persona y teléfono</p>
                                </div>
                            </div>
                            <div class="profile-card__body" style="padding: 28px;">
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label style="font-weight: 600; margin-bottom: 10px; display: block;">Persona de contacto</label>
                                        <input type="text" name="contacto_nombre" value="{{ old('contacto_nombre', '') }}" class="form-control" style="padding: 12px; font-size: 15px;">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <label style="font-weight: 600; margin-bottom: 10px; display: block;">Teléfono</label>
                                        <input type="text" name="contacto_telefono" value="{{ old('contacto_telefono', '') }}" class="form-control" style="padding: 12px; font-size: 15px;">
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="col-lg-6 col-md-6 col-12 mb-3">
                        <!-- Espacio reservado para "Últimas entregas EPIS" u otro contenido -->
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
