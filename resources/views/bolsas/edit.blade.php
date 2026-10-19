@extends('adminlte::page')

@section('title', $titulo ?? 'Editar Bolsa Global - MoncobraCRM')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <p class="text-muted mb-0" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">
                {{ $breadcrumb ?? 'Inicio / Bolsas / Editar' }}
            </p>
            <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-briefcase text-primary mr-2"></i>Editar Bolsa Global</h1>
        </div>
        <a href="{{ route('bolsas.show', $bolsa) }}" class="btn btn-outline-secondary font-weight-bold">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 mx-auto">
            @if ($errors->any())
                <div class="alert alert-danger" style="border-radius: 8px;">
                    <h5 class="font-weight-bold"><i class="fas fa-ban mr-1"></i>Error</h5>
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="card shadow-sm" style="border-radius: 12px; border: none;">
                <div class="card-header bg-white" style="border-bottom: 2px solid #f4f6f9; border-radius: 12px 12px 0 0;">
                    <h3 class="card-title font-weight-bold text-secondary" style="font-size: 1.1rem;">Datos de la bolsa</h3>
                </div>
                <form action="{{ route('bolsas.update', $bolsa) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="id_cliente" class="text-secondary">Cliente <span class="text-danger">*</span></label>
                            <select name="id_cliente" id="id_cliente" class="form-control select2" required>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" {{ old('id_cliente', $bolsa->id_cliente) == $cliente->id ? 'selected' : '' }}>{{ $cliente->empresa_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="numero" class="text-secondary">Número / Referencia</label>
                                <input type="text" name="numero" id="numero" class="form-control" value="{{ old('numero', $bolsa->numero) }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="fecha" class="text-secondary">Fecha</label>
                                <input type="date" name="fecha" id="fecha" class="form-control" value="{{ old('fecha', optional($bolsa->fecha)->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="tipo_bolsa" class="text-secondary">Grupo (Tipo de bolsa) <span class="text-danger">*</span></label>
                                <input type="text" name="tipo_bolsa" id="tipo_bolsa" class="form-control" value="{{ old('tipo_bolsa', $bolsa->tipo_bolsa) }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="total" class="text-secondary">Total (€) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="total" id="total" class="form-control" step="0.01" min="0" value="{{ old('total', number_format((float) $bolsa->total, 2, '.', '')) }}" required>
                                    <div class="input-group-append"><span class="input-group-text font-weight-bold">€</span></div>
                                </div>
                                @if ($minimoPartidas > 0)
                                    <small class="text-warning font-weight-bold">Mínimo permitido por partidas actuales: {{ number_format($minimoPartidas, 2, ',', '.') }} €</small>
                                @else
                                    <small class="text-muted">Introducir 0 mantiene la Bolsa Abierta.</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-right" style="border-top: 2px solid #f4f6f9;">
                        <button type="submit" class="btn btn-primary font-weight-bold px-4"><i class="fas fa-save mr-1"></i>Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.select2').forEach(function (el) {
                if (window.TomSelect) {
                    new TomSelect(el, { create: false });
                }
            });
        });
    </script>
@endsection
