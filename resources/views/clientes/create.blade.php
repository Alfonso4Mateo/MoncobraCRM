@extends('adminlte::page')

@section('title', 'Nuevo Cliente - MoncobraCRM')

@section('content')
    <section class="cliente-create-ui">
        <header class="cliente-create-topbar">
            <nav aria-label="breadcrumb" class="cliente-breadcrumbs">
                <a href="{{ route('dashboard') }}">Inicio</a>
                <span><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                <a href="{{ route('clientes.index') }}">Clientes</a>
                <span><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                <strong>Nuevo Cliente</strong>
            </nav>
            <div class="cliente-top-actions">
                <button type="button" aria-label="Notificaciones"><i class="far fa-bell"></i></button>
                <button type="button" aria-label="Ayuda"><i class="far fa-question-circle"></i></button>
            </div>
        </header>

        <section class="cliente-page-head">
            <h1>Añadir Nuevo Cliente</h1>
            <p>Complete los datos de contacto y facturación para registrar una nueva entidad en el sistema.</p>
        </section>

        @if ($errors->any())
            <div class="alert alert-danger cliente-errors" role="alert">
                <strong>No se pudo guardar el cliente.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <article class="cliente-create-card">
            <header>
                <i class="fas fa-building" aria-hidden="true"></i>
                <h2>Información de la Empresa</h2>
            </header>

            <form action="{{ route('clientes.store') }}" method="POST" novalidate>
                @csrf

                <div class="cliente-form-grid empresa-grid">
                    <div class="field-group field-lg">
                        <label for="empresa_nombre">Nombre de la Empresa</label>
                        <input type="text" id="empresa_nombre" name="empresa_nombre" value="{{ old('empresa_nombre') }}" placeholder="Ej: Aladdin Software S.L." class="@error('empresa_nombre') is-invalid @enderror" required>
                    </div>

                    <div class="field-group field-sm">
                        <label for="cif_nif">CIF / NIF</label>
                        <input type="text" id="cif_nif" name="cif_nif" value="{{ old('cif_nif') }}" placeholder="B12345678" class="@error('cif_nif') is-invalid @enderror" required>
                    </div>

                    <div class="field-group field-full">
                        <label for="direccion">Dirección</label>
                        <input type="text" id="direccion" name="direccion" value="{{ old('direccion') }}" placeholder="Calle, número, piso, oficina..." class="@error('direccion') is-invalid @enderror" required>
                    </div>

                    <div class="field-group field-md">
                        <label for="localidad">Localidad</label>
                        <input type="text" id="localidad" name="localidad" value="{{ old('localidad') }}" placeholder="Madrid" class="@error('localidad') is-invalid @enderror" required>
                    </div>

                    <div class="field-group field-md">
                        <label for="codigo_postal">Código Postal</label>
                        <input type="text" id="codigo_postal" name="codigo_postal" value="{{ old('codigo_postal') }}" placeholder="28001" class="@error('codigo_postal') is-invalid @enderror" required>
                    </div>
                </div>

                <section class="contacto-section">
                    <h3>
                        <i class="far fa-id-card" aria-hidden="true"></i>
                        Contacto Principal
                    </h3>

                    <div class="cliente-form-grid contacto-grid">
                        <div class="field-group with-icon">
                            <label for="telefono">Teléfono</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-phone-alt" aria-hidden="true"></i>
                                <input type="text" id="telefono" name="telefono" value="{{ old('telefono') }}" placeholder="+34 900 000 000" class="@error('telefono') is-invalid @enderror">
                            </div>
                        </div>

                        <div class="field-group with-icon">
                            <label for="email">Email</label>
                            <div class="input-icon-wrap">
                                <i class="far fa-envelope" aria-hidden="true"></i>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="contacto@empresa.com" class="@error('email') is-invalid @enderror">
                            </div>
                        </div>

                        <div class="field-group with-icon">
                            <label for="persona_contacto">Persona de Contacto</label>
                            <div class="input-icon-wrap">
                                <i class="far fa-user" aria-hidden="true"></i>
                                <input type="text" id="persona_contacto" name="persona_contacto" value="{{ old('persona_contacto') }}" placeholder="Nombre completo" class="@error('persona_contacto') is-invalid @enderror">
                            </div>
                        </div>
                    </div>
                </section>

                <footer class="cliente-form-actions">
                    <a href="{{ route('clientes.index') }}" class="btn-cancelar">Cancelar</a>
                    <button type="submit" class="btn-guardar">Guardar</button>
                </footer>
            </form>
        </article>

        <footer class="cliente-bottom-note">
            © 2026 Factumon · Sistema de Gestión Empresarial Avanzado
        </footer>
    </section>
@endsection

@section('css')
    @vite(['resources/css/clientes-create.css'])
@endsection
