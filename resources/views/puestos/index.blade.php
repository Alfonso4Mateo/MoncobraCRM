@extends('adminlte::page')

@section('title', 'Perfiles Formativos')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .puestos-btn { display: inline-flex; align-items: center; border: 0; border-radius: 10px; padding: 8px 16px; font-weight: 700; font-size: 0.85rem; transition: all 0.2s; text-decoration: none; }
        .puestos-btn--primary { background: #173e67; color: #fff; }
        .puestos-btn--primary:hover { background: #0f2a47; color: #fff; }
        .puestos-btn--soft { background: #eef3f8; color: #173e67; }
        
        .puesto-card { background: #fff; border: 1px solid #e7ecf3; border-radius: 14px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(15,23,42,0.02); display: flex; justify-content: space-between; align-items: center; }
        .puesto-card:hover { border-color: #173e67; box-shadow: 0 8px 15px rgba(15,23,42,0.05); }
        .puesto-title { font-size: 1.15rem; font-weight: 800; color: #173e67; margin: 0; }
        .puesto-stats { display: flex; gap: 15px; margin-top: 6px; font-size: 0.85rem; color: #667085; }
        
        .create-form-container { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 20px; margin-bottom: 25px; }
    </style>
@endsection

@section('content')
    <section class="p-3">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 style="font-weight: 800; color: #173e67;">Perfiles Formativos (Puestos)</h2>
                <p class="text-muted mb-0">Gestiona los puestos de la empresa y define qué cursos son obligatorios para cada uno.</p>
            </div>

            <a href="{{ route('cursos.index') }}" class="btn btn-outline-secondary" style="border-radius: 10px; font-weight: 700;">
                <i class="fas fa-arrow-left mr-2"></i> Volver a Cursos
            </a>
        </div>

        <!-- Formulario rápido para crear un puesto nuevo -->
        <div class="create-form-container">
            <form action="{{ route('puestos.store') }}" method="POST" class="d-flex align-items-center" style="gap: 15px;">
                @csrf
                <div style="flex-grow: 1; max-width: 400px;">
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: FONTANERO/A, SOLDADOR/A..." required style="border-radius: 10px;">
                </div>
                <button type="submit" class="puestos-btn puestos-btn--primary">
                    <i class="fas fa-plus mr-2"></i> Crear nuevo puesto
                </button>
            </form>
            @error('nombre') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
        </div>

        <!-- Listado de puestos -->
        <div class="row">
            @forelse($puestos as $puesto)
                <div class="col-md-6 col-lg-4">
                    <div class="puesto-card">
                        <div>
                            <h3 class="puesto-title">{{ $puesto->nombre }}</h3>
                            <div class="puesto-stats">
                                <span><i class="fas fa-book-open mr-1"></i> {{ $puesto->cursos_count }} cursos req.</span>
                                <span><i class="fas fa-users mr-1"></i> {{ $puesto->personal_count }} empleados</span>
                            </div>
                        </div>
                        <div class="d-flex" style="gap: 8px;">
                            <!-- Botón para ver el Dashboard de Auditoría -->
                            <a href="{{ route('puestos.auditoria', $puesto->id) }}" class="puestos-btn" style="background: #e0e7ff; color: #4338ca;" title="Ver Auditoría">
                                <i class="fas fa-chart-bar"></i>
                            </a>
                            <!-- Botón para Editar las normas -->
                            <a href="{{ route('puestos.edit', $puesto->id) }}" class="puestos-btn puestos-btn--soft" title="Configurar Matriz">
                                <i class="fas fa-cog"></i>
                            </a>
                            <form action="{{ route('puestos.destroy', $puesto->id) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar este puesto?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="puestos-btn" style="background: #fee2e2; color: #ef4444;" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5 text-muted">
                    No hay puestos registrados en el sistema todavía.
                </div>
            @endforelse
        </div>
    </section>
@endsection