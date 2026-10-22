@extends('adminlte::page')

@section('title', $titulo ?? 'Nueva Bolsa Global - MoncobraCRM')

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
    @vite(['resources/css/bolsas.css'])
@endsection

@section('content')
    <section class="bolsa-ui">
        <header class="bolsa-hero">
            <div>
                <div class="bolsa-eyebrow">{{ $breadcrumb ?? 'Bolsas · Crear' }}</div>
                <h1>{{ $titulo ?? 'Crear Bolsa Global' }}</h1>
                <p>Define el saldo total de un cliente y cuelga de él las partidas que lo consumirán.</p>
            </div>
            <div class="bolsa-hero-actions">
                <a href="{{ route('bolsas.index') }}" class="bolsa-btn bolsa-btn--soft">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
                </a>
            </div>
        </header>

        <div class="bolsa-form-shell">
            @if ($errors->any())
                <div class="bolsa-alert bolsa-alert--error" role="alert">
                    <i class="fas fa-ban" aria-hidden="true"></i>
                    <div>
                        <strong>No se pudo crear la bolsa.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="bolsa-alert bolsa-alert--info">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <div>
                    <strong>¿Qué es una Bolsa Global?</strong> Permite definir un saldo total para un cliente (ej. Mantenimiento 2026). Luego podrás colgar múltiples "Pedidos Bolsa" de ella y todos compartirán el mismo dinero.
                </div>
            </div>

            <form action="{{ route('bolsas.store') }}" method="POST" class="bolsa-panel">
                @csrf

                <div class="bolsa-panel__head">
                    <div>
                        <h2>Datos de la nueva bolsa</h2>
                        <p>Los campos marcados con * son obligatorios.</p>
                    </div>
                </div>

                <div class="bolsa-form-grid">
                    <div class="bolsa-field bolsa-field--full">
                        <label for="id_cliente">Cliente <span class="req">*</span></label>
                        <select name="id_cliente" id="id_cliente" class="bolsa-select" required>
                            <option value="">Selecciona un cliente...</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ old('id_cliente') == $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->empresa_nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="bolsa-field">
                        <label for="numero">Número / Referencia</label>
                        <input type="text" name="numero" id="numero" value="{{ old('numero') }}" placeholder="Ej. B-2026-001">
                        <small>Opcional. Código interno para identificar la bolsa.</small>
                    </div>

                    <div class="bolsa-field">
                        <label for="fecha">Fecha <span class="req">*</span></label>
                        <input type="date" name="fecha" id="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required>
                    </div>

                    <div class="bolsa-field">
                        <label for="tipo_bolsa">Grupo (Tipo de bolsa) <span class="req">*</span></label>
                        <input type="text" name="tipo_bolsa" id="tipo_bolsa" value="{{ old('tipo_bolsa', $tipoBolsaDefault ?? date('Y')) }}" required>
                        <small>Agrupación para compartir sobrantes (por defecto el año).</small>
                    </div>

                    <div class="bolsa-field">
                        <label for="total">Total inicial <span class="req">*</span></label>
                        <div class="bolsa-money">
                            <input type="number" name="total" id="total" step="0.01" min="0" value="{{ old('total') }}" placeholder="0.00" required>
                            <span>€</span>
                        </div>
                        <small>Introducir 0 creará una Bolsa Abierta (sin límite inicial).</small>
                    </div>
                </div>

                <div class="bolsa-actions">
                    <a href="{{ route('bolsas.index') }}" class="bolsa-btn bolsa-btn--soft">Cancelar</a>
                    <button type="submit" class="bolsa-btn bolsa-btn--primary">
                        <i class="fas fa-save" aria-hidden="true"></i> Crear Bolsa Global
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.bolsa-select').forEach(function (el) {
                new TomSelect(el, { create: false });
            });
        });
    </script>
@endsection