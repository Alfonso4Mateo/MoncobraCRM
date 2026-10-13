@extends('adminlte::page')

@section('title', $titulo ?? 'Nueva Bolsa Global - MoncobraCRM')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <p class="text-muted mb-0" style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">
                {{ $breadcrumb ?? 'Inicio / Bolsas / Crear' }}
            </p>
            <h1 class="m-0 text-dark font-weight-bold">
                <i class="fas fa-briefcase text-primary mr-2"></i> {{ $titulo ?? 'Crear Bolsa Global' }}
            </h1>
        </div>
        <a href="{{ route('bolsas.index') }}" class="btn btn-outline-secondary font-weight-bold">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 mx-auto">
            
            @if ($errors->any())
                <div class="alert alert-danger" style="border-radius: 8px;">
                    <h5 class="font-weight-bold"><i class="icon fas fa-ban"></i> Error</h5>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm" style="border-radius: 12px; border: none;">
                <div class="card-header bg-white" style="border-bottom: 2px solid #f4f6f9; border-radius: 12px 12px 0 0;">
                    <h3 class="card-title font-weight-bold text-secondary" style="font-size: 1.1rem;">
                        Datos de la nueva bolsa
                    </h3>
                </div>
                
                <form action="{{ route('bolsas.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        
                        <div class="alert alert-info" style="background-color: #f0f6ff; color: #17385d; border-color: #c7d9ee; border-radius: 8px;">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>¿Qué es una Bolsa Global?</strong> Permite definir un saldo total para un cliente (ej. Mantenimiento 2026). Luego, podrás colgar múltiples "Pedidos Bolsa" de ella, y todos compartirán el mismo dinero.
                        </div>

                        <div class="form-group">
                            <label for="id_cliente" class="text-secondary">Cliente <span class="text-danger">*</span></label>
                            <select name="id_cliente" id="id_cliente" class="form-control select2" required>
                                <option value="">Selecciona un cliente...</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" {{ old('id_cliente') == $cliente->id ? 'selected' : '' }}>
                                        {{ $cliente->empresa_nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="numero" class="text-secondary">Número / Referencia</label>
                                <input type="text" name="numero" id="numero" class="form-control" value="{{ old('numero') }}" placeholder="Ej. B-2026-001">
                                <small class="text-muted">Opcional. Código interno para identificar la bolsa.</small>
                            </div>
                            
                            <div class="col-md-6 form-group">
                                <label for="fecha" class="text-secondary">Fecha <span class="text-danger">*</span></label>
                                <input type="date" name="fecha" id="fecha" class="form-control" value="{{ old('fecha', date('Y-m-d')) }}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="tipo_bolsa" class="text-secondary">Grupo (Tipo de bolsa) <span class="text-danger">*</span></label>
                                <input type="text" name="tipo_bolsa" id="tipo_bolsa" class="form-control" value="{{ old('tipo_bolsa', $tipoBolsaDefault ?? date('Y')) }}" required>
                                <small class="text-muted">Agrupación para compartir sobrantes (por defecto el año).</small>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="total" class="text-secondary">Total Inicial (€) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="total" id="total" class="form-control" step="0.0001" min="0" value="{{ old('total') }}" placeholder="0.0000" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text font-weight-bold">€</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="card-footer bg-white text-right" style="border-top: 2px solid #f4f6f9; border-radius: 0 0 12px 12px;">
                        <button type="submit" class="btn btn-primary font-weight-bold px-4">
                            <i class="fas fa-save mr-1"></i> Crear Bolsa Global
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
    <style>
        .form-control { border-radius: 6px; border: 1px solid #d4deec; }
        .form-control:focus { border-color: #1f74dd; box-shadow: 0 0 0 0.2rem rgba(31, 116, 221, 0.25); }
    </style>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.select2, .pedido-select').forEach(function(el) {
                new TomSelect(el, {
                    create: false
                });
            });
        });
    </script>
@endsection