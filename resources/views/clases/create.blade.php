@extends('adminlte::page')

@section('title', 'Crear Clase - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/clases-create.css'])
@endsection

@section('content')
    <section class="clases-create-page">
        <section class="clases-create-head">
            <h1>Crear Nueva Clase</h1>
            <p>Define una nueva categoría para clasificar los items del inventario del proyecto.</p>
        </section>

        <div class="clases-create-shell">
            <form class="clases-create-card" action="{{ route('clases.store') }}" method="POST" novalidate>
                @csrf

                @if ($errors->any())
                    <div style="margin-bottom: 1.2rem; padding: 0.85rem; border-radius: 0.6rem; background: #fee2e2; border: 1px solid #fca5a5; color: #dc2626;">
                        <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                        <strong>Errores encontrados:</strong>
                        <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="clases-form-grid">
                    <div class="field-group">
                        <label for="nombre">Nombre de la Clase</label>
                        <div class="input-wrap">
                            <i class="fas fa-tag"></i>
                            <input
                                id="nombre"
                                name="nombre"
                                type="text"
                                value="{{ old('nombre') }}"
                                placeholder="Ej: EPI, Herramientas, Consumibles"
                                required
                            >
                        </div>
                        @error('nombre')
                            <small style="color: #dc2626; font-size: 0.8rem;">{{ $message }}</small>
                        @enderror
                    </div>

                    <small style="color: #72849f; font-size: 0.85rem; display: block;">
                        <i class="fas fa-info-circle"></i>
                        Las clases se utilizan para clasificar y organizar los items del inventario por categorías.
                    </small>
                </div>

                <footer class="clases-create-actions">
                    <a href="{{ route('clases.index') }}" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-create">
                        <i class="fas fa-plus-square"></i>
                        Crear Clase
                    </button>
                </footer>
            </form>
        </div>

        <section class="clases-feature-grid">
            <article class="feature-card">
                <i class="fas fa-folder-open"></i>
                <div>
                    <h3>Organización</h3>
                    <p>Agrupa items similares para facilitar búsqueda y gestión.</p>
                </div>
            </article>

            <article class="feature-card">
                <i class="fas fa-filter"></i>
                <div>
                    <h3>Filtrado</h3>
                    <p>Filtra rápidamente el inventario por clasificación.</p>
                </div>
            </article>

            <article class="feature-card">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <h3>Control</h3>
                    <p>Solo admins pueden crear y eliminar categorías.</p>
                </div>
            </article>
        </section>
    </section>
@endsection
