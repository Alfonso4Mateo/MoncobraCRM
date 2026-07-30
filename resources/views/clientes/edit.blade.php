@extends('adminlte::page')

@section('title', 'Editar Cliente - MoncobraCRM')

@section('content')
    <section class="cliente-edit-ui">
        <header class="cliente-edit-topbar">
            <nav aria-label="breadcrumb" class="cliente-edit-breadcrumbs">
                <a href="{{ route('clientes.index') }}">Clientes</a>
                <span><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                <span>{{ $cliente->empresa_nombre }}</span>
                <span><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                <strong>Editar Cliente</strong>
            </nav>
        </header>

        <section class="cliente-edit-head">
            <h1>Editar Cliente: {{ $cliente->empresa_nombre }}</h1>
            <p>Actualice la información del cliente y los parámetros de gestión empresarial.</p>
        </section>

        @if ($errors->any())
            <div class="alert alert-danger cliente-edit-errors" role="alert">
                <strong>No se pudo actualizar el cliente.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <article class="cliente-edit-card">
            <form action="{{ route('clientes.update', $cliente->id) }}" method="POST" novalidate>
                @csrf
                @method('PUT')

                <section class="edit-block">
                    <h2>
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        Información General
                    </h2>

                    <div class="edit-grid cols-2">
                        <div class="field-group">
                            <label for="empresa_nombre">Nombre de la Empresa</label>
                            <input type="text" id="empresa_nombre" name="empresa_nombre" value="{{ old('empresa_nombre', $cliente->empresa_nombre) }}" class="@error('empresa_nombre') is-invalid @enderror" required>
                        </div>

                        <div class="field-group">
                            <label for="cif_nif">CIF / VAT Number</label>
                            <input type="text" id="cif_nif" name="cif_nif" value="{{ old('cif_nif', $cliente->cif_nif) }}" class="@error('cif_nif') is-invalid @enderror" required>
                        </div>

                        <div class="field-group">
                            <label for="localidad">Localidad</label>
                            <input type="text" id="localidad" name="localidad" value="{{ old('localidad', $cliente->localidad) }}" class="@error('localidad') is-invalid @enderror" required>
                        </div>

                        <div class="field-group">
                            <label for="codigo_postal">CP</label>
                            <input type="text" id="codigo_postal" name="codigo_postal" value="{{ old('codigo_postal', $cliente->codigo_postal) }}" class="@error('codigo_postal') is-invalid @enderror" required>
                        </div>
                    </div>
                </section>

                <section class="edit-block with-divider">
                    <h2>
                        <i class="far fa-user" aria-hidden="true"></i>
                        Contacto Principal
                    </h2>

                    <div class="edit-grid cols-2">
                        <div class="field-group">
                            <label for="persona_contacto">Nombre del Responsable</label>
                            <input type="text" id="persona_contacto" name="persona_contacto" value="{{ old('persona_contacto', $cliente->persona_contacto) }}" class="@error('persona_contacto') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $cliente->email) }}" class="@error('email') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="telefono">Teléfono Directo</label>
                            <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $cliente->telefono) }}" class="@error('telefono') is-invalid @enderror">
                        </div>

                        <div class="field-group">
                            <label for="cargo_visual">Cargo / Posición</label>
                            <input type="text" id="cargo_visual" value="Responsable de cuenta" disabled>
                        </div>
                    </div>
                </section>

                <section class="edit-block with-divider">
                    <h2>
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        Ubicación
                    </h2>

                    <div class="edit-grid cols-1">
                        <div class="field-group">
                            <label for="direccion">Dirección</label>
                            <input type="text" id="direccion" name="direccion" value="{{ old('direccion', $cliente->direccion) }}" class="@error('direccion') is-invalid @enderror" required>
                        </div>
                    </div>

                    <div class="edit-grid cols-3 edit-grid--location">
                        <div class="field-group">
                            <label for="localidad">Localidad</label>
                            <input type="text" id="localidad" name="localidad" value="{{ old('localidad', $cliente->localidad) }}" class="@error('localidad') is-invalid @enderror" required>
                        </div>

                        <div class="field-group">
                            <label for="provincia">Provincia</label>
                            <input type="text" id="provincia" name="provincia" value="{{ old('provincia', $cliente->provincia) }}" class="@error('provincia') is-invalid @enderror" required>
                        </div>

                        <div class="field-group">
                            <label for="codigo_postal">Código Postal</label>
                            <input type="text" id="codigo_postal" name="codigo_postal" value="{{ old('codigo_postal', $cliente->codigo_postal) }}" class="@error('codigo_postal') is-invalid @enderror" required>
                        </div>
                    </div>
                </section>

                <footer class="edit-actions">
                    <a href="{{ route('clientes.index') }}" class="btn-cancelar">Cancelar</a>
                    <button type="submit" class="btn-actualizar">
                        <i class="far fa-save" aria-hidden="true"></i>
                        Actualizar Datos
                    </button>
                </footer>
            </form>
        </article>
    </section>
@endsection

@section('css')
    @vite(['resources/css/clientes-edit.css'])
@endsection
