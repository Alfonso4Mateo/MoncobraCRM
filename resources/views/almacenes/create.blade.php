@extends('adminlte::page')

@section('title', 'Crear Almacen - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/almacenes-create.css'])
@endsection

@section('content')
    @if (session('success'))
        <div class="almacen-success" role="alert">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <section class="almacen-create-page">
        <section class="almacen-create-head">
            <a href="{{ route('inventario.index') }}" class="btn-cancel mb-3">← Volver al Inventario</a>
            <h1>Crear nuevo almacén</h1>
            <p>Configure los parámetros técnicos de la nueva zona logística en el sistema.</p>
        </section>

        <div class="almacen-create-shell">
            <form class="almacen-create-card" action="{{ route('almacenes.store') }}" method="POST" novalidate>
                @csrf

                <div class="almacen-form-grid">
                    <div class="field-group field-name">
                        <label for="nombre_almacen">Nombre del almacén</label>
                        <div class="input-wrap">
                            <i class="far fa-warehouse"></i>
                            <input
                                id="nombre_almacen"
                                name="nombre_almacen"
                                type="text"
                                value="{{ old('nombre_almacen') }}"
                                placeholder="Ej. Almacén Central"
                                    required
                            >
                        </div>
                    </div>

                    <div class="field-group field-description">
                        <label for="descripcion_almacen">Descripción del almacén</label>
                        <div class="input-wrap textarea-wrap">
                            <i class="far fa-clipboard"></i>
                            <textarea
                                id="descripcion_almacen"
                                name="descripcion_almacen"
                                rows="4"
                                placeholder="Ej: Introduce descripcion general del almacen"
                                required
                            >{{ old('descripcion_almacen') }}</textarea>
                        </div>
                    </div>

                </div>

                <footer class="almacen-create-actions">
                    <a href="{{ route('inventario.index') }}" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-create">
                        <i class="far fa-plus-square"></i>
                        Crear Ubicación
                    </button>
                </footer>
            </form>
        </div>

        <section class="almacen-feature-grid">
            <article class="feature-card">
                <i class="far fa-shield-alt"></i>
                <div>
                    <h3>Seguridad</h3>
                    <p>Registro cifrado de alta disponibilidad bajo estándares industriales.</p>
                </div>
            </article>

            <article class="feature-card">
                <i class="fas fa-retweet"></i>
                <div>
                    <h3>Sincronización</h3>
                    <p>Actualización instantánea en terminales PDA y escáners remotos.</p>
                </div>
            </article>

            <article class="feature-card">
                <i class="far fa-folder-open"></i>
                <div>
                    <h3>Trazabilidad</h3>
                    <p>Generación automática de códigos QR para nueva ubicación.</p>
                </div>
            </article>
        </section>
    </section>
@endsection