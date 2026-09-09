@extends('adminlte::page')

@section('title', 'Centros de Coste - MoncobraCRM')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Gestión de Centros de Coste (CC)</h1>
            <p class="text-muted mt-1 mb-0">Añade o elimina los códigos que aparecerán en los presupuestos.</p>
        </div>
        <a href="{{ route('presupuestos.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver a presupuestos
        </a>
    </div>
@endsection

@section('content')
    <div class="row mt-3">
        
        <!-- COLUMNA IZQUIERDA: Formulario de Alta -->
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> Añadir Nuevo CC</h3>
                </div>
                
                <form action="{{ route('centros-costes.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        
                        @if ($errors->any())
                            <div class="alert alert-danger p-2 text-sm">
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="codigo">Código <span class="text-danger">*</span></label>
                            <input type="text" name="codigo" id="codigo" class="form-control @error('codigo') is-invalid @enderror" value="{{ old('codigo') }}" placeholder="Ej: 426" required maxlength="50">
                            <small class="form-text text-muted">Debe ser único en el sistema.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción <span class="text-danger">*</span></label>
                            <input type="text" name="descripcion" id="descripcion" class="form-control @error('descripcion') is-invalid @enderror" value="{{ old('descripcion') }}" placeholder="Ej: Sum. Repuestos" required maxlength="255">
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white text-right">
                        <button type="submit" class="btn btn-primary">Guardar Centro de Coste</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- COLUMNA DERECHA: Listado Actual -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Listado Actual</h3>
                </div>
                
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th class="text-center" style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($centrosCoste as $cc)
                                <tr>
                                    <td class="font-weight-bold">{{ $cc->codigo }}</td>
                                    <td>{{ $cc->descripcion }}</td>
                                    <td class="text-center">
                                        <!-- Formulario para eliminar -->
                                        <form action="{{ route('centros-costes.destroy', $cc) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este Centro de Coste? Esta acción no afectará a los presupuestos antiguos.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        No hay Centros de Coste registrados en el sistema.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
@endsection